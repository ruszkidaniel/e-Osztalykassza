<h1>Új felhasználó regisztrálása</h1>
<h4>Ezen az oldalon létrehozhatja az Ön személyes fiókját.</h4>
<?php

if($_SERVER['REQUEST_METHOD'] == 'POST') {  
    call_local_api('register');
    
    if($api_response['success']) {
        echo '<p id="response" class="success">A regisztráció sikeres volt!</p>';
        $redirect = true;
    } else {
        $errors = [
            'already_logged_in' => 'Már be van jelentkezve!',
            'data_mismatch' => 'Hibás adatok lettek elküldve.',
            'username_length' => 'A felhasználónév nem megfelelő hosszúságú.',
            'email_length' => 'Az email cím nem megfelelő hosszúságú.',
            'username_invalid_characters' => 'A felhasználónév nem megfelelő karaktereket tartalmaz.',
            'fullname_invalid_characters' => 'A teljes név nem megfelelő karaktereket tartalmaz.',
            'fullname_length' => 'A teljes név nem megfelelő hosszúságú.',
            'password_length' => 'A jelszó nem megfelelő hosszúságú.',
            'password_mismatch' => 'A két jelszó nem egyezik.',
            'email_mismatch' => 'A két email cím nem egyezik.',
            'invalid_email' => 'Az email cím nem megfelelő formátumú.',
            'user_already_exists' => 'A felhasználónév vagy email cím már foglalt.',
            'cookies_are_not_accepted' => 'A sütik nincsenek engedélyezve.'
        ];
        $error = array_key_exists($api_response['error'], $errors) ? $errors[$api_response['error']] : 'Ismeretlen hiba történt.';
        echo '<p id="response" class="failure">'. $error .'</p>';
    }
} else {
    echo '<p id="response">A fiók létrehozásához írja be adatait az alábbi mezőkbe.</p>';
}

if(isset($redirect)) {

    redirect_to_url('/register/verify');

} else {
    ?>
    <form action="/register/user" method="POST" autocomplete="off">
        <div class="register-form">
            <div>
                <label for="username">
                    Felhasználónév:
                    <small>Maximum 32 karakterből állhat</small>
                    <input type="text" name="username" id="username" value="<?=(isset($_POST['username'])?$_POST['username']:'')?>" tabindex="1" required>
                </label>
                <label for="email">
                    Email cím:
                    <small>Fontos, hogy létező cím legyen</small>
                    <input type="email" name="email" id="email" value="<?=(isset($_POST['email'])?$_POST['email']:'')?>" tabindex="3" required>
                </label>
                <label for="password">
                    Jelszó:
                    <small>Használjon egyedi, erős jelszavakat!</small>
                    <input type="password" name="password" id="password" tabindex="5" required>
                </label>
            </div>
            <div>
                <label for="fullname">
                    Teljes név:
                    <small>Ez a név fog megjelenni másoknál</small>
                    <input type="text" name="fullname" id="fullname" value="<?=(isset($_POST['fullname'])?$_POST['fullname']:'')?>" tabindex="2" required>
                </label>
                <label for="email2">
                    Email cím ismét:
                    <small>Ellenőrzés miatt szükséges</small>
                    <input type="email" name="email2" id="email2" tabindex="4" required>
                </label>
                <label for="password2">
                    Jelszó ismét:
                    <small>Ellenőrzés miatt szükséges</small>
                    <input type="password" name="password2" id="password2" tabindex="6" required>
                </label>
            </div>
        </div>
        <div class="flex-spread">
            <p><a href="/register" class="btn">Vissza</a></p>
            <input type="submit" value="Tovább" class="btn" id="regbtn">
        </div>
    </form>
    <?php
}
?>