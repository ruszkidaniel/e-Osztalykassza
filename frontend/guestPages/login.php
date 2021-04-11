<?php
if(isset($_SESSION['REGISTER_DATA']))
	die(header('Location: /register/verify'));

$response = '<p id="response">Jelentkezzen be, ha van már fiókja!</p>';
if(isset($_SESSION['REGISTER_SUCCESS'])) {
	unset($_SESSION['REGISTER_SUCCESS']);
	$response = '<p id="response" class="success">A regisztráció sikeres! Most már bejelentkezhet.</p>';
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
	call_local_api('login');
	if($api_response['success']) {
		if(isset($_SESSION['NEED_2FA']))
			redirect_to_url('/login/2fa');
		else
			redirect_to_url('/');
	} else {
		$errors = [
			'user_not_found' => 'Nem található felhasználó ilyen névvel.',
			'permission_error' => 'Nincs jogosultsága bejelentkezni.',
			'max_login_attempts_reached' => 'Túl sokszor próbált bejelentkezni, ezért ideiglenesen letiltottuk.',
			'invalid_password' => 'A jelszó nem megfelelő.'
		];

		$error = $api_response['error'];
		$response = '<p id="response" class="failure">'. (isset($errors[$error]) ? $errors[$error] : 'Ismeretlen hiba történt.') .'</p>';
	}
}

?>

<h1>Üdvözöljük!</h1>
<h4>
	<strong>Az oldal megtekintéséhez be kell jelentkeznie.</strong><br>Ha nincs még felhasználója, készíthet egyet meghívó linkkel, vagy új osztály létrehozásával.</h4>
<?=$response?>
<form action="/" method="POST" autocomplete="off">
	<label for="username">
		<i class="fas fa-user text-orange"></i> Felhasználónév:
		<input type="text" name="username" id="username" tabindex="1">
	</label>
	<label for="password">
		<i class="fas fa-key text-orange"></i> Jelszó: (<a href="/forgotpw" class="forgotpw" tabindex="5">Elfelejtett jelszó?</a>)
		<input type="password" name="password" id="password" tabindex="2">
	</label>
	<input type="submit" value="Belépés" class="btn" tabindex="3">
	
	<?php if(strlen($pageConfig::FB_APP_ID) > 0): ?>
	<p class="text-center">
		Belépés Facebookkal:
		<fb:login-button 
		scope="public_profile,email"
		onlogin="checkLoginState();">
		</fb:login-button>
	</p>
	<?php endif; ?>
	
	<p class="text-center">
		<a href="/register" class="btn" tabindex="4">Regisztráció</a>
	</p>
</form>