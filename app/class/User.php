<?php

    /**
     * This class represents an user of this site.
     */
    class User {
        
        public $username, $email, $classes;

        function AddClass($class) {
            $this->classes[] = $class;
        }

    }

?>