#!/bin/sh
set -e
cd /var/www/html

# Render, Railway y similares indican el puerto en $PORT
if [ -n "$PORT" ]; then
  sed -ri "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
  sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
fi

# Si hay un volumen montado en storage, asegura la estructura y el enlace público de las fotos
mkdir -p storage/app/public/uploads storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data storage bootstrap/cache
php artisan storage:link --force >/dev/null 2>&1 || true

# Esquema al día en cada arranque (no borra datos).
# En planes gratis la base de datos se duerme: si todavía no responde, reintenta hasta ~1 minuto.
intentos=0
until php artisan migrate --force; do
  intentos=$((intentos + 1))
  if [ "$intentos" -ge 20 ]; then
    echo "No se pudo conectar a la base de datos después de $intentos intentos." >&2
    exit 1
  fi
  echo "Base de datos no disponible todavía (intento $intentos/20); reintentando en 3 s..."
  sleep 3
done

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
