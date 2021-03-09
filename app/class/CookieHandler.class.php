<?php

    class CookieHandler {

        function __construct($cookieArray, $path) {
            $this->cookiesAllowed = array_key_exists('cookiesAllowed', $cookieArray);
            $this->checkPath($path);
        }

        function Check() {
            return $this->cookiesAllowed;
        }

        function checkPath($path) {
            if(gettype($path) == 'array' && count($path) > 0 && $path[0] == 'accept-cookies') {
                $this->cookiesAllowed = true;
                setcookie('cookiesAllowed',time());
            }
        }

    }