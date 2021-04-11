<?php

class SettingsPage extends BasePage {

    public function init($userPermissions, $globalPermissions) {
        if(!isset($_SESSION['ClassInfo']))
            return false;

        // Fetch permissions
        
        $this->manageSettings = 
            $_SESSION['ClassInfo']['OwnerID'] == $_SESSION['UserID'] ||
            array_search('MANAGE_SETTINGS', $userPermissions) !== false ||
            array_search('MODIFY_ALL_CLASSES', $globalPermissions) !== false;
        
        if(!$this->manageSettings) return false;

        $this->modifyMaxMembers = false; // Ready for future implementation (paid features)

        $this->classInfo = $this->dataManager->GetDetailedClassData($_SESSION['ClassInfo']['ClassID']);
        $this->isOwner = $_SESSION['UserID'] == $this->classInfo['info']['OwnerID'];

        // Load members

        $this->membersDOM = '';
        foreach($this->classInfo['members'] as $m) {
            $selected = $m['UserID'] == $this->classInfo['info']['OwnerID'] ? ' selected' : '';
            $this->membersDOM .= '<option value="'.$m['UserID'].'"'.$selected.'>'.htmlentities($m['FullName']).'</option>'.PHP_EOL;
        }

        // Parse post request

        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->parseSettings();
        }

        $this->run();
        return true;
    }

    function parseSettings() {
        if(isset($_POST['className'], $_POST['maxMembers'])) {
            if(strlen($_POST['className']) < 3 || strlen($_POST['className']) > 16) return;
            $maxMembers = $this->classInfo['info']['MaxMembers'];
            if($this->modifyMaxMembers && $_POST['maxMembers'] > 1 && $_POST['maxMembers'] < 60)
                $maxMembers = $_POST['maxMembers']; 

            $this->dataManager->UpdateClass($_SESSION['ClassInfo']['ClassID'], $_POST['className'], $maxMembers);
        }
        elseif(isset($_POST['newAdmin'])) {
            if(!in_array($_POST['newAdmin'], array_column($this->classInfo['members'], 'UserID'))) return;
            if(!$this->isOwner) return;

            $this->dataManager->UpdateClassOwner($_SESSION['ClassInfo']['ClassID'], $_POST['newAdmin']);
        }
        redirect_to_url('/settings');
    }

    private function run() {
        $this->setIntro('Osztály beállításainak módosítása');
        $this->echoHeader();
        echo '<div class="box">
        <h2>Beállítások módosítása</h2>
        <div class="flex">
        <form method="POST" action="/settings" class="box fit-content align-center text-center">
            <h2>Általános</h2>
            <hr>
            <label for="className">
                <i class="fas fa-pen-square text-orange"></i> Osztály neve:<br>
                <input type="text" id="className" name="className" value="'.htmlentities($this->classInfo['info']['ClassName']).'">
            </label>
            <label for="maxMembers">
                <i class="fas fa-arrows-alt-v text-orange"></i> Max létszám:<br>
                <input type="number" id="maxMembers" name="maxMembers" '.($this->modifyMaxMembers?'':'readonly').' value="'.htmlentities($this->classInfo['info']['MaxMembers']).'"><br>
                <small class="text-'.($this->modifyMaxMembers?'green':'red').'">'.($this->modifyMaxMembers?'(Van lehetőség a módosításra)':'(Ez egy prémium funkció)').'</small>
            </label>
            <input type="submit" value="Mentés">
        </form>
        <form method="POST" action="/settings" class="box fit-content align-center text-center">
            <h2>Adminisztrátori jogosultság átruházása</h2>
            <hr>
            <label for="newAdmin">
                <i class="fas fa-user-shield text-orange"></i> Osztály adminisztrátor:<br>
                <select name="newAdmin" '.($this->isOwner?'':'disabled').'>
                    '.$this->membersDOM.'
                </select>
            </label>';
        
        if($this->isOwner) 
            echo '<p class="margin"><span class="text-red">Figyelem!</span> Ennek a jogkörnek az átruházásával megvonja a saját jogosultságait.</p>
            <p class="margin">
                Csak akkor adja át az adminisztrátori jogosultságot, ha lemond minden jogáról amivel az osztályt kezelni tudja.<br>
                Ezeket a jogokat egyenként az új osztály adminisztrátor tudja később megadni.
            </p>
            <p><input type="submit" value="Jogosultságok átadása"></p>';

        echo '</form>
        </div>
        </div>
        ';
    }

}

$loaded = new SettingsPage();

?>