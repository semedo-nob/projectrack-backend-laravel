# Cloudflare Tunnel (local laptop only)

**Presentations should use Render**, not this tunnel: see [RENDER.md](RENDER.md).

Use **cloudflared** only to expose a laptop Laravel process. Do not use `npx wrangler setup` / Cloudflare Workers.

| Tool | Purpose |
|------|---------|
| **cloudflared** | Tunnel your local **Laravel + PHP + Postgres** app to a public `https://*.trycloudflare.com` URL |
| **wrangler / Workers** | Runs JavaScript Workers or static assets. **Cannot run this Laravel API** |

## What went wrong with the Workers deploy

Your build log shows Cloudflare:

1. Ran `npm run build` / `vite build` (only CSS/fonts for the Blade UI)
2. Ran `npx wrangler deploy`
3. Published **static files only** to  
   `https://projectrack-backend-laravel.nsemedo73.workers.dev`

That URL is **not** ProjectRack. It has no PHP, no `/api/v1`, no Postgres, no `/p/{token}` share pages — so owners cannot use it.

**Disconnect / delete that Workers project** in the Cloudflare dashboard (or stop auto-deploy from Git). Do not set Flutter’s Server URL to `*.workers.dev`.

## Correct approach: Quick Tunnel

```bash
cd ~/testdev/projectrack1-backend
./scripts/dev-tunnel.sh
```

That script will:

1. Start Postgres (`projectrack-pg`) if needed  
2. Start `php artisan serve` on `:8000` if needed  
3. Open a Cloudflare Quick Tunnel to your **running Laravel** process  
4. Set `APP_URL` in `.env` to the public URL (so share links are public HTTPS)  
5. Print the URL to paste into the Flutter app  

Example public URL:

```text
https://something.trycloudflare.com
```

## Flutter app

**Settings → Cloud sync & owner sharing → Server URL** → paste the tunnel URL.

Then **Connect with password**, then **Share with owner** on a project.

## Permanent production option

For a stable domain (not a changing trycloudflare URL):

1. Deploy **Laravel + Postgres** to a PHP host (Forge, Railway with PHP, Fly.io, a VPS) — not Workers  
2. Point DNS at it  
3. Set `APP_URL=https://your-domain.com`  
4. Point the Flutter Server URL at that domain  

Or create a **named** Cloudflare Tunnel (Zero Trust) that points at your always-on Laravel server.

## Stop the local tunnel

```bash
kill "$(cat /tmp/projectrack-cloudflared.pid)" 2>/dev/null
kill "$(cat /tmp/projectrack-artisan.pid)" 2>/dev/null
```

Quick tunnels die when `cloudflared` exits; run `./scripts/dev-tunnel.sh` again before sharing.
