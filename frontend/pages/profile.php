<?php

class ProfilePage extends BasePage {

    function __construct($twoFactorAuthenticator) {
        $this->twoFactorAuthenticator = $twoFactorAuthenticator;
    }

    public function init($userPermissions, $globalPermissions) {
        $this->currentUser = isset($_SESSION['UserID']) ? $_SESSION['UserID'] : -1;
        $this->targetUser = isset($this->path) && count($this->path) > 1 ? intval($this->path[1]) : $this->currentUser;

        if($this->targetUser != $this->currentUser && !in_array('MANAGE_USERS', $globalPermissions)) {
            $this->targetUser = $this->currentUser;
        }

        $this->userData = $this->dataManager->GetUserProfile($this->targetUser);
        $this->userSecret = $this->dataManager->GetLoginData($this->userData['UserName']);

        $this->run();
        return true;
    }

    private function run() {
        $intro = '';
        if($this->userData === false) $intro = 'Profil betöltése sikertelen.';
        elseif($this->targetUser != $this->currentUser) $intro = htmlentities($this->userData['UserName']) . ' profiljának megtekintése';
        else $intro = 'Saját profil megtekintése';

        $this->setIntro($intro);
        $this->echoHeader();

        if($this->userData === false)
            return $this->profileNotFound();
    
        if(in_array('changepassword', $this->path))
            $this->changePassword();
        elseif(in_array('2fa', $this->path))
            $this->change2FA();
        else
            $this->loadProfile();
    }

    private function doChange2FA($userid, $type) {
        $this->dataManager->Change2FAType($this->targetUser, $type);
        $this->userData['2FAType'] = $_POST['type'];
    }

    private function parse2FAChange() {
        if(isset($_POST['type'], $_POST['code']) && $_POST['type'] >= 0 && $_POST['type'] <= 2) {
            // Disable 2FA without validating input
            if($_POST['type'] == 0)
                return $this->doChange2FA($this->targetUser, 0);

            // Check format
            if(!preg_match('/^\d{6}$/', $_POST['code'])) {
                $this->error = 'Hibás formátum. (Példa: 123456)';
                return;
            }

            // Validate code
            $current2FACode = $this->twoFactorAuthenticator->getCode($this->userSecret['2FA']);
            if($current2FACode != $_POST['code']) {
                $this->error = 'Hibás kód.';
                return;
            }

            // Change 2FA
            return $this->doChange2FA($this->targetUser, $_POST['type']);
        }
    }

    private function change2FA() {
        $this->parse2FAChange();

        $code = $this->userSecret['2FA'];
        $url = '/api/qrcode?url=' . urlencode('otpauth://totp/e-Osztálykassza:'. str_replace('/\s/','_',$this->userData['UserName']) .'?secret='. $code .'&issuer=e-Osztálykassza');

        $type = $this->userData['2FAType'];
        echo '<form method="POST" action="/'.implode('/',$this->path).'" class="flex box fit-content align-center">
            <div>
            <div class="box margin">
                <h2><i class="fas fa-mobile-alt text-green"></i> Kétfaktoros hitelesítés</h2>
                <hr>
                <p>
                A kétfaktoros hitelesítést egy mobilalkalmazás letöltésével tudja elvégezni.<br>
                Androidos eszközökre például a <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2">Google Hitelesítő</a> is lehetőséget biztosít erre.<br>
                A hitelesítés használatához szkennelje be az itt látható QR kódot, vagy gépelje be manuálisan: <strong>'.$code.'</strong>
                </p>
            </div>
            <div class="box margin">
                <h2><i class="fas fa-clock text-orange"></i> Mikor kérje a hitelesítést?</h2>
                <hr>
                <ol>
                <li>Ha <span>kikapcsolja</span> ezt a hitelesítést, akkor az Ön jelszavával és felhasználónevével bárki beléphet a fiókjába.</li>
                <li>Amennyiben nem szeretné mindig beírni a kódját, akkor megadhatja azt is, hogy <span>csak új eszközöknél</span> kelljen megadnia.</li>
                <li>Ha viszont teljes biztonságban szeretné tudni felhasználóját, akkor válassza a <span>minden lehetőségnél kérje</span> opciót!</li>
                </ol>
            </div>
            </div>
            <div class="box margin text-center">
                <img src="'.$url.'">
                <label for="type">
                    Hitelesítés kérése: 
                    <select name="type" id="type">
                        <option value="0"'.($type == 0 ? ' selected' : '').'>Kikapcsolás</option>
                        <option value="1"'.($type == 1 ? ' selected' : '').'>Csak új eszközöknél</option>
                        <option value="2"'.($type == 2 ? ' selected' : '').'>Minden bejelentkezésnél</option>
                    </select>
                </label>
                <label for="code">
                    Kód:
                    <input type="number" name="code" id="code" maxlength="6">
                </label>';
            
            if(isset($this->error))
                echo '<p class="margin text-red">'.$this->error.'</p>';
                
            echo '<div class="flex-spread">
                    <a href="/profile/'.$this->targetUser.'" class="btn"><i class="fas fa-chevron-circle-left text-red"></i> Vissza</a>
                    <button type="submit"><i class="fas fa-save text-green"></i> Alkalmazás</button>
                </div>
            </div>
        </form>';
    }

    private function parsePasswordChange() {
        if(isset($_POST['current'], $_POST['new'], $_POST['new2'])) {
            if(strlen($_POST['new']) < $this->pageConfig::REG_PASSWORD_MIN) {
                $this->error = 'Túl rövid a jelszó.';
                return;
            }
            
            // New password match
            if($_POST['new'] != $_POST['new2']) {
                $this->error = 'Nem egyezik a két új jelszó.';
                return;
            }

            // Old password validating
            $genPass = hash('sha256', hash('sha256', $_POST['current']) . $this->userSecret['PasswordSalt']);
            if($genPass != $this->userSecret['Password']) {
                $this->error = 'Hibás jelenlegi jelszó.';
                return;
            }
            
            // Generate new password
            $newSecret = random_characters(8);
            $newPass = hash('sha256', hash('sha256', $_POST['new']) . $newSecret);
            $this->success = $this->dataManager->ChangePassword($this->targetUser, $newPass, $newSecret);
        }
    }

    private function changePassword() {
        $this->parsePasswordChange();

        echo '<form method="POST" action="/'.implode('/',$this->path).'" class="box fit-content align-center text-center">
        <div class="box">   
            <h2><i class="fas fa-lock text-orange"></i> Jelszó megváltoztatása</h2>
            <hr>
                <label for="current">
                    Jelenlegi jelszó: <br>
                    <input type="password" name="current" id="current">
                </label>
                <label for="new">
                    Új jelszó: <br>
                    <input type="password" name="new" id="new">
                </label>
                <label for="new2">
                    Új jelszó ismét: <br>
                    <input type="password" name="new2" id="new2">
                </label>
                ';
             
        if(isset($this->error)) echo '<p class="text-red">Hiba: '.$this->error.'</p>';
        elseif(isset($this->success)) echo '<p class="text-green">Jelszó megváltoztatva.</p>';

        echo '
            </div>
            <hr>
            <div class="box">
            <p class="margin text-center">A gombra kattintás után már csak az új jelszavával léphet be.</p>
            <div class="flex-spread">
                <a href="/profile/'.$this->targetUser.'" class="btn"><i class="fas fa-chevron-circle-left text-red"></i> Vissza</a>
                <button type="submit"><i class="fas fa-save text-green"></i> Megváltoztatás</button>
            </div>
            </div>
        </form>';
        return true;
    }

    private function getData($data) {
        return isset($this->userData[$data]) ? $this->userData[$data] : 'n/a';
    }

    private function loadProfile() {
        echo '
        <div id="profiledata" class="box fit-content">
            <div class="box">
                <h2>Adatok</h2>
                <hr>
                <div class="flex">
                    <i class="fas fa-user" style="font-size: 100px; margin-right: -20px; padding-top: 10px;"></i>
                    <ul>
                        <li><strong>Felhasználónév:</strong> <input type="text" value="'.$this->getData('UserName').'" disabled></li>
                        <li><strong>Teljes név (amit mások látnak):</strong> <input type="text" value="'.$this->getData('FullName').'" disabled></li>
                        <li><strong>Email cím:</strong> <input type="text" value="'.$this->getData('Email').'" disabled></li>
                    </ul>
                </div>
            </div>
            <div class="text-center flex-spread">
                <a href="/profile/'.$this->targetUser.'/2fa" class="btn"><i class="fas fa-mobile-alt text-green"></i> Kétfaktoros hitelesítés</a>
                <a href="/profile/'.$this->targetUser.'/changepassword" class="btn"><i class="fas fa-key text-orange"></i> Jelszó módosítása</a>
            </div>
        </div>';
    }

    private function profileNotFound() {
        echo '<div class="box text-center">
        <h3>Hiba történt!</h3>
        <p>A keresett profil nem található</p>
        </div>';
    }

}

$loaded = new ProfilePage($twoFactorAuthenticator);

?>