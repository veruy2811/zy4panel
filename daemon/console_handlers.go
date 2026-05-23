package main

import (
	"bufio"
	"context"
	"encoding/json"
	"net/http"
	"time"

	"github.com/docker/docker/api/types/container"
	"github.com/gorilla/mux"
	"github.com/gorilla/websocket"
)

var upgrader = websocket.Upgrader{
	ReadBufferSize:  1024,
	WriteBufferSize: 4096,
	CheckOrigin: func(r *http.Request) bool {
		return true
	},
}

type consoleMessage struct {
	Type string `json:"type"`
	Data string `json:"data"`
}

func (app *App) consoleSocket(w http.ResponseWriter, r *http.Request) {
	uuid, err := cleanUUID(mux.Vars(r)["uuid"])
	if err != nil {
		writeError(w, http.StatusUnprocessableEntity, err.Error())
		return
	}
	if !app.validConsoleToken(uuid, r.URL.Query().Get("token")) {
		writeError(w, http.StatusUnauthorized, "invalid console token")
		return
	}

	ws, err := upgrader.Upgrade(w, r, nil)
	if err != nil {
		return
	}
	defer ws.Close()

	ctx, cancel := context.WithCancel(r.Context())
	defer cancel()
	attach, err := app.Docker.client.ContainerAttach(ctx, containerName(uuid), container.AttachOptions{
		Stream: true,
		Stdin:  true,
		Stdout: true,
		Stderr: true,
		Logs:   true,
	})
	if err != nil {
		_ = ws.WriteMessage(websocket.TextMessage, []byte(err.Error()))
		return
	}
	defer attach.Close()

	done := make(chan struct{})
	go func() {
		defer close(done)
		reader := bufio.NewReader(attach.Reader)
		buf := make([]byte, 4096)
		for {
			n, err := reader.Read(buf)
			if n > 0 {
				_ = ws.SetWriteDeadline(time.Now().Add(10 * time.Second))
				if writeErr := ws.WriteMessage(websocket.TextMessage, buf[:n]); writeErr != nil {
					return
				}
			}
			if err != nil {
				return
			}
		}
	}()

	for {
		_, payload, err := ws.ReadMessage()
		if err != nil {
			cancel()
			<-done
			return
		}
		var msg consoleMessage
		if err := json.Unmarshal(payload, &msg); err != nil {
			msg = consoleMessage{Type: "command", Data: string(payload)}
		}
		if msg.Type == "command" && msg.Data != "" {
			_, _ = attach.Conn.Write([]byte(msg.Data + "\n"))
		}
	}
}
