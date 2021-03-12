<?php

    $user = new User($pageConfig, $dataManager, $cookieHandler);
    try {
        if(!isset($_POST['username'], $_POST['password']))
            throw new Exception('data_mismatch');

        $data = $user->LoginUser($_POST['username'], $_POST['password']);

    } catch(Exception $e) {
        $error = $e->getMessage();
        return api_response(false, $error);
    }

    $need2fa = false; // 0: 2fa disabled

    if($data['2FAType'] == 1) { // only if logging in from new ip

        $hasLoginWithThisIP = $dataManager->FindUserSessionByIP($data['UserID'], $_SERVER['REMOTE_ADDR']) > 0;
        if(!$hasLoginWithThisIP)
            $need2fa = true;

    } elseif($data['2FAType'] == 2) { // every login
        $need2fa = true;
    }

    if($need2fa)
        $_SESSION['NEED_2FA'] = $data['2FA'];

    $_SESSION['UserID'] = $data['UserID'];
    $_SESSION['UserName'] = $data['UserName'];
    $_SESSION['GlobalPermissions'] = $data['GlobalPermissions'];

    return api_response(true);