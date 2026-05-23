#!/usr/bin/env bash
# Lanza Lighthouse contra las 5 URLs core de healthgroup.es y guarda HTML+JSON.
# Salida: lighthouse_reports/{YYYYMMDD}/{slug}.report.html + .report.json
# Actualiza snapshots/INDEX.json con lighthouse_last.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
DAY="$(date +%Y%m%d)"
OUT="${ROOT}/lighthouse_reports/${DAY}"
mkdir -p "${OUT}"

declare -A URLS=(
  [home]="https://healthgroup.es/"
  [ofertas-empleo]="https://healthgroup.es/ofertas-empleo/"
  [oferta-miranda]="https://healthgroup.es/oferta-de-empleo/enfermero-a-aplicacion-de-contrastes-miranda-de-ebro/"
  [candidatos]="https://healthgroup.es/candidatos/"
  [contacto]="https://healthgroup.es/contacto/"
)

echo "Lighthouse run @ ${DAY}"
echo "  out: ${OUT#${ROOT}/}"

for slug in "${!URLS[@]}"; do
  url="${URLS[$slug]}"
  echo "  -> ${slug}  ${url}"
  npx --yes lighthouse "${url}" \
      --quiet \
      --chrome-flags="--headless=new --no-sandbox" \
      --output=html --output=json \
      --output-path="${OUT}/${slug}" \
      --only-categories=performance,accessibility,best-practices,seo \
      --form-factor=desktop \
      --screenEmulation.disabled \
      --throttling.cpuSlowdownMultiplier=1 \
      2> "${OUT}/${slug}.stderr" || echo "     WARN: fallo en ${slug}"
  [ -s "${OUT}/${slug}.stderr" ] || rm -f "${OUT}/${slug}.stderr"
done

# Resumen rápido de scores
echo
echo "Resumen scores:"
python - "${OUT}" <<'PY'
import json, sys, os, glob
out_dir = sys.argv[1]
files = sorted(glob.glob(os.path.join(out_dir, "*.report.json")))
print(f"  {'slug':<20} {'Perf':>6} {'A11y':>6} {'BP':>6} {'SEO':>6}  LCP")
for f in files:
    slug = os.path.basename(f).replace(".report.json", "")
    try:
        d = json.load(open(f, encoding="utf-8"))
        c = d.get("categories", {})
        def s(k): return round(100*c.get(k, {}).get("score", 0)) if c.get(k, {}).get("score") is not None else "n/a"
        lcp = d.get("audits", {}).get("largest-contentful-paint", {}).get("displayValue", "?")
        print(f"  {slug:<20} {s('performance'):>6} {s('accessibility'):>6} {s('best-practices'):>6} {s('seo'):>6}  {lcp}")
    except Exception as e:
        print(f"  {slug:<20} ERROR: {e}")
PY

# INDEX.json
INDEX="${ROOT}/snapshots/INDEX.json"
mkdir -p "$(dirname "${INDEX}")"
python - "${INDEX}" "${DAY}" <<'PY'
import json, sys, os
path, day = sys.argv[1], sys.argv[2]
data = {}
if os.path.exists(path):
    try: data = json.load(open(path, encoding="utf-8"))
    except Exception: data = {}
data["lighthouse_last"] = day
open(path, "w", encoding="utf-8").write(json.dumps(data, indent=2, ensure_ascii=False))
PY

echo
echo "Hecho. Reports en ${OUT}/"
