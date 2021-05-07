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

        // Parse URL

        if(in_array('delete', $this->path))
            return $this->deleteClass();
        elseif(in_array('description', $this->path))
            return $this->modifyDescription();
        
        $this->run();
        return true;
    }

    private function modifyDescription() {
        $this->setIntro('Osztály leírásának módosítása');
        $this->echoHeader();

        echo '<div class="box">
        <form method="POST" action="/settings/description" class="box">
            <h2>Leírás módosítása</h2>
            <hr>
            <p>Ha frissíteni szeretné a mindenkinek megjelenő leírást, változtassa meg itt, és kattintson a mentés gombra!</p>
            <label for="description">Új leírás:
            <textarea name="description" id="description">'.htmlentities($this->classInfo['info']['Description']).'</textarea>
            </label>
            <input type="submit" value="Mentés">
        </form>
        <a href="/settings" class="btn"><i class="fas fa-arrow-circle-left text-orange"></i> Vissza</a>
        </div>';
        return true;
    }

    private function deleteClass() {

        if(!$this->isOwner) return false;
        $this->setIntro('Osztály törlése');
        $this->echoHeader();
        $_SESSION['csrf'] = random_characters(22);

        echo '<div class="box">
        <form method="POST" action="/settings" class="box fit-content">
            <h2>Osztály végleges törlése</h2>
            <hr>
            <p>Biztos, hogy törölni szeretné az osztályát?</p>
            <p class="margin"><strong class="text-red"><i class="fas fa-exclamation-triangle"></i> FIGYELEM!</strong> Ez a lépés nem vonható vissza! Minden befizetés és egyéb adat törlődni fog.</p>
            <input type="hidden" name="csrf" value="'.$_SESSION['csrf'].'">
            <p><button type="submit" name="deleteclass"> Igen, biztosan törölni szeretném az osztályt</button></p>
        </form>
        <a href="/settings" class="btn"><i class="fas fa-arrow-circle-left text-orange"></i> Vissza</a>
        </div>';

        return true;
    }

    private function parseSettings() {
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
        elseif(isset($_POST['csrf'], $_POST['deleteclass'])) {
            if(!$this->isOwner) return;

            $this->dataManager->DeleteClass($this->classInfo['info']['ClassID']);
            redirect_to_url('/');
            return;
        }
        elseif(isset($_POST['description'])) {
            $description = trim(substr($_POST['description'], 0, 2000));

            $this->dataManager->UpdateClassDescription($this->classInfo['info']['ClassID'], $description);
            $this->classInfo['info']['Description'] = $description;
            return;
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
        </div>';
        if($this->isOwner)
            echo '<a href="/settings/delete" class="btn"><i class="fas fa-trash text-red"></i> Osztály törlése</a> ';
        echo '<a href="/settings/description" class="btn"><i class="fas fa-clipboard text-orange"></i> Leírás módosítása</a>
        </div>';
    }

}

$loaded = new SettingsPage();

?>