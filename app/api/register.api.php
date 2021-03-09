<?php

    $user = new User($pageConfig, $dataManager, $cookieHandler);
    try {
        if(isset($api_data) && $api_data == 'email_verified') {
            $_2fa = $user->HandleEmailVerify($_SESSION['REGISTER_DATA']);
            $_SESSION['REGISTER_DATA']['2FA'] = $_2fa;
            return;
        }
        
        $data = $user->HandleRegister($_POST);

    } catch(Exception $e) {
        $error = $e->getMessage();
        return api_response(false, $error);
    }

    $_SESSION['REGISTER_DATA'] = $data;

    return api_response(true);