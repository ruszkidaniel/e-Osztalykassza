<?php
    define('EO_project_version', '0.1');
    
    /**
     * PATH Parsing
     * Everything goes into a $path variable
     */

    if(array_key_exists('path', $_GET)) {
        $path = explode('/', $_GET['path']);
    } else {
        $path = [];
    }

    /**
     * CLASS Loading
     * Files in /app/class named *.class.php 
     */

    $classes = glob('./app/class/*.class.php');
    foreach($classes as $class) {
        require_once($class);
    }

    /**
     * Loading page
     */
    
    $cookieHandler = new CookieHandler($_COOKIE, $path);
    $pageConfig = new Config();

    require_once('./app/database.php');
    $dataManager = new DataManager($db);

    require_once('./app/session.php');
    require_once('./app/functions.php');

    require_once('./app/main.php');
    
    // todo: title, egyéni jogosultságokkal elérhető fájlok inklúdálásának beállítása