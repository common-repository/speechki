<?php

function speeckiKvark_siteConfirmation($id, $token)
{
    $postData['body'] = array (
        "check_type" => "meta",
    );

    $postData['headers'] = array(
        'Authorization' => 'Token ' . $token,
    );

    $output = wp_remote_post( "http://speechkit.ru/api/v1/sites/$id/accept/", $postData );

    if($output['response']['message'] = 'OK') {
        $result['token'] = $token;
    } else {
        $result['error'] = true;
        $result['error_text'] = $output['body'];
    }

    return $result;
}

function speeckiKvark_addSiteToDb($token)
{

    $postData['body'] = array (
        "name" => get_bloginfo('name'),
        "domain" => get_bloginfo('url'),
        'rss_url' => get_bloginfo('url').'/feed/retell',
        'rss_article_tag' => 'string',
        'language' => true,
        'parsing_source' => 'rss',
        'provider' => true,
        'mobile_mirror' => get_bloginfo('url'),
        'speaker' => true,
        'crawler_links' => ''
    );

    $postData['headers'] = array(
        'Authorization' => 'Token ' . $token,
    );

    $response = wp_remote_post( 'http://speechkit.ru/api/v1/sites/', $postData );
    $output = json_decode($response['body']);

    if(!empty($output->detail)) {
        $result['error'] = true;
        $result['error_text'] = $output->detail;
    } else {
        update_option('accept_code', $output->accept_code);
        $result = speeckiKvark_siteConfirmation($output->id, $token);
    }
    return $result;
}


add_action( 'wp_ajax_speeckiKvark_getTemporaryToken', 'speeckiKvark_getTemporaryToken' );

function speeckiKvark_getTemporaryToken()
{

    if (!check_ajax_referer( 'wp_ajax_speeckiKvark_getTemporaryToken', 'nonce_for_getting_tokken' )){
        wp_die();
    }

    if(!empty($_POST['login']) && !empty($_POST['pass'])) {

        $postData['body'] = array (
            "email" => sanitize_text_field($_POST['login']),
            "password" => sanitize_text_field($_POST['pass'])
        );

        $response = 'false';

        $response = wp_remote_post( 'http://speechkit.ru/rest-auth/login/', $postData );

        $output = json_decode($response['body']);

    }

    $result = array('error' => false);

    if (!empty($output->non_field_errors)) {
        $result['error'] = true;
        $result['error_text'] = $output->non_field_errors[0];
    } else {
        $postData['headers'] = array (
            'Authorization' => 'JWT ' . $output->token,
        );

        $response = wp_remote_post( 'http://speechkit.ru/rest-auth/token_login/', $postData );
        $output = json_decode($response['body']);

        if(!empty($output->detail)) {
            $result['error'] = true;
            $result['error_text'] = $output->detail;
        } else {
            $result = speeckiKvark_addSiteToDb($output->key);
            update_option('token', $output->key);
        }
    }

    echo json_encode($result);

    wp_die();
}

add_action( 'wp_ajax_speeckiKvark_SaveSettings', 'speeckiKvark_SaveSettings' );

function speeckiKvark_SaveSettings()
{
    $result = false;
    if (!check_ajax_referer( 'speeckiKvark_SaveSettings', 'nonce' )){
        wp_die();
    }

    if(!empty($_POST['post_types'])) {
        $post_types = isset( $_POST['post_types'] ) ? (array) $_POST['post_types'] : array();
        $post_types = array_map( 'esc_attr', $post_types );

        update_option('selected_post_type', $post_types);
        update_option('player_location', sanitize_text_field( $_POST['player_location']));
        $result = 'ok';
    }

    echo json_encode($result);

    wp_die();
}

?>
