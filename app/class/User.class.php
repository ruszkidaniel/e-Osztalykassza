<?php

    /**
     * This class represents an user of this site.
     */
    class User {

        function __construct($pageConfig, $dataManager, $cookieHandler) {
            $this->pageConfig = $pageConfig;
            $this->dataManager = $dataManager;
            $this->cookieHandler = $cookieHandler;
        }
        
        /**
         * Tries to register the user with the given data.
         * Returns an array of UserID, Email and UserName
         * 
         * @param mixed[] $data The data object with username, fullname, password(2), email(2) keys
         * 
         * @return string[]
         */
        function HandleRegister($data) {
            if(IsLogged()) 
                throw new Exception('already_logged_in');
        
            if(!isset($data['username'], $data['fullname'], $data['password'], $data['password2'], $data['email'], $data['email2']))
                throw new Exception('data_mismatch');
        
            foreach($data as $k => $v) {
                $data[$k] = trim($v);
            }

            if(strlen($data['username']) < $this->pageConfig::REG_USERNAME_MIN || strlen($data['username']) > $this->pageConfig::REG_USERNAME_MAX)
                throw new Exception('username_length');
            
            if(strlen($data['email']) > $this->pageConfig::REG_EMAIL_MAX)
                throw new Exception('email_length');
        
            if(!preg_match($this->pageConfig::REG_USERNAME_REGEX, strtolower($data['username'])))
                throw new Exception('username_invalid_characters');
        
            if(!preg_match($this->pageConfig::REG_FULLNAME_REGEX, strtolower($data['fullname'])))
                throw new Exception('fullname_invalid_characters');
                
            if(strlen($data['fullname']) < $this->pageConfig::REG_FULLNAME_MIN || strlen($data['fullname']) > $this->pageConfig::REG_FULLNAME_MAX)
                throw new Exception('fullname_length');

            if(strlen($data['password']) < $this->pageConfig::REG_PASSWORD_MIN)
                throw new Exception('password_length');
        
            if($data['password'] != $data['password2'])
                throw new Exception('password_mismatch');
                
            if($data['email'] != $data['email2'])
                throw new Exception('email_mismatch');
        
            if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL))
                throw new Exception('invalid_email');
        
            if($this->dataManager->doesUserExist($data['username'], $data['email']))
                throw new Exception('user_already_exists');
        
            if(!$this->cookieHandler->Check())
                throw new Exception('cookies_are_not_accepted');

            $userid = $this->dataManager->RegisterUser($data);
            if(!$userid) 
                throw new Exception('register_failure');

            return [
                'UserID' => $userid,
                'UserName' => $data['username'],
                'FullName' => $data['fullname'],
                'Email' => $data['email']
            ];
        }

        function HandleEmailVerify($data) {

            $_2fa = random_characters(16);
            $this->dataManager->SetUser2FA($data['UserID'], $_2fa, 0);

            $this->LoginUser($data['UserName'], '', true);

        }

        function LoginUser($username, $password, $forceLogin = false) {

            // get user from database

            $loginData = $this->dataManager->GetLoginData($username);
            
            // check if user exists
            
            if(!$loginData)
                throw new Exception('user_not_found');
            
            // validating data
            
            if(!$forceLogin) {
                if($loginData['GlobalPermissions'] == 0) 
                    throw new Exception('permission_error');

                if($loginData['FailedCount'] >= $this->pageConfig::MAX_LOGIN_ATTEMPTS) 
                    throw new Exception('max_login_attempts_reached');

                $genPass = hash('sha256', hash('sha256', $password . $loginData['PasswordSalt']));
                if($genPass != $loginData['Password']) {
                    $this->dataManager->LogFailedLogin($username);
                    throw new Exception('invalid_password');
                }
            }
            // logging in

            $_SESSION['UserID'] = $loginData['UserID'];
            $_SESSION['UserName'] = $username;
            return true;

        }

    }

?>