<?php

    class Database {

        /**
         * Initialize the database handler
         */
        function __construct($host, $username, $password, $dbname) {
            try {
                $this->db = new PDO(
                    'mysql:host='.$host.';dbname='.$dbname.';charset=utf8', $username, $password,
                    [
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
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
         * 
         * @param string $sql The query string which replaces the first $prefix string with the DB_PREFIX constant.
         * @param mixed[] $params Array of parameters of the query (optional)
         * @param boolean $returnStatement Tells whether the function should return the PDOStatement, or the result of the execute function
         * 
         * @return PDOStatement|boolean
         */
        function query($sql, $params = [], $returnStatement = true) {

            $q = $this->db->prepare($sql);
            $success = $q->execute($params);
            
            return ($returnStatement ? $q : $success);
        }

        function Insert($sql, $params = []) {

            $this->query($sql, $params);
            return $this->db->lastInsertId();

        }

        function queryList($sql, $params) {

            $questionmarks = implode(', ',str_split(str_repeat('?', count($params))));
            $sql = str_replace('?', '('. $questionmarks .')', $sql);
            return $this->query($sql, $params);

        }

        function InsertMultiple($sql, $datas) {

            $q = $this->db->prepare($sql);
            
            $this->db->beginTransaction();
            foreach($datas as $data)
                $q->execute($data);
            $this->db->commit();

            return $q;

        }

    }