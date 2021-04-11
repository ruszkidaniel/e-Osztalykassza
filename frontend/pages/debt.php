<?php

class DashboardPage extends BasePage {

    public function init($userPermissions, $globalPermissions) {
        if(!isset($_SESSION['ClassInfo']) || count($this->path) < 2)
            return false;

        $this->debtData = $this->dataManager->GetDebtInfo($this->path[1], $_SESSION['ClassInfo']['ClassID']);
        if(!$this->debtData)
            return false;
    
        // Load permisisions

        $this->managePays = 
            $_SESSION['ClassInfo']['OwnerID'] == $_SESSION['UserID'] ||
            array_search('MANAGE_PAYS', $userPermissions) !== false ||
            array_search('MODIFY_ALL_CLASSES', $globalPermissions) !== false;

        if(!$this->managePays) return false;

        $this->run();
        return true;
    }

    private function run() {
        $this->setIntro('Információk egy befizetésről');
        $this->echoHeader();

        if(count($this->path) > 2) {
            switch($this->path[2]) {
                case 'info':
                case 'edit':
                    return $this->loadInfo();
                case 'done':
                    return $this->parseDone();
            }
        }
        
    }
    
    // Load and edit required amount

    private function parseInfo() {
        
        // Parse new data
        if(isset($_POST['amount'], $_POST['requiredAmount'])) {
            $this->dataManager->SetDebtAmounts($_SESSION['UserID'], $this->path[1], $_POST['amount'], $_POST['requiredAmount']);
        }
        elseif(isset($_POST['newPay'])) {
            $this->dataManager->AddDebtAmount($_SESSION['UserID'], $this->path[1], $_POST['newPay']);
        }

        // Reload info
        if($_SERVER['REQUEST_METHOD'] == 'POST') 
            $this->debtData = $this->dataManager->GetDebtInfo($this->path[1], $_SESSION['ClassInfo']['ClassID']);

        $logs = $this->dataManager->GetPaylog($this->path[1]);
        $this->infoDOM = '';
        foreach($logs as $log) {
            $this->infoDOM .= '<tr>
                <td>'.$log['Date'].'</td>
                <td>'.$this->logEventToText($log['Type']).'</td>
                <td>'.$this->logAmountToText($log['Type'], $log['Amount']).'</td>
                <td>'.htmlentities($log['FullName']).'</td>
            </tr>'.PHP_EOL;
        }

    }
    
    private function loadInfo() {
        $this->parseInfo();
        if(is_null($this->debtData['Amount']))
            $this->debtData['Amount'] = 0;

        echo '
        <div class="box flex">
            <form method="POST" action="/debt/'.$this->path[1].'/edit" class="box fit-content align-center text-center">
                <h2>Befizetési információk</h2>
                <hr>
                <label for="name">Befizető neve: <input type="text" id="name" disabled value="'.htmlentities($this->debtData['FullName']).'"></label>
                <label for="requiredAmount">Bekért összeg: <input type="number" id="requiredAmount" name="requiredAmount" value="'.$this->debtData['RequiredAmount'].'"> Ft</label>
                <label for="amount">Kifizetve: <input type="number" id="amount" name="amount" value="'.$this->debtData['Amount'].'"> Ft</label>
                <button type="submit"><i class="fas fa-save text-green"></i> Módosítás</button>
            </form>
            <div class="box full-width margin">
                <h2>Befizetési napló</h2>
                <hr>
                <div class="small-height">
                <table class="eo-table">
                    <thead>
                        <tr><th>Dátum</th><th>Esemény</th><th>Érték</th><th>Kezelő</th></tr>
                    </thead>
                    <tbody>'.$this->infoDOM.'</tbody>
                </table>
                </div>
                <form method="POST" action="/debt/'.$this->path[1].'/edit">
                    <label for="newPay">Új befizetés: <input type="number" id="newPay" name="newPay"> Ft</label>
                    <button type="submit"><i class="fas fa-plus-circle text-green"></i> Rögzítés</button>
                </form>
            </div>
        </div>
        <a class="btn" href="/request/'.$this->debtData['RequestID'].'/debts"><i class="fas fa-chevron-circle-left text-green"></i> Vissza</a>';
    }

    // Parse done status

    private function parseDone() {
        $this->dataManager->SetDebtDone($_SESSION['UserID'], $this->debtData['DebtID'], !$this->debtData['IsDone']);
        redirect_to_url('/request/'.$this->debtData['RequestID'].'/debts');
    }

    function logEventToText($type) {
        $texts = [
            'Bekért összeg kifizetve',
            'Befizetett összeg módosítva',
            'Új befizetés'
        ];
        return isset($texts[$type]) ? $texts[$type] : '-';
    }

    function logAmountToText($type, $amount) {
        if($type == 0)
            return '<span class="text-'.($amount ? 'green' : 'red').'">'.($amount ? 'igen' : 'nem').'</span>';
        
        return '<span>'.price_format($amount).'</span> Ft';
    }

}

$loaded = new DashboardPage();

?>