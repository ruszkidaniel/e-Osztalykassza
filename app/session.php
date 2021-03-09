<?php

    if($cookieHandler->Check()) {
        session_name('e-osztalykassza');
        session_start();

        $session_id = session_id();
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $ip_address = $_SERVER['REMOTE_ADDR'];

        if(!$dataManager->IsValidSession($session_id)) {
            session_unset();
            $dataManager->StoreSession($session_id, $user_agent, $ip_address);
        } else {
            $dataManager->UpdateSession($session_id, $user_agent, $ip_address);
        }
    }