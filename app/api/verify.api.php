<?php

    $verificationHelper = new VerificationHelper($pageConfig, $dataManager);
    try {
        if(isset($api_data) && $api_data !== false)
            $verificationHelper->VerifyCode($_SESSION['REGISTER_DATA'], $api_data);
        else
            $verificationHelper->HandleRequest($_POST, $_SESSION['REGISTER_DATA']);
    } catch(Exception $e) {
        $error = $e->getMessage();
        return api_response(false, $error);
    }

    return api_response(true);
    
?>