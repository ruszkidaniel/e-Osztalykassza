<?php

class NewClassPage extends BasePage {

    static $steps = [
        'Iskola kiválasztása',
        'Osztály adatainak meghatározása',
        'Csoportok létrehozása',
        'Szülők meghívása'
    ];

    public function init($userPermissions, $globalPermissions) {
        $this->setIntro('Osztály létrehozása');
        $this->echoHeader();

        $this->classes = $this->dataManager->GetUserOwnedClasses($_SESSION['UserID']);

        if(count($this->classes) > 0) {
            // PREMIUM: users who bought premium features should be allowed to make more than 1 classes
            $this->alreadyOwnAClass();
            return true;
        }

        if(!isset($_SESSION['NewClass']))
            $_SESSION['NewClass'] = 0;

        $this->run();
        return true;
    }

    private function run() {
    
        $parse = 'parseStep' . $_SESSION['NewClass'];
        if($_SERVER['REQUEST_METHOD'] == 'POST' && method_exists($this, $parse))
            $this->$parse();

        echo '
        <div class="box">
            <h2>Új osztály létrehozása</h2>
            <p>Az új osztály létrehozásának lépései: '.$this->getSteps().'</p>
        </div>';
    
        $method = 'runStep' . $_SESSION['NewClass'];
        if(method_exists($this, $method))
            $this->$method();
    }

    private function parseStep0() {
        if(isset($_POST['found'], $_POST['school']) && $_POST['school'] != -1) {

            $found = $this->dataManager->FindSchool($_POST['school']);
            if(count($found) == 0) {
                $this->error = 'Nem található a kiválasztott iskola!';
                return;
            }

            $_SESSION['SelectedSchool'] = $found[0];

        } else {

            if(!isset($_POST['schoolName'])) {
                $this->error = 'Hibás adatok lettek elküldve.';
                return;
            }

            $len = mb_strlen($_POST['schoolName']);
            if($len < 12 || $len > 64) {
                $this->error = 'Az iskola neve minimum 12, maximum 64 karakter lehet.';
                return;
            }

            $found = $this->dataManager->FindSchool($_POST['schoolName'], false);
            if(count($found) > 0) {
                $this->error = 'Már létezik iskola ilyen névvel!';
                return;
            }

            $school = $this->dataManager->CreateSchool($_POST['schoolName']);
            if(count($school) == 0) {
                $this->error = 'Az iskola létrehozása sikeretelen volt.';
                return;
            }

            $_SESSION['SelectedSchool'] = $school[0];
        }

        if(isset($_SESSION['SelectedSchool']))
            $_SESSION['NewClass'] = 1;
    }

    private function runStep0() {
        $schools = $this->dataManager->GetSchools();
        $schoolsDOM = '';

        if(count($schools) > 0) {
            $domElements = [];
            foreach($schools as $school) {
                $domElements[] = '<option value="'.$school['SchoolID'].'">'.htmlspecialchars($school['SchoolName']).'</option>'.PHP_EOL;
            }
            $schoolsDOM = implode(PHP_EOL, $domElements);
        }
        $schoolsDOM .= '<option value="-1" selected>Új iskola hozzáadása</option>';

        echo '
        <div class="box fit-content align-center">
            <h2 class="text-center">Iskola kiválasztása</h2>
            <p class="text-center align-center">Ha szerepel a listában az Ön iskolájának neve, kérjük válassza ki!</p>
            <form method="POST" action="/new" id="school-select">
                <select name="school" id="school">
                    '.$schoolsDOM.'
                </select> <input type="submit" name="found" value="Kiválaszt">
                <hr>
                <div>
                    <p>Ha nem találta meg az iskoláját, adja hozzá az alábbi űrlapon:</p>
                    <label for="schoolName">
                        Iskola neve:<br>
                        <input type="text" id="schoolName" class="large-input" name="schoolName" maxlength="64"><br>
                        <small>Kérük ügyeljen az iskola nevének pontos leírására! (Maximum: 64 karakter)</small><br>
                    </label>
                    <input type="submit" name="add" value="Hozzáadás">
                </div>
            </form>';
        if(isset($this->error))
            echo '<p id="response" class="align-center failure">'.$this->error.'</p>';
        echo '
        </div>';
    }

    private function parseStep1() {
        if(isset($_POST['back'])) {
            $_SESSION['NewClass'] = 0;
            unset($_SESSION['SelectedSchool']);
            return;
        }

        if(!isset($_POST['ClassName'], $_POST['Description'])) {
            $this->error = 'Hibás adatok lettek elküldve.';
            return;
        }

        $name_len = mb_strlen($_POST['ClassName']);
        if($name_len < 3 || $name_len > 16) {
            $this->error = 'Az osztály nevének 3 és 16 karakter között kell lennie.';
            return;
        }
        
        $desc_len = mb_strlen($_POST['Description']);
        if($desc_len > 1024) {
            $this->error = 'A leírás maximum 1024 karakteres lehet.';
            return;
        }

        $found = $this->dataManager->FindClass($_SESSION['SelectedSchool']['SchoolID'], $_POST['ClassName']);
        if(count($found) > 0) {
            $this->error = 'Ebben az iskolában már létre van hozva ilyen nevű osztály!';
            return;
        }

        $success = $this->dataManager->CreateClass($_SESSION['SelectedSchool']['SchoolID'], $_POST['ClassName'], $_POST['Description']);
        if(!$success) {
            $this->error = 'Nem sikerült létrehozni az osztályt.';
            return;
        }

        $_SESSION['NewClass'] = 2;
    }

    private function runStep1() {
        if(!isset($_SESSION['SelectedSchool']))
            throw new Exception('Nincs kiválasztott iskola.');

        echo '
        <div class="box fit-content align-center">
            <h2 class="text-center">Osztály adatainak meghatározása</h2>
            <p  class="text-center">Ebben a lépésben hozza létre az új osztályát.<br>Az iskola módosítását csak az előző oldalon tudja megtenni.</p>
            <form method="POST" action="/new">
                <label for="school">
                    Kiválasztott iskola:
                    <input type="text" value="'.$_SESSION['SelectedSchool']['SchoolName'].'" class="large-input" name="school" id="school" disabled>
                </label>
                <label for="ClassName">
                    Osztály megnevezése:
                    <input type="text" name="ClassName" class="large-input" id="ClassName" placeholder="pl: 8.A osztály">
                </label>
                <label for="ClassName">
                    Leírása:<br>
                    <textarea name="Description" id="Description" rows="10" cols="100" placeholder="pl: Ez a 8.A osztály elektronikus osztálykasszája.
A kasszát a jogszabálynak megfelelően Zsolt szülei kezelik.
Egyelőre ismerkedünk a rendszerrel, kérném szíves türelmüket!"></textarea>
                </label>
                <div class="flex-spread">
                    <input type="submit" value="Vissza" name="back">
                    <input type="submit" value="Tovább" name="next">
                </div>
            </form>
        </div>
        ';
    }

    private function getSteps() {
        return implode(' -> ', array_map(function($x, $i) { return $i == $_SESSION['NewClass'] ? '<span>'.$x.'</span>' : $x; }, $this::$steps, array_keys($this::$steps)));
    }

    private function alreadyOwnAClass() {
        echo '
        <div class="box fit-content align-center text-center">
            <h3>Új osztály létrehozása</h3>
            <p>Önnek már van egy saját osztálya, ezért már nem hozhat létre többet!</p>
        </div>';
    }

}

$loaded = new NewClassPage();

?>