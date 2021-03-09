<?php

    if(!isset($_SESSION['REGISTER_DATA']['2FA'])) {
        redirect_to_url('/register/verify');
    }

    $url = 'otpauth://totp/e-Osztálykassza: '. $_SESSION['REGISTER_DATA']['UserName'] .'?secret=A2CDEF5HIJKLMNOP&issuer=e-Osztálykassza';
    $qrcode = 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' . urlencode($url);
?>
<h3>Beállítások</h3>
<form action="/register/user" method="POST" autocomplete="off">
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
                <input type="date" id="dob" name="dob">
            </label>
        </div>
        <div>
            <img src="" alt="">
        </div>
    </div>
</form>