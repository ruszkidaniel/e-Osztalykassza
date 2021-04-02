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
                'SELECT UserID, GlobalPermissions, AccountType, Password, PasswordSalt, 2FA, 2FAType, 
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

        function GetClassPermissions($classid, $userid) {

            $result = $this->db->query(
                'SELECT `Permissions` FROM `ClassMembers` WHERE `ClassID` = ? AND `UserID` = ?',
                [ $classid, $userid ]
            )->fetchColumn();

            return is_null($result) ? 0 : $result;

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
            
            return $this->GetFirstResult($result);

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

        function SetGlobalPermissions($userid, $perms) {

            return $this->db->query(
                'UPDATE Users SET GlobalPermissions = ? WHERE UserID = ?',
                [ $perms, $userid ],
                false
            );

        }

        function GetGlobalPermissions($userid) {

            return $this->db->query(
                'SELECT GlobalPermissions FROM Users WHERE UserID = ?',
                [ $userid ]
            )->fetchColumn();

        }

        function FindUserByIP($userid, $ip) {

            $ipid = $this->AssociateDatabaseValueWithID('IpAddresses', 'IP', 'IPID', $ip);

            return $this->db->query(
                'SELECT COUNT(*) FROM `UserIpTable` WHERE `UserID` = ? AND `IPID` = ?',
                [ $userid, $ipid ]
            )->fetchColumn();

        }

        function FindUserByEmail($email) {

            $results = $this->db->query(
                'SELECT UserID, UserName, FullName FROM Users WHERE `Email` = ?',
                [ $email ]
            )->fetchAll();

            return $this->GetFirstResult($results);

        }

        function StoreUserIP($userid, $ip) {
            
            $ipid = $this->AssociateDatabaseValueWithID('IpAddresses', 'IP', 'IPID', $ip);

            return $this->db->query(
                'INSERT IGNORE INTO `UserIpTable` (UserID, IPID) VALUES (?, ?)',
                [ $userid, $ipid ]
            );

        }

        function DeleteSession($sessionid) {

            return $this->db->query('DELETE FROM `Sessions` WHERE `SessionID` = ?', [$sessionid], false);

        }

        function GetUserProfile($userid) {

            $result = $this->db->query(
                'SELECT UserName, Email, GlobalPermissions, AccountType, 2FAType, FullName, DOB, DOBHidden FROM Users WHERE UserID = ?', 
                [ $userid ]
            )->fetchAll();
            
            return $this->GetFirstResult($result);

        }

        function GetUserEmail($userid) {

            return $this->db->query(
                'SELECT Email FROM Users WHERE UserID = ?',
                [ $userid ]
            )->fetchColumn();
            
        }

        function FindUserInClass($classid, $userid) {

            $result = $this->db->query(
                'SELECT FullName FROM Users NATURAL LEFT JOIN ClassMembers WHERE UserID = ? AND ClassID = ?', 
                [ $userid, $classid ]
            )->fetchColumn();
            
            return $result;

        }

        function IsMemberInClass($email, $classid) {

            $result = $this->db->query(
                'SELECT 1 FROM Users NATURAL LEFT JOIN ClassMembers WHERE Users.Email = ? AND ClassID = ?', 
                [ $email, $classid ]
            )->fetchColumn();
            
            return $result;

        }

        function KickUserFromClass($classid, $userid) {

            $result = $this->db->query(
                'DELETE FROM ClassMembers WHERE UserID = ? AND ClassID = ?',
                [ $userid, $classid ],
                false
            );
            
            return $result;

        }

        function GetUserClassrooms($userid) {

            return $this->db->query(
                'SELECT ClassID, ClassName, SchoolName FROM Classrooms NATURAL LEFT JOIN ClassMembers NATURAL LEFT JOIN Schools WHERE UserID = ?',
                [ $userid ]
            )->fetchAll();

        }

        function GetUserOwnedClasses($userid) {

            return $this->db->query(
                'SELECT ClassID, ClassName FROM Classrooms WHERE OwnerID = ?',
                [ $userid ]
            )->fetchAll();

        }

        function GetSchools() {

            return $this->db->query(
                'SELECT * FROM Schools ORDER BY SchoolName'
            )->fetchAll();

        }

        function FindSchool($id, $byId = true) {

            $by = $byId ? 'SchoolID =' : 'SchoolName LIKE';

            return $this->db->query(
                'SELECT * FROM Schools WHERE '.$by.' ?',
                [ $id ]
            )->fetchAll();

        }

        function CreateSchool($schoolName) {

            $id = $this->db->Insert(
                'INSERT INTO Schools (SchoolName) VALUES (?)',
                [ $schoolName ]
            );

            return $this->FindSchool($id);

        }

        function GetClassInfo($class) {

            $classInfo = $this->db->query(
                'SELECT * FROM Classrooms WHERE ClassID = ?',
                [ $class ]
            )->fetchAll();

            return $this->GetFirstResult($classInfo);

        }

        function GetClassMembers($class) {

            return $this->db->query(
                'SELECT UserID, FullName, Email, DOB, `Permissions` FROM Users NATURAL RIGHT JOIN ClassMembers WHERE ClassID = ?',
                [ $class ]
            )->fetchAll();

        }

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

        function GetClassInvites($class) {

            return $this->db->query(
                'SELECT Invites.*, FullName FROM Invites 
                LEFT JOIN Users ON Users.UserID = Invites.InvitedBy
                WHERE Invites.ClassID = ?
                ORDER BY `Date` DESC',
                [ $class ]
            )->fetchAll();

        }

        function FindClass($schoolid, $id, $byId = true) {

            $by = $byId ? 'ClassID =' : 'ClassName LIKE';
    
            $class = $this->db->query(
                'SELECT * FROM Classrooms WHERE SchoolID = ? AND '.$by.' ?',
                [ $schoolid, $id ]
            )->fetchAll();

            return $this->GetFirstResult($class);
            
        }
    
        function CreateClass($schoolid, $classname, $ownerid, $description) {

            $id = $this->db->Insert(
                'INSERT INTO Classrooms (OwnerID, SchoolID, ClassName, Description) VALUES (?, ?, ?, ?)',
                [ $ownerid, $schoolid, $classname, $description ]
            );

            return $this->FindClass($schoolid, $id);
    
        }

        function DeleteClass($classid) {

            $result = $this->db->query(
                'DELETE FROM Classrooms WHERE ClassID = ?',
                [ $classid ],
                false
            );
            
            return $result && $this->db->query(
                'DELETE FROM ClassGroups WHERE ClassID = ?',
                [ $classid ],
                false
            );

        }

        function AddMemberToClass($userid, $classid) {

            return $this->db->Insert(
                'INSERT INTO ClassMembers (`ClassID`, `UserID`) VALUES (?,?)',
                [ $classid, $userid ]
            );

        }

        function FindClassGroup($classid, $id, $byId = true) {
            
            $by = $byId ? 'GroupID =' : 'GroupName LIKE';
    
            $class = $this->db->query(
                'SELECT * FROM ClassGroups WHERE ClassID = ? AND '.$by.' ?',
                [ $classid, $id ]
            )->fetchAll();

            return $this->GetFirstResult($class);
    
        }
        
        function AddClassGroup($classid, $groupname) {

            $id = $this->db->Insert(
                'INSERT INTO ClassGroups (ClassID, GroupName) VALUES (?, ?)',
                [ $classid, $groupname ]
            );

            return $this->FindClassGroup($classid, $id);
    
        }

        function RenameGroup($classid, $groupid, $groupname) {

            $this->db->query(
                'UPDATE ClassGroups SET GroupName = ? WHERE GroupID = ? AND ClassID = ?',
                [ $groupname, $groupid, $classid ],
                false
            );

            return $this->FindClassGroup($classid, $groupid);

        }

        function DeleteClassGroup($classid, $groupid) {
            
            return $this->db->query(
                'DELETE FROM ClassGroups WHERE ClassID = ? AND GroupID = ?',
                [ $classid, $groupid ],
                false
            );

        }

        function GetClassGroups($classid) {

            return $this->db->query(
                'SELECT * FROM ClassGroups WHERE ClassID = ?',
                [ $classid ]
            )->fetchAll();

        }

        function GetSchoolClasses($schoolid) {

            return $this->db->query(
                'SELECT * FROM Classrooms WHERE SchoolID = ?',
                [ $schoolid ]
            )->fetchAll();

        }

        function DeleteSchool($schoolid) {
            
            return $this->db->query(
                'DELETE FROM Schools WHERE SchoolID = ?',
                [ $schoolid ],
                false
            );

        }

        function GetUnsubscribedEmails($list) {

            if(count($list) == 0) return [];

            $result = $this->db->queryList(
                'SELECT Address FROM EmailIgnoreList WHERE Address IN ?',
                $list
            )->fetchAll();

            return array_map(function($x){ return $x['Address']; }, $result);

        }

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

        function FindPendingInviteByEmail($classid, $email) {

            return $this->db->query(
                'SELECT * FROM Invites WHERE Email = ? AND ClassID = ? AND `Status` = ?',
                [ $email, $classid, 'pending' ]
            )->fetchAll();

        }

        function HandleInviteResponse($code, $accept) {

            return $this->db->query(
                'UPDATE Invites SET `Status` = ? WHERE `InviteCode` = ?',
                [ $accept ? 'accepted' : 'declined', $code ],
                false
            );

        }

        function GetDetailedClassData($classid) {

            $response = [
                'info' => [],
                'members' => [],
                'invites' => []
            ];

            $info = $this->GetClassInfo($classid);
            if($info) $response['info'] = $info;

            $members = $this->GetClassMembers($classid);
            if($members) $response['members'] = $members;

            $invites = $this->GetClassInvites($classid);
            if($invites) $response['invites'] = $invites;

            return $response;

        }

        function OptOutEmail($email) {

            return $this->db->query(
                'INSERT INTO EmailIgnoreList (`Address`, `Date`) VALUES (?, NOW())',
                [ $email ],
                false
            );

        }

        function GetUserDebts($classid, $userid) {

            return $this->db->query(
                'SELECT * FROM UserDebts NATURAL LEFT JOIN PayRequests WHERE ClassID = ? AND UserID = ?',
                [ $classid, $userid ]
            )->fetchAll();

        }

        function GetPayRequestInfo($requestid) {

            $result = $this->db->query(
                'SELECT PayRequests.*, FullName, COUNT(UserDebts.DebtID) as RequestedUsers, SUM(UserDebts.Amount) as PaidTotal, SUM(UserDebts.RequiredAmount) as RequiredTotal
                FROM `PayRequests` 
                NATURAL LEFT JOIN UserDebts
                LEFT JOIN Users ON Users.UserID = PayRequests.RequestedBy
                WHERE PayRequests.RequestID = ?',
                [ $requestid ]
            )->fetchAll();

            return $this->GetFirstResult($result);

        }

        function GetDebtsByRequest($requestid) {

            return $this->db->query(
                'SELECT FullName, UserDebts.*
                FROM UserDebts
                NATURAL LEFT JOIN Users
                WHERE RequestID = ?',
                [ $requestid ]
            )->fetchAll();

        }

        function Uninvite($inviteid) {

            return $this->db->query(
                'UPDATE `Invites` SET `Status` = ? WHERE `InviteID` = ?',
                [ 'canceled', $inviteid ],
                false
            );

        }

        function FindInvite($email) {

            return $this->db->query(
                'SELECT COUNT(InviteID) FROM Invites WHERE Email = ? AND `Date` > (NOW() - INTERVAL 1 DAY)',
                [ $email ]
            )->fetchColumn();

        }

        function CreateRequest($classid, $userid, $subject, $description, $deadline) {

            return $this->db->Insert(
                'INSERT INTO PayRequests (`ClassID`, `RequestedBy`, `Subject`, `Description`, `Date`, `Deadline`) VALUES (?, ?, ?, ?, NOW(), ?)',
                [ $classid, $userid, $subject, $description, $deadline ]
            );

        }

        function InsertDebts($data) {

            return $this->db->InsertMultiple(
                'INSERT INTO UserDebts (RequestID, UserID, RequiredAmount) VALUES (?,?,?)',
                $data
            );

        }

        function GetFirstResult($result) {
            
            if(count($result) > 0)
                $result = $result[0];
            else
                $result = false;
            
            return $result;

        }

    }