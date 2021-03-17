<?php

    if(!isset($_SESSION['NEED_2FA'])) {
        redirect_to_url('/');
    }

    if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['Code'])) {
        call_local_api('login', '2fa');
    }

?>
<h1>Kétfaktoros hitelesítés</h1>
<form action="/login/2fa" method="POST" autocomplete="off">
    <p class="text-center">A felhasználóba csak kétfaktoros hitelesítést követően lehet belépni.</p>
    <label for="Code">
        Adja meg a kódot!
        <input type="text" name="Code" id="Code" pattern="[0-9]{6}" maxlength="6" required>
        <input type="submit" value="Belépés">
    </label>
    <?php
        if(isset($api_response)) {
            if($api_response['success']) {
                unset($_SESSION['NEED_2FA']);
                redirect_to_url('/');
            } else {
                echo '<p id="response" class="failure">Hibás kódot adott meg.</p>';
            }
        }
        ?>
</form>