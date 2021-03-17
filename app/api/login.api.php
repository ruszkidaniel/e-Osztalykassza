<?php

    $user = new User($pageConfig, $dataManager, $cookieHandler);
    try {

        if(isset($api_data, $_SESSION['NEED_2FA'], $_POST['Code']) && $api_data == '2fa') {
            // parse 2FA
            
            $currentCode = $twoFactorAuthenticator->getCode($_SESSION['NEED_2FA']);
            $success = $currentCode == $_POST['Code'];
            
            if($success)
                $dataManager->StoreUserIP($data['UserID'], $_SERVER['REMOTE_ADDR']); // only assign ip to user if logged in already

            return api_response($success);
        } else {

            if(!isset($_POST['username'], $_POST['password']))
                throw new Exception('data_mismatch');

            $data = $user->LoginUser($_POST['username'], $_POST['password']);
        }
    } catch(Exception $e) {
        $error = $e->getMessage();
        return api_response(false, $error);
    }

    // parse login
    $need2fa = false; // 0: 2fa disabled

    if($data['2FAType'] == 1) { // only if logging in from new ip
        
        $hasLoginWithThisIP = intval($dataManager->FindUserByIP($data['UserID'], $_SERVER['REMOTE_ADDR'])) > 0;

        if($hasLoginWithThisIP === false)
            $need2fa = true;

    } elseif($data['2FAType'] == 2) { // every login

        $need2fa = true;

    }

    if($need2fa)
        $_SESSION['NEED_2FA'] = $data['2FA'];
    else
        $dataManager->StoreUserIP($data['UserID'], $_SERVER['REMOTE_ADDR']); // only assign ip to user if logged in already

    $_SESSION['UserID'] = $data['UserID'];
    $_SESSION['UserName'] = $data['UserName'];
    $_SESSION['GlobalPermissions'] = $data['GlobalPermissions'];

    return api_response(true);