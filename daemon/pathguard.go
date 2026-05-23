package main

import (
	"errors"
	"os"
	"path"
	"path/filepath"
	"strings"
)

func cleanUUID(uuid string) (string, error) {
	if uuid == "" || strings.Contains(uuid, "/") || strings.Contains(uuid, "\\") || strings.Contains(uuid, "..") {
		return "", errors.New("invalid server uuid")
	}
	return uuid, nil
}

func (app *App) serverDir(uuid string) (string, error) {
	uuid, err := cleanUUID(uuid)
	if err != nil {
		return "", err
	}
	return filepath.Join(app.Config.BasePath, "servers", uuid), nil
}

func (app *App) backupDir(uuid string) (string, error) {
	uuid, err := cleanUUID(uuid)
	if err != nil {
		return "", err
	}
	return filepath.Join(app.Config.BasePath, "backups", uuid), nil
}

func (app *App) safeServerPath(uuid, requested string) (string, string, error) {
	base, err := app.serverDir(uuid)
	if err != nil {
		return "", "", err
	}
	cleaned := path.Clean("/" + strings.ReplaceAll(requested, "\\", "/"))
	if strings.Contains(cleaned, "..") {
		return "", "", errors.New("path traversal blocked")
	}
	full := filepath.Join(base, filepath.FromSlash(cleaned))
	rel, err := filepath.Rel(base, full)
	if err != nil || strings.HasPrefix(rel, "..") || filepath.IsAbs(rel) {
		return "", "", errors.New("path outside server root")
	}
	return full, cleaned, nil
}

func ensureParent(file string) error {
	return os.MkdirAll(filepath.Dir(file), 0750)
}
