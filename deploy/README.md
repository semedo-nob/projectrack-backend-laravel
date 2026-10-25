# Deploy

Two origins exist. Use the one that matches the machine.

## Apache VPS

Apache is the public HTTPS server. PHP-FPM runs the Laravel `public/index.php` front controller. There is no separate `php artisan serve` process and no public PHP port.

Copy these files onto the VPS:

| File | Install path |
| --- | --- |
| `deploy/apache/projectrack.conf` | `/etc/apache2/sites-available/projectrack.conf` |
| `deploy/apache/security.conf` | `/etc/apache2/conf-available/projectrack-security.conf` |
| `deploy/php/projectrack.conf` | `/etc/php/8.4/fpm/pool.d/projectrack.conf` |
| `deploy/php/projectrack.ini` | `/etc/php/8.4/fpm/conf.d/99-projectrack.ini` |
| `deploy/backend.env.example` | `/var/www/projectrack/.env` (fill in, `chmod 640`) |

Replace `YOUR_DNS_NAME`, set `APP_KEY` with `php artisan key:generate --show`, and set the database password. Do not commit that `.env`.

```bash
sudo apt install apache2 php8.4-fpm php8.4-pgsql php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip postgresql
sudo a2enmod rewrite headers ssl proxy proxy_fcgi setenvif
sudo a2enconf projectrack-security
sudo a2ensite projectrack
sudo a2dissite 000-default
sudo mkdir -p /var/www/certbot /var/www/projectrack
# Put the Laravel tree at /var/www/projectrack, then:
sudo chown -R www-data:www-data storage bootstrap/cache
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan storage:link
sudo certbot certonly --webroot -w /var/www/certbot -d YOUR_DNS_NAME
sudo apachectl configtest
sudo systemctl reload apache2
sudo systemctl reload php8.4-fpm
```

PostgreSQL listens on `127.0.0.1:5432` only. Open firewall ports 22, 80, and 443. Do not publish 5432 or the PHP-FPM socket.

Routes stay the same: `/api/v1`, `/up`, `/p/{token}`, and the owner portal. Share links are `https://YOUR_DNS_NAME/p/{token}`. Point the Flutter build at that origin with `--dart-define=PROJECTRACK_API_URL=https://YOUR_DNS_NAME`.

Logs: `/var/log/apache2/projectrack.error.log`. Access lines for `/p/` and `/api/v1/shares/` are omitted so share tokens are not stored. `APP_DEBUG` stays false, so a PHP failure does not return a stack trace.

Backup: `pg_dump -Fc -h 127.0.0.1 -p 5432 -U projectrack projectrack`.

## This Debian workstation

Nginx on `127.0.0.1:8080` proxies to Laravel on `127.0.0.1:8000`. Postgres is `127.0.0.1:55434` because port 5434 belongs to another application. See the Flutter repo `docs/NGINX_HOSTING.md`. That layout is not the Apache VPS layout.
