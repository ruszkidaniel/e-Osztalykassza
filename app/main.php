<?php

    if(count($path) > 0) {
        if($path[0] == 'api') {
            require_once('./app/api/main.php');
            die();
        }
    }

    require_once('./frontend/main.php');
?>