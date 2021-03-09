<?php

if(isset($_SESSION['REGISTER_DATA']))
	die(header('Location: /register/verify'));

?>

<h1>Üdvözöljük!</h1>
<h4>
	<strong>Az oldal megtekintéséhez be kell jelentkeznie.</strong><br>Ha nincs még felhasználója, készíthet egyet meghívó linkkel, vagy új osztály létrehozásával.</h4>
<form action="/" method="POST" autocomplete="off">
	<label for="username">
		<i class="fas fa-user"></i> Felhasználónév:
		<input type="text" name="username" id="username" tabindex="1">
	</label>
	<label for="password">
		<i class="fas fa-key"></i> Jelszó: (<a href="/forgotpw" class="forgotpw" tabindex="5">Elfelejtett jelszó?</a>)
		<input type="password" name="password" id="password" tabindex="2">
	</label>
	<input type="submit" value="Belépés" class="btn" tabindex="3">
	<p class="text-center">
		Belépés Facebokkal:
		<fb:login-button 
		scope="public_profile,email"
		onlogin="checkLoginState();">
		</fb:login-button>
	</p>
	<p class="text-center">
		<a href="/register" class="btn" tabindex="4">Regisztráció</a>
	</p>
</form>