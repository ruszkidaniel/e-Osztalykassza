<?php

class InviteManager {

    function __construct($dataManager, $inviteCode) {
        $this->dataManager = $dataManager;
        $this->inviteCode = $inviteCode;
        if($inviteCode !== false)
            $this->loadInvite();
    }

    function loadInvite() {
        $this->inviteData = $this->dataManager->GetInviteData($this->inviteCode);
        if($this->inviteData === false) return false;
    }

    function ask($isAccept) {
        if($this->inviteCode === false) return $this->noInviteCode();
        if($this->inviteData === false) return $this->invalidInviteCode();
        if($this->inviteData['Status'] != 'pending') return $this->inviteAlreadyUsed();
        
        $_SESSION['csrf'] = random_characters(24);
        
        return '<h2 class="align-center text-center">Meghívó '.($isAccept?'elfogadása':'elutasítása').'</h2>
            <hr>
            <p><strong>'.htmlentities($this->inviteData['FullName']).'</strong> meghívta Önt az e-Osztálykassza szolgáltatásra.</p>
            <p>Az osztály neve: <strong>'.htmlentities($this->inviteData['ClassName']).'</strong></p>
            <p>Amennyiben el szeretné '.($isAccept?'fogadni':'utasítani').' a meghívót, kattintson a tovább gombra! Ellenkező esetben zárja be ezt az oldalt.</p>
            <form method="POST" action="/invite">
                <input type="hidden" name="csrf" value="'.$_SESSION['csrf'].'">
                <input type="hidden" name="inviteCode" value="'.$this->inviteCode.'">
                <input type="hidden" name="accept" value="'.($isAccept?'true':'false').'">
                <input type="submit" value="Tovább" class="btn">
            </form>';
    }

    function handleResponse($isAccept) {
        if($this->inviteData == false) return $this->noInviteCode();
        if($this->inviteData['Status'] != 'pending') return $this->invalidInviteCode();
        
        return $this->dataManager->HandleInviteResponse($this->inviteData['InviteCode'], $isAccept);
    }

    function getInviteData() {
        return $this->inviteData;
    }

    function noInviteCode() {
        return '<h2 class="align-center text-center">Nincs megadva meghívó!</h2>
        <hr>
        <p class="text-center">Nem található meghívó a linkben.<br>Másolja ki a teljes hivatkozást, a végén lévő kóddal együtt!</p>';
    }

    function invalidInviteCode() {
        return '<h2 class="align-center text-center">Nem létező meghívó!</h2>
        <hr>
        <p class="text-center">Nem található a meghívó az adatbázisban.<br>Győződjön meg róla, hogy megfelelően másolta ki a hivatkozást!</p>';
    }

    function inviteAlreadyUsed() {
        return '<h2 class="align-center text-center">Felhasznált meghívó!</h2>
        <hr>
        <p class="text-center">Ezt a meghívót már valaki felhasználta! Kérjen újbóli meghívást az osztály készítőjétől.</p>';
    }

}

?>