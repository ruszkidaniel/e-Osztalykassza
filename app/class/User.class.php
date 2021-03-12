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

        function SetupProfile($registerData, $postData, $current2FACode) {

            if(!isset($postData['2fa'], $postData['dob'], $postData['Code']))
                throw new Exception('data_mismatch');

            if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $postData['dob']))
                throw new Exception('invalid_dob_format');

            if($postData['2fa'] != 0 && strlen($postData['Code']) == 0)
                throw new Exception('2fa_not_provided');

            if($postData['2fa'] == 0 && strlen($postData['Code']) != 0)
                $postData['2fa'] = 1;

            if($postData['2fa'] > 0 && $current2FACode != $postData['Code']) 
                throw new Exception('wrong_2fa_code');

            $this->dataManager->SetDOB($registerData['UserID'], $_POST['dob']);
            $this->dataManager->SetGlobalPermissions($registerData['UserID'], 1);

            if(isset($postData['dobhidden']))
                $this->dataManager->SetDOBHidden($registerData['UserID'], 1);

            return true;

        }

        function LoginUser($username, $password, $forceLogin = false) {

            // get user from database

            $loginData = $this->dataManager->GetLoginData($username);

            // check if user exists
            
            if(!$loginData || is_null($loginData['UserID']))
                throw new Exception('user_not_found');
            
            // validating data
            
            if(!$forceLogin) {
                if($loginData['GlobalPermissions'] == 0) 
                    throw new Exception('permission_error');

                if($loginData['FailedCount'] >= $this->pageConfig::MAX_LOGIN_ATTEMPTS) 
                    throw new Exception('max_login_attempts_reached');

                $genPass = hash('sha256', hash('sha256', $password) . $loginData['PasswordSalt']);
                if($genPass != $loginData['Password']) {
                    $this->dataManager->LogFailedLogin($loginData['UserID']);
                    throw new Exception('invalid_password');
                }
            }
            // logging in

            return [
                'UserID' => $loginData['UserID'],
                'UserName' => $username,
                'GlobalPermissions' => $loginData['GlobalPermissions'],
                '2FA' => $loginData['2FA'],
                '2FAType' => $loginData['2FAType']
            ];

        }

    }

?>