package main

import (
	"context"
	"net/http"
	"time"

	"github.com/gorilla/mux"
)

func (app *App) createServer(w http.ResponseWriter, r *http.Request) {
	var spec ServerSpec
	if err := decodeJSON(r, &spec); err != nil {
		writeError(w, http.StatusBadRequest, "invalid json")
		return
	}
	uuid, err := cleanUUID(spec.UUID)
	if err != nil {
		writeError(w, http.StatusUnprocessableEntity, err.Error())
		return
	}
	spec.UUID = uuid
	dir, err := app.serverDir(uuid)
	if err != nil {
		writeError(w, http.StatusUnprocessableEntity, err.Error())
		return
	}

	ctx, cancel := context.WithTimeout(r.Context(), 5*time.Minute)
	defer cancel()
	if err := app.Docker.Create(ctx, spec, dir); err != nil {
		writeError(w, http.StatusBadGateway, err.Error())
		return
	}
	writeJSON(w, http.StatusCreated, map[string]any{"ok": true, "uuid": uuid})
}

func (app *App) startServer(w http.ResponseWriter, r *http.Request) {
	app.power(w, r, "start")
}

func (app *App) stopServer(w http.ResponseWriter, r *http.Request) {
	app.power(w, r, "stop")
}

func (app *App) restartServer(w http.ResponseWriter, r *http.Request) {
	app.power(w, r, "restart")
}

func (app *App) killServer(w http.ResponseWriter, r *http.Request) {
	app.power(w, r, "kill")
}

func (app *App) power(w http.ResponseWriter, r *http.Request, action string) {
	uuid, err := cleanUUID(mux.Vars(r)["uuid"])
	if err != nil {
		writeError(w, http.StatusUnprocessableEntity, err.Error())
		return
	}
	ctx, cancel := context.WithTimeout(r.Context(), time.Minute)
	defer cancel()

	switch action {
	case "start":
		err = app.Docker.Start(ctx, uuid)
	case "stop":
		err = app.Docker.Stop(ctx, uuid)
	case "restart":
		err = app.Docker.Restart(ctx, uuid)
	case "kill":
		err = app.Docker.Kill(ctx, uuid)
	}
	if err != nil {
		writeError(w, http.StatusBadGateway, err.Error())
		return
	}
	writeJSON(w, http.StatusOK, map[string]any{"ok": true, "action": action})
}

func (app *App) statsServer(w http.ResponseWriter, r *http.Request) {
	uuid, err := cleanUUID(mux.Vars(r)["uuid"])
	if err != nil {
		writeError(w, http.StatusUnprocessableEntity, err.Error())
		return
	}
	dir, err := app.serverDir(uuid)
	if err != nil {
		writeError(w, http.StatusUnprocessableEntity, err.Error())
		return
	}
	stats, err := app.Docker.Stats(r.Context(), uuid, dir)
	if err != nil {
		writeError(w, http.StatusBadGateway, err.Error())
		return
	}
	writeJSON(w, http.StatusOK, stats)
}
