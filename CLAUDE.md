# Health Group — Web pública (healthgroup.es) · Guía operativa

> Documento de referencia para cualquier sesión de Claude que opere sobre
> la web pública WordPress de Health Group. Lectura obligatoria antes de
> aplicar cambios en producción.

## 1. Identidad

- **Empresa**: MEDICAL SERVICE M. CASTILLA S.L.
- **CIF**: B92639186
- **Dirección postal**: Calle Puerta del Mar 7, 29005 Málaga
- **Web pública**: `https://healthgroup.es` (canónico, sin `www`; `www.healthgroup.es` redirige 301)
- **Sector**: Consultora de RRHH especializada en perfiles sanitarios.
- **Buzones operativos**:
  - `info@healthgroup.es` — contacto general
  - `rrhh@healthgroup.es` — candidaturas (destinatario unificado tras 22-may-2026)
  - `direccion@healthgroup.es` — Pablo Heredia (admin WP)
- **Teléfonos**: 952 22 45 54 oficina · 648 90 90 90 candidatos.

## 2. Stack técnico

| Componente | Valor |
|---|---|
| CMS | WordPress 6.7.5 (6.9.4 actualizable) |
| Tema | Flatsome (licencia sin activar — sin updates de tema) |
| PHP | 8.2.31 |
| Constructor de páginas | UX Builder de Flatsome + Toolset Views/Types |
| Anti-bot | Cloudflare + Turnstile (sitekey `0x4AAAAAABDoyN9gXe_ZOR63`) |
| Prefijo BBDD | `anFJpiGC` (no `wp_`) |
| Plugins activos | 25 (tras limpieza 22-may) |
| Hosting | NetSolutions · ISPConfig · `/var/www/clients/client1/web1/` |

**Plugins críticos en uso:**
- WPForms Pro (form 206853 "Solicitud de empleo")
- Contact Form 7 + CFDB7 (form #205104 "For Mautic - Ofertas de empleo" en `/candidatos`)
- Toolset Types + Views (CPT `oferta-de-empleo` + 4 views)
- WPML Multilingual CMS (ES + EN parcial)
- Yoast SEO + Site Kit (Google Analytics + Search Console)
- Really Simple Security (2FA + hardening)
- WP Mail SMTP (transaccional)
- Cloudflare Turnstile (anti-spam)
- Slider Revolution v5.4.7.2 (en home — pendiente decisión A/B)
- WP Mautic (marketing automation, conectado al form #205104)
- Hotjar (mapas de calor)

## 3. Acceso técnico

### REST API
- **URL base**: `https://healthgroup.es/wp-json/`
- **Auth**: Application Password de Pablo (alias "Claude Code - Pablo").
- **Cloudflare**: requiere `User-Agent: Mozilla/5.0` en todas las peticiones, sin UA da 403.

### SSH + WP-CLI (desde 22-may-2026)
- **Alias**: `ssh hg` (configurado en `~/.ssh/config` local)
- **Host**: `healt-ws01.servers.2n2.es` (atención: `healt`, no `health`)
- **Puerto**: 6666
- **Usuario**: `healthgroup_dev`
- **Auth**: clave pública ed25519 (`~/.ssh/hg_netsolutions_ed25519`)
- **Filtrado**: solo IPs españolas
- **WP-CLI**: 2.12.0 disponible en path; uso `ssh hg "cd ~/web && wp <comando>"`
- **Ruta WP**: `/var/www/clients/client1/web1/web/` (alcanzable como `~/web`)
- **Logs**: `~/log/` (symlinks de Apache)

### Custom endpoints (mu-plugins propios)
- **`hg-offer-meta.php`** (`wp-content/mu-plugins/`):
  - `GET /wp-json/hg/v1/offer/{id}/meta` → lee los 7 wpcf-* del CPT oferta-de-empleo.
  - `POST /wp-json/hg/v1/offer/{id}/meta` → actualiza. Whitelist de 7 fields.
- **`hg-frontend-css.php`** (`wp-content/mu-plugins/`):
  - Inyecta CSS HG en `wp_head` (Select2 visible, inputs legibles, botón Enviar con paleta).
- Backups locales en `Documents/HG_REPOS/diseno/`.

## 4. Modelo de datos del CPT `oferta-de-empleo`

- **CPT** creado por Toolset Types. URL pública: `/oferta-de-empleo/{slug}/`.
- **Taxonomías**: `provincia` (30+ términos), `perfil` (Medicina, Enfermería, TES, TCAE, Fisioterapeuta, Gerocultor, Otro).
- **Custom fields** (Toolset, prefijo `wpcf-`): `descripcion` (textarea HTML), `fecha` (timestamp Unix), `contrato`, `empleo`, `provincia` (texto libre, distinto de la taxonomía), `vacantes`, `duracion`.
- **content y excerpt nativos de WP NO se muestran** — el Content Template de Toolset (`loop-item-in-ofertas` ID 204992) renderiza título + custom fields + form de candidatura.
- Para crear/editar ofertas: REST `wp/v2/oferta-de-empleo` para datos básicos + endpoint custom `hg/v1/offer/{id}/meta` para los wpcf-*.

## 5. Paleta y branding

| Token | Valor | Uso |
|---|---|---|
| Verde médico claro | `#5EBA9E` | Acentos, CTAs primarios, estados positivos |
| Azul marino oscuro | `#1B3A5C` | Textos importantes, cabeceras, fondos premium |
| Gris fondo suave | `#F5F5F2` | Fondos de paneles |
| Gris claro | `#EDEEEC` | Bordes y separadores |
| Rojo destaque | `#E94B3C` | Puntos de mapa, mensajes de error |
| Blanco | `#FFFFFF` | Tarjetas, inputs |

- **Logo**: caballo de mar HG, NO se toca en v1.
- **Identidad**: profesional sanitario, cercana, confiable. Sin neón, sin pop. Estilo "médico moderno español".

## 6. Convenciones de código (no negociables)

### WordPress / PHP

- **`post_content` con JSON**: SIEMPRE `wp_unslash()` antes de `json_decode()`, y `wp_slash(wp_json_encode())` antes de guardar. Sin esto, decode devuelve null y wp_update_post borra el contenido. [Ver `feedback-wp-postcontent-unslash` en memoria.]
- **Antes de modificar `post_content` de un plugin**: anotar tamaño actual y `count($content)`. Tras update, releer y verificar que no se ha reducido dramáticamente.
- **Revisions de WP** son red de seguridad para posts. `wp post list --post_type=revision --post_parent={id}` para recuperar.
- **mu-plugins** deben tener cabecera `Plugin Name:` válida y `if (!defined('ABSPATH')) exit;` al inicio.
- **Permission callbacks** en endpoints REST custom: siempre `current_user_can('edit_posts')` mínimo.
- **Sanitización** de input que va a BBDD: `wp_kses_post()` para HTML, `sanitize_text_field()` para text, etc.

### Comunicación

- **NetSolutions**: peticiones cortas y operativas. Sin explicaciones técnicas largas, sin justificaciones, sin contexto educativo. [Ver `feedback-netsolutions-tono` en memoria.]
- **Pablo**: directo, conciso. Sin sobrecargar con detalles que no pidió.

## 7. Workflow de cambios seguro (obligatorio)

```
1. SNAPSHOT antes:
   - ssh hg "cd ~/web && wp db export ~/backups/pre-{cambio}-$(date +%Y%m%d-%H%M).sql"
   - Anotar IDs y tamaños de objetos a modificar
   - Capturar HTML/CSS frontend si aplica (curl)

2. CAMBIO:
   - Para code/script: subir a wp_scripts/ local, scp a servidor, wp eval-file
   - Para BBDD: wp_unslash() antes de decode, wp_slash() antes de encode
   - Para frontend (paginas/posts): trabajar en duplicado primero si es estructural

3. VERIFICACIÓN:
   - Releer objeto modificado: tamaño coherente, claves esperadas presentes
   - Frontend: curl + grep marcadores esperados
   - Lighthouse antes/después si afecta a rendimiento

4. SI ALGO FALLA:
   - wp_restore_post_revision({rev_id}) para posts
   - wp db import del snapshot pre-cambio
   - SCP del backup del archivo modificado

5. DOCUMENTAR:
   - Qué se cambió, por qué, cómo verificar
   - Si es lección reutilizable: añadir memoria de feedback
```

## 8. Herramientas locales del proyecto

| Herramienta | Versión | Propósito |
|---|---|---|
| Python | 3.12.9 | Scripts (composición de imágenes, sync, batch processing) |
| Pillow | 12.2.0 | Manipulación de imágenes |
| matplotlib | 3.10.9 | Generación de mapas de CCAA |
| openpyxl | 3.1.5 | Plantilla Excel para equipo |
| requests | 2.34.2 | REST API |
| Node | v24.14.1 | Tooling (Lighthouse) |
| Lighthouse | (instalando) | Auditorías de performance/SEO/A11y |
| Playwright | 1.60.0 + Chromium | Browser automation (UI tests, WPForms checks) |

## 9. Skills/agentes recomendados para este proyecto

| Skill/Agente | Cuándo usar |
|---|---|
| `init` | Esta misma guía la genera/refresca |
| `review` | Antes de aplicar mu-plugins, CSS importante, cambios PHP a producción |
| `security-review` | Cambios que tocan auth/forms/uploads/RGPD |
| `simplify` | Code review periódico de mu-plugins y scripts |
| `schedule` | Tareas recurrentes: backup automático semanal, auditoría Lighthouse mensual |
| Agent `Plan` | Antes de cambios estructurales (rediseño layout de página) |
| Agent `Explore` | Auditar zonas del sitio sin saturar contexto |
| Agent `localizacion-tecnica-es` | Cuando interactúe con UI de Toolset/WPML/Flatsome en español |

## 10. Estado actual del proyecto (al 22-may-2026)

- ✅ Backup completo `.wpress` (2 GB) descargado local 16-may.
- ✅ Pipeline de publicación de ofertas operativo (REST + custom endpoint + imagen automática).
- ✅ 81 ofertas viejas archivadas. 3 ofertas activas: Médico Cádiz, Médico pediatra Priego, Enfermero/a Miranda de Ebro.
- ✅ Plantilla Excel para equipo (`PLANTILLA_OFERTAS_HG.xlsx`).
- ✅ SSH + WP-CLI operativos.
- ✅ Mu-plugins propios: `hg-offer-meta.php` y `hg-frontend-css.php`.
- ✅ CF7 #205104 recipient unificado a `rrhh@`, mail_2 activado.
- ⚠️ **Pendiente**: HTTP 400 en POST a `admin-ajax.php` del WPForms — entries no se guardan; activar WP_DEBUG_LOG y capturar logs en tiempo real.
- ⚠️ **Pendiente**: rediseño de `/ofertas-empleo` (ofertas muy abajo, marketing arriba).
- ⚠️ **Pendiente**: gestión de las 22 candidaturas WPForms sin leer (incluye médico argentino homologado).
- ⚠️ **Pendiente**: limpieza usuarios WP, decisión Slider Revolution A/B, actualizaciones WP core y plugins.

## 11. Lo que NO tocar sin permiso explícito

- **Logo HG** (caballo de mar) — postergado para más adelante.
- **Form CF7 #205104** y su integración con Mautic — gestiona candidaturas históricas.
- **WPForms #206853 `post_content`** — vital para `/oferta-de-empleo/*` (incidente 22-may).
- **Páginas `/candidatos`, `/contacto`, `/quienes-somos`** — contenido sensible que requiere revisión humana.
- **Configuración de Cloudflare** — propiedad de NetSolutions, abrir ticket.
- **Usuarios WP** — algunos son de NetSolutions o ex-agencia, requiere confirmación.
- **Actualización de plugins/core en producción** — pedir confirmación, hacer backup antes.

## 12. Documentos y referencias en disco

- `Documents/HG_REPOS/diseno/CLAUDE.md` — este documento.
- `Documents/HG_REPOS/diseno/WEB_HEALTHGROUP_RENOVACION_v1.md` — plan original.
- `Documents/HG_REPOS/diseno/PLANTILLA_OFERTAS_HG.xlsx` — plantilla para equipo.
- `Documents/HG_REPOS/diseno/img_ofertas/` — workspace de imágenes destacadas (mapa, fotos DALL-E, script `compose_offer_image.py`).
- `Documents/HG_REPOS/diseno/wp_scripts/` — scripts PHP de mantenimiento (eval-file).
- `Documents/HG_REPOS/diseno/hg-offer-meta.php` — backup local del mu-plugin REST.
- `Documents/HG_REPOS/diseno/wp_scripts/hg-frontend-css.php` — backup local del mu-plugin CSS.
- Memorias relevantes: `project-web-healthgroup`, `feedback-netsolutions-tono`, `feedback-wp-postcontent-unslash`, `project-medicaljobs` (no confundir).

---

**Última actualización**: 2026-05-22 · Versión 1.0
