#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/zy4hosting}"
DAEMON_SRC="${DAEMON_SRC:-$APP_DIR/daemon}"
INSTALL_DIR="${INSTALL_DIR:-/opt/zy4daemon}"
GO_VERSION="${GO_VERSION:-1.22.12}"

if [[ $EUID -ne 0 ]]; then
  echo "Run as root: sudo bash installer/install-daemon.sh"
  exit 1
fi

apt-get update
apt-get install -y ca-certificates curl gnupg tar git build-essential openssl

if ! command -v docker >/dev/null 2>&1; then
  install -m 0755 -d /etc/apt/keyrings
  curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
  chmod a+r /etc/apt/keyrings/docker.gpg
  . /etc/os-release
  echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu ${VERSION_CODENAME} stable" > /etc/apt/sources.list.d/docker.list
  apt-get update
  apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
fi

if ! command -v go >/dev/null 2>&1 || ! go version | grep -q "go${GO_VERSION%.*}"; then
  curl -fsSL "https://go.dev/dl/go${GO_VERSION}.linux-amd64.tar.gz" -o /tmp/go.tar.gz
  rm -rf /usr/local/go
  tar -C /usr/local -xzf /tmp/go.tar.gz
  ln -sfn /usr/local/go/bin/go /usr/local/bin/go
fi

mkdir -p /var/lib/zy4daemon/servers /var/lib/zy4daemon/backups "$INSTALL_DIR"
cp -R "$DAEMON_SRC"/. "$INSTALL_DIR"/
cd "$INSTALL_DIR"
if [[ ! -f .env ]]; then
  cp .env.example .env
  sed -i "s#DAEMON_SECRET=.*#DAEMON_SECRET=$(openssl rand -hex 32)#g" .env
fi

/usr/local/bin/go mod tidy
/usr/local/bin/go build -o /usr/local/bin/zy4daemon .
cp zy4daemon.service /etc/systemd/system/zy4daemon.service
systemctl daemon-reload
systemctl enable --now docker zy4daemon
systemctl restart zy4daemon
echo "Zy4Daemon installed. Token is in ${INSTALL_DIR}/.env; use it for the node token in Zy4Panel."
