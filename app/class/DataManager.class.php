<?php

    class DataManager {

        /**
         * Initialize the DataManager
         * 
         * @param Database $database
         */
        function __construct($database) {
            $this->db = $database;
        }

        /**
         * Returns a boolean whether the specified username or email can be found in the database
         * 
         * @param string $username The name of the searched user
         * @param string $email The e-mail address of the searched user
         * 
         * @return boolean
         */
        function doesUserExist($username, $email) {

            $result = $this->db->query('SELECT COUNT(*) FROM Users WHERE UserName = ? OR Email = ?', [$username, $email]);

            return ($result->fetchColumn() > 0);

        }

        /**
         * Storing the account's data in the database. 
         * This should be called after data validation.
         * 
         * @param mixed[] $data
         * 
         * @return boolean
         */
        function RegisterUser($data) {
            if(!isset($data['username'], $data['email'], $data['password'], $data['fullname']))
                throw new Exception('data_mismatch');
            
            $salt = random_characters(8);
            $password = hash('sha256', hash('sha256',$data['password']).$salt);
            $data = [ $data['username'], $data['email'], 0, 'normal', $password, $salt, $data['fullname'] ];

            $result = $this->db->Insert(
                'INSERT INTO Users (UserName, Email, GlobalPermissions, AccountType, Password, PasswordSalt, FullName) VALUES (?,?,?,?,?,?,?)',
                $data
            );
            
            return $result;
        }

        /**
         * Fetches the GlobalPermissions, AccountType, Password, PasswordSalt and the FailedCount from the database
         * associated with the given username
         * 
         * @param string $username
         */
        function GetLoginData($username) {
            
            $result = $this->db->query(
                'SELECT UserID, GlobalPermissions, AccountType, Password, PasswordSalt, COUNT(FailedLogins.LoginID) as FailedCount 
                FROM Users NATURAL JOIN FailedLogins 
                WHERE UserName = ? AND FailedLogins.Date >= date_sub(NOW(), interval 1 hour)',
                [ $username ]
            )->fetchAll();

            if(!$result || count($result) == 0)
                return false;
            
            return $result[0];

        }

        /**
         * Inserts a row to the FailedLogins table with the current userID, sessionID and date
         * 
         * @param int $userid
         */
        function LogFailedLogin($userid) {

            $this->db->query(
                'INSERT INTO FailedLogins (UserID, SessionID, Date) VALUES (?,?,NOW())',
                [ $userid, $this->sessionHandler->GetSessionDBID() ]
            );

        }

        /**
         * Returns the ID based on the given VALUE
         * OR creates a new pair, and returns the newly created ID
         * 
         * @param string $table The table where the pairs are stored
         * @param string $valueColumn The name of the value column
         * @param string $idColumn The name of the ID column
         * @param string $value The name of the ID column
         * 
         * @return int ID
         */
        function AssociateDatabaseValueWithID($table, $valueColumn, $idColumn, $value) {

            $result = $this->db->query(
                'SELECT '.$idColumn.' FROM '.$table.' WHERE '.$valueColumn.' = ?',
                [ $value ]
            )->fetchColumn();

            if(!$result) {
                return $this->db->Insert(
                    'INSERT INTO '.$table.' ('.$valueColumn.') VALUES (?)',
                    [ $value ]
                );
            }
            return $result;

        }

        function IsValidSession($sessionid) {

            $val = $this->db->query(
                'SELECT count(SessionID) FROM `Sessions` WHERE SessionID = ?',
                [ $sessionid ]
            )->fetchColumn();

            return $val > 0;

        }

        function StoreSession($sessionid, $useragent, $ip) {
            $ipid = $this->AssociateDatabaseValueWithID('IpAddresses', 'IP', 'IPID', $ip);
            $useragentid = $this->AssociateDatabaseValueWithID('UserAgents', 'UserAgent', 'UserAgentID', $useragent);

            return $this->db->Insert(
                'INSERT INTO `Sessions` (SessionID, IPID, FirstInteraction, LastInteraction, UserAgentID) VALUES (?,?,NOW(),NOW(),?)',
                [$sessionid, $ipid, $useragentid]
            );
        }

        function UpdateSession($sessionid, $useragent, $ip, $userid = null) {
            $ipid = $this->AssociateDatabaseValueWithID('IpAddresses', 'IP', 'IPID', $ip);
            $useragentid = $this->AssociateDatabaseValueWithID('UserAgents', 'UserAgent', 'UserAgentID', $useragent);

            $this->db->query(
                'UPDATE `Sessions` SET UserAgentID = ?, UserID = ?, IPID = ?, LastInteraction = NOW() WHERE SessionID = ?',
                [ $useragentid, $userid, $ipid, $sessionid ]
            );
        }

        function UpdateVerificationCode($code) {
            return $this->db->query(
                'UPDATE `VerificationCodes` SET `Date` = NOW() WHERE `Code` = ?',
                [ $code ],
                false
            );
        }

        function DeleteVerificationCode($code) {
            return $this->db->query(
                'DELETE FROM `VerificationCodes` WHERE `Code` = ?',
                [ $code ]
            );
        }

        function FindVerificationCode($userid, $type) {
            $result = $this->db->query(
                'SELECT `Code`, `Date` FROM `VerificationCodes` WHERE `UserID` = ? AND `Type` = ?',
                [ $userid, $type ]
            )->fetchAll();
            
            if(!$result || count($result) == 0)
                return false;

            return $result[0];
        }

        function InsertNewVerificationCode($code, $userID, $type) {

            return $this->db->Insert(
                'INSERT INTO `VerificationCodes` (`Code`, `UserID`, `Date`, `Type`) VALUES (?, ?, NOW(), ?)',
                [ $code, $userID, $type ]
            );

        }

        function SetUser2FA($userid, $code = null, $type = 0) {

            if(is_null($code)) {
                $result = $this->db->query(
                    'UPDATE Users SET 2FAType = ? WHERE UserID = ?',
                    [ $type, $userid ],
                    false
                );
            } else {
                $result = $this->db->query(
                    'UPDATE Users SET 2FA = ?, 2FAType = ? WHERE UserID = ?',
                    [ $code, $type, $userid ],
                    false
                );
            }

            return $result;

        }

    }