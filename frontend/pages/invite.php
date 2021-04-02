<?php

class InvitePage extends BasePage {

    public function init($userPermissions, $globalPermissions) {
        if(count($this->path) < 3) return false;

        $inviteData = $this->dataManager->GetInviteData($this->path[2]);
        if(!$inviteData) return false;

        $email = $this->dataManager->GetUserEmail($_SESSION['UserID']);
        if($inviteData['Email'] != $email) return false;

        $isAccept = $this->path[1] == 'accept';
        if($isAccept)
            $this->dataManager->AddMemberToClass($_SESSION['UserID'], $inviteData['ClassID']);

        $this->dataManager->HandleInviteResponse($inviteData['InviteCode'], $isAccept);

        redirect_to_url('/');
        return true;
    }

}

$loaded = new InvitePage();

?>