# Publicar Mi Pastel y usarlo como app en las sucursales

El sistema es Laravel 13 (PHP 8.3) y **necesita MySQL o MariaDB** en producción. Para que las sucursales lo usen
como app, el sitio debe servirse por **HTTPS** (Railway lo da automáticamente en su dominio).

Este documento tiene dos partes: **A) paso a paso en Railway** (para empezar gratis) y **B) otros hostings**.
Los planes y límites de Railway son los de su documentación oficial al **26 de noviembre de 2026**; cambian seguido,
así que confírmalos en su página de precios antes de decidir.

---

## A) Railway, paso a paso

### Qué te da cada plan (lo que importa para este sistema)

| | Prueba (30 días) | Free (después de la prueba) | Hobby |
|---|---|---|---|
| Costo | 5 USD de crédito, una sola vez | 0 USD, con 1 USD de crédito al mes | 5 USD/mes, con 5 USD de uso incluidos |
| ¿Se duerme sin tráfico? | No | **Sí** (a los 5-10 min); si se acaba el crédito, se detiene hasta el siguiente ciclo | No |
| Volúmenes por proyecto | 3 | **1** | 10 |
| Tamaño de volumen | 0.5 GB | 0.5 GB | 5 GB |
| Dominio propio | Sí | No | Sí |

Consecuencias:

- **Fotos de pedidos de clientes:** necesitan un volumen propio, además del que usa MySQL. En el plan **Free solo se
  permite 1 volumen por proyecto** y MySQL ya ocupa ese lugar, así que ahí las fotos se perderían en cada despliegue
  (los pedidos sí se guardan). Con la **Prueba** (3 volúmenes) y **Hobby** (10) no hay problema. Confírmalo en tu panel.
- **Al terminar la prueba**, Railway borra los volúmenes de las cuentas de prueba 30 días después de que se acabe el
  crédito, salvo que pases a un plan de pago. Si ya tienes datos reales, respáldalos o actualiza a Hobby antes.
- **Plan Free:** la primera persona que abra la app después de un rato sin uso espera unos segundos mientras despierta.
- Al pasar de la Prueba al plan Free rige el límite de 1 volumen por proyecto; la documentación no aclara qué pasa con un segundo volumen ya creado, así que lo más seguro es pasar a Hobby antes de que termine la prueba.

### Paso 1. Subir los cambios a GitHub

Con los archivos de `mipastelweb-cambios.zip` ya copiados en tu proyecto:

```bash
git add -A
git commit -m "App instalable, correcciones y preparación para publicar"
git push
```

### Paso 2. Crear el proyecto en Railway

1. Entra a railway.com e inicia sesión con tu cuenta de GitHub (las cuentas nuevas no piden tarjeta).
2. **New Project → Deploy from GitHub repo** y elige `MipastelWeb`.
3. Railway detecta el `Dockerfile` y empieza a construir. **Es normal que este primer despliegue falle o se reinicie**:
   todavía no tiene base de datos ni variables.

Railway le pasa el puerto a la app con la variable `PORT`; el `Dockerfile` ya lo lee solo.

### Paso 3. Agregar MySQL

En el lienzo del proyecto: **+ New → Database → MySQL** (o `Ctrl/Cmd + K` y busca MySQL). Anota el **nombre exacto
del servicio** (por defecto `MySQL`): en el paso siguiente importa mayúsculas y minúsculas.

### Paso 4. Variables de la app

Abre el servicio de **la app** (no el de MySQL) → pestaña **Variables** y agrega estas, una por una:

```
APP_NAME=Mi Pastel
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:PEGA-AQUI-LA-LLAVE
APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}
APP_TIMEZONE=America/Guatemala
APP_LOCALE=es
APP_FALLBACK_LOCALE=es

LOG_CHANNEL=stderr
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database

ADMIN_EMAIL=tu-correo@ejemplo.com
```

- Las líneas con `${{ ... }}` son **referencias**: Railway las reemplaza con los datos reales de MySQL. Si renombraste
  el servicio de la base, cambia `MySQL` por ese nombre.
- **APP_KEY:** genera una con `php artisan key:generate --show`, o con `openssl rand -base64 32` y antepón `base64:`.
  Guárdala: si cambia, se cierran todas las sesiones.
- **ADMIN_PASSWORD** es opcional. Si no la defines, el sistema crea una contraseña aleatoria y la muestra una sola vez (paso 8).
- `APP_URL` usa el dominio del paso 5; si la referencia no se resuelve, pega la dirección `https://...up.railway.app` completa.

### Paso 5. Generar el dominio

Servicio de la app → **Settings → Networking → Generate Domain**. Esa dirección `https://...` es la que van a abrir
las sucursales. Guárdala.

### Paso 6. Volumen para las fotos

1. `Ctrl/Cmd + K` (o clic derecho en el lienzo) → crear **Volume** y conéctalo al servicio **de la app**.
2. **Mount path:** `/var/www/html/storage/app/public`
3. Respeta los límites de la tabla: en el plan Free este paso no es posible (ya hay un volumen en MySQL).

Notas del volumen según la documentación de Railway: se monta al **iniciar** el contenedor (no durante la
construcción), y cada redespliegue tiene una breve interrupción. Si en los logs ves errores de permisos al guardar
fotos, agrega la variable `RAILWAY_RUN_UID=0` al servicio de la app.

### Paso 7. Desplegar y revisar los logs

Haz **Redeploy** del servicio de la app y abre **View logs**. Lo normal al arrancar:

- Puede aparecer `Base de datos no disponible todavía (intento N/20)`: la base estaba despertando. Es esperado y se
  reintenta solo hasta por 1 minuto.
- Luego deben verse las migraciones (`DONE`) y el servidor Apache arrancado.

Abre el dominio: debes ver la pantalla de inicio de sesión de Mi Pastel.

### Paso 8. Crear el administrador y los usuarios de las sucursales (una sola vez)

Hace falta una consola dentro del contenedor. Dos formas:

- **Panel:** en el servicio de la app, la consola (*Console*) del panel de Railway.
- **CLI:** instala la CLI de Railway, luego `railway login`, `railway link` (elige tu proyecto) y `railway ssh`
  (la primera vez te pide registrar una llave SSH).

En ambos casos el servicio debe estar **corriendo**: si es plan Free y está dormido, abre primero la URL del sitio.
Ya dentro, ejecuta:

```bash
cd /var/www/html
php artisan db:seed --force      # sucursales, productos, precios y el administrador
php artisan mipastel:usuarios    # un usuario por sucursal, con contraseña aleatoria
```

Cada comando **muestra las contraseñas una sola vez**: cópialas de inmediato y entrégalas a cada sucursal.
Si definiste `ADMIN_PASSWORD`, el seeder usa esa para el administrador y no la muestra.

> No vuelvas a correr `db:seed` en producción sin necesidad: `ProductoSeeder` reactiva todos los productos y
> restablece sus nombres y precios. Los precios de pasteles sí están protegidos (solo se cargan si la tabla está vacía).

### Paso 9. Probar antes de repartir contraseñas

1. Entra como administrador y como una sucursal. La sucursal solo debe ver sus pedidos.
2. Registra un pedido normal y un pedido de cliente **con foto**.
3. Haz **Redeploy** y comprueba que la foto sigue ahí (confirma que el volumen funciona).
4. Guarda un control diario y descarga un PDF de reporte.
5. Revisa la hora: un pedido hecho en la tarde debe tener la fecha de hoy (zona horaria de Guatemala).

### Paso 10. Instalar la app en cada celular

- **Android (Chrome):** abrir la dirección, iniciar sesión y tocar **Instalar app** (botón en la barra superior) o el menú ⋮ → *Instalar aplicación*.
- **iPhone (Safari):** tocar **Compartir** → *Agregar a pantalla de inicio*.

Queda un ícono de Mi Pastel que abre el sistema en pantalla completa. Sin internet muestra un aviso de
"Sin conexión" y los datos no se guardan hasta recuperar la conexión.

### Medir el consumo y decidir si pagar

Deja el sistema corriendo una semana con una o dos sucursales y mira el consumo de crédito en el panel de Railway.
Con eso sabrás si cabe en el plan Free o si conviene **Hobby** (5 USD/mes), que además evita que se duerma y permite
el volumen de fotos junto con MySQL.

### Problemas comunes

| Síntoma | Qué revisar |
|---|---|
| Pantalla "Application failed to respond" o el contenedor se reinicia | Mira *View logs*. Casi siempre falta una variable o la base no respondió. |
| `No application encryption key has been specified` | Falta `APP_KEY`. |
| `SQLSTATE ... Connection refused` o `Unknown host` | Las referencias `${{MySQL...}}` no coinciden con el nombre del servicio de la base, o MySQL está dormida (reintenta). |
| La página se ve sin estilos o la sesión se cierra sola | `APP_URL` debe ser `https://...` y `SESSION_SECURE_COOKIE=true`. |
| Las fotos desaparecen tras un despliegue | El volumen no está conectado al servicio de la app o el mount path no es exactamente `/var/www/html/storage/app/public`. |
| Quedó la cuenta de prueba vencida | Pasa a Hobby antes de que se borren los volúmenes (30 días después de acabarse el crédito). |

### Mantenimiento

- **Respaldos:** los pedidos y controles viven en MySQL. Railway ofrece respaldos manuales y automáticos para
  servicios con volumen; revisa en el volumen de MySQL qué incluye tu plan, y exporta la base de vez en cuando.
- Para actualizar los celulares después de cambiar estilos o el ícono, sube el número `VERSION` en `public/sw.js`.
- Pruebas automáticas antes de publicar cambios: `php artisan test`.

---

## B) Otros hostings

**Con Docker (Fly.io, un VPS con Docker, etc.):** el `Dockerfile` está listo. Necesitas una base MySQL/MariaDB,
las variables de `.env.production.example`, y un volumen persistente en `/var/www/html/storage/app/public` para las
fotos. Al arrancar, el contenedor corre `php artisan migrate --force` solo. Después ejecuta los comandos del paso 8.

> Render gratis no sirve para este sistema: sus servicios gratuitos no permiten disco persistente (las fotos se
> pierden), su base Postgres gratuita expira a los 30 días y no ofrece MySQL nativo.

**Hosting compartido (cPanel):** sube el proyecto, apunta el dominio a la carpeta `public/`, crea la base MySQL,
copia `.env.production.example` a `.env` con tus datos y, desde Terminal, ejecuta `composer install --no-dev`,
`php artisan key:generate`, `php artisan migrate --force` y `php artisan storage:link`. Requiere PHP 8.3 con las
extensiones `gd`, `mbstring`, `zip` y `pdo_mysql`.
