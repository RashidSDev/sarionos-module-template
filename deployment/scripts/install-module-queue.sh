#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$APP_DIR"

MODULE_KEY="$(php artisan tinker --execute='echo config("sarionos.module_key", "module");' 2>/dev/null | tr -d '\n\r ')"

if [ -z "$MODULE_KEY" ]; then
  echo "Unable to read SARIONOS_MODULE_KEY/config sarionos.module_key"
  exit 1
fi

SERVICE_NAME="sarionos-${MODULE_KEY}-queue"
SERVICE_FILE="/etc/systemd/system/${SERVICE_NAME}.service"

sed \
  -e "s#__APP_DIR__#${APP_DIR}#g" \
  -e "s#__MODULE_KEY__#${MODULE_KEY}#g" \
  deployment/systemd/sarionos-module-queue.service.example > "$SERVICE_FILE"

systemctl daemon-reload
systemctl enable "$SERVICE_NAME"
systemctl restart "$SERVICE_NAME"
systemctl status "$SERVICE_NAME" --no-pager
