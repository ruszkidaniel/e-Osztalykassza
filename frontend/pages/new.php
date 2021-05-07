<?php

class NewClassPage extends BasePage {

    static $steps = [
        'Iskola kiválasztása',
        'Osztály adatainak meghatározása',
        'Csoportok létrehozása',
        'Szülők meghívása'
    ];

    public function init($userPermissions, $globalPermissions) {
        $this->echoHeader();

        $this->classes = $this->dataManager->GetUserOwnedClasses($_SESSION['UserID']);

        if(!isset($_SESSION['NewClass'])) {
            
            if(count($this->classes) > 0) {
                // PREMIUM: users who bought premium features should be allowed to make more than 1 classes
                $this->alreadyOwnAClass();
                return true;
            }
            $_SESSION['NewClass'] = 0;
        }

        $this->run();
        return true;
    }

    private function run() {
        
        $parse = 'parseStep' . $_SESSION['NewClass'];
        if($_SERVER['REQUEST_METHOD'] == 'POST' && method_exists($this, $parse))
            $this->$parse();

        if(count($this->path) > 1 && $this->path[1] == 'cancel') {
            if(isset($_SESSION['ClassInfo'])) {
                if($_SESSION['NewClass'] >= 2) // has selected class AND after the class creation phase
                    $this->dataManager->DeleteClass($_SESSION['ClassInfo']['ClassID']);
                
                unset($_SESSION['ClassInfo']);
            }

            if(isset($_SESSION['SelectedSchool'])) {
                $isEmpty = count($this->dataManager->GetSchoolClasses($_SESSION['SelectedSchool']['SchoolID'])) == 0;
                if($isEmpty) 
                    $this->dataManager->DeleteSchool($_SESSION['SelectedSchool']['SchoolID']);

                unset($_SESSION['SelectedSchool']);
            }
            $_SESSION['NewClass'] = 0;
            redirect_to_url('/');
        }

        echo '
        <div class="box">
            <h2>Új osztály létrehozása</h2>
            <p>Az új osztály létrehozásának lépései: '.$this->getSteps().' | Vagy: <a href="/new/cancel" class="btn">Megszakítás</a></p>
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
        <div class="box fit-content align-center" id="school-select">
            <h2 class="text-center">Iskola kiválasztása</h2>
            <p class="text-center align-center">Ha szerepel a listában az Ön iskolájának neve, kérjük válassza ki!</p>
            <form method="POST" action="/new">
                <select name="school" id="school">
                    '.$schoolsDOM.'
                </select> <input type="submit" name="found" value="Kiválaszt">
            </form>
            <hr>
            <form method="POST" action="/new">
                <div>
                    <p>Ha nem találta meg az iskoláját, adja hozzá az alábbi űrlapon:</p>
                    <label for="schoolName">
                        Iskola neve:<br>
                        <input type="text" id="schoolName" class="large-input" name="schoolName" maxlength="64"><br>
                        <small>Kérük ügyeljen az iskola nevének pontos leírására! (Maximum: 64 karakter)</small><br>
                    </label>
                    <input type="submit" name="add" value="Hozzáadás">
                </div>
            </form>
            ';
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

        $found = $this->dataManager->FindClass($_SESSION['SelectedSchool']['SchoolID'], $_POST['ClassName'], false);
        if($found) {
            $this->error = 'Ebben az iskolában már létre van hozva ilyen nevű osztály!';
            return;
        }

        $class = $this->dataManager->CreateClass($_SESSION['SelectedSchool']['SchoolID'], $_POST['ClassName'], $_SESSION['UserID'], $_POST['Description']);
        if(!$class) {
            $this->error = 'Nem sikerült létrehozni az osztályt.';
            return;
        }

        $this->dataManager->AddMemberToClass($_SESSION['UserID'], $class['ClassID']);

        $_SESSION['NewClass'] = 2;
        $_SESSION['ClassInfo'] = $class;
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
                    <textarea name="Description" id="Description" rows="8" cols="100" placeholder="pl: Ez a 8.A osztály elektronikus osztálykasszája.
A kasszát a jogszabálynak megfelelően Zsolt szülei kezelik.
Egyelőre ismerkedünk a rendszerrel, kérném szíves türelmüket!"></textarea>
                </label>
                <div class="flex-spread flex-reverse"> <!-- flex-reverse: first submit should be the next button -->
                    <input type="submit" value="Tovább" name="next">
                    <input type="submit" value="Vissza" name="back">
                </div>
            </form>
            ';
        if(isset($this->error))
            echo '<p id="response" class="align-center failure">'.$this->error.'</p>';
        echo '
        </div>
        ';
    }

    private function parseStep2() {
        $classID = $_SESSION['ClassInfo']['ClassID'];
        if(isset($_POST['saveGroupName'], $_POST['groupName'], $_POST['groupID'])) {
            if(strlen($_POST['groupName']) > 16 || strlen($_POST['groupName']) < 3) {
                $this->error = 'A csoport nevének 3 és 16 karakter között kell lennie.';
                return;
            }
            
            // checking whether its a new group
            $found = $this->dataManager->FindClassGroup($classID, $_POST['groupName'], false);

            if($found) {
                $idx = $this->findGroupInSession($found['GroupID']);
                if($idx == -1) return;

                $renamed = $this->dataManager->RenameGroup($classID, $found['GroupID'], $_POST['groupName']);
                $_SESSION['ClassGroups'][$idx] = $renamed;
                return;
            }

            $groups = $this->dataManager->GetClassGroups($classID);
            if(count($groups) >= 3) return;

            $group = $this->dataManager->AddClassGroup($classID, $_POST['groupName']);
            if(!isset($_SESSION['ClassGroups']))
                $_SESSION['ClassGroups'] = [];

            $_SESSION['ClassGroups'][] = $group;
        }
        elseif(isset($_POST['delete'], $_POST['groupID'])) {
            $idx = $this->findGroupInSession($_POST['groupID']);
            if($idx != -1)
                array_splice($_SESSION['ClassGroups'], $idx, 1);

            $this->dataManager->DeleteClassGroup($classID, $_POST['groupID']);
        }
        elseif(isset($_POST['done'])) {
            $_SESSION['csrf'] = random_characters(24);
            $_SESSION['NewClass'] = 3;
        }
    }

    private function runStep2() {
        echo '
        <div class="box fit-content align-center">
            <h2 class="text-center">Csoportok létrehozása</h2>
            <p class="text-center">Most opcionálisan megadhat különböző osztályokon belüli csoportokat, legfeljebb hármat.<br>
            Ez a későbbiekben hasznos lehet, amikor előre meghatározott emberektől kell pénzt kérni / kihagyni a kérelemből.<br>
            Amennyiben nem szeretne csoportokat megadni, vagy elmentett minden módosítást, kattintson a megerősítéshez:
            <form method="POST" action="/new" class="text-center">
                <input type="submit" name="done" value="Készen vagyok, következő lépés" class="align-center">
            </form></p>
        </div>
        <div class="text-center" id="classgroups">';

        $template = '<div class="box colored">
            <form method="POST" class="classgroup-header">
                <h2>{{groupName}}</h2>
                <input type="hidden" name="groupID" value="{{groupID}}">
                {{delete}}
            </form>
            <hr>
            <form method="POST" action="/new">
                <label for="groupName">
                    Csoport neve:
                    <input type="text" name="groupName" placeholder="{{groupName}}" id="groupName">
                    <input type="submit" name="saveGroupName" value="Mentés">
                </label>
                <input type="hidden" name="groupID" value="{{groupID}}">
            </form>
        </div>';

        $len = 0;
        $deleteDOM = '<button type="submit" name="delete"><i class="fas fa-trash text-red"></i></button>';
        if(isset($_SESSION['ClassGroups'])) {
            for($i = 0; $i < min(3, count($_SESSION['ClassGroups'])); $i++) {
                $group = $_SESSION['ClassGroups'][$i];
                if(!$group) continue;
                
                $len++;
                echo str_replace(
                    ['{{groupName}}', '{{delete}}', '{{groupID}}'], 
                    [htmlentities($group['GroupName']), $deleteDOM, $group['GroupID']],
                    $template
                );
            }
        }

        if($len < 3)
            echo str_replace(['{{groupName}}', '{{delete}}', '{{groupID}}'], ['Új csoport felvétele', '', -1], $template);
            
        if(isset($this->error))
            echo '<p id="response" class="align-center failure">'.$this->error.'</p>';
            
        echo '</div>';
    }

    private function parseStep3() {
        $this->response = ['success' => [], 'failed' => []];

        if(isset($_POST['skip'])) return;
        
        if(!isset($_POST['email'], $_POST['csrf'], $_SESSION['csrf']) || gettype($_POST['email']) != 'array') return;
        if($_POST['csrf'] != $_SESSION['csrf']) return;
        unset($_SESSION['csrf']);
        
        // filter invalid emails
        $emails = array_unique(array_filter($_POST['email'], function($email){ return filter_var($email, FILTER_VALIDATE_EMAIL); }));

        $url = $this->pageConfig::WEBSITE_ADDRESS;
        $email = $this->pageConfig::INVITE_MAIL_TEMPLATE;

        $optoutlist = $this->dataManager->GetUnsubscribedEmails($emails);

        $subject = "e-Osztálykassza meghívó";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: <noreply@'.$this->pageConfig::WEBSITE_DOMAIN.'>' . "\r\n";

        for($i = 0; $i < count($emails); $i++) {
            $address = $emails[$i];
            
            if(array_search($address, $optoutlist) !== false) {
                $this->response['failed'][] = $address;
                continue;
            }
            
            $inviteCode = random_characters(32);
            $inviteurl = $url . 'invite/accept/' . $inviteCode;
            $declineurl = $url . 'invite/decline/' . $inviteCode;
            $optouturl = $url . 'invite/optout/' . $inviteCode;

            $this->response['success'][] = ['Email' => $address, 'Code' => $inviteCode];
            
            $message = str_replace(['{{inviteurl}}', '{{declineurl}}', '{{optouturl}}'], [$inviteurl, $declineurl, $optouturl], $email);
            
            mail($address,$subject,$message,$headers);
        }

        $this->dataManager->CreateInvitations($_SESSION['UserID'], $_SESSION['ClassInfo']['ClassID'], $this->response['success']);

    }

    private function runStep3() {
        if(isset($this->response)) {
            $failed = count($this->response['failed']);
            $failedDOM = $failed > 0 ?
                '<strong>'.$failed.'</strong> szülő korábban leiratkozott a szolgáltatásról, így nem került meghívásra. Lista:' :
                '<strong>Minden kiválasztott szülő meg lett hívva.</strong>';

            if($failed > 0) 
                $failedDOM .= '<ul>'.PHP_EOL.implode(PHP_EOL, array_map(function($f) { return '<li>'.$f.'</li>'; }, $this->response['failed'])).PHP_EOL.'</ul>';

            echo '
            <div class="box fit-content align-center" id="invite-success">
                <h2>Szülők meghívva</h2>
                <hr class="align-center">
                <p>'.$failedDOM.'</p>
                <p>Meghívottak listája (<strong>'.count($this->response['success']).'</strong>):</p>
                <ul>
                    '.implode(PHP_EOL, array_map(function($e){return '<li>'.$e['Email'].'</li>';}, $this->response['success'])).'
                </ul>
                <p class="text-center"><a href="/dashboard" class="btn">Befejezés</a></p>
            </div>';

            unset($_SESSION['NewClass']);

            return;
        }
        
        if(!isset($_SESSION['csrf'])) return;
        
        $dom = str_repeat('<div>'.str_repeat('<input type="email" placeholder="E-mail cím" name="email[]">'.PHP_EOL, 15).'</div>'.PHP_EOL, 2);
        echo '
        <div class="box fit-content align-center">
            <h2 class="text-center">Szülők meghívása</h2>
            <p class="text-center">
            Az utolsó lépésben meghívhatja az osztály tanulóinak szüleit a csoportba az email címük megadásával.<br>
            Ez a lépés kihagyható, azonban később csak egyesével hívhatja meg a csoport tagjait.
            <hr class="align-center">
            <form method="POST" action="/new" class="text-center">
                <div class="flex-spread">
                '.$dom.'
                </div>
                <input type="hidden" name="csrf" value="'.$_SESSION['csrf'].'">
                <input type="submit" name="invite" value="Szülők meghívása" class="align-center"><br>
                <input type="submit" name="skip" value="Kihagyás" class="align-center">
            </form></p>
        </div>';
    }

    private function getSteps() {
        return implode(' -> ', array_map(function($x, $i) { return $i == $_SESSION['NewClass'] ? '<span>'.$x.'</span>' : $x; }, $this::$steps, array_keys($this::$steps)));
    }

    private function findGroupInSession($id) {
        if(!isset($_SESSION['ClassGroups'])) return -1;
        $i = 0;
        $count = count($_SESSION['ClassGroups']);

        while($i < $count && (gettype($_SESSION['ClassGroups'][$i]) != 'array' || $_SESSION['ClassGroups'][$i]['GroupID'] != $id)) $i++;
        return $i == $count ? -1 : $i;
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