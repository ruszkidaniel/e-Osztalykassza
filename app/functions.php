<?php

    function isLogged() {
        global $cookieHandler;
        return $cookieHandler->Check() && isset($_SESSION['UserID']);
    }

    function random_characters($len) {
        $seed = str_split('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
        shuffle($seed);
        $res = '';
        foreach (array_rand($seed, $len) as $k) $res .= $seed[$k];
        return $res;
    }

    function censored_email($email, $char = '•') {
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) return $email;

        $parts = explode('@',$email);
        $res = substr($parts[0], 0, 3) . str_repeat($char, max(0, strlen($parts[0])-7)) . substr($parts[0], -3) . '@';
        
        if(strlen($res) < 8)
            $res = substr($parts[0], 0, 2) . str_repeat($char, max(0, strlen($res) - 3)) . '@';

        $subparts = explode('.', $parts[1]);
        $domain = [];
        foreach($subparts as $s) {
            $domain[] = substr($s, 0, 2) . str_repeat($char, max(0, strlen($s)-2));
        }
        return $res . implode('.', $domain);
    }

    function call_local_api($path, $data = false) {
        global $cookieHandler, $pageConfig, $dataManager;
        define('INCLUDED_API_PATH', ['api', $path]);
        $api_data = $data;
        require_once('./app/api/main.php');
    }

    function redirect_to_url($url) {
        header('Location: ' . $url);
        die('<meta http-equiv="refresh" content="0;'. $url .'">');
    }