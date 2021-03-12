<?php

    $user = new User($pageConfig, $dataManager, $cookieHandler);
    try {
        if(isset($api_data)) {
            switch($api_data) {
                case 'email_verified':
                    $_2fa = $twoFactorAuthenticator->createSecret(16);
                    $dataManager->SetUser2FA($_SESSION['REGISTER_DATA']['UserID'], $_2fa, 0);
                    $_SESSION['REGISTER_DATA']['2FA'] = $_2fa;
                    break;
                case 'profile':
                    $current2FACode = $twoFactorAuthenticator->getCode($_SESSION['REGISTER_DATA']['2FA']);
                    $user->SetupProfile($_SESSION['REGISTER_DATA'], $_POST, $current2FACode);
                    break;
            }
            return api_response(true);
        }
        
        $data = $user->HandleRegister($_POST);

    } catch(Exception $e) {
        $error = $e->getMessage();
        return api_response(false, $error);
    }

    $_SESSION['REGISTER_DATA'] = $data;

    return api_response(true);