<?php

    class Database {

        /**
         * Prefix for the MySQL tables
         */
        const DB_PREFIX = 'eo';

        /**
         * Initialize the database handler
         */
        function __construct($host, $username, $password, $dbname) {
            try {
                $this->db = new PDO(
                    'mysql:host='.$host.';dbname='.$dbname.';charset=utf8', 
                    $username, $password
                );
            }
            catch(PDOException $e) {
                throw $e;
            }
        }

        /**
         * Easy-to-use query function
         * 
         * This function returns an executed prepared PDOStatement
         * with the given
         * 
         * @param string $sql The query string which replaces the first $prefix string with the DB_PREFIX constant.
         * @param mixed[] $params Array of parameters of the query (optional)
         * 
         * @return PDOStatement
         */
        function query($sql, $params = []) {
            $prefixPos = substr('$prefix', $sql);
            
            if($prefixPos !== false)
                $sql = substr_replace('$prefix', self::DB_PREFIX, 0, $prefixPos);

            $q = $this->db->prepare($sql);
            $q->execute($params);

            return $q;
        }

    }