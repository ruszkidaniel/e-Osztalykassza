<?php

    if($_SERVER['REQUEST_METHOD'] != 'POST') {
        die('<h2 class="align-center text-center">Hiba történt!</h2>
        <hr>
        <p class="text-center">Erre az oldalra csak a meghívást követően juthat el.<br>Győződjön meg róla, hogy megfelelően másolta ki a hivatkozást az emailből!</p>');
    }
    
    /*if(!isset($_POST['csrf'], $_POST['inviteCode'], $_POST['accept'], $_SESSION['csrf']) || $_SESSION['csrf'] != $_POST['csrf']) {
        die('<h2 class="align-center text-center">Hibás adatok!</h2>
        <hr>
        <p>Hibás adatok lettek elküldve.</p>');
    }*/

    $invite = new InviteManager($dataManager, $_POST['inviteCode']);
    $result = $invite->handleResponse($_POST['accept']);
    if($result === true) {
        $_SESSION['InviteData'] = $invite->getInviteData();
        redirect_to_url('/register/user');
    }
    elseif($result)
        var_dump($result);