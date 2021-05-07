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
                'SELECT UserID, GlobalPermissions, AccountType, Password, PasswordSalt, 2FA, 2FAType, FullName, 
                (SELECT COUNT(FailedLogins.LoginID) FROM FailedLogins NATURAL LEFT JOIN Users WHERE Users.UserName = ? AND FailedLogins.Date >= date_sub(NOW(), interval 1 hour)) 
                    as FailedCount
                FROM Users WHERE UserName = ?',
                [ $username, $username ]
            )->fetchAll();

            return $this->GetFirstResult($result);

        }

        /**
         * Inserts a row to the FailedLogins table with the current userID, sessionID and date
         * 
         * @param int $userid
         */
        function LogFailedLogin($userid) {

            $this->db->query(
                'INSERT INTO FailedLogins (UserID, SessionID, Date) VALUES (?,?,NOW())',
                [ $userid, session_id() ]
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

        /**
         * Returns with a permission bitflag hash of a class member, or 0 if it's null
         * 
         * @param int $classid
         * @param int $userid
         * 
         * @return int
         */
        function GetClassPermissions($classid, $userid) {

            $result = $this->db->query(
                'SELECT `Permissions` FROM `ClassMembers` WHERE `ClassID` = ? AND `UserID` = ?',
                [ $classid, $userid ]
            )->fetchColumn();

            return is_null($result) ? 0 : $result;

        }

        /**
         * Checks whether a session exists in the database
         * 
         * @param string $sessionid
         * 
         * @return boolean
         */
        function IsValidSession($sessionid) {

            $val = $this->db->query(
                'SELECT count(SessionID) FROM `Sessions` WHERE SessionID = ?',
                [ $sessionid ]
            )->fetchColumn();

            return $val > 0;

        }

        /**
         * Stores a session with IP and UserAgent in the database.
         * 
         * @param string $sessionid
         * @param string $useragent
         * @param string $ip
         * 
         * @return boolean
         */
        function StoreSession($sessionid, $useragent, $ip) {
            $ipid = $this->AssociateDatabaseValueWithID('IpAddresses', 'IP', 'IPID', $ip);
            $useragentid = $this->AssociateDatabaseValueWithID('UserAgents', 'UserAgent', 'UserAgentID', $useragent);

            return $this->db->Insert(
                'INSERT INTO `Sessions` (SessionID, IPID, FirstInteraction, LastInteraction, UserAgentID) VALUES (?,?,NOW(),NOW(),?)',
                [$sessionid, $ipid, $useragentid]
            );
        }

        /**
         * Updates a session in the database
         * 
         * @param string $sessionid
         * @param string $useragent
         * @param string $ip
         * @param int|null $userid
         * 
         * @return boolean
         */
        function UpdateSession($sessionid, $useragent, $ip, $userid = null) {
            $ipid = $this->AssociateDatabaseValueWithID('IpAddresses', 'IP', 'IPID', $ip);
            $useragentid = $this->AssociateDatabaseValueWithID('UserAgents', 'UserAgent', 'UserAgentID', $useragent);

            return $this->db->query(
                'UPDATE `Sessions` SET UserAgentID = ?, UserID = ?, IPID = ?, LastInteraction = NOW() WHERE SessionID = ?',
                [ $useragentid, $userid, $ipid, $sessionid ],
                false
            );
        }

        /**
         * Updates a verification code's date in the database
         * 
         * @param string $code
         * 
         * @return boolean
         */
        function UpdateVerificationCode($code) {
            return $this->db->query(
                'UPDATE `VerificationCodes` SET `Date` = NOW() WHERE `Code` = ?',
                [ $code ],
                false
            );
        }

        /**
         * Deletes a verification code from the database
         * 
         * @param string $code
         * 
         * @return boolean
         */
        function DeleteVerificationCode($code) {
            return $this->db->query(
                'DELETE FROM `VerificationCodes` WHERE `Code` = ?',
                [ $code ]
            );
        }

        /**
         * Searches for the given user's given type of verification code, and
         * returns it's Code and Date value in an array
         * 
         * @param int $userid
         * @param string $type
         * 
         * @return false|array
         */
        function FindVerificationCode($userid, $type) {

            $result = $this->db->query(
                'SELECT `Code`, `Date` FROM `VerificationCodes` WHERE `UserID` = ? AND `Type` = ? ORDER BY `Date` DESC LIMIT 1',
                [ $userid, $type ]
            )->fetchAll();
            
            return $this->GetFirstResult($result);

        }
        
        /**
         * Searches for a User using a verification code and type
         * 
         * @param string $code
         * @param string $type
         * 
         * @return false|array
         */
        function FindUserByVerificationCode($code, $type) {

            $result = $this->db->query(
                'SELECT Users.UserID, UserName, FullName, Email FROM Users NATURAL RIGHT JOIN VerificationCodes WHERE `Code` = ? AND `Type` = ?',
                [ $code, $type ]
            )->fetchAll();
            
            return $this->GetFirstResult($result);

        }

        /**
         * Inserts a new verification code into the database
         * 
         * @param string $code
         * @param int $userId
         * @param string $type
         */
        function InsertNewVerificationCode($code, $userID, $type) {

            return $this->db->Insert(
                'INSERT INTO `VerificationCodes` (`Code`, `UserID`, `Date`, `Type`) VALUES (?, ?, NOW(), ?)',
                [ $code, $userID, $type ]
            );

        }

        /**
         * Updates a User's 2FA login method, and the code itself, if provided
         * 
         * @param int $userid
         * @param null|string $code
         * @param int $type
         */
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

        /**
         * Sets the hidden status of a User's birthdate
         * 
         * @param int $userid The ID of the User
         * @param int $status The updated status of DOBHidden
         */
        function SetDOBHidden($userid, $status) {

            return $this->db->query(
                'UPDATE Users SET DOBHidden = ? WHERE UserID = ?',
                [ $status, $userid ],
                false
            );

        }

        /**
         * Sets the birthdate of a User
         * 
         * @param int $userid The ID of the User
         * @param string $date The birthday of the User (format: YYYY-MM-DD)
         * 
         * @return boolean
         */
        function SetDOB($userid, $date) {

            return $this->db->query(
                'UPDATE Users SET DOB = ? WHERE UserID = ?',
                [ $date, $userid ],
                false
            );

        }

        /**
         * Sets user's password
         * 
         * @param int $userid The ID of User
         * @param string $newpassword The hash of new password
         * @param string $newsalt The salt of the password
         * 
         * @return boolean
         */
        function ChangePassword($userid, $newpassword, $newsalt) {

            return $this->db->query(
                'UPDATE Users SET Password = ?, PasswordSalt = ? WHERE UserID = ?',
                [ $newpassword, $newsalt, $userid ],
                false
            );

        }

        /**
         * Sets the permission bitflag hash of the given user
         * 
         * @param int $userid
         * @param int $perms
         * 
         * @return boolean
         */
        function SetGlobalPermissions($userid, $perms) {

            return $this->db->query(
                'UPDATE Users SET GlobalPermissions = ? WHERE UserID = ?',
                [ $perms, $userid ],
                false
            );

        }

        /**
         * Gets the permission bitflag hash of the given user
         * 
         * @param int $userid
         * 
         * @return int
         */
        function GetGlobalPermissions($userid) {

            return $this->db->query(
                'SELECT GlobalPermissions FROM Users WHERE UserID = ?',
                [ $userid ]
            )->fetchColumn();

        }

        /**
         * Returns the number of associated IP addresses for a user
         * 
         * @param int $userid
         * @param string $ip
         * 
         * @return int
         */
        function FindUserByIP($userid, $ip) {

            $ipid = $this->AssociateDatabaseValueWithID('IpAddresses', 'IP', 'IPID', $ip);

            return $this->db->query(
                'SELECT COUNT(*) FROM `UserIpTable` WHERE `UserID` = ? AND `IPID` = ?',
                [ $userid, $ipid ]
            )->fetchColumn();

        }

        /**
         * @param string $email
         * 
         * @return false|array
         */
        function FindUserByEmail($email) {

            $results = $this->db->query(
                'SELECT UserID, UserName, FullName FROM Users WHERE `Email` = ?',
                [ $email ]
            )->fetchAll();

            return $this->GetFirstResult($results);

        }

        /**
         * @param string $username
         * @param string $email
         * 
         * @return false|array
         */
        function FindUserByEmailAndUsername($username, $email) {
            
            $results = $this->db->query(
                'SELECT UserID, FullName FROM Users WHERE `Email` = ? AND UserName = ?',
                [ $email, $username ]
            )->fetchAll();

            return $this->GetFirstResult($results);

        }

        /**
         * @param int $userid
         * @param string $ip
         * 
         * @return boolean
         */
        function StoreUserIP($userid, $ip) {
            
            $ipid = $this->AssociateDatabaseValueWithID('IpAddresses', 'IP', 'IPID', $ip);

            return $this->db->query(
                'INSERT IGNORE INTO `UserIpTable` (UserID, IPID) VALUES (?, ?)',
                [ $userid, $ipid ],
                false
            );

        }

        /**
         * @param string $sessionid
         * 
         * @return boolean
         */
        function DeleteSession($sessionid) {

            return $this->db->query('DELETE FROM `Sessions` WHERE `SessionID` = ?', [$sessionid], false);

        }

        /**
         * @param int $userid
         * 
         * @return false|array
         */
        function GetUserProfile($userid) {

            $result = $this->db->query(
                'SELECT UserName, Email, GlobalPermissions, AccountType, 2FAType, FullName, DOB, DOBHidden FROM Users WHERE UserID = ?', 
                [ $userid ]
            )->fetchAll();
            
            return $this->GetFirstResult($result);

        }

        /**
         * @param int $userid
         * 
         * @return string
         */
        function GetUserEmail($userid) {

            return $this->db->query(
                'SELECT Email FROM Users WHERE UserID = ?',
                [ $userid ]
            )->fetchColumn();
            
        }

        /**
         * @param int $userid
         * @param int $type
         * 
         * @return boolean
         */
        function Change2FAType($userid, $type) {

            return $this->db->query(
                'UPDATE Users SET 2FAType = ? WHERE UserID = ?',
                [ $type, $userid ],
                false
            );

        }

        /**
         * @param int $classid
         * @param int $userid
         * 
         * @return string
         */
        function FindUserInClass($classid, $userid) {

            $result = $this->db->query(
                'SELECT FullName FROM Users NATURAL LEFT JOIN ClassMembers WHERE UserID = ? AND ClassID = ?', 
                [ $userid, $classid ]
            )->fetchColumn();
            
            return $result;

        }

        /**
         * @param string $email
         * @param int $classid
         * 
         * @return int
         */
        function IsMemberInClass($email, $classid) {

            $result = $this->db->query(
                'SELECT 1 FROM Users NATURAL LEFT JOIN ClassMembers WHERE Users.Email = ? AND ClassID = ?', 
                [ $email, $classid ]
            )->fetchColumn();
            
            return $result;

        }

        /**
         * @param int $classid
         * @param int $userid
         * 
         * @return boolean
         */
        function KickUserFromClass($classid, $userid) {

            $result = $this->db->query(
                'DELETE FROM ClassMembers WHERE UserID = ? AND ClassID = ?',
                [ $userid, $classid ],
                false
            );
            
            return $result;

        }

        /**
         * @param int $userid
         * 
         * @return array
         */
        function GetUserClassrooms($userid) {

            return $this->db->query(
                'SELECT ClassID, ClassName, SchoolName FROM Classrooms NATURAL LEFT JOIN ClassMembers NATURAL LEFT JOIN Schools WHERE UserID = ?',
                [ $userid ]
            )->fetchAll();

        }

        /**
         * @param int $userid
         * 
         * @return array
         */
        function GetUserOwnedClasses($userid) {

            return $this->db->query(
                'SELECT ClassID, ClassName FROM Classrooms WHERE OwnerID = ?',
                [ $userid ]
            )->fetchAll();

        }

        /**
         * @return array
         */
        function GetSchools() {

            return $this->db->query(
                'SELECT * FROM Schools ORDER BY SchoolName'
            )->fetchAll();

        }

        /**
         * @param int $id
         * @param boolean $byId
         * 
         * @return array
         */
        function FindSchool($id, $byId = true) {

            $by = $byId ? 'SchoolID =' : 'SchoolName LIKE';

            return $this->db->query(
                'SELECT * FROM Schools WHERE '.$by.' ?',
                [ $id ]
            )->fetchAll();

        }

        /**
         * @param string $schoolName
         * 
         * @return array
         */
        function CreateSchool($schoolName) {

            $id = $this->db->Insert(
                'INSERT INTO Schools (SchoolName) VALUES (?)',
                [ $schoolName ]
            );

            return $this->FindSchool($id);

        }

        /**
         * @param int $class
         * 
         * @return false|array
         */
        function GetClassInfo($class) {

            $classInfo = $this->db->query(
                'SELECT * FROM Classrooms WHERE ClassID = ?',
                [ $class ]
            )->fetchAll();

            return $this->GetFirstResult($classInfo);

        }

        /**
         * @param int $class
         * 
         * @return array
         */
        function GetClassMembers($class) {

            return $this->db->query(
                'SELECT UserID, FullName, Email, DOB, `Permissions` FROM Users NATURAL RIGHT JOIN ClassMembers WHERE ClassID = ?',
                [ $class ]
            )->fetchAll();

        }

        /**
         * @param int $requestid
         * 
         * @return array
         */
        function GetRequestDebts($requestid) {

            return $this->db->query(
                'SELECT UserID, RequiredAmount FROM UserDebts WHERE RequestID = ?',
                [ $requestid ]
            )->fetchAll(PDO::FETCH_GROUP);

        }
        
        /**
         * @param int $requestid
         * @param string $title
         * @param string $description
         * @param string|null $deadline
         */
        function ModifyRequest($requestid, $title, $description, $deadline) {

            return $this->db->query(
                'UPDATE PayRequests SET `Subject` = ?, `Description` = ?, `Deadline` = ? WHERE RequestID = ?',
                [ $title, $description, $deadline, $requestid ],
                false
            );

        }

        /**
         * @param array $delete
         * @param array $modify
         * @param array $insert
         */
        function ModifyDebts($delete, $modify, $insert) {

            if(count($delete) > 0)
            $this->db->InsertMultiple(
                'DELETE FROM UserDebts WHERE UserID = ? AND RequestID = ?',
                $delete
            );

            if(count($modify) > 0)
            $this->db->InsertMultiple(
                'UPDATE UserDebts SET RequiredAmount = ? WHERE UserID = ? AND RequestID = ?',
                $modify
            );

            if(count($insert) > 0)
            $this->InsertDebts($insert);

        }

        /**
         * @param int $userid
         * 
         * @return array
         */
        function GetPendingInvitesByID($userid) {

            return $this->db->query(
                'SELECT Invites.*, ClassName, Inviter.FullName as Inviter
                FROM Invites 
                LEFT JOIN Users AS Invited ON Invited.Email = Invites.Email
                LEFT JOIN Users AS Inviter ON Inviter.UserID = Invites.InvitedBy
                NATURAL LEFT JOIN Classrooms
                WHERE `Status` = ? AND Invited.UserID = ?',
                [ 'pending', $userid ]
            )->fetchAll();

        }

        /**
         * @param int $class
         * 
         * @return array
         */
        function GetClassInvites($class) {

            return $this->db->query(
                'SELECT Invites.*, FullName FROM Invites 
                LEFT JOIN Users ON Users.UserID = Invites.InvitedBy
                WHERE Invites.ClassID = ?
                ORDER BY `Date` DESC',
                [ $class ]
            )->fetchAll();

        }

        /**
         * @param int $schoolid
         * @param int $id
         * @param boolean $byId
         * 
         * @return false|array
         */
        function FindClass($schoolid, $id, $byId = true) {

            $by = $byId ? 'ClassID =' : 'ClassName LIKE';
    
            $class = $this->db->query(
                'SELECT * FROM Classrooms WHERE SchoolID = ? AND '.$by.' ?',
                [ $schoolid, $id ]
            )->fetchAll();

            return $this->GetFirstResult($class);
            
        }
    
        /**
         * @param int $schoolid
         * @param string $classname
         * @param int $ownerid
         * @param string $description
         * 
         * @return false|array
         */
        function CreateClass($schoolid, $classname, $ownerid, $description) {

            $id = $this->db->Insert(
                'INSERT INTO Classrooms (OwnerID, SchoolID, ClassName, Description) VALUES (?, ?, ?, ?)',
                [ $ownerid, $schoolid, $classname, $description ]
            );

            return $this->FindClass($schoolid, $id);
    
        }

        /**
         * @param int $classid
         * 
         * @return boolean
         */
        function DeleteClass($classid) {

            $result = $this->db->query(
                'DELETE FROM Classrooms WHERE ClassID = ?',
                [ $classid ],
                false
            );
            
            return $result && $result2 && $this->db->query(
                'DELETE FROM ClassGroups WHERE ClassID = ?',
                [ $classid ],
                false
            );

        }

        /**
         * @param int $userid
         * @param int $classid
         * 
         * @return boolean
         */
        function AddMemberToClass($userid, $classid) {

            return $this->db->Insert(
                'INSERT INTO ClassMembers (`ClassID`, `UserID`) VALUES (?,?)',
                [ $classid, $userid ],
                false
            );

        }

        /**
         * @param int $classid
         * @param int $id
         * @param boolean $byId
         * 
         * @return false|array
         */
        function FindClassGroup($classid, $id, $byId = true) {
            
            $by = $byId ? 'GroupID =' : 'GroupName LIKE';
    
            $class = $this->db->query(
                'SELECT * FROM ClassGroups WHERE ClassID = ? AND '.$by.' ?',
                [ $classid, $id ]
            )->fetchAll();

            return $this->GetFirstResult($class);
    
        }
        
        /**
         * @param int $classid
         * @param string $groupname
         * 
         * @return false|array
         */
        function AddClassGroup($classid, $groupname) {

            $id = $this->db->Insert(
                'INSERT INTO ClassGroups (ClassID, GroupName) VALUES (?, ?)',
                [ $classid, $groupname ]
            );

            return $this->FindClassGroup($classid, $id);
    
        }

        /**
         * @param int $classid
         * @param int $groupid
         * @param string $groupname
         * 
         * @return false|array
         */
        function RenameGroup($classid, $groupid, $groupname) {

            $this->db->query(
                'UPDATE ClassGroups SET GroupName = ? WHERE GroupID = ? AND ClassID = ?',
                [ $groupname, $groupid, $classid ],
                false
            );

            return $this->FindClassGroup($classid, $groupid);

        }

        /**
         * @param int $classid
         * @param int $groupid
         * 
         * @return boolean
         */
        function DeleteClassGroup($classid, $groupid) {
            
            return $this->db->query(
                'DELETE FROM ClassGroups WHERE ClassID = ? AND GroupID = ?',
                [ $classid, $groupid ],
                false
            );

        }

        /**
         * @param int $classid
         * 
         * @return array
         */
        function GetClassGroups($classid) {

            return $this->db->query(
                'SELECT * FROM ClassGroups WHERE ClassID = ?',
                [ $classid ]
            )->fetchAll();

        }

        /**
         * @param int $schoolid
         * 
         * @return array
         */
        function GetSchoolClasses($schoolid) {

            return $this->db->query(
                'SELECT * FROM Classrooms WHERE SchoolID = ?',
                [ $schoolid ]
            )->fetchAll();

        }

        /**
         * @param int $schoolid
         * 
         * @return boolean
         */
        function DeleteSchool($schoolid) {
            
            return $this->db->query(
                'DELETE FROM Schools WHERE SchoolID = ?',
                [ $schoolid ],
                false
            );

        }

        /**
         * @param string $email
         * 
         * @return boolean
         */
        function UnsubscribeEmail($email) {

            return $this->db->Insert(
                'INSERT INTO EmailIgnoreList (`Address`, `Date`) VALUES (?,NOW())',
                [ $email ],
                false
            );

        }

        /**
         * @return array
         */
        function GetUnsubscribedEmails($list) {

            if(count($list) == 0) return [];

            $result = $this->db->queryList(
                'SELECT Address FROM EmailIgnoreList WHERE Address IN ?',
                array_values($list)
            )->fetchAll();

            return array_map(function($x){ return $x['Address']; }, $result);

        }

        /**
         * @param int $from
         * @param int $classid
         * @param array $targets
         * 
         * @return array
         */
        function CreateInvitations($from, $classid, $targets) {

            $data = array_map(function($target) use($from, $classid) {
                return [$target['Code'], $classid, $from, $target['Email'], 'pending'];
            }, $targets);
            
            $this->db->InsertMultiple(
                'INSERT INTO Invites (InviteCode, ClassID, InvitedBy, Email, Date, Status) VALUES (?,?,?,?,NOW(),?)',
                $data
            );
            return true;

        }

        /**
         * @param int $inviteCode
         * 
         * @return false|array
         */
        function GetInviteData($inviteCode) {
            
            $result = $this->db->query(
                'SELECT Invites.*, Users.FullName, Classrooms.ClassName FROM Invites 
                LEFT JOIN Users ON Users.UserID = Invites.InvitedBy 
                NATURAL LEFT JOIN Classrooms
                WHERE InviteCode = ?',
                [ $inviteCode ]
            )->fetchAll();

            return $this->GetFirstResult($result);

        }

        /**
         * @param int $classid
         * @param string $email
         * 
         * @return array
         */
        function FindPendingInviteByEmail($classid, $email) {

            return $this->db->query(
                'SELECT * FROM Invites WHERE Email = ? AND ClassID = ? AND `Status` = ?',
                [ $email, $classid, 'pending' ]
            )->fetchAll();

        }

        /**
         * @param string $code
         * @param boolean $accept
         * 
         * @return boolean
         */
        function HandleInviteResponse($code, $accept) {

            return $this->db->query(
                'UPDATE Invites SET `Status` = ? WHERE `InviteCode` = ?',
                [ $accept ? 'accepted' : 'declined', $code ],
                false
            );

        }

        /**
         * @param int $classid
         * 
         * @return array
         */
        function GetDetailedClassData($classid) {

            $response = [
                'info' => [],
                'members' => [],
                'invites' => []
            ];

            $info = $this->GetClassInfo($classid);
            if($info) $response['info'] = $info;
            else return false;

            $members = $this->GetClassMembers($classid);
            if($members) $response['members'] = $members;

            $invites = $this->GetClassInvites($classid);
            if($invites) $response['invites'] = $invites;

            return $response;

        }

        /**
         * @param string $email
         * 
         * @return boolean
         */
        function OptOutEmail($email) {

            return $this->db->query(
                'INSERT INTO EmailIgnoreList (`Address`, `Date`) VALUES (?, NOW())',
                [ $email ],
                false
            );

        }

        /**
         * @param int $classid
         * @param int $userid
         * 
         * @return array
         */
        function GetUserDebts($classid, $userid) {

            return $this->db->query(
                'SELECT * FROM UserDebts NATURAL LEFT JOIN PayRequests WHERE ClassID = ? AND UserID = ?',
                [ $classid, $userid ]
            )->fetchAll();

        }

        /**
         * @param int $classid
         * 
         * @return array
         */
        function GetPayRequests($classid) {

            return $this->db->query(
                'SELECT PayRequests.Subject, PayRequests.RequestID, COUNT(UserDebts.DebtID) as RequestedUsers, PayRequests.Deadline
                FROM PayRequests 
                INNER JOIN UserDebts ON UserDebts.RequestID = PayRequests.RequestID
                WHERE ClassID = ?
                GROUP BY PayRequests.RequestID',
                [ $classid ]
            )->fetchAll();

        }

        /**
         * @param int $requestid
         * @param int $classid
         * 
         * @return false|array
         */
        function GetPayRequestInfo($requestid, $classid) {

            $result = $this->db->query(
                'SELECT PayRequests.*, FullName, COUNT(UserDebts.DebtID) as RequestedUsers, SUM(UserDebts.Amount) as PaidTotal, SUM(UserDebts.RequiredAmount) as RequiredTotal
                FROM `PayRequests` 
                NATURAL LEFT JOIN UserDebts
                LEFT JOIN Users ON Users.UserID = PayRequests.RequestedBy
                WHERE PayRequests.RequestID = ?
                AND PayRequests.ClassID = ?',
                [ $requestid, $classid ]
            )->fetchAll();

            return $this->GetFirstResult($result);

        }

        /**
         * @param int $requestid
         * 
         * @return array
         */
        function GetDebtsByRequest($requestid) {

            return $this->db->query(
                'SELECT FullName, UserDebts.*
                FROM UserDebts
                NATURAL LEFT JOIN Users
                WHERE RequestID = ?',
                [ $requestid ]
            )->fetchAll();

        }

        /**
         * @param int $inviteid
         * 
         * @return boolean
         */
        function Uninvite($inviteid) {

            return $this->db->query(
                'UPDATE `Invites` SET `Status` = ? WHERE `InviteID` = ?',
                [ 'canceled', $inviteid ],
                false
            );

        }

        /**
         * @param string $email
         * 
         * @return int
         */
        function FindInvite($email) {

            return $this->db->query(
                'SELECT COUNT(InviteID) FROM Invites WHERE Email = ? AND `Date` > (NOW() - INTERVAL 1 DAY)',
                [ $email ]
            )->fetchColumn();

        }

        /**
         * @param int $classid
         * @param int $userid
         * @param string $subject
         * @param string $description
         * @param string|null $deadline
         * 
         * @return array
         */
        function CreateRequest($classid, $userid, $subject, $description, $deadline) {

            return $this->db->Insert(
                'INSERT INTO PayRequests (`ClassID`, `RequestedBy`, `Subject`, `Description`, `Date`, `Deadline`) VALUES (?, ?, ?, ?, NOW(), ?)',
                [ $classid, $userid, $subject, $description, $deadline ]
            );

        }

        /**
         * @param int $classid
         * @param int $requestid
         */
        function DeleteRequest($classid, $requestid) {

            $this->db->query(
                'DELETE FROM PayRequests WHERE ClassID = ? AND RequestID = ?',
                [ $classid, $requestid ],
                false
            );
            
            $this->db->query(
                'DELETE FROM UserDebts WHERE RequestID = ?',
                [ $requestid ],
                false
            );
            
        }
        
        /**
         * @param array $data
         * 
         * @return boolean
         */
        function InsertDebts($data) {

            if(count($data) == 0) return true;
            return $this->db->InsertMultiple(
                'INSERT INTO UserDebts (RequestID, UserID, RequiredAmount) VALUES (?,?,?)',
                $data
            );

        }

        /**
         * @param int $debtid
         * @param int $classid
         * 
         * @return false|array
         */
        function GetDebtInfo($debtid, $classid) {

            $result = $this->db->query(
                'SELECT UserDebts.*, PayRequests.*, Users.FullName FROM UserDebts
                LEFT JOIN PayRequests ON PayRequests.RequestID = UserDebts.RequestID
                LEFT JOIN Users ON Users.UserID = UserDebts.UserID
                WHERE DebtID = ? AND PayRequests.ClassID = ?',
                [ $debtid, $classid ]
            )->fetchAll();

            return $this->GetFirstResult($result);

        }

        /**
         * @param int $debtid
         * 
         * @return array
         */
        function GetPaylog($debtid) {

            return $this->db->query(
                'SELECT PayLog.*, Users.FullName FROM PayLog NATURAL JOIN Users WHERE DebtID = ? ORDER BY `Date` DESC',
                [ $debtid ]
            )->fetchAll();

        }

        /**
         * @param int $userid
         * @param int $debtid
         * @param boolean $done
         * 
         * @return boolean
         */
        function SetDebtDone($userid, $debtid, $done) {

            $success = $this->db->query(
                'UPDATE UserDebts SET IsDone = ? WHERE DebtID = ?',
                [ $done ? 1 : 0, $debtid ],
                false
            );
            if($success)
                $this->AddPaylog($userid, $debtid, 0, $done);

            return $success;

        }

        /**
         * @param int $userid
         * @param int $debtid
         * @param int $amount
         * @param int $requiredamount
         * 
         * @return boolean
         */
        function SetDebtAmounts($userid, $debtid, $amount, $requiredamount) {

            $success = $this->db->query(
                'UPDATE UserDebts SET Amount = ?, RequiredAmount = ? WHERE DebtID = ?',
                [ $amount, $requiredamount, $debtid ],
                false
            );
            if($success)
                $this->AddPaylog($userid, $debtid, 1, $amount);
         
            return $success;

        }

        /**
         * @param int $userid
         * @param int $debtid
         * @param int $amount
         * 
         * @return boolean
         */
        function AddDebtAmount($userid, $debtid, $amount) {
            $success = $this->db->query(
                'UPDATE UserDebts SET Amount = IFNULL(Amount, 0) + ? WHERE DebtID = ?',
                [ $amount, $debtid ],
                false
            );
            if($success)
                $this->AddPaylog($userid, $debtid, 2, $amount);
         
            return $success;

        }

        /**
         * @param int $userid
         * @param int $debtid
         * @param int $event Event type mappings: [0 = debt done, 1 = debt changed, 2 = new payment]
         * @param int|boolean $amount Amount of paid money or status of payment
         * 
         * @return boolean
         */
        function AddPaylog($userid, $debtid, $event, $amount) {
            if(gettype($amount) == 'boolean') $amount = $amount ? 1 : 0;

            return $this->db->Insert(
                'INSERT INTO PayLog (`Type`, `Date`, `Amount`, `DebtID`, `UserID`) VALUES (?, NOW(), ?, ?, ?)',
                [ $event, $amount, $debtid, $userid ]
            );

        }
     
        /**
         * @param int $classid
         * @param int $ownerid
         * 
         * @return boolean
         */
        function UpdateClassOwner($classid, $ownerid) {

            return $this->db->query(
                'UPDATE Classrooms SET OwnerID = ? WHERE ClassID = ?',
                [ $ownerid, $classid ],
                false
            );

        }

        /**
         * @param int $classid
         * @param string $classname
         * @param int $maxmembers
         * 
         * @return boolean
         */
        function UpdateClass($classid, $classname, $maxmembers) {

            return $this->db->query(
                'UPDATE Classrooms SET ClassName = ?, MaxMembers = ? WHERE ClassID = ?',
                [ $classname, $maxmembers, $classid ],
                false
            );

        }

        /**
         * @param int $classid
         * @param string $description
         * 
         * @return boolean
         */
        function UpdateClassDescription($classid, $description) {
            
            return $this->db->query(
                'UPDATE Classrooms SET `Description` = ? WHERE ClassID = ?',
                [ $description, $classid ],
                false
            );

        }

        /**
         * @param int $classid
         * @param int $userid
         * @param int $newhash
         * 
         * @return boolean
         */
        function UpdateUserPermission($classid, $userid, $newhash) {

            return $this->db->query(
                'UPDATE ClassMembers SET Permissions = ? WHERE UserID = ? AND ClassID = ?',
                [ $newhash, $userid, $classid ],
                false
            );

        }
     
        /**
         * @param array $results
         * 
         * @return boolean|array
         */
        function GetFirstResult($results) {
            
            if(count($results) > 0)
                $result = $results[0];
            else
                $result = false;
            
            return $result;

        }

    }