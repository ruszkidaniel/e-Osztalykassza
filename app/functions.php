<?php

    function isLogged() {
        global $cookiesAllowed;
        return $cookiesAllowed && isset($_SESSION['user']);
    }