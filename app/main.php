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

    $userClassPermissions = [];
    $userGlobalPermissions = [];
    LoadPermissions();

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

    require_once('./frontend/main.php');
?>