"""Snapshot visual + DOM + consola JS de las URLs criticas de healthgroup.es.

Uso:
    python snapshot_paginas.py              # todas las URLs, desktop + mobile
    python snapshot_paginas.py home         # solo el slug "home"
    python snapshot_paginas.py --mobile     # solo viewport mobile
    python snapshot_paginas.py --desktop    # solo viewport desktop

Salida:
    snapshots/visual/{TS}/{slug}_{viewport}.png
    snapshots/dom/{TS}/{slug}.html
    snapshots/console/{TS}/{slug}_{viewport}.log
    snapshots/INDEX.json   actualizado con el ultimo TS por carpeta
"""
from __future__ import annotations

import argparse
import json
import sys
import time
from datetime import datetime
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parent.parent
CONFIG_PATH = Path(__file__).resolve().parent / "urls_criticas.json"
SNAPSHOTS_DIR = ROOT / "snapshots"


def load_config() -> dict:
    with CONFIG_PATH.open(encoding="utf-8") as f:
        return json.load(f)


def timestamp() -> str:
    return datetime.now().strftime("%Y%m%d_%H%M")


def snapshot_url(context, page_cfg: dict, viewport_name: str, viewport: dict,
                 base_url: str, wait_seconds: int,
                 visual_dir: Path, dom_dir: Path, console_dir: Path) -> dict:
    slug = page_cfg["slug"]
    url = base_url.rstrip("/") + page_cfg["path"]

    page = context.new_page()
    page.set_viewport_size(viewport)

    console_log: list[str] = []
    page.on("console", lambda msg: console_log.append(f"[{msg.type}] {msg.text}"))
    page.on("pageerror", lambda exc: console_log.append(f"[pageerror] {exc}"))

    print(f"  -> {viewport_name:8s} {url}")
    try:
        page.goto(url, wait_until="load", timeout=45_000)
    except Exception as e:
        console_log.append(f"[load-timeout] {e}")
        try:
            page.goto(url, wait_until="domcontentloaded", timeout=30_000)
        except Exception as e2:
            console_log.append(f"[goto-fallback-error] {e2}")

    time.sleep(wait_seconds)

    visual_path = visual_dir / f"{slug}_{viewport_name}.png"
    page.screenshot(path=str(visual_path), full_page=True)

    if viewport_name == "desktop":
        dom_path = dom_dir / f"{slug}.html"
        dom_path.write_text(page.content(), encoding="utf-8")

    log_path = console_dir / f"{slug}_{viewport_name}.log"
    log_path.write_text("\n".join(console_log), encoding="utf-8")

    page.close()
    return {"slug": slug, "viewport": viewport_name,
            "png": str(visual_path.relative_to(ROOT)),
            "errors": sum(1 for l in console_log if "[error]" in l or "[pageerror]" in l)}


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("slug", nargs="?", default=None)
    parser.add_argument("--mobile", action="store_true")
    parser.add_argument("--desktop", action="store_true")
    args = parser.parse_args()

    cfg = load_config()
    base_url = cfg["base_url"]
    wait_seconds = cfg["wait_after_load_seconds"]
    user_agent = cfg["user_agent"]

    urls = cfg["urls"]
    if args.slug:
        urls = [u for u in urls if u["slug"] == args.slug]
        if not urls:
            print(f"slug no encontrado: {args.slug}", file=sys.stderr)
            sys.exit(2)

    viewports = cfg["viewports"]
    if args.mobile and not args.desktop:
        viewports = {"mobile": viewports["mobile"]}
    elif args.desktop and not args.mobile:
        viewports = {"desktop": viewports["desktop"]}

    ts = timestamp()
    visual_dir = SNAPSHOTS_DIR / "visual" / ts
    dom_dir = SNAPSHOTS_DIR / "dom" / ts
    console_dir = SNAPSHOTS_DIR / "console" / ts
    for d in (visual_dir, dom_dir, console_dir):
        d.mkdir(parents=True, exist_ok=True)

    print(f"Snapshot timestamp: {ts}")
    print(f"  visual : {visual_dir.relative_to(ROOT)}")
    print(f"  dom    : {dom_dir.relative_to(ROOT)}")
    print(f"  console: {console_dir.relative_to(ROOT)}")
    print()

    results = []
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        for vp_name, vp in viewports.items():
            context = browser.new_context(user_agent=user_agent, viewport=vp,
                                          ignore_https_errors=True)
            for page_cfg in urls:
                r = snapshot_url(context, page_cfg, vp_name, vp, base_url,
                                 wait_seconds, visual_dir, dom_dir, console_dir)
                results.append(r)
            context.close()
        browser.close()

    index_path = SNAPSHOTS_DIR / "INDEX.json"
    index = json.loads(index_path.read_text(encoding="utf-8")) if index_path.exists() else {}
    index["visual_last"] = ts
    index["dom_last"] = ts
    index["console_last"] = ts
    index["paginas_last_run"] = {
        "ts": ts,
        "count": len(results),
        "errors_total": sum(r["errors"] for r in results),
        "results": results,
    }
    index_path.write_text(json.dumps(index, indent=2, ensure_ascii=False), encoding="utf-8")

    print()
    print(f"Hecho. {len(results)} capturas. Errores JS: {index['paginas_last_run']['errors_total']}.")


if __name__ == "__main__":
    main()
