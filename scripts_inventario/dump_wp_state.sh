#!/usr/bin/env bash
# Vuelca el estado de WordPress (healthgroup.es) vía SSH alias `hg` + WP-CLI.
# Ejecutar desde Git Bash o WSL en C:\Users\Pablo Heredia\Documents\HG_REPOS\diseno\
#
# Salida: snapshots/wp_state/{TS}/<area>.json
# Actualiza snapshots/INDEX.json con el último TS.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
TS="$(date +%Y%m%d_%H%M)"
OUT="${ROOT}/snapshots/wp_state/${TS}"
mkdir -p "${OUT}"

SSH="ssh hg"
WP="cd ~/web && wp"

echo "Dump WP state @ ${TS}"
echo "  out: ${OUT#${ROOT}/}"

run() {
  local name="$1"; shift
  echo "  -> ${name}.json"
  ${SSH} "${WP} $*" > "${OUT}/${name}.json" 2> "${OUT}/${name}.stderr" || {
    echo "     WARN: fallo en ${name}, ver ${name}.stderr"
  }
  # Limpia stderr vacíos
  [ -s "${OUT}/${name}.stderr" ] || rm -f "${OUT}/${name}.stderr"
}

# 1. Plugins / temas / mu-plugins
run plugins "plugin list --format=json --fields=name,status,version,update,title,author"
run themes  "theme list --format=json --fields=name,status,version,update,title"

echo "  -> muplugins.json"
${SSH} "ls -la ~/web/wp-content/mu-plugins/ 2>/dev/null && echo '---SHA256---' && cd ~/web/wp-content/mu-plugins/ && sha256sum *.php 2>/dev/null" \
  > "${OUT}/muplugins.txt"

# 2. Opciones core + por plugin (whitelist de prefijos)
echo "  -> options_core.json"
${SSH} "${WP} eval '
\$keys = [
  \"siteurl\", \"home\", \"blogname\", \"blogdescription\",
  \"template\", \"stylesheet\", \"current_theme\",
  \"active_plugins\", \"permalink_structure\",
  \"timezone_string\", \"date_format\", \"time_format\",
  \"WPLANG\", \"users_can_register\", \"default_role\",
  \"posts_per_page\", \"show_on_front\", \"page_on_front\", \"page_for_posts\"
];
\$out = [];
foreach (\$keys as \$k) { \$out[\$k] = get_option(\$k); }
echo json_encode(\$out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
'" > "${OUT}/options_core.json"

# Opciones por prefijo (cada plugin relevante)
for prefix in wpforms wpcf7 mautic revslider wpseo flatsome toolset types_ icl_ wpml_ ux_ kirki_ yith_; do
  fname="options_${prefix}.json"
  echo "  -> ${fname}"
  ${SSH} "${WP} option list --search=\"${prefix}*\" --format=json --fields=option_name,autoload" \
    > "${OUT}/${fname}" 2>/dev/null || echo "[]" > "${OUT}/${fname}"
done

# 3. Theme mods (Customizer)
echo "  -> customizer.json"
${SSH} "${WP} eval '
\$theme = get_option(\"stylesheet\");
\$mods = get_option(\"theme_mods_\" . \$theme);
echo json_encode([\"theme\" => \$theme, \"mods\" => \$mods], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
'" > "${OUT}/customizer.json"

# 4. Widgets + menús
echo "  -> widgets.json"
${SSH} "${WP} eval '
global \$wp_registered_sidebars;
\$sidebars_widgets = get_option(\"sidebars_widgets\");
\$out = [];
foreach (\$wp_registered_sidebars as \$id => \$sb) {
    \$widgets = isset(\$sidebars_widgets[\$id]) ? \$sidebars_widgets[\$id] : [];
    \$widget_data = [];
    foreach (\$widgets as \$w_id) {
        \$base = preg_replace(\"/-\d+$/\", \"\", \$w_id);
        \$num  = (int) preg_replace(\"/^.*-(\d+)$/\", \"\$1\", \$w_id);
        \$instances = get_option(\"widget_\" . \$base);
        \$widget_data[] = [
            \"id\" => \$w_id, \"base\" => \$base,
            \"instance\" => isset(\$instances[\$num]) ? \$instances[\$num] : null
        ];
    }
    \$out[\$id] = [
        \"name\" => \$sb[\"name\"],
        \"description\" => \$sb[\"description\"],
        \"widgets\" => \$widget_data
    ];
}
echo json_encode(\$out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
'" > "${OUT}/widgets.json"

echo "  -> menus.json"
${SSH} "${WP} menu list --format=json" > "${OUT}/menus_list.json" 2>/dev/null || echo "[]" > "${OUT}/menus_list.json"
${SSH} "${WP} eval '
\$menus = wp_get_nav_menus();
\$out = [];
foreach (\$menus as \$m) {
    \$items = wp_get_nav_menu_items(\$m->term_id);
    \$out[] = [
        \"id\" => \$m->term_id, \"name\" => \$m->name, \"slug\" => \$m->slug,
        \"items\" => array_map(function(\$it) {
            return [
                \"ID\" => \$it->ID, \"title\" => \$it->title,
                \"url\" => \$it->url, \"parent\" => \$it->menu_item_parent,
                \"type\" => \$it->type, \"object\" => \$it->object,
                \"object_id\" => \$it->object_id
            ];
        }, \$items)
    ];
}
echo json_encode(\$out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
'" > "${OUT}/menus.json"

# 5. Toolset: CPTs, taxonomías, Views, Content Templates
echo "  -> toolset_cpts.json"
${SSH} "${WP} post-type list --format=json --fields=name,label,public,show_in_rest,hierarchical" \
  > "${OUT}/toolset_cpts.json"
echo "  -> toolset_taxonomies.json"
${SSH} "${WP} taxonomy list --format=json --fields=name,label,object_type,public,show_in_rest" \
  > "${OUT}/toolset_taxonomies.json"
echo "  -> toolset_views.json"
${SSH} "${WP} post list --post_type=view --post_status=publish,draft --format=json --fields=ID,post_title,post_status,post_name" \
  > "${OUT}/toolset_views.json" 2>/dev/null || echo "[]" > "${OUT}/toolset_views.json"
echo "  -> toolset_templates.json"
${SSH} "${WP} post list --post_type=view-template --post_status=publish,draft --format=json --fields=ID,post_title,post_status,post_name" \
  > "${OUT}/toolset_templates.json" 2>/dev/null || echo "[]" > "${OUT}/toolset_templates.json"
echo "  -> toolset_ct.json"
${SSH} "${WP} post list --post_type=ct --post_status=publish,draft --format=json --fields=ID,post_title,post_status,post_name" \
  > "${OUT}/toolset_ct.json" 2>/dev/null || echo "[]" > "${OUT}/toolset_ct.json"

# 6. Páginas críticas → mapa URL → post_id → template + shortcodes presentes
echo "  -> pages_map.json"
${SSH} "${WP} eval '
\$urls = [
    \"/\"                                                                                  => \"home\",
    \"/ofertas-empleo/\"                                                                   => \"ofertas-empleo\",
    \"/oferta-de-empleo/enfermero-a-aplicacion-de-contrastes-miranda-de-ebro/\"           => \"oferta-miranda\",
    \"/candidatos/\"                                                                       => \"candidatos\",
    \"/contacto/\"                                                                         => \"contacto\",
    \"/quienes-somos/\"                                                                    => \"quienes-somos\",
    \"/blog/\"                                                                             => \"blog\",
];
\$out = [];
foreach (\$urls as \$path => \$slug) {
    \$url = home_url(\$path);
    \$post_id = url_to_postid(\$url);
    if (!\$post_id) {
        \$out[] = [\"slug\" => \$slug, \"url\" => \$path, \"post_id\" => 0, \"note\" => \"url_to_postid devolvio 0\"];
        continue;
    }
    \$post = get_post(\$post_id);
    \$tmpl = get_post_meta(\$post_id, \"_wp_page_template\", true);
    preg_match_all(\"/\[([a-zA-Z0-9_\\-]+)/\", \$post->post_content, \$m);
    \$shortcodes = array_values(array_unique(\$m[1]));
    \$out[] = [
        \"slug\" => \$slug,
        \"url\" => \$path,
        \"post_id\" => \$post_id,
        \"post_type\" => \$post->post_type,
        \"post_status\" => \$post->post_status,
        \"template\" => \$tmpl ?: \"default\",
        \"content_length\" => strlen(\$post->post_content),
        \"shortcodes_present\" => \$shortcodes,
        \"featured_media\" => get_post_thumbnail_id(\$post_id) ?: null,
    ];
}
echo json_encode(\$out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
'" > "${OUT}/pages_map.json"

# 7. INDEX.json
echo "  -> INDEX.json (update)"
INDEX="${ROOT}/snapshots/INDEX.json"
python - "${INDEX}" "${TS}" <<'PY'
import json, sys, os
path, ts = sys.argv[1], sys.argv[2]
data = {}
if os.path.exists(path):
    try:
        data = json.load(open(path, encoding="utf-8"))
    except Exception:
        data = {}
data["wp_state_last"] = ts
open(path, "w", encoding="utf-8").write(json.dumps(data, indent=2, ensure_ascii=False))
PY

echo
echo "Hecho. Snapshot WP state: snapshots/wp_state/${TS}/"
ls -la "${OUT}" | head -40
