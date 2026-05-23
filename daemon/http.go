package main

import (
	"crypto/hmac"
	"crypto/sha256"
	"encoding/hex"
	"encoding/json"
	"net/http"
	"strings"
	"time"
)

type App struct {
	Config Config
	Docker *DockerManager
}

func (app *App) authMiddleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		token := strings.TrimPrefix(r.Header.Get("Authorization"), "Bearer ")
		if token == "" {
			token = r.Header.Get("X-Daemon-Token")
		}
		if !hmac.Equal([]byte(token), []byte(app.Config.Token)) {
			writeError(w, http.StatusUnauthorized, "invalid daemon token")
			return
		}
		next.ServeHTTP(w, r)
	})
}

func (app *App) validConsoleToken(uuid, token string) bool {
	now := time.Now().UTC()
	for _, t := range []time.Time{now, now.Add(-1 * time.Hour), now.Add(1 * time.Hour)} {
		msg := uuid + "|" + t.Format("2006010215")
		mac := hmac.New(sha256.New, []byte(app.Config.Token))
		mac.Write([]byte(msg))
		expected := hex.EncodeToString(mac.Sum(nil))
		if hmac.Equal([]byte(expected), []byte(token)) {
			return true
		}
	}
	return false
}

func writeJSON(w http.ResponseWriter, status int, value any) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	_ = json.NewEncoder(w).Encode(value)
}

func writeError(w http.ResponseWriter, status int, message string) {
	writeJSON(w, status, map[string]any{"error": message})
}

func decodeJSON(r *http.Request, dst any) error {
	defer r.Body.Close()
	return json.NewDecoder(r.Body).Decode(dst)
}
