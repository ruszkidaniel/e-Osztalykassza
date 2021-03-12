<?php

    if(!isset($_SESSION['NEED_2FA'])) {
        redirect_to_url('/');
    }

    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        call_local_api('register', 'profile');
    }

    $url = '/api/qrcode?url=' . urlencode('otpauth://totp/e-Osztálykassza:'. str_replace('/\s/','_',$_SESSION['REGISTER_DATA']['UserName']) .'?secret='. $_SESSION['REGISTER_DATA']['2FA'] .'&issuer=e-Osztálykassza');

?>
<h1>Kétfaktoros hitelesítés</h1>
<form action="/login/2fa" method="POST" autocomplete="off">
    <p>A felhasználóba csak kétfaktoros hitelesítést követően lehet belépni.</p>
    <label for="2fa">
        Adja meg a kódot!
        <input type="text" name="2fa" id="2fa" required>
        <input type="button" value="Belépés">
    </label>
    <?php
        if(isset($api_response)) {
            if($api_response['success']) {
                $_SESSION['REGISTER_SUCCESS'] = true;
                unset($_SESSION['REGISTER_DATA']);
                redirect_to_url('/login');
            } else {
                $errors = [
                    'invalid_dob_format' => 'Hibás formátumú születési dátum.',
                    'wrong_2fa_code' => 'Hibás kódot adott meg.',
                    '2fa_not_provided' => 'Nem adott meg kódot, de bekapcsolta a két faktoros hitelesítést.'
                ];

                echo '<p id="response" class="failure">'. (array_key_exists($api_response['error'], $errors) ? $errors[$api_response['error']] : 'Ismeretlen hiba történt' ) .'</p>';
            }
        }
        ?>
</form>