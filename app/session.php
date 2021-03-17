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
            $userid = null;

            if(isset($_SESSION['UserID'])) 
                $userid = $_SESSION['UserID'];
            elseif(isset($_SESSION['REGISTER_DATA'], $_SESSION['REGISTER_DATA']['UserID']))
                $userid = $_SESSION['REGISTER_DATA']['UserID'];

            $dataManager->UpdateSession($session_id, $user_agent, $ip_address, $userid);
        }
    }