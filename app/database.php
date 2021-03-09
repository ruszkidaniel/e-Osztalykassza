<?php

    $host = '';
    $user = '';
    $pass = '';
    $dbname = '';

    try {
        $db = new Database($host, $user, $pass, $dbname);
    }
    catch(PDOException $e) {
        include('frontend/database_error.php');
        die();
    }