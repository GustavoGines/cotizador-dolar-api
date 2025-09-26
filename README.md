# Cotizador Dólar API (Laravel)

API REST en **Laravel** para convertir **USD ⇄ ARS** y consultar **promedios mensuales** de cotizaciones.  
Persistimos la cotización diaria en BD y ofrecemos **agrupado por año** (con nombre de mes).  
Desplegable en **Render** (Docker). **APK móvil (Flutter)** disponible en *GitHub Releases*.

---

## 🧭 URLs base

- **Producción:** `https://cotizador-dolar-api.onrender.com/api`
- **Local:** `http://127.0.0.1:8000/api`

---

## 📐 Arquitectura (resumen)

- `App\Http\Controllers\CotizacionController`  
  Manejo de endpoints HTTP (validación de params y armado de responses).
- `App\Services\CotizacionService`  
  Lógica de negocio: consulta API externa, **fallback al último valor** guardado, persistencia diaria (`updateOrCreate`).
- **BD**: tabla `cotizaciones` (`tipo`, `tipo_valor` = *venta*, `fecha`, `valor`).

> Ventajas: SRP, DRY, testeabilidad, reutilización (jobs/commands/Livewire/Flutter).

---

## 🔌 Endpoints

### 1) Convertir moneda (USD ⇄ ARS)

**GET** `/cotizaciones/convertir`

**Query params**

| Parámetro   | Tipo   | Req. | Default       | Valores                         | Descripción                            |
|-------------|--------|------|---------------|---------------------------------|----------------------------------------|
| `valor`     | number | Sí   | —             | numérico > 0                   | Monto a convertir.                     |
| `tipo`      | string | No   | `oficial`     | `oficial`, `blue`, etc.         | Tipo de dólar a usar.                  |
| `direccion` | string | No   | `usd_a_pesos` | `usd_a_pesos`, `pesos_a_usd`    | Dirección de conversión.               |

**Ejemplos**

```bash
# Producción: USD → ARS (blue)
curl -G "https://cotizador-dolar-api.onrender.com/api/cotizaciones/convertir"   -d valor=125 -d tipo=blue -d direccion=usd_a_pesos

# Local: ARS → USD (oficial)
curl -G "http://127.0.0.1:8000/api/cotizaciones/convertir"   -d valor=100000 -d tipo=oficial -d direccion=pesos_a_usd
```

**Respuesta 200 (ejemplo)**

```json
{
  "tipo": "oficial",
  "direccion": "usd_a_pesos",
  "valor": 100.0,
  "cotizacion": 980.12,
  "resultado": 98012.0
}
```

**Errores**

| Código | Causa                                   | Ejemplo                                               |
|-------:|-----------------------------------------|-------------------------------------------------------|
| 400    | `valor` ausente o no numérico           | `{"error":"Debe enviar un valor numérico válido."}`   |
| 500    | Sin datos del proveedor y sin histórico | `{"error":"Cotización no disponible."}`               |

**Notas:** timeout 8s + 2 reintentos; se persiste **una vez por día** (tipo=*, tipo_valor=venta, fecha=today) y hay **fallback** al último valor guardado.

---

### 2) Promedio mensual (histórico)

**GET** `/cotizaciones/promedio-mensual`  
Devuelve **promedios mensuales agrupados por año** (con nombre de mes).  
Se puede **filtrar** por año/mes. Salida **plana** opcional con `flat=1`.

**Query params**

| Parámetro   | Tipo   | Req. | Default | Valores        | Descripción                                      |
|-------------|--------|------|---------|----------------|--------------------------------------------------|
| `tipo`      | string | No   | oficial | oficial, blue, … | Tipo de dólar.                                   |
| `tipo_valor`| string | No   | venta   | venta          | Campo a promediar (se persiste `venta`).         |
| `anio`      | int    | No   | —       | 4 dígitos      | Filtra por año.                                  |
| `mes`       | int    | No   | —       | 1..12          | Filtra por mes.                                  |
| `flat`      | bool   | No   | false   | 0/1, true/false| Si `flat=1`, devuelve salida plana (compatibilidad). |

**Ejemplos**

```bash
# Producción: agrupado (default)
curl "https://cotizador-dolar-api.onrender.com/api/cotizaciones/promedio-mensual?tipo=oficial"

# Producción: blue 2025 agrupado
curl "https://cotizador-dolar-api.onrender.com/api/cotizaciones/promedio-mensual?tipo=blue&tipo_valor=venta&anio=2025"

# Local: blue 2025/09 agrupado
curl "http://127.0.0.1:8000/api/cotizaciones/promedio-mensual?tipo=blue&tipo_valor=venta&anio=2025&mes=9"

# Local: salida PLANA (compatibilidad)
curl "http://127.0.0.1:8000/api/cotizaciones/promedio-mensual?tipo=blue&anio=2025&flat=1"
```

**Respuesta 200 — Agrupado (ejemplo)**

```json
{
  "tipo": "blue",
  "tipo_valor": "venta",
  "anio": null,
  "mes": null,
  "resultados": [
    {
      "anio": 2025,
      "meses": [
        { "mes": "enero", "promedio": 1074.68 },
        { "mes": "febrero", "promedio": 964.01 }
      ]
    },
    {
      "anio": 2024,
      "meses": [
        { "mes": "enero", "promedio": 1017.33 }
      ]
    }
  ]
}
```

**Respuesta 200 — Plana (ejemplo, `flat=1`)**

```json
{
  "tipo": "blue",
  "tipo_valor": "venta",
  "anio": "2025",
  "mes": null,
  "resultados": [
    { "anio": 2025, "mes": 1, "promedio": 1074.68 },
    { "anio": 2025, "mes": 2, "promedio": 964.01 }
  ]
}
```

**Errores**

| Código | Causa                  | Ejemplo                    |
|-------:|------------------------|----------------------------|
| 500    | Error interno/consulta | `{"error":"mensaje"}`      |

---

## 🗄️ Base de datos

**Tabla `cotizaciones` (mínimo)**  
`id`, `tipo` (string), `tipo_valor` (string = `'venta'`), `fecha` (date), `valor` (decimal/float), `timestamps`.

> Cada consulta a cotización **persiste/actualiza** el valor del día (**idempotente** por fecha).

**Comandos**

```bash
php artisan migrate
```

---

## ⚙️ Configuración (`.env`)

```env
APP_NAME="Cotizador Dólar API"
APP_URL=https://cotizador-dolar-api.onrender.com

# DB
DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

# Proveedor externo
DOLARAPI_URL=https://dolarapi.com/v1/dolares  # ejemplo

# Timezone (recomendado AR)
APP_TIMEZONE=America/Argentina/Buenos_Aires
```

`config/services.php`:

```php
'dolarapi' => [
  'url' => env('DOLARAPI_URL', 'https://dolarapi.com/v1/dolares'),
],
```

---

## 🧪 Desarrollo (local)

```bash
composer install
cp .env.example .env
php artisan key:generate

# configurar DB y DOLARAPI_URL en .env

php artisan migrate
php artisan serve
```

**Si usás UI (Livewire/Vite):**

```bash
npm install
npm run dev
```

**Limpiar caches (útil tras cambiar controllers/servicios):**
```bash
php artisan optimize:clear
composer dump-autoload -o
```

---

## 🚀 Deploy (Render / Docker)

- Variables mínimas: `APP_KEY`, `APP_URL`, credenciales de DB, `DOLARAPI_URL`.
- Ejecutar **migrations** en el deploy.
- Si actualizás endpoints/formato, corré:
  ```bash
  php artisan optimize:clear
  ```

> Nota: si incorporás Vite/Livewire, confiá en los **defaults de `laravel-vite-plugin`** (genera `public/build/manifest.json`).

---

## 📱 App móvil (Flutter)

**APK (última versión):**  
`https://github.com/GustavoGines/cotizador-dolar-api/releases/latest/download/app-release.apk`

**Repo / Releases:**  
- Repo: `https://github.com/GustavoGines/cotizador-dolar-api`  
- Releases: `https://github.com/GustavoGines/cotizador-dolar-api/releases`

**Instalación (Android):** descargar el APK, abrir y permitir “instalar apps de orígenes desconocidos” si el sistema lo solicita.

**Métricas (opcional, Firebase Analytics):**

- **Realtime:** Firebase Console → Analytics → *Realtime* (usuarios últimos 30 min).
- **DebugView (ADB):**
  ```bash
  adb shell setprop debug.firebase.analytics.app <applicationId>
  ```

---

## 📚 Rutas de referencia (resumen)

- `GET /api/cotizaciones/convertir`
- `GET /api/cotizaciones/promedio-mensual`

---

## 📄 Licencia

Uso académico/demostrativo.
