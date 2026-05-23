# Health Group — Arquitectura propuesta v1
**Fecha**: 2026-05-06 · Presupuesto orientativo: 300 €/mes

---

## 0. Decisiones de fondo (mi recomendación)

Antes de la arquitectura, los seis criterios que la informan:

1. **Reutilizar lo que ya está pagado y publicado.** Acceleralia entregó un Laravel + React Native serio. Las apps están en tienda con vuestro identificador (`com.healthgroup.app`, vendedor "Medical Service M Castilla SL"). Tirar eso para empezar de cero sería tirar 6-12 meses de inversión por mero gusto de tener algo mío. **Las retomamos.**

2. **El Excel debe morir como fuente de verdad, pero gradualmente.** Mientras coexistan Excel y backend, hay riesgos de divergencia. El plan es **leer del Excel hacia el backend** durante una fase de transición (semanas/meses), y luego apagar el Excel cuando el equipo trabaje cómodo en el panel admin. Forzar el corte el día 1 es receta para rebelión interna y errores en producción.

3. **IA solo donde paga.** Mi opinión clara: el matching automático con IA real es **mucho trabajo, datos limpios necesarios y poca ganancia frente a la heurística que ya existe + filtros**. La IA sí merece invertirse en el extremo del proceso donde HOY perdéis tiempo: **extracción del email del cliente → fila estructurada lista para revisar y aprobar**. Eso ahorra 5-10 minutos por solicitud, es barato (~30-50 €/mes) y bajo riesgo.

4. **WhatsApp en su sitio.** El día 1 lo dejamos como está (manual). El push notification de la app cubre buena parte del caso. WhatsApp Business API se valora más adelante si los DUEs lo piden. Migrar el 648527475 a una API tiene fricción con vuestra operativa actual y puede esperar.

5. **PRL como módulo de primera clase.** No es un anexo. Es una entidad central con vencimientos, alertas, bloqueo automático de asignación si el trabajador no está al día. Lo metemos desde el principio porque (a) lo pediste y (b) es un argumento de venta frente a vuestra competencia.

6. **Hosting barato y controlado.** Cloudways está bien, pero por 30-50 €/mes podemos tener algo equivalente con más control. Si el Cloudways revive y os sale rentable, lo mantenemos. Si no, se migra. **No es un drama mover Laravel de host**.

---

## 1. Arquitectura por capas

```
┌───────────────────────────────────────────────────────────────────┐
│  CANAL PÚBLICO                                                    │
│  ┌─────────────┐  ┌─────────────┐  ┌──────────────────────────┐   │
│  │ App móvil   │  │ Portal web  │  │ Email / WhatsApp legacy  │   │
│  │ (DUEs)      │  │ cliente     │  │ (transición)             │   │
│  └─────────────┘  └─────────────┘  └──────────────────────────┘   │
└────────────────────┬──────────────────────────┬───────────────────┘
                     ▼                          ▼
┌───────────────────────────────────────────────────────────────────┐
│  CAPA DE APLICACIÓN — Laravel 9 + Inertia + Livewire               │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐ ┌────────────┐ │
│  │ Auth +      │  │ Ofertas y   │  │ PRL         │ │ Cierre de  │ │
│  │ Permisos    │  │ Inscripción │  │ (NUEVO)     │ │ turno      │ │
│  └─────────────┘  └─────────────┘  └─────────────┘ └────────────┘ │
│  ┌─────────────────────┐  ┌──────────────────────┐ ┌────────────┐ │
│  │ Panel admin web     │  │ AI: extracción email │ │ Cron jobs  │ │
│  │ (HG operaciones)    │  │ + OCR calendario     │ │ (avisos)   │ │
│  └─────────────────────┘  └──────────────────────┘ └────────────┘ │
└────────────────────┬──────────────────────────┬───────────────────┘
                     ▼                          ▼
┌───────────────────────────────────────────────────────────────────┐
│  CAPA DE DATOS                                                    │
│  ┌────────────┐  ┌───────────────────┐  ┌──────────────────────┐  │
│  │ MySQL      │  │ Storage           │  │ Excel ACTIVIDAD/OK   │  │
│  │ (verdad)   │  │ S3-compatible     │  │ (transición lectora) │  │
│  └────────────┘  │ (docs PRL,        │  └──────────────────────┘  │
│                  │  fotos, etc.)     │                            │
│                  └───────────────────┘                            │
└────────────────────┬──────────────────────────┬───────────────────┘
                     ▼                          ▼
┌───────────────────────────────────────────────────────────────────┐
│  INTEGRACIONES                                                    │
│  ┌──────────────┐  ┌────────────┐  ┌─────────────┐ ┌────────────┐ │
│  │ Firebase FCM │  │ Email out  │  │ Odoo 12/19  │ │ OpenAI API │ │
│  │ (push)       │  │ (SES /     │  │ (facturas)  │ │ (extracc.) │ │
│  │              │  │  Outlook)  │  │             │ │            │ │
│  └──────────────┘  └────────────┘  └─────────────┘ └────────────┘ │
│  ┌────────────────────┐  ┌──────────────────────┐                 │
│  │ Twilio SMS         │  │ Power BI / Power     │                 │
│  │ (confirm. corta)   │  │ Automate (fase 2)    │                 │
│  └────────────────────┘  └──────────────────────┘                 │
└───────────────────────────────────────────────────────────────────┘
```

---

## 2. Servicios y costes mensuales (estimación 2026)

| Pieza | Servicio recomendado | Coste/mes | Justificación |
|---|---|---|---|
| Hosting backend | **Hetzner Cloud CCX23** (4 vCPU AMD, 16 GB RAM, 240 GB NVMe) Frankfurt | **~32 €** | RGPD-friendly (UE), barato, profesional. Migración desde Cloudways estimada 1-2 días. |
| Backups | Snapshots Hetzner + BBDD a Backblaze B2 | **~5 €** | Diarios incrementales, retención 30 días. |
| Storage docs/PRL | **Backblaze B2** (S3-compatible) | **~3-8 €** | Para PDFs de PRL, fotos, certificados. ~100-300 GB previstos. |
| Email transaccional | **Amazon SES** | **~1-3 €** | Avisos a clientes, recuperación de password, alertas de PRL próximas a vencer. 0.10 $/1000 emails. |
| Push notifications | **Firebase FCM** | **0 €** | Free tier suficiente para volumen previsto. |
| SMS de confirmación (opcional) | **Twilio** | **~10-20 €** | Confirmación rápida del trabajador cuando recibe asignación, como respaldo del push. ~100-200 SMS/mes a 0,07 € c/u. |
| OpenAI API | **gpt-4o-mini** (90 % uso) + **gpt-4o** (extracción de imágenes) | **~30-50 €** | Extracción de email cliente → fila estructurada. OCR calendario disponibilidad. Holgura para experimentos. |
| Apple Developer Program | (existente) | **~8 €** | 99 €/año amortizado. |
| Google Play | (existente) | **0 €** | Pago único 25 € ya hecho. |
| Monitoring | **Sentry free** + **Better Stack** uptime free | **0 €** | Free tiers cubren un proyecto de este tamaño. Alertas a email. |
| Dominio + DNS | (existente) | **~1 €** | healthgroup.es ya pagado. |
| **Total fijo mensual estimado** | | **~90-130 €/mes** | |
| **Holgura para crecimiento / WhatsApp Business futuro / Power BI** | | **~170 €/mes** | |

**Conclusión**: 300 €/mes da holgura amplia. La operación arranca cómodamente en ~120 €/mes.

---

## 3. Hoja de ruta por fases

### Fase 0 — Resucitar y validar (1-2 semanas)

**Pre-requisito**: confirmar mañana credenciales Cloudways + tienda Apple/Google.

Tareas:
- Levantar backend Laravel en su estado actual (Cloudways si revive, o redeploy en Hetzner).
- Restaurar BBDD desde el backup más reciente disponible (Cloudways daily, off-server, o como último recurso `init.sql` de agosto 2024 + reseed).
- Smoke test end-to-end: la app móvil instalada en tu teléfono se loguea y carga el listado.
- **Documentar lo que funciona y lo que no** con un DUE de tu confianza.

**Entregable**: backend operativo, app móvil funcional, decisión binaria "retomamos" o "no".

### Fase 1 — Construcción de las piezas que faltan (3-4 meses)

En orden de prioridad:

1. **Modelo de turnos correcto** (2-3 sem): añadir a la tabla `offers` los campos start_datetime, end_datetime, real_end_datetime (cierre tras turno), extension_minutes, coordinator_name, coordinator_phone. Ajustar mobile y admin.
2. **Cierre de turno** (2 sem): pantalla en la app para que el DUE reporte horas reales y posibles incidencias. Estado nuevo `Reported pending review` antes de `Finalized`.
3. **Módulo PRL** (4-6 sem): catálogo de formaciones, asignación a trabajadores, vencimientos, alertas, bloqueo de asignación si caducado, repositorio de documentos firmados. (Detalle abajo).
4. **Extractor de email cliente con IA** (1-2 sem): Power Automate o script PHP en el cron del backend, conectado a una casilla compartida de Outlook. Email entra → GPT-4o extrae cliente, perfil, fechas, lugar, horario, observaciones → notificación al admin con botón "Aprobar y crear oferta".
5. **Portal cliente** (4-6 sem): nuevo rol "client" en el backend, pantallas web para que el cliente cree solicitudes, vea histórico, descargue facturas, etc. Construido como Inertia/Livewire dentro del mismo Laravel (más rápido) o como SPA Vue independiente (más limpio). **Recomiendo Inertia**: menos coste, mismo deploy.
6. **Sincronización Excel → Backend (transitoria)** (1-2 sem): cron diario que lee del ACTIVIDAD/OK actual y rellena/actualiza el backend. **Solo lectura del Excel** — el backend no escribe Excel. Sirve para que ningún dato se pierda mientras el equipo migra.

### Fase 2 — Calidad de vida y dashboards (1-2 meses)

7. **Power BI conectado al MySQL** (1-2 sem): dashboard de servicios pendientes, asignados, confirmados, finalizados; por mes/perfil/provincia/cliente. Alimentación directa, no Excel.
8. **Notificaciones más finas**: SMS Twilio de respaldo cuando push falla. Email recordatorios próximos a turnos.
9. **Mejoras UX a partir del feedback de los DUEs y clientes**.

### Fase 3 — Integraciones e ingresos (2-3 meses)

10. **Bridge backend → Odoo** (4-6 sem): cuando un turno pasa a `Finalized`, generar borrador de factura en Odoo 12 (vuestro actual) vía API. Migración a Odoo 19 cuando entre en vigor Verifactu.
11. **Borrador de bridge con asesoría laboral**: exportación de altas SS pendientes en formato compatible.
12. **WhatsApp Business API** (si los DUEs lo piden): Meta Cloud API para mensajes salientes (recordatorios, confirmaciones). El número 648527475 puede convivir con la API si Pablo migra a una solución de "WhatsApp en escritorio" tipo Whapi o equivalente (a evaluar con cuidado).

### Fase 4 — Largo plazo

13. **Matching mejorado**: si el RecommendationService heurístico no da, entonces se evalúa entrenar modelo o usar LLM para ranking. Solo si los datos lo justifican.
14. **CRM básico** integrado en el portal cliente.
15. **Portal de descarga de nóminas** para los DUEs (depende de cómo nos lo dé la asesoría).

---

## 4. Detalle del módulo PRL

Entidades nuevas en BBDD:

- `training_types` (catálogo: PRL básico, PRL específico de enfermería, RCP, manejo de equipos, etc.)
- `trainings` (instancias concretas: fecha, contenido, formador, evidencia)
- `user_trainings` (n:m: usuario × formación, con `completion_date`, `expiry_date`, `document_path`)
- `medical_checkups` (reconocimiento médico anual: fecha, apto/no apto, próximo)
- `risk_assessments` (evaluaciones de riesgo por puesto/cliente)
- `risk_acceptances` (firmas de aceptación del trabajador a una evaluación)
- `prl_alerts_log` (qué se ha avisado y cuándo)

Lógica:

- Cron diario que mira vencimientos. T-30 días → email + push al trabajador. T-15 → recordatorio. T-0 → bloqueo automático (no aparecen ofertas que requieran esa formación caducada).
- En el alta a una oferta, se valida que el trabajador tenga vigentes todas las formaciones que el `professional_position` y/o el `client` requieran.
- Admin puede emitir certificados (PDF generado por el backend con sello/firma).
- El trabajador puede subir desde la app: certificado externo, foto del documento.

Coste de desarrollo: 4-6 semanas si se hace con criterio.

---

## 5. Decisiones que necesito que tomes

1. **¿Mantenemos Cloudways o migramos a Hetzner?** Mi recomendación: **migrar a Hetzner** salvo que el coste real de Cloudways sea ya bajo (≤ 30 €/mes) y prefieras no mover nada. La migración te ahorraría costes a medio plazo y te da más control.

2. **¿Inertia/Livewire o SPA separada para el portal cliente?** Mi recomendación: **Inertia** (el panel admin ya lo usa, mismo deploy, mismos developers).

3. **¿Quién hace el desarrollo?** Tres escenarios:
   - **Yo (asistido por ti)**: tú pones contexto y validación, yo escribo el código. Tiempo total ≈ 5-7 meses, no hay gasto extra de desarrollo.
   - **Contratar dev Laravel a tiempo parcial**: 30-50 €/h, 20-40 h/mes = 600-2000 €/mes adicionales. Va más rápido pero excede el presupuesto inicial.
   - **Externalizar completo a una agencia de nuevo**: caro y arriesgado tras la experiencia con Acceleralia. **No lo recomiendo**.

4. **¿Vamos con la idea de "matching IA" o nos conformamos con la heurística mejorada?** Mi recomendación: **heurística mejorada + IA en el extractor de email** durante 6-12 meses. Si en ese tiempo se ve que el matching falla mucho, se reevalúa.

5. **¿Migramos el equipo a usar el panel admin o seguimos con Excel mientras el backend lee?** Mi recomendación: **dejar a Excel funcionando 3-6 meses en lectura mientras el equipo aprende el panel admin**, luego apagar Excel.

6. **¿Quieres que el portal del cliente sustituya al email completamente o conviva?** Mi recomendación: **convivencia indefinida**. El email cliente nunca debería desaparecer (legal, reputacional), pero el portal será el canal preferido y mejor experiencia. Y el extractor de IA cubre el email para que no genere trabajo extra.

---

## 6. Lo que NO recomiendo y por qué

- **Reescribir desde cero en otro stack** (Node, Python, lo que sea): tirarías meses de trabajo entregado. El Laravel está sano.
- **Plataforma low-code completa (Bubble, Power Apps, Glide)**: no escala bien para esta complejidad y os encierra en un proveedor.
- **WhatsApp Business API el día 1**: alta fricción operativa para una ganancia marginal frente a push notifications. Llegará en su momento.
- **Matching con IA real ya**: alto coste, datos no listos, beneficio incierto. La heurística + filtros cubre el 80 %. La IA del 20 % restante puede esperar.
- **Migrar a Odoo 19 antes de Verifactu**: vuestro Odoo 12 funciona, Verifactu aún no obliga. Migrar añade riesgo sin beneficio inmediato.
- **Despublicar las apps de las tiendas**: aunque estén sin backend, mantenerlas listadas (con versión nueva apuntando a backend nuevo cuando esté) es más barato que reaprobar todo otra vez.

---

## 7. Próximos pasos concretos para mañana

1. Confirmar credenciales Cloudways y estado del servidor (runbook ya redactado).
2. Confirmar credenciales Apple Developer Program y Google Play Console.
3. Hablar con NetSolutions si el dominio o el DNS necesita ajustes.
4. Decidir las 6 cosas del apartado 5.
5. Si todo OK, empezamos por la Fase 0 mismo lunes.
