<?php

    if(!isset($_SESSION['REGISTER_DATA']['2FA'])) {
        redirect_to_url('/register/verify');
    }

    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        call_local_api('register', 'profile');
    }

    $url = '/api/qrcode?url=' . urlencode('otpauth://totp/e-Osztálykassza:'. str_replace('/\s/','_',$_SESSION['REGISTER_DATA']['UserName']) .'?secret='. $_SESSION['REGISTER_DATA']['2FA'] .'&issuer=e-Osztálykassza');

?>
<h1>Beállítások</h1>
<form action="/register/profile" method="POST" autocomplete="off">
    <div class="register-form">
        <div>
            <label for="2fa">
                Két faktoros hitelesítés
                <small>Ha engedélyezni szeretné a kétfaktoros hitelesítést, válasszon!</small>
                <select name="2fa" id="2fa">
                    <option value="0">Kikapcsolás</option>
                    <option value="1">Csak új eszközöknél kérje</option>
                    <option value="2">Minden bejelentkezésnél kérje</option>
                </select>
            </label>
            <label for="dob">
                Születési dátum beállítása
                <input type="date" id="dob" name="dob" required>
            </label>
            <label for="dobhidden">
                Elrejtés más felhasználók elől:
                <small>Az osztály készítője minden esetben láthatja.</small>
                <input type="checkbox" name="dobhidden" id="dobhidden">
            </label>
        </div>
        <div>
            <p class="text-center">Kétfaktoros hitelesítés QR kód</p>
            <img src="<?=$url?>" class="img-center" alt="Két faktoros hitelesítő QR kód"><br>
            <input type="text" name="Code" pattern="[0-9]{6}" maxlength="6" placeholder="Kód az alkalmazásból:">
        </div>
    </div>
    <p class="info">Amennyiben aktiválni szeretné a két faktoros hitelesítést, töltse le Androidos okostelefonjára a <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=hu&gl=US" target="_blank">Google Hitelesítő</a> alkalmazást.<br>
    Ha a kép nem jelenik meg, vagy hibás, írja be manuálisan az alábbi kódot: <strong><?=$_SESSION['REGISTER_DATA']['2FA']?></strong></p>
    <p class="info">Ha nem szeretné bekapcsolni, akkor válassza a <strong>Kikapcsolás</strong> opciót, és ne töltse ki a kép alatti szövegdobozt.</p>
    <p class="text-center"><button class="btn text-center">Regisztráció befejezése</button></p>
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