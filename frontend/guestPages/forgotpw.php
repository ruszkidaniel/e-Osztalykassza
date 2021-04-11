<?php

    if(isset($_POST['username'], $_POST['email'])) {
        $verificationHelper = new VerificationHelper($pageConfig, $dataManager);
        try {
            $verificationHelper->HandleLostPassword($_POST['username'], $_POST['email']);
        } catch(Exception $e) {
            $e = $e->getMessage();
            $errors = [
                'user_not_found' => 'A felhasználó nem található.',
                'already_requested' => 'Már lett beadva jelszóváltási kérelem.'
            ];
            $error = isset($errors[$e]) ? $errors[$e] : 'Ismeretlen hiba történt.';
        }
    }

?>

<h1>Elfelejtett jelszó visszaállítása</h1>
<p>Amennyiben elfelejtette jelszavát, itt visszaállíthatja azt a felhasználóneve és emailcíme segítségével.</p>
<hr>
<form method="POST">
    <label for="username">
        <i class="fas fa-user text-orange"></i> Felhasználónév:
        <input type="text" name="username" id="username">
    </label>
    <label for="email">
        <i class="fas fa-envelope text-orange"></i> E-mail cím:
        <input type="email" id="email" name="email">
    </label>
    <div class="flex-spread">
        <a href="/" class="btn">Vissza</a>
        <input type="submit" value="Visszaállítás" class="btn">
    </div>
</form>

<?php
if(isset($_POST['username'], $_POST['email']))
    echo '<p id="response" class="'.(
        isset($error) ?
        'failure">'.$error :
        'success">Az email sikeresen el lett küldve.'
    ).'</p>';