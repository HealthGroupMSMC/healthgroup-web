"""Captura el slider de la home cada 2s durante 30s y reporta cuantos slides hay.

Uso:
    python snapshot_slider.py                       # home, 30s, intervalo 2s
    python snapshot_slider.py --url URL             # otra URL
    python snapshot_slider.py --duration 60         # 60s
    python snapshot_slider.py --interval 1.5        # cada 1.5s

Salida:
    snapshots/visual/slider_{TS}/frame_NN.png
    snapshots/visual/slider_{TS}/info.json
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


def detect_slider(page, selector_candidates: list[str]) -> tuple[str | None, dict]:
    """Devuelve (selector_encontrado, info_dom) probando cada candidato."""
    js = """(selectors) => {
        const out = {};
        for (const sel of selectors) {
            const el = document.querySelector(sel);
            if (!el) continue;
            const slides = el.querySelectorAll('.slide, .ux-slide, li, [data-slide]');
            const dots   = el.querySelectorAll('.flickity-page-dots .dot, .slider-nav-dot, [class*="dot"]');
            out[sel] = {
                found: true,
                children_direct: el.children.length,
                slides_count: slides.length,
                dots_count: dots.length,
                tagName: el.tagName,
                classes: el.className,
            };
        }
        return out;
    }"""
    info = page.evaluate(js, selector_candidates)
    if not info:
        return None, {}
    selector = next(iter(info.keys()))
    return selector, info


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--url", default=None)
    parser.add_argument("--duration", type=float, default=30.0)
    parser.add_argument("--interval", type=float, default=2.0)
    args = parser.parse_args()

    cfg = load_config()
    base_url = cfg["base_url"]
    user_agent = cfg["user_agent"]
    desktop_vp = cfg["viewports"]["desktop"]
    selector_candidates = cfg["slider_selector_candidates"]
    url = args.url or base_url + "/"

    ts = datetime.now().strftime("%Y%m%d_%H%M%S")
    out_dir = SNAPSHOTS_DIR / "visual" / f"slider_{ts}"
    out_dir.mkdir(parents=True, exist_ok=True)
    print(f"Slider snapshot: {url}")
    print(f"Output: {out_dir.relative_to(ROOT)}")

    info_run = {"ts": ts, "url": url, "frames": [],
                "duration_s": args.duration, "interval_s": args.interval}

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(user_agent=user_agent, viewport=desktop_vp,
                                      ignore_https_errors=True)
        page = context.new_page()
        try:
            page.goto(url, wait_until="load", timeout=45_000)
        except Exception as e:
            print(f"  WARN load timeout: {e}; fallback a domcontentloaded")
            page.goto(url, wait_until="domcontentloaded", timeout=30_000)
        time.sleep(5)  # asienta JS y permite primera rotación de slider

        sel, dom_info = detect_slider(page, selector_candidates)
        info_run["slider_selector"] = sel
        info_run["slider_dom_info"] = dom_info
        print(f"Slider detectado: {sel}")
        if sel:
            print(f"  DOM info: {json.dumps(dom_info[sel], ensure_ascii=False)}")

        frames_n = int(args.duration / args.interval)
        for i in range(frames_n):
            elapsed = i * args.interval
            frame_path = out_dir / f"frame_{i:02d}_t{int(elapsed*10):04d}.png"
            if sel:
                try:
                    el = page.query_selector(sel)
                    if el:
                        el.screenshot(path=str(frame_path))
                    else:
                        page.screenshot(path=str(frame_path), full_page=False)
                except Exception:
                    page.screenshot(path=str(frame_path), full_page=False)
            else:
                page.screenshot(path=str(frame_path), full_page=False)

            # Snapshot del dot activo (si lo hay)
            try:
                active_dot = page.evaluate("""() => {
                    const dots = document.querySelectorAll(
                      '.flickity-page-dots .dot, .slider-nav-dot, [class*="dot"][class*="active"], [aria-selected="true"]'
                    );
                    let active_idx = -1;
                    dots.forEach((d, idx) => {
                        if (d.classList.contains('is-selected') ||
                            d.classList.contains('active') ||
                            d.getAttribute('aria-selected') === 'true') {
                            active_idx = idx;
                        }
                    });
                    return {total_dots: dots.length, active_idx};
                }""")
            except Exception as e:
                active_dot = {"error": str(e)}

            info_run["frames"].append({
                "idx": i, "elapsed_s": elapsed,
                "png": frame_path.name,
                "dots": active_dot,
            })
            print(f"  frame {i:02d}  t={elapsed:5.1f}s  dots={active_dot}")
            if i < frames_n - 1:
                time.sleep(args.interval)

        # Recuento final
        distinct_active = sorted({f["dots"].get("active_idx", -1) for f in info_run["frames"]
                                  if isinstance(f["dots"], dict) and "active_idx" in f["dots"]})
        info_run["slides_observados"] = distinct_active
        info_run["slides_count_observado"] = len([x for x in distinct_active if x >= 0])

        browser.close()

    info_path = out_dir / "info.json"
    info_path.write_text(json.dumps(info_run, indent=2, ensure_ascii=False), encoding="utf-8")
    print()
    print(f"Slides distintos observados (indices): {info_run['slides_observados']}")
    print(f"Conteo observado: {info_run['slides_count_observado']}")
    print(f"Info: {info_path.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
