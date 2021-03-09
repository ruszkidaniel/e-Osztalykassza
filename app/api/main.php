<?php

    $api_response = [
        'success' => false,
        'error' => null,
        'data' => null
    ];

    $api_path = defined('INCLUDED_API_PATH') ? INCLUDED_API_PATH : $path;

    if(count($api_path) == 1 || !file_exists('./app/api/' . $api_path[1] . '.api.php')) {
        header('Location: /');
        die('<meta http-equiv="refresh" content="0;/">');
    }

    function api_response($success, $error = null, $data = null) {
        global $api_response;

        $api_response['success'] = $success;
        $api_response['error'] = $error;
        $api_response['data'] = $data;

        if(!defined('INCLUDED_API_PATH')) 
            die(json_encode($api_response));
    }

    require_once('./app/api/' . $api_path[1] . '.api.php');

?>