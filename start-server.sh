#!/usr/bin/env bash
set -Eeuo pipefail

echo ">> Booting container at $(date -u +"%Y-%m-%dT%H:%M:%SZ")"

# 0) Validaciones previas (clave de app obligatoria en prod)
if [[ -z "${APP_KEY:-}" ]]; then
  echo "ERROR: APP_KEY no está definida. Configurala en las Environment Variables de Render."
  exit 1
fi

# 1) Limpiar caches
php artisan optimize:clear || true

# 2) Enlace storage
php artisan storage:link || true

# 3) Cachear para producción
php artisan config:cache || true
php artisan route:cache  || true
php artisan view:cache   || true
php artisan optimize     || true

# 4) Migraciones con retry (solo en pgsql)
if [[ "${RUN_MIGRATIONS:-1}" == "1" ]]; then
  echo ">> Running migrations (with retry) using pgsql"
  for i in {1..5}; do
    if php artisan migrate --force --database=pgsql; then
      echo ">> Migrations OK"
      break
    fi
    echo ">> Attempt $i failed, retrying in 3s..."
    sleep 3
  done

  # 4b) Seed inicial solo si cotizaciones está vacía
  count=$(php artisan tinker --execute="echo DB::table('cotizaciones')->count();" 2>/dev/null || echo 0)
  if [[ "$count" -eq 0 ]]; then
    echo ">> Cotizaciones vacía, corriendo seed inicial..."
    php artisan db:seed --force
  else
    echo ">> Cotizaciones ya tiene $count registros, se salta seed."
  fi
fi

# 5) Iniciar Apache
echo ">> Starting Apache"
exec apache2-foreground
