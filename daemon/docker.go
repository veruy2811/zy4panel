package main

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"math"
	"os"
	"path/filepath"
	"strings"
	"time"

	"github.com/docker/docker/api/types/container"
	"github.com/docker/docker/api/types/image"
	"github.com/docker/docker/client"
	"github.com/docker/go-connections/nat"
)

type DockerManager struct {
	client *client.Client
	cfg    Config
}

type PortSpec struct {
	IP      string `json:"ip"`
	Port    int    `json:"port"`
	Primary bool   `json:"primary"`
}

type ServerSpec struct {
	UUID      string            `json:"uuid"`
	Name      string            `json:"name"`
	Image     string            `json:"image"`
	Startup   string            `json:"startup"`
	Env       map[string]string `json:"env"`
	MemoryMB  int64             `json:"memory_mb"`
	CPULimit  float64           `json:"cpu_limit"`
	DiskMB    int64             `json:"disk_mb"`
	Ports     []PortSpec        `json:"ports"`
}

func NewDockerManager(cfg Config) (*DockerManager, error) {
	cli, err := client.NewClientWithOpts(client.FromEnv, client.WithAPIVersionNegotiation())
	if err != nil {
		return nil, err
	}
	return &DockerManager{client: cli, cfg: cfg}, nil
}

func containerName(uuid string) string {
	return "zy4_" + uuid
}

func (d *DockerManager) Create(ctx context.Context, spec ServerSpec, serverDir string) error {
	_ = os.MkdirAll(serverDir, 0750)
	_ = os.WriteFile(filepath.Join(serverDir, ".zy4server"), []byte(spec.UUID+"\n"), 0640)

	if _, err := d.client.ContainerInspect(ctx, containerName(spec.UUID)); err == nil {
		return nil
	}

	reader, err := d.client.ImagePull(ctx, spec.Image, image.PullOptions{})
	if err == nil {
		_, _ = io.Copy(io.Discard, reader)
		_ = reader.Close()
	}

	env := make([]string, 0, len(spec.Env)+3)
	for key, value := range spec.Env {
		env = append(env, key+"="+value)
	}
	env = append(env, fmt.Sprintf("SERVER_MEMORY=%d", spec.MemoryMB))

	exposed := nat.PortSet{}
	bindings := nat.PortMap{}
	for _, p := range spec.Ports {
		port := nat.Port(fmt.Sprintf("%d/tcp", p.Port))
		exposed[port] = struct{}{}
		bindings[port] = []nat.PortBinding{{HostIP: p.IP, HostPort: fmt.Sprintf("%d", p.Port)}}
	}

	host := &container.HostConfig{
		Binds:        []string{serverDir + ":/home/container"},
		PortBindings: bindings,
		Resources: container.Resources{
			Memory:   spec.MemoryMB * 1024 * 1024,
			NanoCPUs: int64(math.Max(0.1, spec.CPULimit) * 1_000_000_000),
		},
		RestartPolicy: container.RestartPolicy{Name: "unless-stopped"},
	}
	if spec.DiskMB > 0 {
		host.StorageOpt = map[string]string{"size": fmt.Sprintf("%dm", spec.DiskMB)}
	}

	cmd := spec.Startup
	if cmd == "" {
		cmd = "sleep infinity"
	}
	for key, value := range spec.Env {
		cmd = strings.ReplaceAll(cmd, "{{"+key+"}}", value)
	}
	cmd = strings.ReplaceAll(cmd, "{{SERVER_MEMORY}}", fmt.Sprintf("%d", spec.MemoryMB))

	_, err = d.client.ContainerCreate(ctx, &container.Config{
		Image:        spec.Image,
		Env:          env,
		WorkingDir:   "/home/container",
		Cmd:          []string{"/bin/sh", "-lc", cmd},
		Tty:          true,
		OpenStdin:    true,
		StdinOnce:    false,
		AttachStdin:  true,
		AttachStdout: true,
		AttachStderr: true,
		ExposedPorts: exposed,
		Labels: map[string]string{
			"zy4.server": spec.UUID,
			"zy4.name":   spec.Name,
		},
	}, host, nil, nil, containerName(spec.UUID))
	return err
}

func (d *DockerManager) Start(ctx context.Context, uuid string) error {
	return d.client.ContainerStart(ctx, containerName(uuid), container.StartOptions{})
}

func (d *DockerManager) Stop(ctx context.Context, uuid string) error {
	timeout := 20
	return d.client.ContainerStop(ctx, containerName(uuid), container.StopOptions{Timeout: &timeout})
}

func (d *DockerManager) Restart(ctx context.Context, uuid string) error {
	timeout := 20
	return d.client.ContainerRestart(ctx, containerName(uuid), container.StopOptions{Timeout: &timeout})
}

func (d *DockerManager) Kill(ctx context.Context, uuid string) error {
	return d.client.ContainerKill(ctx, containerName(uuid), "SIGKILL")
}

func (d *DockerManager) Stats(ctx context.Context, uuid string, serverDir string) (map[string]any, error) {
	inspect, err := d.client.ContainerInspect(ctx, containerName(uuid))
	if err != nil {
		return nil, err
	}
	stats, err := d.client.ContainerStatsOneShot(ctx, containerName(uuid))
	if err != nil {
		return nil, err
	}
	defer stats.Body.Close()

	var data dockerStats
	_ = json.NewDecoder(stats.Body).Decode(&data)

	cpuDelta := float64(data.CPUStats.CPUUsage.TotalUsage - data.PreCPUStats.CPUUsage.TotalUsage)
	systemDelta := float64(data.CPUStats.SystemUsage - data.PreCPUStats.SystemUsage)
	cpuPercent := 0.0
	if systemDelta > 0 && cpuDelta > 0 {
		cpuPercent = (cpuDelta / systemDelta) * float64(len(data.CPUStats.CPUUsage.PercpuUsage)) * 100
	}
	memMB := float64(data.MemoryStats.Usage) / 1024 / 1024
	diskMB := dirSizeMB(serverDir)

	uptime := "-"
	if inspect.State != nil && inspect.State.StartedAt != "" {
		if started, err := time.Parse(time.RFC3339Nano, inspect.State.StartedAt); err == nil && inspect.State.Running {
			uptime = time.Since(started).Round(time.Second).String()
		}
	}

	return map[string]any{
		"running":     inspect.State != nil && inspect.State.Running,
		"status":      inspect.State.Status,
		"cpu_percent": math.Round(cpuPercent*100) / 100,
		"memory_mb":   math.Round(memMB*100) / 100,
		"disk_mb":     diskMB,
		"uptime":      uptime,
	}, nil
}

type dockerStats struct {
	CPUStats struct {
		SystemUsage uint64 `json:"system_cpu_usage"`
		CPUUsage    struct {
			TotalUsage uint64   `json:"total_usage"`
			PercpuUsage []uint64 `json:"percpu_usage"`
		} `json:"cpu_usage"`
	} `json:"cpu_stats"`
	PreCPUStats struct {
		SystemUsage uint64 `json:"system_cpu_usage"`
		CPUUsage    struct {
			TotalUsage uint64 `json:"total_usage"`
		} `json:"cpu_usage"`
	} `json:"precpu_stats"`
	MemoryStats struct {
		Usage uint64 `json:"usage"`
	} `json:"memory_stats"`
}

func dirSizeMB(root string) float64 {
	var bytes int64
	_ = filepath.WalkDir(root, func(_ string, d os.DirEntry, err error) error {
		if err != nil || d.IsDir() {
			return nil
		}
		if info, err := d.Info(); err == nil {
			bytes += info.Size()
		}
		return nil
	})
	return math.Round((float64(bytes)/1024/1024)*100) / 100
}
