package main

import (
	"io"
	"net/http"
	"os"
	"path"
	"path/filepath"
	"sort"
	"strings"

	"github.com/gorilla/mux"
)

type fileRequest struct {
	Path    string `json:"path"`
	From    string `json:"from"`
	To      string `json:"to"`
	Content string `json:"content"`
}

func (app *App) listFiles(w http.ResponseWriter, r *http.Request) {
	uuid := mux.Vars(r)["uuid"]
	full, clean, err := app.safeServerPath(uuid, r.URL.Query().Get("path"))
	if err != nil {
		writeError(w, http.StatusUnprocessableEntity, err.Error())
		return
	}
	entries, err := os.ReadDir(full)
	if err != nil {
		writeError(w, http.StatusNotFound, err.Error())
		return
	}
	items := make([]map[string]any, 0, len(entries))
	for _, entry := range entries {
		info, err := entry.Info()
		if err != nil {
			continue
		}
		itemPath := path.Join(clean, entry.Name())
		if !strings.HasPrefix(itemPath, "/") {
			itemPath = "/" + itemPath
		}
		items = append(items, map[string]any{
			"name":     entry.Name(),
			"path":     itemPath,
			"is_dir":   entry.IsDir(),
			"size":     info.Size(),
			"modified": info.ModTime().Format("2006-01-02 15:04:05"),
		})
	}
	sort.Slice(items, func(i, j int) bool {
		if items[i]["is_dir"] != items[j]["is_dir"] {
			return items[i]["is_dir"].(bool)
		}
		return items[i]["name"].(string) < items[j]["name"].(string)
	})
	writeJSON(w, http.StatusOK, map[string]any{"path": clean, "items": items})
}

func (app *App) uploadFile(w http.ResponseWriter, r *http.Request) {
	uuid := mux.Vars(r)["uuid"]
	if err := r.ParseMultipartForm(100 << 20); err != nil {
		writeError(w, http.StatusBadRequest, "invalid multipart upload")
		return
	}
	targetDir, _, err := app.safeServerPath(uuid, r.FormValue("path"))
	if err != nil {
		writeError(w, http.StatusUnprocessableEntity, err.Error())
		return
	}
	file, header, err := r.FormFile("file")
	if err != nil {
		writeError(w, http.StatusBadRequest, "file is required")
		return
	}
	defer file.Close()

	name := filepath.Base(header.Filename)
	if name == "." || name == string(filepath.Separator) {
		writeError(w, http.StatusUnprocessableEntity, "invalid file name")
		return
	}
	dest := filepath.Join(targetDir, name)
	if err := ensureParent(dest); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	out, err := os.OpenFile(dest, os.O_CREATE|os.O_WRONLY|os.O_TRUNC, 0640)
	if err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	defer out.Close()
	if _, err := io.Copy(out, file); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, http.StatusCreated, map[string]any{"ok": true, "path": filepath.ToSlash(dest)})
}

func (app *App) writeFile(w http.ResponseWriter, r *http.Request) {
	uuid := mux.Vars(r)["uuid"]
	var req fileRequest
	if err := decodeJSON(r, &req); err != nil {
		writeError(w, http.StatusBadRequest, "invalid json")
		return
	}
	full, clean, err := app.safeServerPath(uuid, req.Path)
	if err != nil {
		writeError(w, http.StatusUnprocessableEntity, err.Error())
		return
	}
	if err := ensureParent(full); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	if err := os.WriteFile(full, []byte(req.Content), 0640); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, http.StatusOK, map[string]any{"ok": true, "path": clean})
}

func (app *App) mkdirFile(w http.ResponseWriter, r *http.Request) {
	uuid := mux.Vars(r)["uuid"]
	var req fileRequest
	if err := decodeJSON(r, &req); err != nil {
		writeError(w, http.StatusBadRequest, "invalid json")
		return
	}
	full, clean, err := app.safeServerPath(uuid, req.Path)
	if err != nil {
		writeError(w, http.StatusUnprocessableEntity, err.Error())
		return
	}
	if err := os.MkdirAll(full, 0750); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, http.StatusOK, map[string]any{"ok": true, "path": clean})
}

func (app *App) renameFile(w http.ResponseWriter, r *http.Request) {
	uuid := mux.Vars(r)["uuid"]
	var req fileRequest
	if err := decodeJSON(r, &req); err != nil {
		writeError(w, http.StatusBadRequest, "invalid json")
		return
	}
	from, _, err := app.safeServerPath(uuid, req.From)
	if err != nil {
		writeError(w, http.StatusUnprocessableEntity, err.Error())
		return
	}
	to, cleanTo, err := app.safeServerPath(uuid, req.To)
	if err != nil {
		writeError(w, http.StatusUnprocessableEntity, err.Error())
		return
	}
	if err := ensureParent(to); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	if err := os.Rename(from, to); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, http.StatusOK, map[string]any{"ok": true, "path": cleanTo})
}

func (app *App) deleteFile(w http.ResponseWriter, r *http.Request) {
	uuid := mux.Vars(r)["uuid"]
	var req fileRequest
	if err := decodeJSON(r, &req); err != nil {
		writeError(w, http.StatusBadRequest, "invalid json")
		return
	}
	full, clean, err := app.safeServerPath(uuid, req.Path)
	if err != nil {
		writeError(w, http.StatusUnprocessableEntity, err.Error())
		return
	}
	if clean == "/" {
		writeError(w, http.StatusUnprocessableEntity, "cannot delete server root")
		return
	}
	if err := os.RemoveAll(full); err != nil {
		writeError(w, http.StatusInternalServerError, err.Error())
		return
	}
	writeJSON(w, http.StatusOK, map[string]any{"ok": true})
}
