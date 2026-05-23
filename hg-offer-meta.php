<?php
/**
 * Plugin Name: HG - Expose Toolset offer fields to REST
 * Description: Endpoint REST custom para leer/escribir los wpcf-* del CPT oferta-de-empleo, salteando la captura interna de Toolset Types.
 * Version: 2.0
 * Author: Health Group
 *
 * Destino en el servidor: wp-content/mu-plugins/hg-offer-meta.php
 *
 * NOTA: Esta version SUSTITUYE a la 1.0. La v1.0 usaba register_post_meta,
 * pero Toolset Types 3.6.0 secuestra los meta keys 'wpcf-*' y los excluye
 * del schema REST. Esta version crea un endpoint custom propio que escribe
 * los meta directamente con update_post_meta, evitando ese filtro.
 *
 * Endpoints expuestos:
 *   GET  /wp-json/hg/v1/offer/{id}/meta   -> lee los 7 wpcf-* del post {id}
 *   POST /wp-json/hg/v1/offer/{id}/meta   -> escribe los wpcf-* enviados en el body JSON
 *
 * Seguridad: solo usuarios con capacidad 'edit_posts' pueden invocar los
 * endpoints. La escritura solo acepta meta keys con prefijo 'wpcf-' y limitados
 * a la lista blanca de 7 campos. Totalmente reversible: borrar el archivo
 * desactiva los endpoints; los datos en BBDD no se tocan.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {

    $allowed_fields = array(
        'descripcion',
        'fecha',
        'contrato',
        'empleo',
        'provincia',
        'vacantes',
        'duracion',
    );

    register_rest_route(
        'hg/v1',
        '/offer/(?P<id>\d+)/meta',
        array(
            array(
                'methods'             => 'GET',
                'permission_callback' => function () {
                    return current_user_can( 'edit_posts' );
                },
                'callback'            => function ( $request ) use ( $allowed_fields ) {
                    $post_id = (int) $request['id'];
                    $post    = get_post( $post_id );
                    if ( ! $post || $post->post_type !== 'oferta-de-empleo' ) {
                        return new WP_Error( 'hg_offer_not_found', 'Oferta no encontrada.', array( 'status' => 404 ) );
                    }
                    $out = array();
                    foreach ( $allowed_fields as $f ) {
                        $key         = 'wpcf-' . $f;
                        $out[ $key ] = get_post_meta( $post_id, $key, true );
                    }
                    return rest_ensure_response( $out );
                },
            ),
            array(
                'methods'             => 'POST',
                'permission_callback' => function () {
                    return current_user_can( 'edit_posts' );
                },
                'callback'            => function ( $request ) use ( $allowed_fields ) {
                    $post_id = (int) $request['id'];
                    $post    = get_post( $post_id );
                    if ( ! $post || $post->post_type !== 'oferta-de-empleo' ) {
                        return new WP_Error( 'hg_offer_not_found', 'Oferta no encontrada.', array( 'status' => 404 ) );
                    }
                    $body    = $request->get_json_params();
                    if ( ! is_array( $body ) ) {
                        return new WP_Error( 'hg_invalid_body', 'Body JSON requerido.', array( 'status' => 400 ) );
                    }
                    $updated = array();
                    foreach ( $body as $key => $value ) {
                        // Aceptar tanto 'wpcf-xxx' como 'xxx'.
                        $bare = ( strpos( $key, 'wpcf-' ) === 0 ) ? substr( $key, 5 ) : $key;
                        if ( ! in_array( $bare, $allowed_fields, true ) ) {
                            continue;
                        }
                        $meta_key = 'wpcf-' . $bare;
                        update_post_meta( $post_id, $meta_key, wp_kses_post( (string) $value ) );
                        $updated[ $meta_key ] = get_post_meta( $post_id, $meta_key, true );
                    }
                    return rest_ensure_response(
                        array(
                            'updated' => $updated,
                            'count'   => count( $updated ),
                        )
                    );
                },
            ),
        )
    );
} );
