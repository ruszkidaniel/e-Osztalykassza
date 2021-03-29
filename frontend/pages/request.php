<?php

class DashboardPage extends BasePage {

    public function init($userPermissions, $globalPermissions) {
        if(!isset($_SESSION['ClassInfo']) || count($this->path) < 2)
            return false;

        $validRequest = $this->parseRequest();
        if(!$validRequest)
            return false;

        // Load permisisions

        $this->managePays = 
            array_search('MANAGE_MEMBERS', $userPermissions) ||
            array_search('MODIFY_ALL_CLASSES', $globalPermissions);

        $this->manageRequest = 
            array_search('MANAGE_MEMBERS', $userPermissions) ||
            array_search('MODIFY_ALL_CLASSES', $globalPermissions);

        // Parse debts

        if(count($this->path) > 3) {
            switch($this->path[2]) {
                case 'manage':
                    return $this->managePage($this->path[3]);
                    break;
                case 'user':
                    return $this->manageUser($this->path[3]);
                    break;
            }
        }
        
        $this->run();
        return true;
    }

    function manageUser($user) {
        return true;
    }

    function managePage($action) {
        switch($action) {
            case 'pay':
                if($this->managePays) {
                    $this->loadDebts();
                    return true;
                }
                return false;
            case 'request':
                if($this->manageRequest) {
                    $this->loadDebts();
                    return true;
                }
                return false;
        }
    }

    function parseRequest() {
        $this->requestID = $this->path[1];
        $this->requestData = $this->dataManager->GetPayRequestInfo($this->requestID);
        if(!$this->requestData)
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

    function parseDebts()
    {
        $this->payDOM = '';
        $debts = $this->dataManager->GetDebtsByRequest($this->requestID);
        foreach($debts as $debt) {
            $fulfilled = $debt['Amount'] >= $debt['RequiredAmount'] || $debt['IsDone'];
            $this->payDOM .= '<tr>
                <td><span>'.$debt['FullName'].'</span></td>
                <td>'.price_format($debt['RequiredAmount']).' Ft</td>
                <td class="text-'.($fulfilled?'green':'red').'">'.price_format($debt['Amount']).' Ft</td>
                <td class="text-'.($fulfilled?'green':'red').'">'.($debt['IsDone']?'igen':'nem').'</td>
                <td>
                    <a class="fas fa-pen" href="/request/'.$this->requestID.'/user/'.$debt['UserID'].'/edit" title="Szerkesztés"></a>
                    <a class="fas fa-check-circle" href="/request/'.$this->requestID.'/user/'.$debt['UserID'].'/done" title="Befizette"></a>
                </td>
            </tr>'.PHP_EOL;
        }
    }

    private function run() {
        $this->setIntro('Információk egy befizetési kérelemről');
        $this->echoHeader();
        echo '
        <div class="box" id="request">
            <div class="box">
                <h2>'.$this->requestData['Subject'].'</h2>
                <table class="eo-table">
                    <thead>
                        <tr><th>Érintettek száma</th><th>Szükséges pénzösszeg</th><th>Összegyűlt összeg</th><th>Határidő</th><th>Létrehozó</th><th>Létrehozás dátuma</th></tr>
                    </thead>
                    <tbody>
                        '.$this->requestDOM.'
                    </tbody>
                </table>
                <h3>Leírás</h3>
                <textarea disabled>'.htmlspecialchars($this->requestData['Description']).'</textarea>
            </div>';

        if($this->managePays)
            echo '<a class="btn" href="/request/'.$this->requestID.'/manage/pays">Befizetések kezelése</a>';
        
        if($this->manageRequest)
            echo '<a class="btn" href="/request/'.$this->requestID.'/manage/request">Kérvény módosítása</a>';
        
        echo '</div>';
    }

    private function loadDebts() {
        $this->parseDebts();
        
        echo '<div class="box">
            <h2>Befizetések kezelése</h2>
            <table class="eo-table">
                <thead>
                    <tr><th>Név</th><th>Bekért pénzösszeg</th><th>Befizetett összeg</th><th>Befizette?</th><th>Műveletek</th></tr>
                </thead>
                <tbody>
                    '.$this->payDOM.'
                </tbody>
            </table>
        </div>';
    }

}

$loaded = new DashboardPage();

?>