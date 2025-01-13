<?php
/**
 * Plugin Name: API de Cálculo do Milhão
 * Description: API REST para acessar e manipular a tabela calculo_do_milhao
 * Version: 1.0
 * Author: Lucas Eduardo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function calculo_do_milhao_register_api() {
    // Registro do endpoint GET
    register_rest_route( 'calculo-do-milhao/v1', '/historico/', [
        'methods' => 'GET',
        'callback' => 'calculo_do_milhao_get_historico',
    ] );

    // Registro do endpoint POST
    register_rest_route( 'calculo-do-milhao/v1', '/salvar/', [
        'methods' => 'POST',
        'callback' => 'calculo_do_milhao_salvar_historico',
        'permission_callback' => '__return_true',
    ] );

    // Registro do endpoint PUT
    register_rest_route( 'calculo-do-milhao/v1', '/salvar/', [
        'methods' => 'PUT',
        'callback' => 'calculo_do_milhao_atualizar_historico',
        'permission_callback' => '__return_true',
    ] );
}

// GET
function calculo_do_milhao_get_historico( $data ) {
    global $wpdb;

    //$user_id = get_current_user_id();
    $user_id = 1;

    //if ( ! $user_id ) {
    //    return new WP_Error(
    //        'not_logged_in',
    //        'Você precisa estar logado para acessar esse endpoint.',
    //        [ 'status' => 401 ]
    //    );
    //}

    $result = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM calculo_do_milhao WHERE user_id = %d",
            $user_id
        )
    );

    if ( ! empty( $result ) ) {
        $result = json_decode( json_encode( $result[0] ), true );
        if ( isset( $result['id'] ) ) {
            $result['id'] = intval( $result['meta'] );
        }
        if ( isset( $result['user_id'] ) ) {
            $result['user_id'] = intval( $result['meta'] );
        }
	if ( isset( $result['banca_inicial'] ) ) {
            $result['banca_inicial'] = floatval( $result['banca_inicial'] );
        }
        if ( isset( $result['meta'] ) ) {
            $result['meta'] = floatval( $result['meta'] );
        }
        if ( isset( $result['historico'] ) ) {
            $historico = json_decode( $result['historico'], true );

            $acumulado = 0;
            if ( is_array( $historico ) ) {
                foreach ( $historico as $item ) {
                    $acumulado += isset( $item['valor'] ) ? floatval( $item['valor'] ) : 0;
                }
            }

            $result['acumulado'] = $acumulado;
        }
    }

    return rest_ensure_response( $result );
}

// POST
function calculo_do_milhao_salvar_historico( $data ) {
    global $wpdb;

    //$user_id = get_current_user_id();
    $user_id = 1;
    $banca_inicial = floatval( $data['banca_inicial'] );
    $meta = intval( $data['meta'] );
    $acumulado = floatval( $data['acumulado'] );
    $historico = sanitize_text_field( $data['historico'] );

    $wpdb->insert(
        'calculo_do_milhao',
        [
            'user_id' => $user_id,
            'banca_inicial' => $banca_inicial,
            'meta' => $meta,
            'acumulado' => $acumulado,
            'historico' => $historico,
        ],
        [
            '%d', // user_id
            '%f', // banca_inicial
            '%d', // meta
            '%f', // acumulado
            '%s', // historico
        ]
    );

    return rest_ensure_response( 'Dados salvos com sucesso!' );
}
// PUT
function calculo_do_milhao_atualizar_historico( $data ) {
    global $wpdb;

    //$user_id = get_current_user_id();
    $user_id = 1;
    $banca_inicial = floatval( $data['banca_inicial'] );
    $meta = intval( $data['meta'] );
    $acumulado = floatval( $data['acumulado'] );
    $historico = sanitize_text_field( $data['historico'] );

    $result = $wpdb->update(
        'calculo_do_milhao',
        [
            'banca_inicial' => $banca_inicial,
            'meta' => $meta,
            'acumulado' => $acumulado,
            'historico' => $historico,
        ],
        [ 'user_id' => $user_id ],
        [
            '%f', // banca_inicial
            '%d', // meta
            '%f', // acumulado
            '%s', // historico
        ],
        [ '%d' ]
    );

    if ( $result === false ) {
        return new WP_Error(
            'db_update_error',
            'Erro ao atualizar os dados no banco de dados.',
            [ 'status' => 500 ]
        );
    }

    return rest_ensure_response( 'Dados atualizados com sucesso!' );
}
// Rotas da API
add_action( 'rest_api_init', 'calculo_do_milhao_register_api' );
