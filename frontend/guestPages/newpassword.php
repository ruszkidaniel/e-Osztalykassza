<?php

    if(!isset($_GET['code']) || strlen($_GET['code']) != 16) {
        redirect_to_url('/');
    }
    
    $user = $dataManager->FindUserByVerificationCode($_GET['code'], 'password');
    if($user === false) {
        echo '<h1>Hibás kód.</h1>
        <hr>
        <p>A megadott kód nem található.</p>';
        return;
    }
    
    if(isset($_POST['password'], $_POST['password2'])) {
        if($_POST['password'] != $_POST['password2']) {
            $error = 'A két jelszó nem egyezik.';
        } elseif(strlen($_POST['password']) < $pageConfig::REG_PASSWORD_MIN) {
            $error = 'Túl rövid a jelszó.';
        } else {
            $newSecret = random_characters(8);
            $newPass = hash('sha256', hash('sha256', $_POST['password']) . $newSecret);
            $dataManager->ChangePassword($user['UserID'], $newPass, $newSecret);
            $dataManager->DeleteVerificationCode($_GET['code']);
            $_SESSION['PASSWORD_CHANGED'] = true;
            redirect_to_url('/');
        }
    }
?>

<h1>Új jelszó beállítása</h1>
<p class="text-center">Adja meg új jelszavát!</p>
<hr>
<form method="POST">
    <input type="hidden" name="username" id="username" value="<?=htmlentities($user['UserName'])?>">
    <label for="password">
        <i class="fas fa-key text-orange"></i> Új jelszó:
        <input type="password" name="password" id="password">
    </label>
    <label for="password">
        <i class="fas fa-key text-orange"></i> Új jelszó ismét:
        <input type="password" id="password2" name="password2">
    </label>
    <div class="text-center">
        <input type="submit" value="Beállítás" class="btn">
    </div>
</form>

<?php
if(isset($error))
    echo '<p id="response" class="failure">'.$error.'</p>';