<?php
    define('EO_project_version', '0.1');

    $cookiesAllowed = isset($_COOKIE['cookie-allowed']);

    require_once('./app/database.php');
    require_once('./app/sessionhandler.php');

    