<?php

    if(!defined('EO_project_version'))
        die(header('Location: /'));

    $pageTitle = 'e-Osztálykassza | 2021';

?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include('assets/load.php') ?>
    <title><?= $pageTitle; ?></title>
</head>
<body>
