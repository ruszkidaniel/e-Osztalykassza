<?php

    if(count($path) > 0) {
        if($path[0] == 'api') {
            require_once('./app/api/main.php');
            die();
        }
        elseif($path[0] == 'logout') {
            session_unset();
            redirect_to_url('/');
        }
    }

    // Load permissions

    $userClassPermissions = [];
    $userGlobalPermissions = [];
    SyncPermissions();
    LoadPermissions();

    function SyncPermissions() {
        global $dataManager;
        if(!isset($_SESSION['UserID'])) return;

        if(isset($_SESSION['ClassInfo']['ClassID']))
            $_SESSION['ClassPermissions'] = $dataManager->GetClassPermissions($_SESSION['ClassInfo']['ClassID'], $_SESSION['UserID']);

        $_SESSION['GlobalPermissions'] = $dataManager->GetGlobalPermissions($_SESSION['UserID']);
    }

    function LoadPermissions() {
        global $pageConfig, $userClassPermissions, $userGlobalPermissions;

        if(isset($_SESSION['ClassPermissions'])) {
            $classPermissions = new PermissionManager($pageConfig::CLASS_PERMISSIONS);
            $userClassPermissions = $classPermissions->getPermissions($_SESSION['ClassPermissions']);
        }
        if(isset($_SESSION['GlobalPermissions'])) {
            $globalPermissions = new PermissionManager($pageConfig::GLOBAL_PERMISSIONS);
            $userGlobalPermissions = $globalPermissions->getPermissions($_SESSION['GlobalPermissions']);
        }
   
    }

    // Unselect class if not a member

    if(isset($_SESSION['ClassInfo']['ClassID'], $_SESSION['UserID'])) {
        $inClass = $dataManager->FindUserInClass($_SESSION['ClassInfo']['ClassID'], $_SESSION['UserID']);
        if($inClass === false)
            unset($_SESSION['ClassInfo']);
    }

    // Get invites

    if(isset($_SESSION['UserID'])) {
        $invites = $dataManager->GetPendingInvitesByID($_SESSION['UserID']);
        if(count($invites)) $_SESSION['PendingInvites'] = $invites;
        elseif(isset($_SESSION['PendingInvites'])) unset($_SESSION['PendingInvites']);
    }

    require_once('./frontend/main.php');
?>