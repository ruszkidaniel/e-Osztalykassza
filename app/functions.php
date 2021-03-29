<?php

    function isLogged() {
        global $cookieHandler;
        return $cookieHandler->Check() && isset($_SESSION['UserID']) && !isset($_SESSION['NEED_2FA']);
    }
    
    function random_characters($len) {
        $validChars = str_split('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
        
        $secret = '';
        $rnd = false;
        if (function_exists('random_bytes')) {
            $rnd = random_bytes($len);
        } elseif (function_exists('mcrypt_create_iv')) {
            $rnd = mcrypt_create_iv($len, MCRYPT_DEV_URANDOM);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $rnd = openssl_random_pseudo_bytes($len, $cryptoStrong);
            if (!$cryptoStrong) {
                $rnd = false;
            }
        }

        if ($rnd !== false) {
            for ($i = 0; $i < $len; ++$i) {
                $secret .= $validChars[ord($rnd[$i]) & count($validChars)-1];
            }
        } else {
            foreach (array_rand($validChars, $len) as $k)
                $secret .= $validChars[$k];

            error_log('random_characters is not secure enough.');
        }
        return $secret;
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
        global $cookieHandler, $pageConfig, $dataManager, $twoFactorAuthenticator;
        define('INCLUDED_API_PATH', ['api', $path]);
        $api_data = $data;
        require_once('./app/api/main.php');
    }

    function redirect_to_url($url) {
        header('Location: ' . $url);
        die('<meta http-equiv="refresh" content="0;'. $url .'">');
    }

    function price_format($price) {
        return number_format($price,null,null,'.');
    }