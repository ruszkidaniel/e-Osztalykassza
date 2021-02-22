<?php
    define('EO_project_version', '0.1');

    $cookiesAllowed = isset($_COOKIE['cookie-allowed']);

    require_once('./app/database.php');
    require_once('./app/sessionhandler.php');
    require_once('./app/functions.php');

    // todo: title, egyéni jogosultságokkal elérhető fájlok inklúdálásának beállítása

    require_once('./frontend/document_header.php');
    
    if(!isLogged()) {
        require_once('./frontend/login_page.php');
    }