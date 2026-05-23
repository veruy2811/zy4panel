package main

import (
	"log"
	"os"
)

type Config struct {
	Listen   string
	Token    string
	BasePath string
	PanelURL string
}

func loadConfig() Config {
	cfg := Config{
		Listen:   env("ZY4DAEMON_LISTEN", ":7443"),
		Token:    env("DAEMON_SECRET", ""),
		BasePath: env("DAEMON_BASE_PATH", "/var/lib/zy4daemon"),
		PanelURL: env("PANEL_URL", ""),
	}
	if cfg.Token == "" {
		log.Fatal("DAEMON_SECRET is required")
	}
	return cfg
}

func env(key, fallback string) string {
	if value := os.Getenv(key); value != "" {
		return value
	}
	return fallback
}
