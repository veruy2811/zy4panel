package main

import (
	"log"
	"net/http"
	"os"
	"path/filepath"

	"github.com/gorilla/mux"
	"github.com/joho/godotenv"
)

func main() {
	_ = godotenv.Load()
	cfg := loadConfig()

	for _, dir := range []string{
		filepath.Join(cfg.BasePath, "servers"),
		filepath.Join(cfg.BasePath, "backups"),
	} {
		if err := os.MkdirAll(dir, 0750); err != nil {
			log.Fatalf("create base dir: %v", err)
		}
	}

	docker, err := NewDockerManager(cfg)
	if err != nil {
		log.Fatalf("docker client: %v", err)
	}

	app := &App{Config: cfg, Docker: docker}
	router := mux.NewRouter()
	router.HandleFunc("/health", app.health).Methods(http.MethodGet)

	api := router.NewRoute().Subrouter()
	api.Use(app.authMiddleware)
	api.HandleFunc("/servers/create", app.createServer).Methods(http.MethodPost)
	api.HandleFunc("/servers/{uuid}/start", app.startServer).Methods(http.MethodPost)
	api.HandleFunc("/servers/{uuid}/stop", app.stopServer).Methods(http.MethodPost)
	api.HandleFunc("/servers/{uuid}/restart", app.restartServer).Methods(http.MethodPost)
	api.HandleFunc("/servers/{uuid}/kill", app.killServer).Methods(http.MethodPost)
	api.HandleFunc("/servers/{uuid}/stats", app.statsServer).Methods(http.MethodGet)
	api.HandleFunc("/servers/{uuid}/files", app.listFiles).Methods(http.MethodGet)
	api.HandleFunc("/servers/{uuid}/files/upload", app.uploadFile).Methods(http.MethodPost)
	api.HandleFunc("/servers/{uuid}/files/write", app.writeFile).Methods(http.MethodPost)
	api.HandleFunc("/servers/{uuid}/files/mkdir", app.mkdirFile).Methods(http.MethodPost)
	api.HandleFunc("/servers/{uuid}/files/rename", app.renameFile).Methods(http.MethodPatch)
	api.HandleFunc("/servers/{uuid}/files/delete", app.deleteFile).Methods(http.MethodDelete)
	api.HandleFunc("/servers/{uuid}/backup", app.createBackup).Methods(http.MethodPost)
	api.HandleFunc("/servers/{uuid}/backup", app.downloadBackup).Methods(http.MethodGet)
	api.HandleFunc("/servers/{uuid}/restore", app.restoreBackup).Methods(http.MethodPost)
	api.HandleFunc("/servers/{uuid}/backup", app.deleteBackup).Methods(http.MethodDelete)

	router.HandleFunc("/servers/{uuid}/console", app.consoleSocket).Methods(http.MethodGet)

	log.Printf("Zy4Daemon listening on %s", cfg.Listen)
	log.Fatal(http.ListenAndServe(cfg.Listen, router))
}

func (app *App) health(w http.ResponseWriter, r *http.Request) {
	writeJSON(w, http.StatusOK, map[string]any{"ok": true, "name": "Zy4Daemon"})
}
