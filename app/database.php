<?php

    include_once('class/Database.php');

    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $dbname = 'eosztalykassza';

    try {
        $db = new Database($host, $user, $pass, $dbname);
    }
    catch(PDOException $e) {
        include('frontend/database_error.php');
        die();
    }