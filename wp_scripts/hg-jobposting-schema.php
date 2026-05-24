<?php
/**
 * Plugin Name: HG - JobPosting Schema (Google for Jobs)
 * Description: Inyecta marcado JSON-LD JobPosting en cada oferta de empleo singular
 *              (CPT oferta-de-empleo) para que aparezca en Google for Jobs y mejorar SEO.
 *              Lee los wpcf-* de Toolset Types y los traduce a Schema.org/JobPosting.
 * Version: 1.0
 * Author: Health Group
 *
 * Destino: wp-content/mu-plugins/hg-jobposting-schema.php
 *
 * Por seguridad: solo lee meta del post actual y emite JSON-LD en wp_head.
 * No escribe en BBDD. Reversible borrando este archivo.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Parsea texto libre del contrato HG (ej. "Fijo discontinuo · Jornada parcial (mañana)")
 * a un array de valores Schema.org/JobPosting#employmentType.
 *
 * Valores válidos Schema.org: FULL_TIME, PART_TIME, CONTRACTOR, TEMPORARY,
 * INTERN, VOLUNTEER, PER_DIEM, OTHER.
 *
 * Casos especiales HG:
 *  - "Fijo discontinuo" (RDL 32/2021, código INSS 300) → OTHER + se anota en description.
 *  - "Indefinido" sin más contexto → FULL_TIME (asunción conservadora).
 *  - Si nada hace match → OTHER.
 */
function hg_parse_employment_type( $contrato_text ) {
    $types = [];
    $t = mb_strtolower( (string) $contrato_text, 'UTF-8' );

    if ( $t === '' ) {
        return [ 'OTHER' ];
    }

    if ( strpos( $t, 'jornada completa' ) !== false || strpos( $t, 'tiempo completo' ) !== false ) {
        $types[] = 'FULL_TIME';
    }
    if ( strpos( $t, 'jornada parcial' ) !== false || strpos( $t, 'tiempo parcial' ) !== false || strpos( $t, 'media jornada' ) !== false ) {
        $types[] = 'PART_TIME';
    }
    if ( strpos( $t, 'temporal' ) !== false ) {
        $types[] = 'TEMPORARY';
    }
    if ( strpos( $t, 'becario' ) !== false || strpos( $t, 'prácticas' ) !== false || strpos( $t, 'practicas' ) !== false ) {
        $types[] = 'INTERN';
    }
    if ( strpos( $t, 'autónomo' ) !== false || strpos( $t, 'autonomo' ) !== false || strpos( $t, 'mercantil' ) !== false ) {
        $types[] = 'CONTRACTOR';
    }
    if ( strpos( $t, 'fijo discontinuo' ) !== false ) {
        $types[] = 'OTHER';
    }
    if ( strpos( $t, 'indefinido' ) !== false
         && ! in_array( 'FULL_TIME', $types, true )
         && ! in_array( 'PART_TIME', $types, true ) ) {
        $types[] = 'FULL_TIME';
    }

    if ( empty( $types ) ) {
        $types[] = 'OTHER';
    }

    return array_values( array_unique( $types ) );
}

/**
 * Construye el objeto JobPosting para una oferta dada.
 * Devuelve array listo para json_encode o null si la oferta es inválida.
 */
function hg_build_jobposting_schema( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post || $post->post_status !== 'publish' || $post->post_type !== 'oferta-de-empleo' ) {
        return null;
    }

    $descripcion = get_post_meta( $post_id, 'wpcf-descripcion', true );
    $fecha       = get_post_meta( $post_id, 'wpcf-fecha',       true );
    $contrato    = get_post_meta( $post_id, 'wpcf-contrato',    true );
    $empleo      = get_post_meta( $post_id, 'wpcf-empleo',      true );
    $provincia   = get_post_meta( $post_id, 'wpcf-provincia',   true );
    $vacantes    = get_post_meta( $post_id, 'wpcf-vacantes',    true );
    $duracion    = get_post_meta( $post_id, 'wpcf-duracion',    true );

    // datePosted (ISO 8601 UTC)
    $ts = $fecha ? (int) $fecha : strtotime( $post->post_date_gmt );
    if ( ! $ts ) {
        $ts = time();
    }
    $date_posted   = gmdate( 'c', $ts );
    $valid_through = gmdate( 'c', $ts + ( 90 * DAY_IN_SECONDS ) );

    $employment_types = hg_parse_employment_type( $contrato );

    // Description: texto plano (Google acepta y prefiere); anexamos detalles
    $description = wp_strip_all_tags( $descripcion ?: $post->post_content );
    $extras = [];
    if ( $contrato ) {
        $extras[] = 'Contrato: ' . wp_strip_all_tags( $contrato );
    }
    if ( $duracion ) {
        $extras[] = 'Duración: ' . wp_strip_all_tags( $duracion );
    }
    if ( $vacantes ) {
        $extras[] = 'Vacantes: ' . (int) $vacantes;
    }
    if ( stripos( $contrato, 'fijo discontinuo' ) !== false ) {
        $extras[] = 'Contrato fijo discontinuo (RDL 32/2021, código INSS 300).';
    }
    if ( ! empty( $extras ) ) {
        $description .= "\n\n" . implode( "\n", $extras );
    }

    $job_location = [
        '@type'   => 'Place',
        'address' => [
            '@type'          => 'PostalAddress',
            'addressCountry' => 'ES',
        ],
    ];
    if ( $provincia ) {
        $job_location['address']['addressRegion']   = $provincia;
        $job_location['address']['addressLocality'] = $provincia;
    }

    $hiring_org = [
        '@type'  => 'Organization',
        'name'   => 'MEDICAL SERVICE M. CASTILLA S.L.',
        'sameAs' => 'https://healthgroup.es',
        'logo'   => 'https://healthgroup.es/wp-content/uploads/2018/05/logo_health.png',
    ];

    $job_posting = [
        '@context'         => 'https://schema.org',
        '@type'            => 'JobPosting',
        'title'            => $empleo ?: $post->post_title,
        'description'      => $description,
        'datePosted'       => $date_posted,
        'validThrough'     => $valid_through,
        'employmentType'   => count( $employment_types ) === 1 ? $employment_types[0] : $employment_types,
        'hiringOrganization' => $hiring_org,
        'jobLocation'      => $job_location,
        'industry'         => 'Healthcare',
        'directApply'      => true,
    ];

    return $job_posting;
}

add_action( 'wp_head', function () {
    if ( ! is_singular( 'oferta-de-empleo' ) ) {
        return;
    }
    $post_id = get_the_ID();
    if ( ! $post_id ) {
        return;
    }
    $schema = hg_build_jobposting_schema( $post_id );
    if ( ! $schema ) {
        return;
    }
    echo "\n<script type=\"application/ld+json\" id=\"hg-jobposting-schema\">\n";
    echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
    echo "\n</script>\n";
}, 999 );
