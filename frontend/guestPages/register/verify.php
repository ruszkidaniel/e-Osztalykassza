<?php
/*
    $_SESSION['REGISTER_DATA'] = [
        'UserID' => '18',
        'UserName' => 'ruszki.daniel',
        'FullName' => 'Ruszki Dániel',
        'Email' => 'ruszki.daniel@gmail.com'
    ];//*/

    if(!isset($_SESSION['REGISTER_DATA'])) {
        
        redirect_to_url('/register');

    } elseif(isset($_SESSION['REGISTER_DATA']['2FA'])) {

        redirect_to_url('/register/profile');

    }

    $code = $dataManager->FindVerificationCode($_SESSION['REGISTER_DATA']['UserID'], 'email');

    if(count($path) > 2 && $code) {
        // posted a code
        
        $success = ($code['Code'] == $path[2]);

        echo '<p id="response" class="'.($success?'success':'failure').'">'.
            ($success?'Emailcíme sikeresen megerősítve! Kattintson a gombra folytatáshoz!':'Hiba történt az aktiválás során, a kód nem megfelelő.').
            '</p>';
        
        if($success) {

            call_local_api('register', 'email_verified');
            
            $dataManager->DeleteVerificationCode($code['Code']);

            echo '<div class="flex-spread"><div></div><a href="/register/profile" class="btn">Tovább</a></div>';
        }

    } else {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // requested a code or an update
            if($code) {
                $diff = time() - strtotime($code['Date']);
                if($diff < $pageConfig::VERIFY_CODES_MIN_DIFF) {
                    $waitfor = $pageConfig::VERIFY_CODES_MIN_DIFF - $diff;
                    $mins = floor($waitfor/60);
                    $secs = $waitfor - $mins*60;
                    if($mins > 0) $mins .= ' perc ';
                    else $mins = '';
                    echo '<p id="response" class="failure">A következő email küldéséig még várnia kell '. $mins . $secs .' másodpercet.</p>';
                } else {
                    call_local_api('verify');
                }
            } else {
                call_local_api('verify');
            }
            
            if(isset($api_response)) {
                if($api_response['success']) {
                    echo '<p id="response" class="success">A megerősítő kódot elküldtük a megadott címre! Ha nem találja, nézze meg a spam mappáját is!</p>';
                }
            }
        }

        ?>
        <form action="/register/verify" method="POST">
            <h1>Aktiválás</h1>
            <p class="info">
                Ebben a lépésben győződünk meg az email cím valódiságáról, méghozzá úgy, hogy a megadott email címére (<?=censored_email($_SESSION['REGISTER_DATA']['Email'])?>) egy megerősítő linket küldünk. <br>
                Erre a linkre rá kell kattintania, hogy befejezze a regisztrációját.
            </p>
            <p class="text-center">
                <input type="submit" value="Küldés" name="sendcode" class="btn">
            </p>
        </form>
        <?php

    }

?>