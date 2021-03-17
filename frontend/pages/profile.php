<?php

class ProfilePage extends BasePage {

    const MANAGE_USERS_PERMISSION = 'MANAGE_USERS';

    public function init($userPermissions, $globalPermissions) {
        $this->currentUser = isset($_SESSION['UserID']) ? $_SESSION['UserID'] : -1;
        $this->targetUser = isset($this->path) && count($this->path) > 1 ? intval($this->path[1]) : $this->currentUser;

        if($this->targetUser != $this->currentUser && !in_array($this::MANAGE_USERS_PERMISSION, $globalPermissions)) {
            $this->targetUser = $this->currentUser;
        }

        $this->userData = $this->dataManager->GetUserProfile($this->targetUser);

        $this->run();
        return true;
    }

    private function run() {
        $intro = '';
        if($this->userData === false) $intro = 'Profil betöltése sikertelen.';
        elseif($this->targetUser != $this->currentUser) $intro = $this->userData['username'] . ' profiljának megtekintése';
        else $intro = 'Saját profil megtekintése';

        $this->setIntro($intro);
        $this->echoHeader();

        if($this->userData === false)
            return $this->profileNotFound();

        $this->loadProfile();
    }

    private function getData($data) {
        return isset($this->userData[$data]) ? $this->userData[$data] : 'n/a';
    }

    private function loadProfile() {
        echo '
        <div id="profiledata" class="box fit-content">
        <h2>Adatok</h2><br>
            <div class="flex">
                <i class="fas fa-user" style="font-size: 100px"></i>
                <ul>
                    <li><strong>Felhasználónév:</strong> <input type="text" value="'.$this->getData('UserName').'" disabled></li>
                    <li><strong>Teljes név (amit mások látnak):</strong> <input type="text" value="'.$this->getData('FullName').'" disabled></li>
                    <li><strong>Email cím:</strong> <input type="text" value="'.$this->getData('Email').'" disabled></li>
                </ul>
            </div>
            <hr>
            <div class="text-center">
                <p><a href="/profile/'.$this->targetUser.'/2fa" class="btn">Két faktoros hitelesítés '.($this->getData('2FAType')==0?'be':'ki').'kapcsolása</a></p>
                <p><a href="/profile/'.$this->targetUser.'/password" class="btn">Jelszó módosítása</a></p>
            </div>
        </div>';

        echo '
        <div class="box">
        <h2>Jogosultságok</h2>
        </div>';
    }

    private function profileNotFound() {
        echo '<div class="box text-center">
        <h3>Hiba történt!</h3>
        <p>A keresett profil nem található</p>
        </div>';
    }

}

$loaded = new ProfilePage();

?>