<?php

class RequestPage extends BasePage {

    public function init($userPermissions, $globalPermissions) {
        if(!isset($_SESSION['ClassInfo']['ClassID']) || count($this->path) < 2)
            return false;

        $validRequest = $this->parseRequest();
        if(!$validRequest)
            return false;

        // Load permisisions

        $this->managePays = 
            $_SESSION['ClassInfo']['OwnerID'] == $_SESSION['UserID'] ||
            array_search('MANAGE_PAYS', $userPermissions) !== false ||
            array_search('MODIFY_ALL_CLASSES', $globalPermissions) !== false;
            
        $this->manageRequest = 
            $_SESSION['ClassInfo']['OwnerID'] == $_SESSION['UserID'] ||
            array_search('MANAGE_REQUESTS', $userPermissions) !== false ||
            array_search('MODIFY_ALL_CLASSES', $globalPermissions) !== false;

        // Load routing

        if(count($this->path) > 2) {
            switch($this->path[2]) {
                case 'debts':
                    return $this->loadDebts();
                case 'delete':
                    return $this->deleteRequest();
                case 'modify':
                    return $this->loadModify();
            }
        }
        
        $this->run();
        return true;
    }

    private function run() {
        $this->setIntro('Információk egy befizetési kérelemről');
        $this->echoHeader();
        echo '
        <div class="box" id="dashboard">
            <div class="box">
                <h2>'.$this->requestData['Subject'].'</h2>
                <div class="eo-table-wrapper">
                <table class="eo-table">
                    <thead>
                        <tr><th>Érintettek száma</th><th>Szükséges pénzösszeg</th><th>Összegyűlt összeg</th><th>Határidő</th><th>Létrehozó</th><th>Létrehozás dátuma</th></tr>
                    </thead>
                    <tbody>
                        '.$this->requestDOM.'
                    </tbody>
                </table>
                </div>
                <div id="top">
                    <div id="description">
                        <h3>Leírás</h3>
                        <textarea disabled>'.htmlspecialchars($this->requestData['Description']).'</textarea>
                    </div>
                    <div class="box meta small-width">
                        <h3><i class="far fa-credit-card text-green"></i> Összeg kiegyenlítése</h3>
                        <hr>
                        <div class="center-all">
                            A befizetés jelenleg csak személyesen történhet meg.
                        </div>
                    </div>
                </div>
            </div>';

        if($this->managePays)
            echo '<a class="btn" href="/request/'.$this->requestID.'/debts"><i class="fas fa-money-bill-wave text-green"></i> Befizetések kezelése</a> ';
        
        if($this->manageRequest)
            echo '<a class="btn" href="/request/'.$this->requestID.'/modify"><i class="fas fa-edit text-orange"></i> Kérvény módosítása</a> 
            <a class="btn" href="/request/'.$this->requestID.'/delete"><i class="fas fa-trash text-red"></i> Kérvény törlése</a>';
        
        echo '</div>';
    }

    // REQUEST

    function deleteRequest() {
        if(!$this->manageRequest) return false;

        if(isset($_SESSION['csrf'], $_POST['csrf']) && $_POST['csrf'] == $_SESSION['csrf']) {
            $this->dataManager->DeleteRequest($_SESSION['ClassInfo']['ClassID'], $this->requestID);
            unset($_SESSION['csrf']);
            redirect_to_url('/dashboard');
        }

        $_SESSION['csrf'] = random_characters(24);

        echo '<div class="center-all">
        <form method="POST" action="/request/'.$this->requestID.'/delete" class="box text-center">
            <h2><i class="fas fa-trash text-red"></i> Kérvény törlése</h2>
            <hr>
            <p>Biztos, hogy törölni szeretné ezt a kérvényt?</p>
            <p><span>Figyelem!</span> Ez a művelet nem vonható vissza!</p>
            <input type="hidden" name="csrf" value="'.$_SESSION['csrf'].'">
            <p class="margin"><input type="submit" value="Végleges törlés"></p>
        </form>
        </div>';

        return true;
    }

    function parseRequest() {
        $this->requestID = $this->path[1];
        $this->requestData = $this->dataManager->GetPayRequestInfo($this->requestID, $_SESSION['ClassInfo']['ClassID']);
        
        if(!$this->requestData || is_null($this->requestData['RequestID']))
            return false;
        
        if(is_null($this->requestData['Deadline']))
            $this->requestData['Deadline'] = 'nincs';

        $this->requestDOM = '<tr>
            <td><span>'.$this->requestData['RequestedUsers'].'</span> fő</td>
            <td><span>'.price_format($this->requestData['RequiredTotal']).'</span> Ft</td>
            <td><span>'.price_format($this->requestData['PaidTotal']).'</span> Ft</td>
            <td>'.$this->requestData['Deadline'].'</td>
            <td>'.$this->requestData['FullName'].'</td>
            <td><span>'.$this->requestData['Date'].'</span></td>
        </tr>';
        return true;
    }

    function parseModify() {

        // Get debts before updating to compare values
        $debts = $this->dataManager->GetRequestDebts($this->requestData['RequestID']);

        if(isset($_POST['title'], $_POST['description'], $_POST['amounts'], $_POST['deadline']) && gettype($_POST['amounts']) == 'array') {
    
            $deadline = isset($_POST['no-deadline']) || strlen($_POST['deadline']) == 0 ? null : $_POST['deadline'];
            $description = trim($_POST['description']);
            $title = trim($_POST['title']);
            $validAmounts = array_filter($_POST['amounts'], function($am){ return (strlen($am) == 0 || preg_match('/^\d+$/', $am)); });
    
            if(strlen($title) < $this->pageConfig::REQUEST_MIN_TITLE_LENGTH || strlen($title) > $this->pageConfig::REQUEST_MAX_TITLE_LENGTH)
                $this->error = 'A cím csak '.$this->pageConfig::REQUEST_MIN_TITLE_LENGTH.' és '.$this->pageConfig::REQUEST_MAX_TITLE_LENGTH.' karakter közötti érték lehet.';
            elseif(strlen($description) < $this->pageConfig::REQUEST_MIN_DESCRIPTION_LENGTH || strlen($description) > $this->pageConfig::REQUEST_MAX_DESCRIPTION_LENGTH)
                $this->error = 'A leírás csak '.$this->pageConfig::REQUEST_MIN_DESCRIPTION_LENGTH.' és '.$this->pageConfig::REQUEST_MAX_DESCRIPTION_LENGTH.' karakter közötti érték lehet.';
            elseif(!is_null($deadline) && !strtotime($deadline))
                $this->error = 'A határidőt nem sikerült feldolgozni.';
            elseif(count($validAmounts) == 0)
                $this->error = 'A bekért összegek közül egy sem érvényes.';
            else {
                $success = $this->dataManager->ModifyRequest($this->requestID, $title, $description, $deadline);
                if(!$success) $this->error = 'Nem sikerült módosítani a kérelmet.';
            }
    
            if(!isset($this->error)) {
                $delete = [];
                $modify = [];
                $insert = [];

                // Calculate whether we need to delete or modify the existing data, or insert a new row.
                foreach($validAmounts as $user => $amount) {
                    // If user already has a debt
                    if(isset($debts[$user])) {
                        // ...and the new debt is zero, we need to delete;
                        if($amount == 0)
                            $delete[] = [$user, $this->requestID];
                        // otherwise we modify it to the new value if its new.
                        elseif($debts[$user][0]['RequiredAmount'] != $amount)
                            $modify[] = [$amount, $user, $this->requestID];
                    // But if user doesn't have debt, and now it has, we insert a new row
                    } elseif($amount > 0)
                        $insert[] = [$this->requestID, $user, $amount];
                }
                
                $this->dataManager->ModifyDebts($delete, $modify, $insert);
            }
        }

        $this->parseRequest();

        $members = $this->dataManager->GetClassMembers($this->requestData['ClassID']);
        $debts = $this->dataManager->GetRequestDebts($this->requestData['RequestID']);

        $this->membersDOM = '';
        foreach($members as $member) {
            $memberID = $member['UserID'];
            $requiredAmount = isset($debts[$memberID][0]['RequiredAmount']) ? $debts[$memberID][0]['RequiredAmount'] : 0;
            $this->membersDOM .= '<tr>
                <td>'.htmlentities($member['FullName']).'</td>
                <td>'.$member['DOB'].'</td>
                <td><input type="number" name="amounts['.$member['UserID'].']" value="'.$requiredAmount.'" class="amount"> Ft</td>
            </tr>';
        }
    }

    function loadModify() {
        $this->parseModify();
        
        $this->setIntro('Befizetési kérelem módosítása');
        $this->echoHeader();

        $deadline = $this->requestData['Deadline'];
        if($deadline == 'nincs') $deadline = '';

        echo '<form method="POST" action="/request/'.$this->requestID.'/modify" class="box">';

        if(isset($this->error))
            echo '<div class="box text-red fit-content align-center text-center"><h2>Hiba történt!</h2>'.$this->error.'</div>';

        echo '<div class="box">
                <h2><i class="fas fa-plus-circle text-green"></i> Kérvény adatai <small>(1/2)</small></h2>
                <hr>
                <label for="title">
                    <i class="fas fa-pen-square text-orange"></i> Kérvény új címe:
                    <input type="text" name="title" id="title" value="'.htmlentities($this->requestData['Subject']).'" placeholder="pl: Osztálykirándulás Székesfehérvárra" required>
                </label>
                <label for="deadline">
                    <i class="fas fa-calendar-plus text-orange"></i> Új határidő:
                    <input type="date" name="deadline" value="'.$deadline.'" min="'.date('Y-m-d').'">
                    (<label for="no-deadline">
                        <input type="checkbox" name="no-deadline" value="no-deadline" id="no-deadline" '.($deadline ? '' : 'checked').'> nincs határidő</small>
                    </label>)
                </label>
                <label for="description">
                    <i class="fas fa-book-open text-orange"></i> Új leírás:
                    <small>(max 2000 karakter)</small>
                    <textarea name="description" id="description" max="2000" required>'.htmlentities($this->requestData['Description']).'</textarea>
                </label>
            </div>
            <div class="box">
                <h2><i class="fas fa-users text-green"></i> Kérvényezettek kiválasztása <small>(2/2)</small></h2>
                <p>Ezen a részen módosíthatja, hogy kinek mekkora összeget kell befizetni.<br>
                Akinek a neve mellett 0 forint van, annak nem fog megjelenni a listában.<br>
                <span>Az értékek automatikusan módosulnak, ha fentebb megváltoztatja az árat.</span></p>
                <hr>
                <div class="small-height eo-table-wrapper">
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
            
            <input type="submit" value="Kérvény módosítása">
        </form>';
        return true;
    }

    // DEBTS

    function parseDebts()
    {
        $this->payDOM = '';
        $debts = $this->dataManager->GetDebtsByRequest($this->requestID);
        foreach($debts as $debt) {

            $fulfilled          = $debt['Amount'] >= $debt['RequiredAmount'] || $debt['IsDone'];
            $textColor          = $fulfilled ? 'green'  : 'red';
            $oppositeTextColor  = $fulfilled ? 'red'    : 'green';
            $icon               = $fulfilled ? 'times'  : 'check';
            $doneText           = $fulfilled ? 'igen'   : 'nem';
            $amount             = price_format($debt['Amount']);
            $requiredAmount     = price_format($debt['RequiredAmount']);
            $link               = '/debt/'.$debt['DebtID'];

            $this->payDOM .= '<tr>
                <td><span>'.$debt['FullName'].'</span></td>
                <td>'.$requiredAmount.' Ft <a href="'.$link.'/info" class="box-sm fas fa-pen"></a></td>
                <td class="text-'.$textColor.'">'.$amount.' Ft <a href="'.$link.'/edit" class="box-sm fas fa-pen"></a></td>
                <td class="text-'.$textColor.'">'.$doneText.' <a href="'.$link.'/done" class="box-sm fas fa-'.$icon.'-circle text-'.$oppositeTextColor.'"></a></td>
            </tr>'.PHP_EOL;
        }
    }

    private function loadDebts() {
        if(!$this->managePays) return false;
        $this->parseDebts();

        $this->setIntro('Befizetett összegek áttekintése és módosítása');
        $this->echoHeader();
        
        echo '<div class="box" id="debts-info">
            <h2>Befizetések kezelése</h2>
            <div class="eo-table-wrapper">
            <table class="eo-table">
                <thead>
                    <tr><th>Név</th><th>Bekért pénzösszeg</th><th>Befizetett összeg</th><th>Befizette?</th></tr>
                </thead>
                <tbody>
                    '.$this->payDOM.'
                </tbody>
            </table>
            </div>
            <p class="margin"><a href="/request/'.$this->requestID.'" class="btn">Vissza</a></p>
        </div>
        ';
        return true;
    }

}

$loaded = new RequestPage();

?>