<?php

class MembersPage extends BasePage {

    function __construct($permissionManager) {
        $this->permissionManager = $permissionManager;
    }

    public function init($userPermissions, $globalPermissions) {
        if(!isset($_SESSION['ClassInfo']))
            return false;

        // Fetch permissions
                
        $this->manageMembers = 
            $_SESSION['ClassInfo']['OwnerID'] == $_SESSION['UserID'] ||
            array_search('MANAGE_MEMBERS', $userPermissions) !== false ||
            array_search('MODIFY_ALL_CLASSES', $globalPermissions) !== false;
        if(!$this->manageMembers) return false;
                
        $this->managePermissions = 
            $_SESSION['ClassInfo']['OwnerID'] == $_SESSION['UserID'] ||
            array_search('MANAGE_PERMISSIONS', $userPermissions) !== false ||
            array_search('MODIFY_ALL_CLASSES', $globalPermissions) !== false;

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

        if(in_array('permission', $this->path) && count($this->path) > 4)
            $this->ParsePermissionChange();

        // Load members

        $this->membersDOM = '';
        foreach($this->classInfo['members'] as $member) {
            $self = $member['UserID'] == $_SESSION['UserID'];
            $classOwner = $member['UserID'] == $this->classInfo['info']['OwnerID'];

            if($classOwner)
                $permissionLinks = ['<i class="fas fa-crown text-orange" title="Osztály adminisztrátor (mindenre van joga)"></i>'];
            else
                $permissionLinks = $this->generatePermissionLinks($member['UserID'], $member['Permissions']);
            
            $this->membersDOM .= '<tr>
                <td><span>'.htmlentities($member['FullName']).'</span>'.($self ?' (Ön)':'').'</td>
                <td>'.$member['DOB'].'</td>
                <td>'.implode(' ',$permissionLinks).'</td>
                <td>'.(!$self && !$classOwner ? '<a href="/members/kick/'.$member['UserID'].'" class="fas fa-sign-out-alt text-red box-sm" title="Kirúgás"></a>' : '').'</td>
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
        $this->setIntro('Osztály tagjainak és meghívók állapotának áttekintése');
        $this->echoHeader();
        echo '<div class="box" id="dashboard">
            <div class="box">
                <h2>'.htmlentities($this->classInfo['info']['ClassName']).' <small>osztály tagjai (<span>'.count($this->classInfo['members']).'</span> fő)</small></h2>
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
                <h2>Meghívók <small>az osztályba (<span>'.$this->pendingInvites.'</span> függőben)</small></h2>
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

    private function GeneratePermissionLinks($userid, $userpermhash) {
        if(!$this->managePermissions) return ['-'];

        $linkTemplate = '<a href="/members/permission/{{userid}}/{{hash}}/{{perm}}" class="fas fa-{{icon}} text-{{color}} box-sm" title="{{title}}"></a>';

        // Order of permissions must match with pageConfig::CLASS_PERMISSIONS
        $permissions = [
            ['Tagok kezelése', 'users'], ['Befizetések kezelése', 'dollar-sign'], 
            ['Osztály beállításainak módosítása', 'cogs'], ['Jogosultságok kezelése', 'users-cog'],
            ['Kérelmek kezelése', 'money-bill-wave']
        ];

        $links = [];
        foreach($permissions as $i => $perm) {
            $title = $perm[0];
            $icon = $perm[1];

            $permid = pow(2, $i);
            if(!isset($this->permissionManager->permissions[$permid])) continue;
            
            $permname = $this->permissionManager->permissions[$permid];
            $have = $this->permissionManager->hasPermission($userpermhash, $permname);
            
            $hash = $this->GetPermissionLinkHash($userid, $permid);
            $links[] = str_replace(
                ['{{userid}}', '{{hash}}', '{{perm}}', '{{icon}}', '{{color}}', '{{title}}'],
                [$userid, $hash, $permid, $icon, $have?'green':'red', $title],
                $linkTemplate
            );
        }
        return $links;
    }

    private function GetPermissionLinkHash($userid, $permid) {
        if(!isset($this->permissionManager->permissions[$permid])) return 'e';
        $permission = $this->permissionManager->permissions[$permid];
        return substr(hash('sha256', hash('sha256', $userid . $permission) . $this->pageConfig::PAGE_SECRET), 4, 10);
    }

    // INVITE

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

        mail($to,$subject,$message,$headers);

        $this->success = $this->dataManager->CreateInvitations($_SESSION['UserID'], $_SESSION['ClassInfo']['ClassID'], [['Email' => $_POST['email'], 'Code' => $inviteCode]]);
    }

    private function LoadInvite() {
        $this->ParseInvite();
        
        $this->setIntro('Új tag meghívása az osztályba');
        $this->echoHeader();

        echo '<div class="box">
        <div class="box">
            <h2>Új tag meghívása</h2>
            <p>Ezen az oldalon hívhat meg osztályába emailcím alapján tagokat.</p>
            <p>A tagoknak nem szükséges regisztrált tagnak lenni a meghívás elfogadásához, azonban az elfogadást követően egy fiókot kell regisztrálniuk, ami hozzá lesz rendelve az osztályhoz.</p>
        </div>';

        if($this->limitReached) {
            echo '<div class="box">
            <h2>Nem lehet több felhasználót meghívni!</h2>
            <p>Az osztály elérte a maximális létszámát.</p>
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
            <input type="submit" value="Meghívás az osztályba">
        </form>
        </div>';
        
        if(isset($this->success))
            echo '<p class="box text-'.($this->success?'green':'red').'">'.($this->success ? 'Sikeresen elküldte a meghívót a megadott címre.' : 'Nem sikerült elküldeni a meghívót.').'</p>';

        if(isset($this->error))
            echo '<p class="box text-orange">Hiba! '.$this->error.'</p>';
        return true;
    }

    // PERMISSIONS

    private function ParsePermissionChange() {
        // Can't do anything without permissions
        if(!$this->managePermissions) return;
        
        $userid = $this->path[2];
        $hash = $this->path[3];
        $permid = $this->path[4];

        // Can't change the class owner's permission
        if($userid == $this->classInfo['info']['OwnerID']) return;

        // Invalid permission
        if(!isset($this->permissionManager->permissions[$permid])) return;
        
        // Incorrect hash
        if($hash != $this->GetPermissionLinkHash($userid, $permid)) return;
        
        // Invalid user
        $targetId = array_search($userid, array_column($this->classInfo['members'], 'UserID'));
        if($targetId === false) return;

        // All data correct, update permission
        $target = $this->classInfo['members'][$targetId];
        $have = $this->permissionManager->hasPermission($target['Permissions'], $this->permissionManager->permissions[$permid]);

        if($have)
            $newHash = $this->permissionManager->revokePermission($target['Permissions'], $this->permissionManager->permissions[$permid]);
        else
            $newHash = $this->permissionManager->addPermission($target['Permissions'], $this->permissionManager->permissions[$permid]);
        
        $this->dataManager->UpdateUserPermission($this->classInfo['info']['ClassID'], $userid, $newHash);
        $this->classInfo['members'][$targetId]['Permissions'] = $newHash;
    }

    // UNINVITE

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
            echo '<p class="text-'.($this->success?'green':'red').'">'.($this->success ? 'Sikeresen visszavonta a meghívást.' : 'Nem sikerült visszavonni a meghívót.').'</p>';

        echo '</form>';
        return true;
    }

    // KICK

    private function ParseKick() {
        $targetID = $this->path[2];
        if($targetID == $_SESSION['UserID']) return false;
        if($targetID == $this->classInfo['info']['OwnerID']) return false;

        $target = $this->dataManager->FindUserInClass($this->classInfo['info']['ClassID'], $targetID);
        
        $this->kickInvalidUser = $target === false;
        if($this->kickInvalidUser) return true;

        $this->kickFullName = $target;

        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->success = $this->dataManager->KickUserFromClass($this->classInfo['info']['ClassID'], $targetID);
        }
        return true;
    }

    private function LoadKick() {
        $canKick = $this->ParseKick();
        if(!$canKick) {
            echo '<div class="center-all">
            <div class="box text-center">
            <h2>Nem rúghatja ki önmagát, és az Osztály adminisztrátort!</h2>
            <hr>
            <p class="margin">Kérjük válasszon ki más felhasználót.</p>
            </div></div>';
            return true;
        }

        $this->setIntro('Felhasználó eltávolítása az osztályból');
        $this->echoHeader();

        if($this->kickInvalidUser) {
            echo '<div class="box">
                <h2>Nem található a felhasználó!</h2>
                <p>A keresett felhasználó ezzel az azonosítóval nem található meg a kiválasztott osztályban!</p>
                <p>Kérjük próbálja újra, és győződjön meg arról, hogy megfelelő osztályt választott ki!</p>
            </div>';
            return true;
        }

        echo '<div class="box align-center text-center fit-content">
            <h2>Tag kirúgása</h2>
            <hr>
            <p class="margin">Biztosan ki szeretné rúgni <span>'.htmlentities($this->kickFullName).'</span> felhasználót az osztályból?</p>
            <form method="POST">
                <input type="submit" value="Kirúgás">
            </form>';
            
        if(isset($this->success))
            echo '<p class="margin text-'.($this->success?'green':'red').'">'.($this->success ? 'Sikeresen kirúgta a felhasználót az osztályból.' : 'Nem sikerült kirúgni a felhasználót.').'</p>';

        echo '</div>';

        return true;
    }

}

$permissionManager = new PermissionManager($pageConfig::CLASS_PERMISSIONS);

$loaded = new MembersPage($permissionManager);


?>