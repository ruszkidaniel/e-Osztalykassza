<?php

class MembersPage extends BasePage {

    public function init($userPermissions, $globalPermissions) {
        if(!isset($_SESSION['ClassInfo']))
            return false;

        // Fetch permissions
                
        $this->manageMembers = 
            $_SESSION['ClassInfo']['OwnerID'] == $_SESSION['UserID'] ||
            array_search('MANAGE_MEMBERS', $userPermissions) !== false ||
            array_search('MODIFY_ALL_CLASSES', $globalPermissions) !== false;
        if(!$this->manageMembers) return false;

        // Request class data

        $this->classInfo = $this->dataManager->GetDetailedClassData($_SESSION['ClassInfo']['ClassID']);

        $this->pendingInvites = array_filter($this->classInfo['invites'], function($inv){ return $inv['Status'] == 'pending'; });
        $this->limitReached = (count($this->classInfo['members']) + count($this->pendingInvites)) >= $this->classInfo['info']['MaxMembers'];

        // Fetch path

        if(in_array('invite', $this->path))
            return $this->LoadInvite();

        if(in_array('uninvite', $this->path) && count($this->path) > 2)
            return $this->LoadUninvite();

        if(in_array('kick', $this->path) && count($this->path) > 2)
            return $this->LoadKick();

        // Load members

        $this->membersDOM = '';
        foreach($this->classInfo['members'] as $member) {
            $self = $member['UserID'] == $_SESSION['UserID'];
            $this->membersDOM .= '<tr>
                <td><span>'.htmlentities($member['FullName']).'</span>'.($self ?' (Ön)':'').'</td>
                <td>'.$member['DOB'].'</td>
                <td>-</td>
                <td>'.(!$self ? '<a href="/members/kick/'.$member['UserID'].'" class="fas fa-sign-out-alt text-red" title="Kirúgás"></a>' : '').'</td>
            </tr>'.PHP_EOL;
        }
        
        // Load invites
        
        $this->invitesDOM = '';
        $this->pendingInvites = 0;
        foreach($this->classInfo['invites'] as $inv) {
            $actions = [];
            if($inv['Status'] == 'pending')
                $actions[] = '<a href="/members/uninvite/'.$inv['InviteID'].'" class="fas fa-user-times text-red" title="Meghívás visszavonása"></a>';

            $this->invitesDOM .= '<tr>
                <td>'.$inv['Email'].'</td>
                <td>'.htmlentities($inv['FullName']).'</td>
                <td>'.$inv['Date'].'</td>
                <td class="invite-'.$inv['Status'].'">'.$this->GetStatusMessage($inv['Status']).'</td>
                <td>'.implode(' ',$actions).'</td>
            </tr>';
            if($inv['Status'] == 'pending')
                $this->pendingInvites++;
        }

        $this->run();
        return true;
    }

    private function run() {
        $this->setIntro('Csoport tagjainak és meghívók állapotának áttekintése');
        $this->echoHeader();
        echo '<div class="box" id="dashboard">
            <div class="box">
                <h2>'.htmlentities($this->classInfo['info']['ClassName']).' <small>csoport tagjai (<span>'.count($this->classInfo['members']).'</span> fő)</small></h2>
                <div class="small-height">
                <table class="eo-table">
                    <thead>
                        <tr><th>Teljes név</th><th>Születési dátum</th><th>Jogosultságok kezelése</th><th>Műveletek</th></tr>
                    </thead>
                    <tbody>
                        '.$this->membersDOM.'
                    </tbody>
                </table>
                </div>
                <p class="margin"><a href="/members/invite" class="btn"><i class="fas fa-plus-circle text-green"></i> Új tag meghívása</a></p>
            </div>
            <div class="box">
                <h2>Meghívók <small>a csoportba (<span>'.$this->pendingInvites.'</span> függőben)</small></h2>
                <div class="small-height">
                <table class="eo-table">
                    <thead>
                        <tr><th>Meghívott email címe</th><th>Meghívó</th><th>Meghívás dátuma</th><th>Meghívás állapota</th><th>Műveletek</th></tr>
                    </thead>
                    <tbody>
                        '.$this->invitesDOM.'
                    </tbody>
                </table>
                </div>
            </div>
        </div>';
    }

    private function GetStatusMessage($status) {
        $messages = [
            'pending' => 'Függőben',
            'accepted' => 'Elfogadva',
            'declined' => 'Elutasítva',
            'canceled' => 'Visszavonva',
        ];
        if(isset($messages[$status])) return $messages[$status];

        return $status;
    }

    private function ParseInvite() {
        if(!isset($_POST['email'])) return;

        if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $this->error = 'Hibás email cím lett megadva.';
            return;
        }

        $isMember = $this->dataManager->IsMemberInClass($_POST['email'], $this->classInfo['info']['ClassID']);

        $unsubscribed = count($this->dataManager->GetUnsubscribedEmails([$_POST['email']])) == 1;
        if($unsubscribed) {
            $this->error = 'Ezzel az email címmel korábban leiratkoztak a szolgáltatásról.';
            return;
        }

        $alreadyExists = count($this->dataManager->FindPendingInviteByEmail($this->classInfo['info']['ClassID'], $_POST['email'])) > 0;
        if($alreadyExists) {
            $this->error = 'Erre az email címre már van elfogadásra váró meghívó.';
            return;
        }

        $inviteTimeout = $this->dataManager->FindInvite($_POST['email']) > 0;
        if($inviteTimeout) {
            $this->error = 'Erre a címre már volt meghívó küldve az elmúlt 24 órában.';
            return;
        }

        $url = $this->pageConfig::WEBSITE_ADDRESS;
        $email = $this->pageConfig::INVITE_MAIL_TEMPLATE;

        $inviteCode = random_characters(32);
        $inviteurl = $url . 'invite/accept/' . $inviteCode;
        $declineurl = $url . 'invite/decline/' . $inviteCode;
        $optouturl = $url . 'invite/optout/' . $inviteCode;

        $message = str_replace(['{{inviteurl}}', '{{declineurl}}', '{{optouturl}}'], [$inviteurl, $declineurl, $optouturl], $email);
        
        $to = $_POST['email'];

        $subject = "e-Osztálykassza meghívó";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

        $headers .= 'From: <noreply@'.$this->pageConfig::WEBSITE_DOMAIN.'>' . "\r\n";

        //mail($address,$subject,$message,$headers);

        $this->success = $this->dataManager->CreateInvitations($_SESSION['UserID'], $_SESSION['ClassInfo']['ClassID'], [['Email' => $_POST['email'], 'Code' => $inviteCode]]);
    }

    private function LoadInvite() {
        $this->ParseInvite();
        
        $this->setIntro('Új tag meghívása a csoportba');
        $this->echoHeader();

        echo '<div class="box">
            <h2>Új csoporttag meghívása</h2>
            <p>Ezen az oldalon hívhat meg a csoportjába emailcím alapján tagokat.</p>
            <p>A tagoknak nem szükséges regisztrált tagnak lenni a meghívás elfogadásához, azonban az elfogadást követően egy fiókot kell regisztrálniuk, ami hozzá lesz rendelve a csoporthoz.</p>
        </div>';

        if($this->limitReached) {
            echo '<div class="box">
            <h2>Nem lehet több felhasználót meghívni!</h2>
            <p>A csoport elérte a maximális létszámát.</p>
            <p>Ha több tagot szeretne meghívni, bővítse a maximális létszámot!</p>
        </div>';
            return true;
        }

        echo '
        <form action="/members/invite" method="POST" class="box">
            <h2>Felhasználó emailcíme</h2>
            <p>A meghíváshoz csak egy emailcímet kell megadni, a felhasználó és publikus nevet a meghívást követően állítja be magának a felhasználó.</p>
            <p>A meghívás állapotát az előző oldalon követheti nyomon.</p>
            <label for="email">
                Email cím:
                <input type="email" name="email" id="email" required>
            </label>
            <input type="submit" value="Meghívás a csoportba">
        </form>';
        
        if(isset($this->success))
            echo '<p class="box text-'.($this->success?'green':'red').'">'.($this->success ? 'Sikeresen elküldte a meghívót a megadott címre.' : 'Nem sikerült elküldeni a meghívót.').'</p>';

        if(isset($this->error))
            echo '<p class="box text-orange">Hiba! '.$this->error.'</p>';
        return true;
    }

    private function ParseUninvite() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->success = $this->dataManager->Uninvite($this->path[2]);
        }
    }

    private function LoadUninvite() {
        $this->ParseUninvite();
        
        $this->setIntro('Meghívó visszavonása');
        $this->echoHeader();

        echo '<form action="/'.implode('/',$this->path).'" method="POST" class="box fit-content align-center text-center">
            <h2>Meghívó visszavonása</h2>
            <hr>
            <p>Ha érvénytelenné szeretné tenni a meghívót, amit elküldött, kattintson a gombra:</p>
            <input type="submit" value="Visszavonás">';

        if(isset($this->success))
            echo '<p>'.($this->success ? 'Sikeresen visszavonta a meghívást.' : 'Nem sikerült visszavonni a meghívót.').'</p>';

        echo '</form>';
        return true;
    }

    private function ParseKick() {
        $targetID = $this->path[2];
        if($targetID == $_SESSION['UserID']) return false;

        $target = $this->dataManager->FindUserInClass($this->classInfo['info']['ClassID'], $targetID);
        
        $this->kickInvalidUser = $target === false;
        if($this->kickInvalidUser) return;

        $this->kickFullName = $target;

        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->success = $this->dataManager->KickUserFromClass($this->classInfo['info']['ClassID'], $targetID);
        }
    }

    private function LoadKick() {
        $canKick = $this->ParseKick();
        if(!$canKick) {
            echo '<div class="center-all">
            <div class="box text-center">
            <h2>Nem rúghatja ki önmagát!</h2>
            <hr>
            <p class="margin">Kérjük válasszon ki más felhasználót.</p>
            </div></div>';
            return true;
        }

        $this->setIntro('Felhasználó eltávolítása a csoportból');
        $this->echoHeader();

        if($this->kickInvalidUser) {
            echo '<div class="box">
                <h2>Nem található a felhasználó!</h2>
                <p>A keresett felhasználó ezzel az azonosítóval nem található meg a kiválasztott csoportban!</p>
                <p>Kérjük próbálja újra, és győződjön meg arról, hogy megfelelő csoportot választott ki!</p>
            </div>';
            return true;
        }

        echo '<div class="box">
            <h2>Csoporttag kirúgása</h2>
            <p>Biztosan ki szeretné rúgni <span>'.htmlentities($this->kickFullName).'</span> felhasználót a csoportból?</p>
            <form method="POST">
                <input type="submit" value="Kirúgás">
            </form>';
            
        if(isset($this->success))
            echo '<p class="margin">'.($this->success ? 'Sikeresen kirúgta a felhasználót a csoportból.' : 'Nem sikerült kirúgni a felhasználót.').'</p>';

        echo '</div>';

        return true;
    }

}

$loaded = new MembersPage();


?>