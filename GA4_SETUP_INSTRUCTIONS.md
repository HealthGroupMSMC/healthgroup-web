# Configurar Google Analytics 4 (GA4) en healthgroup.es

> **Contexto**: el código viejo de Universal Analytics (`UA-148056457-3`) que estaba en la cabecera del Customizer fue retirado por Google en julio 2023 y ya no medía nada. Eliminado el 2026-05-24 por estar muerto.
>
> Site Kit by Google tiene el módulo GA4 activo pero sin configurar. Faltan los pasos siguientes para que la web vuelva a medir visitas.
>
> **Lo que pago hacer yo (Claude) requeriría tu cuenta de Google. Estos pasos los tienes que hacer tú desde tu sesión iniciada en Google.**

## Paso 1 — Verificar/crear la propiedad GA4 en Google Analytics

1. Entra a `https://analytics.google.com` con tu cuenta de Google (la que ya tenía el Universal Analytics antiguo).
2. En la esquina inferior izquierda: ⚙️ **Administrar**.
3. Busca la cuenta "Health Group" (o similar) — debería existir, era la que tenía Universal Analytics `148056457`.
4. Comprueba si ya hay una **propiedad GA4** asociada (suelen tener nombre como "Health Group - GA4" o el dominio):
   - **Si EXISTE**: anota el **ID de medición** (formato `G-XXXXXXXXXX`, lo verás en Configuración > Flujos de datos > [tu web] > "ID de medición").
   - **Si NO existe**: créala. Botón **+ Crear propiedad**:
     - Nombre: `Health Group - GA4`
     - Zona horaria: `(GMT+01:00) Madrid`
     - Moneda: Euro (€)
     - Categoría: Salud
     - Tamaño de empresa: el que aplique
     - Configuración de empresa: marca las que apliquen
     - Plataforma: **Web**
     - URL: `https://healthgroup.es`
     - Nombre del flujo: `healthgroup.es`
   - Al finalizar te dará un **ID de medición** `G-XXXXXXXXXX`. **Anótalo**.

## Paso 2 — Conectar GA4 con Site Kit en WordPress

1. Entra a `https://healthgroup.es/wp-admin/` con tu usuario `direccion@healthgroup.es`.
2. En el menú lateral: **Site Kit > Configuración**.
3. Busca el módulo **Analytics**:
   - Si ves "Conectar Analytics" o "Configurar GA4": dale ahí.
   - Site Kit te llevará a un flujo de autorización con Google. Acepta los permisos para tu cuenta.
   - Te pedirá elegir cuenta + propiedad + flujo de datos. **Selecciona la propiedad GA4 del Paso 1** y el flujo `healthgroup.es`.
   - Finaliza.
4. Verifica que en **Site Kit > Panel** aparece la sección "Analytics" mostrando datos (al principio dirá "Sin datos suficientes" hasta que pasen unas horas).

## Paso 3 — Avisar a Claude para verificar

Tras completar los pasos anteriores, en la próxima sesión dime: **"GA4 ya configurado, measurementID es G-XXXXXXXXXX"**.

Yo entonces:
- Verificaré por SSH que `googlesitekit_analytics-4_settings.measurementID` ya tiene tu `G-...`.
- Confirmaré por inspección del HTML público que el snippet GA4 se está inyectando.
- Si todo OK, lo registraré en memoria del proyecto.

## Notas técnicas

- **No necesitas pegar manualmente código** en ninguna parte de la web. Site Kit con `useSnippet: true` (ya está así) inyecta el código automáticamente en cuanto tenga `measurementID`.
- **No reactivar el código viejo de Universal Analytics**. Está muerto desde julio 2023 y no recoge nada.
- Datos a medir relevantes para HG:
  - Visitas a `/ofertas-empleo/` y a `/oferta-de-empleo/{slug}/` (qué ofertas atraen tráfico).
  - Origen del tráfico (orgánico, redes, directo).
  - Páginas de salida (dónde abandonan los candidatos).
  - Conversiones del form de candidatura (cuando se arregle el debug WPForms 400).
- Estado al 2026-05-24:
  - `googlesitekit_active_modules`: `["analytics", "pagespeed-insights", "analytics-4"]` ✓ (los 3 activos)
  - `googlesitekit_analytics_settings.useSnippet`: `false` ✓ (UA no inyectado, correcto)
  - `googlesitekit_analytics-4_settings.measurementID`: `""` ✗ (pendiente de configurar — Paso 2)
  - `html_scripts_header` del Customizer: vacío ✓ (UA viejo eliminado)
