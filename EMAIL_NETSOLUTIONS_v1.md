# Borrador de email a NetSolutions

**Estado**: borrador editable. Enviar idealmente lunes 18-may-2026 o cuando se haya cerrado la decisión de hosting.
**Destinatario sugerido**: contacto técnico habitual de NetSolutions para HG.
**Remitente sugerido**: `direccion@healthgroup.es` (Pablo Heredia, Dirección).

---

## Asunto

`Health Group — Auditoría técnica de www.healthgroup.es y consultas sobre hosting/dominios`

---

## Cuerpo del email

> Buenos días,
>
> Os escribo desde Health Group (MEDICAL SERVICE M. CASTILLA S.L., B92639186). Estamos preparando una **actualización funcional de la web `www.healthgroup.es`** centrada en el módulo de ofertas de empleo y el formulario de candidatura. Antes de tocar la web necesito confirmar varias cosas sobre el hosting y los dominios para asegurar que cualquier cambio se hace sobre una base sólida.
>
> Os agradecería que me pudierais responder a los siguientes puntos:
>
> **Dominios**
>
> 1. ¿Qué dominios tenemos registrados actualmente a nombre de Health Group / Medical Service M. Castilla? Necesito el listado con fechas de renovación de cada uno. En particular: ¿tenemos `.com`, `.health` u otros TLD defensivos además del `.es`?
>
> **Hosting**
>
> 2. ¿Cuál es el plan de hosting actual de `www.healthgroup.es`? Proveedor, plan contratado, panel de gestión (cPanel / Plesk / propio), versión de PHP y MySQL/MariaDB instaladas, RAM y CPU asignadas.
> 3. ¿El hosting realiza **backups automáticos** del sitio? Frecuencia, retención, y cómo se restauran si fuera necesario.
> 4. ¿Disponemos de **entorno de staging** (clon de pruebas) o lo gestionamos nosotros con un plugin?
> 5. ¿El **SSL** está gestionado por vosotros (Let's Encrypt con renovación automática) o lo gestiona el WordPress?
> 6. ¿Tenemos posibilidad de **acceso SSH** al servidor para usar WP-CLI? Si no, ¿se podría habilitar?
>
> **Email**
>
> 7. Confirmo que tenemos `info@healthgroup.es`, `rrhh@healthgroup.es` y `direccion@healthgroup.es` funcionando. ¿Hay algún otro buzón configurado en el dominio que debamos tener en cuenta?
> 8. ¿Tenemos correctamente configurados **SPF, DKIM y DMARC** para el envío saliente desde el dominio? Estamos pensando en enviar emails transaccionales (confirmaciones de candidatura, etc.) y quiero asegurarme de que no acaben en spam.
>
> **RGPD**
>
> 9. ¿Tenemos un **acuerdo de encargado de tratamiento (DPA)** firmado con NetSolutions como parte del contrato de hosting? El sitio va a tratar datos sensibles de candidatos (titulación sanitaria, IBAN, número de afiliación a la Seguridad Social, DNI) y necesitamos tener esta pieza en orden.
>
> **CDN/WAF**
>
> 10. Veo que el sitio tiene **Cloudflare** activo (responde con `Server: cloudflare` y headers CF-*). ¿La cuenta de Cloudflare la gestionáis vosotros o nosotros directamente? ¿Quién tiene las credenciales? Quiero asegurarme antes de hacer cambios DNS o de cabeceras.
>
> Cuando podáis, me decís y nos coordinamos para los próximos pasos.
>
> Un saludo,
> **Pablo Heredia**
> Dirección
> Health Group · Medical Service M. Castilla S.L.
> 952 22 45 54 · direccion@healthgroup.es

---

## Notas internas (NO enviar)

- Si NetSolutions confirma **acceso SSH + WP-CLI** → mejor camino para mantenimiento.
- Si confirma **backups automáticos del hosting** → nos quita la dependencia de UpdraftPlus / All-in-One.
- Si **gestionan ellos Cloudflare** → tendremos que pedirles cualquier ajuste DNS futuro.
- Si **no tienen DPA estándar** → hay que insistir o cambiar de proveedor antes de tratar datos sensibles del candidato.
- Si **no permiten SSH** → trabajaremos solo vía REST API + UI. Operable pero más lento.
