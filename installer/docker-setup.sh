#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="${ROOT_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"

docker build -t zy4/minecraft:latest "$ROOT_DIR/docker-images/minecraft"
docker build -t zy4/samp:latest "$ROOT_DIR/docker-images/samp"
docker build -t zy4/nodejs:latest "$ROOT_DIR/docker-images/nodejs"
docker build -t zy4/generic:latest "$ROOT_DIR/docker-images/generic"

echo "Zy4 Docker images built."
