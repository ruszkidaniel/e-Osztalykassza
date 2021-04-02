<?php

class CreateRequestPage extends BasePage {

    public function init($userPermissions, $globalPermissions) {
        if(!isset($_SESSION['ClassInfo']))
            return false;

        // Fetch permissions
                
        $this->manageRequests = 
            $_SESSION['ClassInfo']['OwnerID'] == $_SESSION['UserID'] ||
            array_search('MANAGE_REQUESTS', $userPermissions) !== false ||
            array_search('MODIFY_ALL_CLASSES', $globalPermissions) !== false;
        if(!$this->manageRequests) return false;
        
        // Load members table

        $this->members = $this->dataManager->GetClassMembers($_SESSION['ClassInfo']['ClassID']);
        $this->membersDOM = '';
        foreach($this->members as $member) {
            $this->membersDOM .= '<tr>
                <td>'.htmlentities($member['FullName']).'</td>
                <td>'.$member['DOB'].'</td>
                <td><input type="number" name="amounts['.$member['UserID'].']" value="'.(isset($_POST['amounts'][$member['UserID']])?$_POST['amounts'][$member['UserID']]:'0').'" class="amount"> Ft</td>
            </tr>';
        }

        if($_SERVER['REQUEST_METHOD'] == 'POST')
            $this->parseRequest();

        $this->run();
        return true;
    }

    private function parseRequest() {
        if(!isset($_POST['title'], $_POST['description'], $_POST['amounts'], $_POST['deadline']) || gettype($_POST['amounts']) != 'array') {
            $this->error = 'Hibás adatok lettek elküldve.';
            return;
        }

        $deadline = isset($_POST['no-deadline']) ? null : $_POST['deadline'];
        $email = !isset($_POST['no-email']);
        $description = trim($_POST['description']);
        $title = trim($_POST['title']);
        $validAmounts = array_filter($_POST['amounts'], function($am){ return (strlen($am) > 0 && preg_match('/^\d+$/', $am)); }); // need to preserve keys

        if(strlen($title) < $this->pageConfig::REQUEST_MIN_TITLE_LENGTH || strlen($title) > $this->pageConfig::REQUEST_MAX_TITLE_LENGTH)
            $this->error = 'A cím csak '.$this->pageConfig::REQUEST_MIN_TITLE_LENGTH.' és .'.$this->pageConfig::REQUEST_MAX_TITLE_LENGTH.' karakter közötti érték lehet.';
        elseif(strlen($description) < $this->pageConfig::REQUEST_MIN_DESCRIPTION_LENGTH || strlen($description) > $this->pageConfig::REQUEST_MAX_DESCRIPTION_LENGTH)
            $this->error = 'A leírás csak '.$this->pageConfig::REQUEST_MIN_DESCRIPTION_LENGTH.' és '.$this->pageConfig::REQUEST_MAX_DESCRIPTION_LENGTH.' karakter közötti érték lehet.';
        elseif(!is_null($deadline) && !strtotime($deadline))
            $this->error = 'A határidőt nem sikerült feldolgozni.';
        elseif(count($validAmounts) == 0)
            $this->error = 'A bekért összegek közül egy sem érvényes.';
        else {
            $requestID = $this->dataManager->CreateRequest($_SESSION['ClassInfo']['ClassID'], $_SESSION['UserID'], $title, $description, $deadline);
            if(!$requestID) $this->error = 'Nem sikerült létrehozni a kérelmet.';
        }

        if(isset($this->error)) return;

        $data = [];
        foreach($validAmounts as $user => $amount) {
            $data[] = [$requestID, $user, $amount];
        }
        $this->dataManager->InsertDebts($data);
        redirect_to_url('/dashboard');
    }

    private function run() {
        $this->setIntro('Osztálypénz bekérése');
        $this->echoHeader();
        
        echo '<form method="POST" action="/createrequest" class="box" id="createrequest">';

        if(isset($this->error))
            echo '<div class="box text-red fit-content align-center text-center"><h2>Hiba történt!</h2>'.$this->error.'</div>';

        echo '<div class="box">
                <h2><i class="fas fa-plus-circle text-green"></i> Új kérvény adatai <small>(1/3)</small></h2>
                <hr>
                <label for="title">
                    <i class="fas fa-pen-square text-orange"></i> Kérvény címe:
                    <input type="text" name="title" id="title" value="'.(isset($_POST['title'])?$_POST['title']:'').'" placeholder="pl: Osztálykirándulás Székesfehérvárra" required>
                </label>
                <label for="amount">
                    <i class="fas fa-money-bill-wave-alt text-orange"></i> Bekért összeg:
                    <input type="number" name="amount" id="amount" value="'.(isset($_POST['amount'])?$_POST['amount']:'').'" placeholder="pl: 5000" required> Ft
                </label>
                <label for="deadline">
                    <i class="fas fa-calendar-plus text-orange"></i> Határidő:
                    <input type="date" name="deadline" min="'.date('Y-m-d').'">
                    (<label for="no-deadline">
                        <input type="checkbox" name="no-deadline" value="no-deadline" id="no-deadline"> nincs határidő</small>
                    </label>)
                </label>
                <label for="description">
                    <i class="fas fa-book-open text-orange"></i> Leírás:
                    <small>(max 2000 karakter)</small>
                    <textarea name="description" id="description" value="'.(isset($_POST['description'])?$_POST['description']:'').'" max="2000" required></textarea>
                </label>
            </div>
            <div class="box">
                <h2><i class="fas fa-users text-green"></i> Kérvényezettek kiválasztása <small>(2/3)</small></h2>
                <p>Ezen a részen megadhatja, hogy kinek mekkora összeget kell befizetni.<br>
                Akinek a neve mellett 0 forint van, az nem fog értesítést kapni erről a kérelemről, és nála nem fog megjelenni a listában.<br>
                <span>Az értékek automatikusan módosulnak, ha fentebb megváltoztatja az árat.</span></p>
                <hr>
                <div class="small-height">
                <table class="eo-table">
                    <thead>
                        <tr><th>Teljes név</th><th>Születési dátum</th><th>Bekért összeg</th></tr>
                    </thead>
                    <tbody>
                        '.$this->membersDOM.'
                    </tbody>
                </table>
                </div>
            </div>
            <div class="box">
                <h2><i class="fas fa-check-circle text-green"></i> Véglegesítés <small>(3/3)</small></h2>
                <hr>
                <p>Alapesetben az új kérvényekről emailben tájékoztatjuk az érintetteket, amit rendszerünk automatikusan kiküld a létrehozást követően.<br>
                Ezt azonban kikapcsolhatja, amennyiben nem szeretné emailben értesíteni a megkért tagokat.</p>
                <label for="no-email">
                Email küldés <span class="text-red">kikapcsolása</span>: <input type="checkbox" name="no-email" id="no-email" value="no-email">
                </label>
                <input type="submit" value="Kérvény benyújtása">
            </div>
        </form>';
    }

}

$loaded = new CreateRequestPage();


?>