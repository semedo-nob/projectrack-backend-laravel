#!/usr/bin/env bash
# Expose local Laravel (127.0.0.1:8000) via Cloudflare Quick Tunnel.
# Share links become https://*.trycloudflare.com/p/{token} — no LAN IP needed.
#
# Usage:
#   ./scripts/dev-tunnel.sh
# Then set the printed PUBLIC URL in:
#   - backend .env APP_URL
#   - Flutter Settings → Cloud sync → Server URL
#
# NOTE: Do NOT use `npx wrangler setup` for this Laravel app.
# Wrangler is for Cloudflare Workers. This project needs cloudflared.

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

export PATH="${HOME}/.local/bin:${PATH}"

if ! command -v cloudflared >/dev/null 2>&1; then
  echo "Installing cloudflared…"
  curl -fsSL https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64 \
    -o "${HOME}/.local/bin/cloudflared"
  chmod +x "${HOME}/.local/bin/cloudflared"
fi

echo "==> Ensuring Postgres (projectrack-pg)…"
docker start projectrack-pg >/dev/null 2>&1 || docker compose up -d

echo "==> Ensuring Laravel on http://127.0.0.1:8000 …"
if ! curl -sf http://127.0.0.1:8000/up >/dev/null 2>&1; then
  php artisan serve --host=127.0.0.1 --port=8000 >/tmp/projectrack-artisan.log 2>&1 &
  echo $! >/tmp/projectrack-artisan.pid
  for i in $(seq 1 30); do
    if curl -sf http://127.0.0.1:8000/up >/dev/null 2>&1; then
      break
    fi
    sleep 0.5
  done
  if ! curl -sf http://127.0.0.1:8000/up >/dev/null 2>&1; then
    echo "Laravel failed to start. See /tmp/projectrack-artisan.log"
    exit 1
  fi
  echo "    artisan pid $(cat /tmp/projectrack-artisan.pid)"
else
  echo "    already running"
fi

LOG=/tmp/projectrack-cloudflared.log
PIDFILE=/tmp/projectrack-cloudflared.pid
rm -f "$LOG"
: >"$LOG"

echo "==> Starting Cloudflare Quick Tunnel…"
cloudflared tunnel --url http://127.0.0.1:8000 >"$LOG" 2>&1 &
echo $! >"$PIDFILE"

PUBLIC_URL=""
for i in $(seq 1 60); do
  PUBLIC_URL="$(grep -oE 'https://[a-zA-Z0-9-]+\.trycloudflare\.com' "$LOG" | head -1 || true)"
  if [[ -n "$PUBLIC_URL" ]]; then
    break
  fi
  sleep 0.5
done

if [[ -z "$PUBLIC_URL" ]]; then
  echo "Could not detect tunnel URL. Log:"
  cat "$LOG"
  exit 1
fi

# Persist APP_URL so share links use the public host
if grep -q '^APP_URL=' .env; then
  sed -i "s|^APP_URL=.*|APP_URL=${PUBLIC_URL}|" .env
else
  echo "APP_URL=${PUBLIC_URL}" >>.env
fi

php artisan config:clear >/dev/null 2>&1 || true

cat <<EOF

============================================================
  ProjectRack public tunnel is live

  Public URL : ${PUBLIC_URL}
  Local      : http://127.0.0.1:8000
  Health     : ${PUBLIC_URL}/up
  Share ex.  : ${PUBLIC_URL}/p/<token>
  API base   : ${PUBLIC_URL}/api/v1

  Flutter app → Settings → Cloud sync → Server URL:
      ${PUBLIC_URL}

  APP_URL in .env was updated to the public URL.
  Keep this terminal/process running while sharing.

  Stop tunnel:  kill \$(cat ${PIDFILE})
============================================================

EOF

# Keep attached so Ctrl+C stops the tunnel (and leaves artisan if we started it)
tail -f "$LOG"
