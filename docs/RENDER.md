# Deploy on Render

Public API + owner portal: **https://projectrack-backend.onrender.com**

This Laravel app cannot run on Cloudflare Workers. Render runs PHP + PostgreSQL so `/api/v1` and `/p/{token}` share links stay valid.

## First-time setup

1. Open **[Deploy to Render](https://render.com/deploy?repo=https://github.com/semedo-nob/projectrack-backend-laravel)**
2. Sign in to Render (GitHub is fine).
3. Apply the Blueprint — it creates the web service + PostgreSQL.
   - Web service `projectrack-backend` (free)
   - PostgreSQL `projectrack-db` (free, 90-day expiry on the free plan)
4. Wait for the first deploy. Health check: `https://projectrack-backend.onrender.com/up`

## Flutter

The mobile app defaults to this URL. Owner sharing: **Share** (system sheet) or **Copy link**. Recipients open `/p/{token}` in a browser and register/log in — they never need the supervisor’s password.

## Free-tier limits

- The web service **sleeps after ~15 minutes idle**. The first request can take 30–60 seconds.
- Open `/up` in a browser a minute before the demo to wake it.
- Free PostgreSQL is deleted after **90 days** unless you upgrade.
- Disk is ephemeral: uploaded files do not survive redeploys.
