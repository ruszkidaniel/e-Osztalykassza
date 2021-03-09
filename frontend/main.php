<?php

    if(!defined('EO_project_version'))
        die(header('Location: /'));

    require_once('./frontend/document_header.php');

    //require_once('./app/class/PageHandler.php');
    //$pageHandler = new PageHandler();

    if(!isLogged()) {
        require_once('./frontend/login_page.php');
    }
    require_once('./frontend/document_footer.php');