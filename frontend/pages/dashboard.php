<?php

class DashboardPage extends BasePage {

    public function init($userPermissions, $globalPermissions) {
        if(!isset($_SESSION['ClassInfo']))
            return false;

        // Fetch permissions
        
        $this->manageRequests = 
            $_SESSION['ClassInfo']['OwnerID'] == $_SESSION['UserID'] ||
            array_search('MANAGE_REQUESTS', $userPermissions) !== false ||
            array_search('MODIFY_ALL_CLASSES', $globalPermissions) !== false;
        
        $this->classInfo = $this->dataManager->GetDetailedClassData($_SESSION['ClassInfo']['ClassID']);
        $this->classInfo['AdminFullName'] = '';

        // Find admin name among the members
        $owner = $this->classInfo['info']['OwnerID'];
        $admin = array_filter($this->classInfo['members'], function($x) use ($owner) { return $x['UserID'] == $owner; });
        if(count($admin) == 1) $admin = array_values($admin)[0]['FullName'];
        else $admin = 'n/a';

        // Set the admin's name
        $this->classInfo['AdminFullName'] = $admin;
        $this->isAdmin = $this->classInfo['info']['OwnerID'] == $_SESSION['UserID'];

        // Get user debts
        $this->debts = $this->dataManager->GetUserDebts($_SESSION['ClassInfo']['ClassID'], $_SESSION['UserID']);
        $this->unfulfilledPayments = count($this->debts) - count(array_filter($this->debts, [$this, 'IsPaymentFulfilled']));

        // Generated table DOM
        $this->debtDOM = '';
        foreach($this->debts as $debt) {
            $fulfilled = $this->IsPaymentFulfilled($debt);
            if(is_null($debt['Deadline'])) $debt['Deadline'] = 'nincs';
            $this->debtDOM .= '<tr>
                <td><a href="/request/'.$debt['RequestID'].'">'.$debt['Subject'].'</a></td>
                <td>'.price_format($debt['RequiredAmount']).' Ft</td>
                <td class="text-'.($fulfilled ? 'green':'red').'">'.price_format($debt['Amount']).' Ft</td>
                <td>'.$debt['Deadline'].'</td></tr>';
        }

        // Load admin section
        $this->LoadAdminSection();

        $this->run();
        return true;
    }

    private function run() {
        $this->setIntro('Információk az osztályról és a befizetési kérelmekről');
        $this->echoHeader();
        echo '
        <div class="box" id="dashboard">
            <div id="top">
                <div class="box meta">
                    <h2>'.htmlentities($_SESSION['ClassInfo']['ClassName']).' <small>osztály adatlapja</small></h2>
                    <hr>
                    <p class="text-center"><i class="fas fa-users"></i> Osztály tagjainak száma:</p>
                    <p class="amount"><span>'.count($this->classInfo['members']).'</span> / <small>'.$this->classInfo['info']['MaxMembers'].'</small></p>
                    <p>e-Osztálykassza adminisztrátor:</p>
                    <p id="admin"><i class="fas fa-user-shield"></i><br><span>'.$this->classInfo['AdminFullName'].'</span>'.
                    ($this->isAdmin ? ' (Ön)':'').'</p>
                </div>
                <div class="box" id="description">
                    <h3>Információk</h3>
                    <textarea disabled>'.htmlspecialchars($this->classInfo['info']['Description']).'</textarea>
                </div>
                <div class="box meta" id="debts">
                    <h3>Befizetések teljesítése</h3>
                    <hr>
                    <div class="center-all">
                        <p class="debt-info"><i class="fas fa-'.($this->unfulfilledPayments == 0 ? 'check-circle text-green' : 'exclamation-triangle text-red').'"></i></p>
                        <p>Önnek jelenleg</p>
                        <p class="amount '.($this->unfulfilledPayments == 0 ? 'text-green">NINCS' : 'text-red"><span>'.$this->unfulfilledPayments.'</span> darab').'</p>
                        <p>befizetetlen tartozása'.($this->unfulfilledPayments > 0 ? ' van' : '').'.</p>
                    </div>
                </div>
            </div>
            <hr>
            <div class="box">
                <h2>Befizetési kérelmek <small>('.count($this->debts).' darab)</small></h2>
                <table class="eo-table">
                    <thead>
                        <tr><th>Megnevezés</th><th>Szükséges pénzösszeg</th><th>Teljesített összeg</th><th>Határidő</th></tr>
                    </thead>
                    <tbody>
                        '.$this->debtDOM.'
                    </tbody>
                </table>
            </div>
            '.$this->adminDOM.'
        </div>
        ';
    }

    function LoadAdminSection() {
        $this->adminDOM = '';
        
        if($this->manageRequests)
            $this->adminDOM .= '<a class="btn" href="/createrequest"><i class="fas fa-clipboard-list text-green"></i> Új kérvény</a>';
        
    }

    function IsPaymentFulfilled($debt) {
        return $debt['Amount'] >= $debt['RequiredAmount'] || $debt['IsDone'];
    }

}

$loaded = new DashboardPage();

?>