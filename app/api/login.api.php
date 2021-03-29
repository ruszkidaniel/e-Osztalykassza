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

    // Parse Login

    // Check 2FA

    $need2fa = false; // 0: 2fa disabled
    if($data['2FAType'] == 1) { // 1: only if logging in from new ip
        
        $hasLoginWithThisIP = intval($dataManager->FindUserByIP($data['UserID'], $_SERVER['REMOTE_ADDR'])) > 0;

        if($hasLoginWithThisIP === false)
            $need2fa = true;

    } elseif($data['2FAType'] == 2) { // 2: every login

        $need2fa = true;

    }

    // Store 2FA

    if($need2fa)
        $_SESSION['NEED_2FA'] = $data['2FA'];
    else
        $dataManager->StoreUserIP($data['UserID'], $_SERVER['REMOTE_ADDR']); // only assign ip to user if logged in already

    // Store login data

    $_SESSION['UserID'] = $data['UserID'];
    $_SESSION['UserName'] = $data['UserName'];
    $_SESSION['FullName'] = $data['FullName'];
    $_SESSION['GlobalPermissions'] = $data['GlobalPermissions'];

    // Auto-select user's class if has only one

    $classes = $dataManager->GetUserClassrooms($data['UserID']);
    if(count($classes) == 1)
        $_SESSION['ClassInfo'] = $dataManager->GetClassInfo($classes[0]['ClassID']);

    return api_response(true);