<?php

    $host = 'localhost';
    $user = '';
    $pass = '';
    $dbname = 'eosztalykassza';

    try {
        $db = new Database($host, $user, $pass, $dbname);
    }
    catch(PDOException $e) {
        include('frontend/database_error.php');
        die();
    }