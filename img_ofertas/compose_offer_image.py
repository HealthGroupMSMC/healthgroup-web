"""
compose_offer_image.py

Genera la imagen destacada de una oferta de empleo para healthgroup.es.

Layout: foto del perfil 800x630 (izquierda) + panel 400x630 con titulo CCAA,
mapa de la comunidad y nombre de la localidad (derecha). Total 1200x630.

Uso desde Python:
    from compose_offer_image import compose_offer_image
    out = compose_offer_image(
        perfil="due",
        ccaa="Castilla-Leon",
        ccaa_display="Castilla y Leon",
        localidad="Miranda de Ebro",
        lat=42.6868,
        lon=-2.9499,
        output_path="oferta_miranda_v1.png",
    )
"""

import os
import io
import json
import matplotlib.pyplot as plt
from PIL import Image, ImageDraw, ImageFont

# Paleta HG
VERDE_HG = "#5EBA9E"
AZUL_HG = "#1B3A5C"
GRIS_FONDO = "#F5F5F2"
ROJO_PUNTO = "#E94B3C"
BLANCO = "#FFFFFF"

# Dimensiones
W, H = 1200, 630
LEFT_W = 800
RIGHT_W = W - LEFT_W

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
GEOJSON_PATH = os.path.join(BASE_DIR, "spain_ccaa.geojson")

# Mapeo perfil -> archivo de foto
PERFIL_TO_FILE = {
    "due": "due.png",
    "enfermeria": "due.png",
    "medicina": "medico.png",
    "medico": "medico.png",
    "tes": "tes.png",
    "tcae": "tcae.png",
    "gerocultor": "gero.png",
    "gero": "gero.png",
    "fisioterapeuta": "fisio.png",
    "fisio": "fisio.png",
}

# Mapeo nombre GeoJSON -> nombre legible en espanol con acentos correctos.
# Si no se pasa ccaa_display explicito, compose_offer_image() lo busca aqui.
CCAA_DISPLAY = {
    "Castilla-Leon": "Castilla y Leon",
    "Cataluña": "Cataluña",
    "Ceuta": "Ceuta",
    "Murcia": "Region de Murcia",
    "La Rioja": "La Rioja",
    "Baleares": "Islas Baleares",
    "Canarias": "Canarias",
    "Cantabria": "Cantabria",
    "Andalucia": "Andalucia",
    "Asturias": "Principado de Asturias",
    "Valencia": "Comunidad Valenciana",
    "Melilla": "Melilla",
    "Navarra": "Navarra",
    "Galicia": "Galicia",
    "Aragon": "Aragon",
    "Madrid": "Comunidad de Madrid",
    "Extremadura": "Extremadura",
    "Castilla-La Mancha": "Castilla-La Mancha",
    "Pais Vasco": "Pais Vasco",
}

# Versiones con acentos (uso aparte porque Python source compatibility).
# Estos overrides sustituyen los anteriores con grafia correcta.
CCAA_DISPLAY["Castilla-Leon"] = "Castilla y León"
CCAA_DISPLAY["Murcia"] = "Región de Murcia"
CCAA_DISPLAY["Andalucia"] = "Andalucía"
CCAA_DISPLAY["Aragon"] = "Aragón"
CCAA_DISPLAY["Pais Vasco"] = "País Vasco"


def _load_font(size, bold=False):
    """Carga una fuente sans-serif del sistema."""
    candidates = []
    if bold:
        candidates = [
            "C:/Windows/Fonts/seguibl.ttf",   # Segoe UI Black
            "C:/Windows/Fonts/segoeuib.ttf",  # Segoe UI Bold
            "C:/Windows/Fonts/arialbd.ttf",
        ]
    else:
        candidates = [
            "C:/Windows/Fonts/segoeui.ttf",
            "C:/Windows/Fonts/arial.ttf",
        ]
    for path in candidates:
        if os.path.exists(path):
            return ImageFont.truetype(path, size)
    return ImageFont.load_default()


def _render_map(ccaa_name, lat, lon, size_px=(400, 320)):
    """Genera el mapa de la CCAA con la localidad marcada. Devuelve PIL.Image."""
    with open(GEOJSON_PATH, "r", encoding="utf-8") as f:
        geo = json.load(f)

    target = None
    for feat in geo["features"]:
        if feat["properties"]["name"] == ccaa_name:
            target = feat
            break
    if not target:
        raise ValueError(f"CCAA '{ccaa_name}' no encontrada en GeoJSON")

    dpi = 150
    fig_w = size_px[0] / dpi
    fig_h = size_px[1] / dpi
    fig, ax = plt.subplots(figsize=(fig_w, fig_h), dpi=dpi)
    fig.patch.set_alpha(0)
    ax.set_facecolor("none")

    geom = target["geometry"]
    polygons = [geom["coordinates"]] if geom["type"] == "Polygon" else geom["coordinates"]

    for poly in polygons:
        for ring in poly:
            xs = [pt[0] for pt in ring]
            ys = [pt[1] for pt in ring]
            ax.fill(xs, ys, color=VERDE_HG, alpha=0.65,
                    edgecolor=AZUL_HG, linewidth=1.8)

    # Punto de la localidad
    ax.plot(lon, lat, marker="o", markersize=11, color=ROJO_PUNTO,
            markeredgecolor="white", markeredgewidth=2, zorder=5)

    ax.set_aspect("equal")
    ax.axis("off")
    plt.tight_layout(pad=0.2)

    buf = io.BytesIO()
    plt.savefig(buf, format="png", dpi=dpi, transparent=True,
                bbox_inches="tight", pad_inches=0.05)
    plt.close(fig)
    buf.seek(0)
    return Image.open(buf).convert("RGBA")


def _crop_cover(img, target_w, target_h):
    """Recorta y escala una imagen al tamaño target manteniendo proporciones (cover)."""
    src_w, src_h = img.size
    target_ratio = target_w / target_h
    src_ratio = src_w / src_h
    if src_ratio > target_ratio:
        # foto más ancha de lo necesario -> recortar laterales
        new_w = int(src_h * target_ratio)
        left = (src_w - new_w) // 2
        img = img.crop((left, 0, left + new_w, src_h))
    else:
        new_h = int(src_w / target_ratio)
        top = (src_h - new_h) // 2
        img = img.crop((0, top, src_w, top + new_h))
    return img.resize((target_w, target_h), Image.LANCZOS)


def compose_offer_image(perfil, ccaa, localidad, lat, lon, output_path, ccaa_display=None):
    """Compone la imagen destacada y la guarda en output_path.

    Si ccaa_display es None, se busca en CCAA_DISPLAY (acentos correctos).
    """
    if ccaa_display is None:
        ccaa_display = CCAA_DISPLAY.get(ccaa, ccaa)
    perfil_key = perfil.lower().strip()
    if perfil_key not in PERFIL_TO_FILE:
        raise ValueError(f"Perfil '{perfil}' no mapeado. Valores: {list(PERFIL_TO_FILE)}")
    photo_path = os.path.join(BASE_DIR, PERFIL_TO_FILE[perfil_key])
    if not os.path.exists(photo_path):
        raise FileNotFoundError(f"Foto no encontrada: {photo_path}")

    # 1) Canvas final
    canvas = Image.new("RGB", (W, H), GRIS_FONDO)

    # 2) Foto perfil (izquierda 800x630)
    photo = Image.open(photo_path).convert("RGB")
    photo = _crop_cover(photo, LEFT_W, H)
    canvas.paste(photo, (0, 0))

    # 3) Panel derecho (400x630, gris claro)
    panel = Image.new("RGB", (RIGHT_W, H), GRIS_FONDO)
    canvas.paste(panel, (LEFT_W, 0))

    # 4) Mapa centrado en el panel derecho
    map_w, map_h = 360, 280
    map_img = _render_map(ccaa, lat, lon, size_px=(map_w, map_h))
    # Centrar en el panel
    map_x = LEFT_W + (RIGHT_W - map_img.width) // 2
    map_y = (H - map_img.height) // 2 - 10
    canvas.paste(map_img, (map_x, map_y), map_img)

    # 5) Textos: título CCAA arriba, localidad abajo
    draw = ImageDraw.Draw(canvas)
    font_title = _load_font(28, bold=True)
    font_subtitle = _load_font(20, bold=True)
    font_small = _load_font(14, bold=False)

    # Título CCAA (encima del mapa)
    title_text = ccaa_display.upper()
    tw = draw.textlength(title_text, font=font_title)
    title_x = LEFT_W + (RIGHT_W - tw) // 2
    title_y = 55
    draw.text((title_x, title_y), title_text, fill=AZUL_HG, font=font_title)

    # Pequeño "Health Group | Oferta de empleo" debajo del título
    sub_text = "OFERTA DE EMPLEO"
    sw = draw.textlength(sub_text, font=font_small)
    draw.text((LEFT_W + (RIGHT_W - sw) // 2, title_y + 38), sub_text,
              fill=VERDE_HG, font=font_small)

    # Localidad debajo del mapa
    loc_text = localidad
    lw = draw.textlength(loc_text, font=font_subtitle)
    loc_y = H - 90
    draw.text((LEFT_W + (RIGHT_W - lw) // 2, loc_y), loc_text,
              fill=AZUL_HG, font=font_subtitle)

    # Punto rojo decorativo antes del nombre de la localidad (en línea separada)
    # ya está dentro del mapa, suficiente

    # 6) Guardar
    canvas.save(output_path, "PNG", optimize=True)
    return output_path


if __name__ == "__main__":
    # Aplicar a la oferta de Miranda de Ebro
    out = compose_offer_image(
        perfil="due",
        ccaa="Castilla-Leon",
        localidad="Miranda de Ebro",
        lat=42.6868,
        lon=-2.9499,
        output_path=os.path.join(BASE_DIR, "oferta_miranda_v2.png"),
    )
    size = os.path.getsize(out)
    print(f"OK generada: {out} ({size} bytes)")
