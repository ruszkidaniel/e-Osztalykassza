<?php

    if(!defined('EO_project_version'))
        die(header('Location: /'));

    if(isset($_SESSION['username']))
        die(header('Location: /'));

?>
<div class="login-wrapper">
    <div class="login-container">
        <h1>Üdvözöljük!</h1>
        <p>Az oldal megtekintéséhez be kell jelentkeznie.</p>
        <form action="/" method="POST">
            <label for="username">
                Felhasználónév:
                <input type="text" name="username" id="username">
            </label>
            <label for="password">
                Jelszó:
                <input type="password" name="password" id="password">
            </label>
            <input type="submit" value="Belépés">
        </form>
        <hr>
        <p>
            <a href="/forgotpw">Elfelejtett jelszó</a> | <a href="/register">Még nem regisztráltam</a>
        </p>
    </div>
</div>