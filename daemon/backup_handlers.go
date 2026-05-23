package main

import (
	"archive/tar"
	"compress/gzip"
	"fmt"
	"io"
	"net/http"
	"os"
	"path/filepath"
	"regexp"
	"strings"
	"time"

	"github.com/gorilla/mux"
)

type backupRequest struct {
	Name   string `json:"name"`
	Backup string `json:"backup"`
}

func (app *App) createBackup(w http.ResponseWriter, r *http.Request) {
	uuid := mux.Vars(r)["uuid"]
	var req backupRequest
	_ = decodeJSON(r, &req)

	serverDir, err := app.serverDir(uuid)
	if err != nil {
		writeError(w, http.StatusUnprocessableEntity, err.Error())
		return
	}
	backupDir, err := app.backupDir(uuid)
	if err != nil {
		writeError(w, http.StatusUnprocessableEntity, err.Error())
		return
	}
	if err := os.MkdirAll(backupDir, 0750); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}

	name := safeBackupName(req.Name)
	if name == "" {
		name = "backup"
	}
	file := filepath.Join(backupDir, fmt.Sprintf("%s-%s.tar.gz", time.Now().Format("20060102-150405"), name))
	if err := tarGzDir(serverDir, file); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	info, _ := os.Stat(file)
	writeJSON(w, http.StatusCreated, map[string]any{
		"ok":         true,
		"path":       file,
		"size_bytes": info.Size(),
	})
}

func (app *App) restoreBackup(w http.ResponseWriter, r *http.Request) {
	uuid := mux.Vars(r)["uuid"]
	var req backupRequest
	if err := decodeJSON(r, &req); err != nil {
		writeError(w, http.StatusBadRequest, "invalid json")
		return
	}
	serverDir, err := app.serverDir(uuid)
	if err != nil {
		writeError(w, http.StatusUnprocessableEntity, err.Error())
		return
	}
	backupDir, err := app.backupDir(uuid)
	if err != nil {
		writeError(w, http.StatusUnprocessableEntity, err.Error())
		return
	}
	file := filepath.Join(backupDir, filepath.Base(req.Backup))
	if err := untarGz(file, serverDir); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, http.StatusOK, map[string]any{"ok": true})
}

func (app *App) downloadBackup(w http.ResponseWriter, r *http.Request) {
	uuid := mux.Vars(r)["uuid"]
	backupDir, err := app.backupDir(uuid)
	if err != nil {
		writeError(w, http.StatusUnprocessableEntity, err.Error())
		return
	}
	name := filepath.Base(r.URL.Query().Get("backup"))
	if name == "." || name == string(filepath.Separator) {
		writeError(w, http.StatusBadRequest, "backup is required")
		return
	}
	file := filepath.Join(backupDir, name)
	w.Header().Set("Content-Disposition", "attachment; filename=\""+name+"\"")
	http.ServeFile(w, r, file)
}

func (app *App) deleteBackup(w http.ResponseWriter, r *http.Request) {
	uuid := mux.Vars(r)["uuid"]
	var req backupRequest
	if err := decodeJSON(r, &req); err != nil {
		writeError(w, http.StatusBadRequest, "invalid json")
		return
	}
	backupDir, err := app.backupDir(uuid)
	if err != nil {
		writeError(w, http.StatusUnprocessableEntity, err.Error())
		return
	}
	file := filepath.Join(backupDir, filepath.Base(req.Backup))
	if err := os.Remove(file); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, http.StatusOK, map[string]any{"ok": true})
}

func safeBackupName(name string) string {
	name = strings.ToLower(strings.TrimSpace(name))
	name = regexp.MustCompile(`[^a-z0-9._-]+`).ReplaceAllString(name, "-")
	return strings.Trim(name, "-_.")
}

func tarGzDir(src, dest string) error {
	out, err := os.Create(dest)
	if err != nil {
		return err
	}
	defer out.Close()
	gz := gzip.NewWriter(out)
	defer gz.Close()
	tw := tar.NewWriter(gz)
	defer tw.Close()

	return filepath.Walk(src, func(file string, info os.FileInfo, err error) error {
		if err != nil {
			return err
		}
		rel, err := filepath.Rel(src, file)
		if err != nil || rel == "." {
			return err
		}
		header, err := tar.FileInfoHeader(info, "")
		if err != nil {
			return err
		}
		header.Name = filepath.ToSlash(rel)
		if err := tw.WriteHeader(header); err != nil {
			return err
		}
		if info.IsDir() {
			return nil
		}
		in, err := os.Open(file)
		if err != nil {
			return err
		}
		_, err = io.Copy(tw, in)
		_ = in.Close()
		return err
	})
}

func untarGz(src, dest string) error {
	in, err := os.Open(src)
	if err != nil {
		return err
	}
	defer in.Close()
	gz, err := gzip.NewReader(in)
	if err != nil {
		return err
	}
	defer gz.Close()
	tr := tar.NewReader(gz)

	for {
		header, err := tr.Next()
		if err == io.EOF {
			break
		}
		if err != nil {
			return err
		}
		target := filepath.Join(dest, filepath.FromSlash(header.Name))
		rel, err := filepath.Rel(dest, target)
		if err != nil || strings.HasPrefix(rel, "..") || filepath.IsAbs(rel) {
			return fmt.Errorf("unsafe path in archive: %s", header.Name)
		}
		if header.FileInfo().IsDir() {
			if err := os.MkdirAll(target, 0750); err != nil {
				return err
			}
			continue
		}
		if err := os.MkdirAll(filepath.Dir(target), 0750); err != nil {
			return err
		}
		out, err := os.OpenFile(target, os.O_CREATE|os.O_WRONLY|os.O_TRUNC, os.FileMode(header.Mode))
		if err != nil {
			return err
		}
		if _, err := io.Copy(out, tr); err != nil {
			_ = out.Close()
			return err
		}
		_ = out.Close()
	}
	return nil
}
