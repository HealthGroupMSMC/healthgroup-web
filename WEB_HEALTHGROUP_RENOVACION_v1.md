# www.healthgroup.es — Renovación incremental (v1)

**Dominio**: `www.healthgroup.es` (existente, WordPress)
**Modo**: renovación in-place sobre la instalación actual, sin tocar la app Laravel del backend ni el proyecto futuro Medicaljobs.
**Alcance v1**: ofertas de empleo + formulario de candidatura con subida de documentación.
**Fecha**: 2026-05-13
**Gestor único**: Pablo Heredia

---

## 1. Contexto

Health Group tiene dos carriles tecnológicos:

1. **HG tradicional** (este documento): operativa actual sobre Excel/Odoo/asesoría laboral + **web pública `www.healthgroup.es`** que necesita modernización. La modernización se hace **incremental** — solo lo que decidamos.
2. **Medicaljobs** (proyecto paralelo, no entra aquí): aplicación Laravel/Vue ya entregada por Acceleralia con auto-matching, app móvil y áreas privadas de cliente/empleado. Vive aparte y se retomará en su momento. Documentación: ver [`ARQUITECTURA_PROPUESTA_v1.md`](ARQUITECTURA_PROPUESTA_v1.md).

**Lo que entra en v1 (acotado):**

- Módulo de **ofertas de empleo** (publicación, listado público, detalle).
- **Formulario de candidatura** con subida de documentación del candidato.
- Mínimos cambios cosméticos para integrar el módulo con la marca HG actual (logo caballo de mar más llamativo, paleta verde médico claro + azul marino oscuro).

**Lo que NO entra en v1:**

- Home, Quiénes somos, Servicios, Contacto, blog, área cliente, área empleado. Se valorarán **sobre la marcha** cuando entremos en harina y veamos el estado real.
- Cualquier integración con el backend Laravel HG o con Medicaljobs.

---

## 2. Auditoría previa

### Lo que ya sabemos (desde fuera, sin acceso al admin)

| Dato | Valor | Implicación |
|---|---|---|
| Stack | **WordPress** confirmado | Endpoint `https://healthgroup.es/wp-json/` activo (API REST WP) |
| Dominio canónico | `https://healthgroup.es/` | `www.healthgroup.es` redirige 301 a sin-www |
| CDN/WAF | **Cloudflare** activo | `Server: cloudflare`, `CF-RAY` presentes |
| Anti-bot | reCAPTCHA / hCaptcha / Cloudflare challenges | Cabecera `permissions-policy` lo indica |
| Cache | `max-age=3600` | Borrar caché al desplegar cambios |
| Page ID home | 25 | Útil al editar la home si toca |

> **Nota**: WebFetch automatizado da 403 (bloqueado por Cloudflare). Cualquier auditoría posterior la haré con curl/UA real o desde wp-admin.

### Inventario de plugins (28 instalados, 26 activos, 21 desactualizados)

**Piezas reutilizables (ya presentes):**

| Pieza | Plugin | Versión | Nota |
|---|---|---|---|
| Job board | **Simple Job Board** (PressTigers) | 2.14.1 | **INACTIVO**. Activar y evaluar antes de plantear sustituto. |
| Formularios | **WPForms** (Pro) | 1.9.8.7 | Cubre multi-step + uploads para el form de candidatura. |
| Anti-spam captcha | **Cloudflare Turnstile** (Simple CAPTCHA Alt.) | 1.37.0 | Ya configurable. |
| Anti-spam comentarios | **Akismet** | 5.6 | Activo. |
| SMTP transaccional | **WP Mail SMTP** | 4.5.0 | Configurable (Gmail/Mailgun/SES/SMTP). |
| Seguridad + 2FA | **Really Simple Security** | 9.4.0 | Ya da 2FA, hardening WP, detección vulnerabilidades. |
| SEO | **Yoast SEO** | 25.3 | Versión razonable. |
| Redirecciones 301 | **Redirection** | 5.5.2 | Útil al cambiar slugs. |
| Multidioma | **WPML Multilingual CMS** + String Translation | 4.7.6 / 3.3.3 | Activo (confirmar si la web es bilingüe ES/EN). |
| Analítica web | **Site Kit by Google** | 1.170.0 | + Hotjar 1.0.16 + Metricool 1.24 |
| Marketing automation | **WP Mautic** | 2.4.2 | Mautic tracker. Confirmar si se usa. |
| Auto-publicación social | **Blog2Social** | 8.4.6 | Versión muy desfasada (8.9.1 disponible). |
| Cookies banner | **Cookie Notice & Compliance** | 2.5.11 | Versión vieja (3.0.5 disponible). **No registra consentimientos**. |
| Constructor de tipos | **Toolset Types** + Layouts + Views | 3.6.x | Pesado. Verificar si se usa para algún CPT (p.ej. las ofertas). |
| Utilidades | **Disable Comments**, **Yoast Duplicate Post**, **Regenerar miniaturas** | — | OK. |

**Deuda técnica seria a sanear:**

| Plugin | Versión | Problema | Acción |
|---|---|---|---|
| **Caldera Forms** | 1.8.9 | Plugin abandonado por Caldera Labs (~2022). Sin parches de seguridad. | **Desinstalar** tras verificar que ningún formulario activo lo usa. |
| **Slider Revolution** | 5.4.7.2 | Versión muy antigua (van por 6.x). CVEs conocidos en la rama 5.x. Licencia no activada. | **Actualizar (requiere licencia)** o **sustituir** por slider gratis. Si no se usa, desinstalar. |
| **Contact Form 7** + **CFDB7** | 6.1.4 / 1.3.4 | Funcional pero **duplicación** con WPForms Pro. | Migrar formularios a WPForms y desinstalar. |
| **WPForms Lite** | 1.9.8.7 | Inactivo, redundante con WPForms Pro. | **Borrar**. |
| **Duplicate Page** | 4.5.6 | Redundante con Yoast Duplicate Post (también instalado). | Quedarse con uno (Yoast). |
| Tema **Flatsome** | — | "Activate Theme" en barra → licencia no activada. Sin updates de tema. | Recuperar licencia (¿Acceleralia?) o renovar (~60 €/año). |
| 21 plugins desactualizados | — | Acumulación. | Aplicar updates **tras backup**. |

**Faltantes críticos:**

| Pieza | Estado | Acción |
|---|---|---|
| **Backups** | ❌ No hay UpdraftPlus / BackWPup / Duplicator | **Instalar UpdraftPlus + Backblaze B2 ANTES de tocar nada.** |
| **Protección direct-access a uploads** | ❌ Sin Prevent Direct Access Gold | Instalar antes de subir DNIs/IBAN. |
| **Offload Media a S3** | ❌ Sin WP Offload Media | Configurar B2 + WP Offload Media Lite (gratis). |
| **Registro de consentimientos RGPD** | ❌ Cookie Notice no lo hace | Cambiar a **Complianz** o complementar con plugin de registro. |

### Lo que hay que comprobar en wp-admin (necesito acceso)

| Comprobación | Para qué |
|---|---|
| Versión de WordPress core | Si está muy desactualizado, prioridad 0 actualizar antes de añadir nada |
| Versión de PHP del hosting | WP moderno requiere PHP 8.1+. Si está en 7.x hay que pedir actualización a NetSolutions |
| Tema activo | Para decidir si añadimos un child theme o usamos el actual |
| Plugins ya instalados | No duplicar: si ya hay Contact Form 7 / Yoast / Complianz, los reutilizamos |
| Backups configurados | Si no hay, instalar UpdraftPlus → Backblaze B2 antes de cualquier cambio |
| Certificado SSL | Comprobar que está vigente y bien configurado |
| Acceso wp-admin | Confirmar credenciales y rol admin |
| Contenido vivo | Páginas existentes a mantener / archivar |

**Acción inmediata**: entrar a `wp-admin/`, sacar capturas/exportar lista de plugins y tema, y pasármelo. Con eso completamos esta tabla.

---

## 3. Stack ajustado al inventario real

Tras auditar los 28 plugins instalados, el stack se reduce a:

**Plugins a instalar (lo que falta):**

| Pieza | Plugin | Coste/año | Justificación |
|---|---|---|---|
| Backups | **UpdraftPlus** + Backblaze B2 | 0 € | **Imprescindible antes de cualquier cambio.** |
| Protección uploads | **Prevent Direct Access Gold** | ~50 € | Bloquea URLs directas a `/wp-content/uploads/`. |
| Offload media | **WP Offload Media Lite** + Backblaze B2 | ~15 € | DNIs/IBAN fuera del servidor WP. |
| Registro consentimientos RGPD | **Complianz** (sustituye o complementa Cookie Notice) | 0–60 € | Necesario para datos sensibles del candidato. |

**Plugins a reutilizar (ya instalados):**

| Pieza | Plugin presente | Acción |
|---|---|---|
| Job board | **Simple Job Board** | Activar y evaluar. Si insuficiente, migrar a WP Job Manager. |
| Formulario candidatura | **WPForms Pro** | Configurar form multi-step + uploads + lógica condicional. |
| Anti-spam | **Cloudflare Turnstile** + Akismet | Acoplar Turnstile a WPForms. |
| SMTP transaccional | **WP Mail SMTP** | Configurar con SES/Brevo y `empleo@healthgroup.es`. |
| 2FA admin | **Really Simple Security** | Forzar para todos los roles administrativos. |
| SEO/redirecciones | **Yoast SEO** + **Redirection** | Mantener URLs en migraciones. |

**Plugins a quitar/consolidar:**

- **Caldera Forms** (abandonado, riesgo).
- **Contact Form 7** + **CFDB7** (migrar a WPForms Pro).
- **WPForms Lite** (redundante con Pro).
- **Duplicate Page** (redundante con Yoast Duplicate Post).
- **Slider Revolution v5.4.7.2** (CVEs antiguos; actualizar con licencia o sustituir).

**Coste anual nuevo estimado**: 50–125 €/año (sólo lo que falta). El resto ya está pagado/instalado. Hosting aparte.

> Tema **Flatsome** se mantiene en v1. Cualquier personalización via child theme. La licencia del tema necesita estar activa para recibir actualizaciones — primera comprobación a hacer.

---

## 4. Módulo de ofertas + candidaturas

### 4.1 CPT `job_listing` (WP Job Manager)

| Campo | Tipo | Notas |
|---|---|---|
| Título | string | "DUE turno noche Cádiz", "Médico urgencias Madrid"... |
| Perfil | taxonomy `job_category` | Médico / DUE / TES / TCAE / Fisio / Otros |
| Tipo de contrato | taxonomy `job_type` | Indefinido / Temporal / Sustitución / Refuerzo |
| Ubicación | string | Provincia o ciudad |
| Régimen | meta | General SS / Autónomo |
| Salario | meta opcional | Si se quiere mostrar |
| Fecha de cierre | meta `_application_deadline` | Auto-desaparece pasada la fecha |
| Descripción | content | HTML libre |

### 4.2 Formulario de candidatura (WPForms Pro — 3 pasos, paralelo al CF7 viejo durante migración)

> **Importante**: el form actual en `/candidatos` es CF7 #205104 vinculado a Mautic. No se toca. El nuevo se monta en URL paralela hasta que esté probado, después se migra el shortcode.

**Paso 1 — Datos personales** (todos obligatorios):

- nombre_completo (text)
- email (email)
- telefono (tel)
- perfil (select: Médico / DUE / Fisio / Gerocultor / TES / TCAE / Otros)
- numero_afiliacion_ss (regex `^\d{2}-\d{10}$`)
- iban (validación IBAN ES mod 97)

**Paso 2 — Documentación**:

| Documento | Obligatorio | Formato | Notas |
|---|---|---|---|
| CV | sí | PDF / DOC / DOCX, máx 5 MB | |
| Título | sí | PDF / JPG / PNG, máx 5 MB | Flag opcional "Homologado" si aplica |
| Certificado de Colegiación | **condicional** | **PDF**, máx 5 MB | **Oculto y omitido** si perfil ∈ {TCAE (Auxiliar de enfermería), TES (conductor de ambulancia)} — perfiles sin obligación de colegiación. Obligatorio para el resto. |
| DNI anverso | sí | JPG / PNG / PDF, máx 5 MB | |
| DNI reverso | sí | JPG / PNG / PDF, máx 5 MB | |
| Foto reciente tipo carnet | **opcional** | JPG / PNG, máx 5 MB | Indicar "(opcional)" claramente en el label |
| Formaciones (PRL, RCP, etc.) | **opcional** | PDF / JPG / PNG, multi-upload hasta 10 | Indicar "(opcional)" claramente en el label |

**Paso 3 — Confirmación**:

- consentimiento_rgpd (obligatorio) — link a `/politica-de-privacidad`
- consentimiento_bolsa (opcional) — "Quiero formar parte de la bolsa de empleo de Health Group"

**Identificador de candidatura**: `CAND-{año}-{nnnnn}` autogenerado en el envío. Email de confirmación a `rrhh@healthgroup.es` (recepción) y al candidato (solo recibo, **sin adjuntos** por seguridad).

**Anti-spam**: Cloudflare Turnstile (sitekey `0x4AAAAAABDoyN9gXe_ZOR63` ya operativo en CF7) + Akismet.

---

## 5. RGPD — no es opcional

La documentación pedida es categoría sensible (DNI por ambas caras, IBAN, número afiliación SS, titulación). Mínimos:

1. **Política de privacidad específica del módulo de empleo**: finalidad (selección de personal), base jurídica (consentimiento + medidas precontractuales), plazo de conservación 12 meses salvo consentimiento de bolsa, derechos ARSULIPO.
2. **Cifrado/protección en reposo**: Prevent Direct Access Gold + Backblaze B2 fuera del servidor WP.
3. **Doble consentimiento**: candidatura ≠ bolsa de empleo.
4. **Registro de consentimientos**: vía Complianz o equivalente ya instalado.
5. **Borrado a petición**: botón en admin que elimina entrada + archivos B2.
6. **Retención automática**: cron 12 meses para candidaturas sin contratación + sin consentimiento de bolsa.
7. **Email de confirmación**: enumera datos recibidos y derechos.
8. **Encargados de tratamiento**: firmar DPA con Backblaze, SES, hosting (NetSolutions).

---

## 6. Decisiones cerradas

| Ref | Decisión | Valor final |
|---|---|---|
| A1 | Dominio | `healthgroup.es` (canónico, sin www). Confirmar TLDs defensivos `.com` con NetSolutions. |
| A2 | Hosting | Dedicado para WP, separado del backend Laravel HG. Confirmar plan actual con NetSolutions. |
| **B3** | **Logo HG en v1** | **No se toca**. Caballo de mar se mantiene como está. El retoque "más llamativo" se trabaja después, sin bloquear lanzamiento. |
| **B4** | **Paleta v1** | Verde médico claro **`#5EBA9E`** + Azul marino oscuro **`#1B3A5C`**. Aplicar vía CSS del child theme. |
| **C5** | **Buzón candidaturas** | **`rrhh@healthgroup.es`** (ya existe y se usa en `/candidatos`). No crear `empleo@`. |
| C5b | Buzón general | `info@healthgroup.es` (ya existe y se usa en `/contacto`). |
| **C6** | **Copia al candidato** | Solo recibo + identificador. **Sin adjuntos por seguridad**. |
| D1 | Job board | Reactivar **Simple Job Board** (ya instalado, página `/current-jobs` con shortcode `[jobpost]` preparada desde 15-ene-2026). Sin instalar WP Job Manager. |
| D2 | Form de candidatura | Construir nuevo con **WPForms Pro** en URL paralela (`/postularme` u otra). El CF7 #205104 actual + integración con Mautic se mantienen vivos durante la migración. |
| D3 | Backup v1 | **All-in-One WP Migration** (UpdraftPlus dio "unhandled case"). Backup nativo de hosting pendiente confirmación NetSolutions. |
| D4 | Acceso operativo | REST API de WP via Application Password ya operativa. WP-CLI por SSH pendiente confirmación NetSolutions. |
| D5 | Versionado configs/assets | **GitHub** disponible. Útil para guardar child theme, CSS de paleta, exports de WPForms, contenido editable. |

---

## 7. Roadmap de implementación

| Fase | Tareas | Responsable | Tiempo |
|---|---|---|---|
| **F0 — Auditoría** ✅ | Inventario plugins (28, 21 desactualizados), CPTs, formularios CF7 (13), páginas (24), buzones reales. **HECHO** vía REST API. | Claude | 0.5 d |
| **F1a — Backup** | Instalar **All-in-One WP Migration** → exportar sitio completo a archivo descargable. Guardar copia local + en Google Drive personal de Pablo. | Pablo (UI) | 0.5 d |
| **F1b — Higiene base** | Actualizar 21 plugins desactualizados (en lotes de 3-5 con verificación visual entre tandas). Actualizar WP core a 6.9.4. Reactivar **Simple Job Board** y verificar ofertas en BBDD. | Pablo + Claude | 1 d |
| **F1c — Saneamiento** | Comprobar Caldera Forms y Slider Revolution en uso real; si están huérfanos, desinstalar. Borrar WPForms Lite (redundante). Borrar Duplicate Page (redundante con Yoast Duplicate Post). | Claude (audit) + Pablo (delete) | 0.5 d |
| **F2 — Identidad mínima** | Crear child theme de Flatsome. Aplicar paleta `#5EBA9E` + `#1B3A5C` vía CSS. Sin tocar logo. | Claude (CSS) | 1.5 d |
| **F3 — Job board** | Activar Simple Job Board, configurar taxonomías de perfil (Médico/DUE/TES/TCAE/Fisio/Gerocultor/Otros) y tipo contrato (Indefinido/Temporal/Sustitución/Refuerzo). Crear 2-3 ofertas reales. | Claude (ofertas) + Pablo (config plugin) | 2 d |
| **F4 — Form candidatura** | WPForms Pro: nuevo form multi-step en `/postularme` con lógica condicional (colegiación oculta si TCAE/TES), validación IBAN mod-97 + nº SS regex, uploads obligatorios/condicionales/opcionales según §4.2. Acoplar Turnstile y notificación a `rrhh@healthgroup.es`. | Claude (config WPForms) + Pablo (revisar) | 3 d |
| **F5 — RGPD** | Política específica del módulo de empleo, doble consentimiento (oferta vs bolsa), retención 12 meses automática. Evaluar si Cookie Notice 2.5.11 actualiza a 3.0.5 o se sustituye por Complianz para registro de consentimientos. | Claude (textos) + Pablo (revisar legal) | 2 d |
| **F6 — Pruebas + go-live** | Test end-to-end de candidatura real, comprobar email a `rrhh@healthgroup.es`, validar Turnstile, pasar shortcode del form viejo CF7 al nuevo WPForms en `/candidatos`. | Pablo (test) + Claude (switch) | 1 d |
| **F7 — Limpieza post-go-live** | Tras 2 semanas con el nuevo form en producción sin incidencias, desinstalar CF7 #205104 antiguo si no se necesita (ojo: vinculado a Mautic). | Decisión post-F6 | 0.5 d |
| **Total** | | | **~12-13 días reales** |

> Resto de elementos (home, servicios, etc.) — se valoran **después** del go-live de F6, con la web ya funcionando y feedback real.

---

## 8. Preguntas para NetSolutions

Lista en bruto para construir el email (en sección §10):

1. ¿Qué dominios tenemos registrados a nombre de Health Group? (`.es`, `.com`, otros TLDs defensivos)
2. ¿Hosting actual de `www.healthgroup.es`: proveedor, plan, panel, versión PHP, MySQL, RAM/CPU asignados?
3. ¿Backups automáticos? ¿Frecuencia y retención?
4. ¿Hay entorno de staging disponible o lo gestionamos nosotros?
5. ¿SSL gestionado por NetSolutions (Let's Encrypt auto-renovable)?
6. ¿Buzón `empleo@healthgroup.es` configurable? ¿IMAP/SMTP outgoing? ¿Cuotas?
7. ¿SPF/DKIM/DMARC configurados para envío transaccional desde el dominio?
8. ¿Pueden firmar DPA (acuerdo de encargado de tratamiento RGPD) o ya hay uno firmado?
9. ¿Migrar a un hosting Cloud/VPS dedicado para WP o el plan actual aguanta? (Recomendación nuestra: dedicado).
10. Estado y credenciales del CDN/WAF actual — la web devuelve 403 a peticiones externas, lo que sugiere protección activa.

---

## 9. Cuestiones abiertas (dependen de respuesta de NetSolutions)

| # | Tema | Impacto |
|---|---|---|
| N1 | Acceso SSH + WP-CLI | Si lo dan: actualizaciones de plugins/core mucho más rápidas y seguras. Si no: lo haces tú vía wp-admin. |
| N2 | Backups automáticos del hosting | Si los tienen: nos quita la dependencia del backup vía plugin. |
| N3 | Entorno de staging | Si lo dan: pruebas sin tocar producción. Si no: usar plugin tipo WP Staging o trabajar con ventana de mantenimiento corta. |
| N4 | DPA firmado | Imprescindible antes de tratar DNI/IBAN/SS de candidatos. |
| N5 | Quién gestiona Cloudflare | Determina si podemos hacer ajustes DNS/cabeceras directamente o hay que pedírselo. |
| N6 | TLDs defensivos (`.com`, `.health`) | Defensivo de marca. Decisión de coste vs riesgo. |
| N7 | Licencia Flatsome | Si Acceleralia la registró, recuperarla. Si no, comprar nueva (~60 €/año). Sin licencia = sin updates de tema. |

Email para resolver N1-N6 está redactado en [`EMAIL_NETSOLUTIONS_v1.md`](EMAIL_NETSOLUTIONS_v1.md). Enviar el lunes 18-may o cuando se considere oportuno.

---

## 10. Estado real del sitio (auditoría completada vía REST API)

Hechos descubiertos sin pedir más capturas al usuario:

**Identidad operativa:**
- Razón social: **MEDICAL SERVICE M. CASTILLA S.L.** (CIF B92639186, sede Málaga)
- Dirección postal: CALLE PUERTA DEL MAR, 7. 29005 MÁLAGA
- Teléfono oficina: 952 22 45 54 · Teléfono candidatos: 648 90 90 90
- Buzones activos: `info@`, `rrhh@`, `direccion@` (todos `@healthgroup.es`)
- Descripción meta: *"Consultora de RRHH especializada en perfiles sanitarios"*

**Plugins con namespace REST activo** (lo que realmente funciona):
- Toolset Blocks + Views + Dynamic Sources (CPTs custom probables)
- Caldera Forms (cf-api v2 + v3) — **NO está pasivo**, su API responde
- Contact Form 7 + Akismet + Redirection + Yoast + Site Kit + WPML (4 namespaces)

**Formularios CF7 vivos** (13 forms detectados):
- `#205104` "For Mautic - Formulario ofertas de empleo" — el que usa `/candidatos` ← **vinculado a Mautic, no romper**
- `#205254` "contacto" — el de `/contacto`
- Plantillas paralelas para hospitales, geriátricos, parques, inversores, empresas, todas con variantes EN
- 3 newsletters (ES + blog + EN)

**Páginas relevantes y su contenido:**
- `/ofertas-empleo` (ID 13, mod 2026-01-15) — contenido estático corporativo + form CF7 #205104 embebido. **No es un listado de ofertas.**
- `/current-jobs` (ID 206829, mod 2026-01-15) — **contiene literalmente `[jobpost]`**. Página preparada para listar ofertas con Simple Job Board, pendiente de reactivación del plugin.
- `/candidatos` (ID 206467) — formulario activo con campos: profesión, ciudadanía UE, homologación, colegiación, disponibilidad, CV, título.
- `/contacto` (ID 205251) — formulario contacto general.
- Legales (`/aviso-legal`, `/politica-de-privacidad`, `/politica-de-cookies`) actualizadas el **2025-07-15**.

**Bugs detectados en el form actual de `/candidatos`:**
- Uploads de CV y Título declaran `accept="audio/*,video/*,image/*"` → no aceptan PDF ni Word correctamente. **Bug real**.
- No es multi-step (todos los campos en una sola pantalla larga).
- Campo de teléfono con placeholder confuso "Mensaje *".

**Bloqueos confirmados:**
- Simple Job Board está inactivo → su CPT `jobpost` da 404 en REST. Las ofertas si las hubo están en BBDD pero invisibles hasta reactivar.
- Caldera Forms NO lista forms vía su API REST (cf-api/v3/forms da 404) — auditoría manual en wp-admin → Caldera Forms necesaria.

**Auditoría de uso real (sesión 2026-05-15):**

| Plugin | ¿En uso real? | Acción |
|---|---|---|
| **Caldera Forms** | ❌ Ninguna página/post lo usa | Desinstalar tras 1 semana de margen |
| **Slider Revolution v5.4.7.2** | ✅ Solo en la **home (ID 25)** | Decisión A (actualizar con licencia) o B (sustituir por UX Slider de Flatsome) — **PENDIENTE** |
| **WPForms Pro** | ✅ Form ID 206853 "Solicitud de empleo" con 22 envíos reales (14-feb a 11-mar-2026) | Ampliar este form con campos pendientes, NO crear uno nuevo |
| **CF7 #205104 "For Mautic"** | ✅ En `/candidatos` + integración Mautic | NO tocar hasta validar el WPForms ampliado |

**WPForms 206853 — pendiente de localizar:**
- Ningún `wp_block`, `view`, `view-template` ni page/post conocido referencia este shortcode.
- 22 envíos reales recibidos en 25 días (14-feb a 11-mar), todos sin leer.
- Hipótesis: vivió en landing externa / campaña Mautic ya desaparecida.
- **Acción**: abrir un envío en wp-admin → WPForms → Envíos → clic "Ver" → ver URL de origen registrada.

**Usuarios WP (8 cuentas):**
| ID | Nombre | Slug | Estado |
|---|---|---|---|
| 1 | Netsolutions | `healthadmin` | NetSolutions, NO tocar |
| 2 | Comunicación Health Group | `healthgroup` | Posible cuenta antigua colectiva |
| 3 | Healthgcomunicacion | `healthgcomunicacion` | Posible duplicado de la 2 |
| 4 | Carlos Vivas | `cvivashealthgroup-es` | ¿Ex-agencia? |
| 5 | Beatriz García | `bgarciahealthgroup-es` | ¿Interna? |
| 7 | Isabel Ibilcieta | `iibilcietahealthgroup-es` | ¿Interna activa? |
| **8** | **Pablo** | `direccionhealthgroup-es` | Admin actual |
| 10 | "Eugenio Pablo Yo" | `echagrev` | Sospechoso; Pablo no lo conoce, posible NetSolutions |

→ Limpieza de cuentas pendiente. Confirmar con NetSolutions cuáles son suyas (pregunta en `EMAIL_NETSOLUTIONS_v1.md`).

**Toolset Views de ofertas (pre-existentes desde 2018):**
- `ofertas` (ID 204991), `ofertas-home` (205061), `ofertas-relacionadas` (205027), `view` (205838) + sus 4 Content Templates correspondientes.
- Hipótesis: el sitio tuvo un módulo de ofertas hecho con Toolset Views antes de Simple Job Board. Verificar al reactivar Simple Job Board en F3.

**Blog muerto desde 2020-02-10:**
- 10+ posts con buenos temas SEO (homologación, requisitos sanitarios, big data salud, etc.).
- No entra en v1 pero anotado como oportunidad post-go-live.

**Backup completo realizado:**
- `.wpress` de 2 GB descargado y verificado el 2026-05-15.
- ⚠️ Versión gratuita de All-in-One WP Migration tiene límite 512 MB para IMPORTAR: si hay que restaurar, comprar extensión Unlimited (~70 €) o subir el `.wpress` por FTP a `/wp-content/ai1wm-backups/`.

**Tono visual actual:**
- Color predominante: turquesa medio `rgb(1, 94, 99)` (fondos de cards) y `rgb(0, 143, 157)` (títulos). Cercano al verde médico nuevo `#5EBA9E` → la transición de paleta es suave.
- Imagen header recurrente: `health_.jpg` (cabecera con efecto oscuro).

---

## 11. Email a NetSolutions

Borrador editable disponible en [`EMAIL_NETSOLUTIONS_v1.md`](EMAIL_NETSOLUTIONS_v1.md). Enviar el **lunes 18-may-2026** o cuando se considere oportuno.

---

## 12. Archivos a consultar

- [`CONTEXTO_HG.md`](../../AGENTE%20CUADRO%20MANDOS/CONTEXTO_HG.md) — ecosistema operativo HG (referencia para qué hacer con una candidatura prometedora)
- [`ARQUITECTURA_PROPUESTA_v1.md`](ARQUITECTURA_PROPUESTA_v1.md) — arquitectura paralela del proyecto Medicaljobs (Laravel + mobile). **No entra en este documento.**
- `C:\Users\Pablo Heredia\Documents\Logo\` — logos HG actuales (caballo de mar)
