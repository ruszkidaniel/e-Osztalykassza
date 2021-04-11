CREATE TABLE `ClassGroupMembers` (
  `GroupID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL
)

CREATE TABLE `ClassGroups` (
  `GroupID` int(11) NOT NULL,
  `GroupName` varchar(32) NOT NULL,
  `ClassID` int(11) NOT NULL
)

CREATE TABLE `ClassMembers` (
  `ClassID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Permissions` int(11) DEFAULT NULL
)

CREATE TABLE `Classrooms` (
  `ClassID` int(11) NOT NULL,
  `OwnerID` int(11) NOT NULL,
  `SchoolID` int(11) NOT NULL,
  `ClassName` varchar(32) NOT NULL,
  `Description` varchar(1024) NOT NULL,
  `Balance` int(11) NOT NULL DEFAULT 0,
  `MaxMembers` int(11) NOT NULL DEFAULT 35
)

CREATE TABLE `EmailIgnoreList` (
  `Address` varchar(64) NOT NULL,
  `Date` datetime NOT NULL
)

CREATE TABLE `FailedLogins` (
  `LoginID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `SessionID` varchar(40) NOT NULL,
  `Date` datetime NOT NULL
) 

CREATE TABLE `Invites` (
  `InviteID` int(11) NOT NULL,
  `InviteCode` varchar(64) NOT NULL,
  `InvitedBy` int(11) NOT NULL,
  `ClassID` int(11) NOT NULL,
  `Email` varchar(64) NOT NULL,
  `Date` datetime NOT NULL,
  `Status` enum('pending','canceled','accepted','declined') NOT NULL
)

CREATE TABLE `IpAddresses` (
  `IPID` int(11) NOT NULL,
  `IP` varchar(45) NOT NULL
)

CREATE TABLE `PayLog` (
  `PayID` int(11) NOT NULL,
  `DebtID` int(11) NOT NULL,
  `Type` int(11) NOT NULL,
  `Date` datetime NOT NULL,
  `UserID` int(11) NOT NULL,
  `Amount` int(11) NOT NULL
)

CREATE TABLE `PayRequests` (
  `RequestID` int(11) NOT NULL,
  `ClassID` int(11) NOT NULL,
  `RequestedBy` int(11) NOT NULL,
  `Subject` varchar(32) NOT NULL,
  `Description` text NOT NULL,
  `Date` datetime NOT NULL,
  `Deadline` date DEFAULT NULL
)

CREATE TABLE `Schools` (
  `SchoolID` int(11) NOT NULL,
  `SchoolName` varchar(64) NOT NULL
)

CREATE TABLE `Sessions` (
  `SessionID` varchar(40) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `IPID` int(11) NOT NULL,
  `FirstInteraction` datetime NOT NULL,
  `LastInteraction` datetime NOT NULL,
  `UserAgentID` int(11) NOT NULL
)

CREATE TABLE `UserAgents` (
  `UserAgentID` int(11) NOT NULL,
  `UserAgent` text NOT NULL
)

CREATE TABLE `UserDebts` (
  `DebtID` int(11) NOT NULL,
  `RequestID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Amount` int(11) DEFAULT NULL,
  `RequiredAmount` int(11) NOT NULL,
  `IsDone` tinyint(4) NOT NULL DEFAULT 0
) 

CREATE TABLE `UserIpTable` (
  `UserID` int(11) NOT NULL,
  `IPID` int(11) NOT NULL
)

CREATE TABLE `Users` (
  `UserID` int(11) NOT NULL,
  `UserName` varchar(32) NOT NULL,
  `Email` varchar(64) NOT NULL,
  `GlobalPermissions` int(11) NOT NULL,
  `AccountType` enum('normal','facebook') NOT NULL,
  `Password` varchar(64) NOT NULL,
  `PasswordSalt` varchar(8) NOT NULL,
  `2FA` varchar(16) DEFAULT NULL,
  `2FAType` int(1) DEFAULT NULL,
  `FullName` varchar(32) NOT NULL,
  `DOB` date DEFAULT NULL,
  `DOBHidden` int(1) DEFAULT NULL
)

CREATE TABLE `VerificationCodes` (
  `Code` varchar(16) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Date` datetime NOT NULL,
  `Type` enum('email','password') NOT NULL
) 

ALTER TABLE `ClassGroupMembers`
  ADD PRIMARY KEY (`GroupID`,`UserID`);

ALTER TABLE `ClassGroups`
  ADD PRIMARY KEY (`GroupID`);

ALTER TABLE `ClassMembers`
  ADD PRIMARY KEY (`ClassID`,`UserID`);

ALTER TABLE `Classrooms`
  ADD PRIMARY KEY (`ClassID`);

ALTER TABLE `EmailIgnoreList`
  ADD UNIQUE KEY `address` (`Address`);

ALTER TABLE `FailedLogins`
  ADD PRIMARY KEY (`LoginID`);

ALTER TABLE `Invites`
  ADD PRIMARY KEY (`InviteID`),
  ADD UNIQUE KEY `InviteCode` (`InviteCode`);

ALTER TABLE `IpAddresses`
  ADD PRIMARY KEY (`IPID`);

ALTER TABLE `PayLog`
  ADD PRIMARY KEY (`PayID`);

ALTER TABLE `PayRequests`
  ADD PRIMARY KEY (`RequestID`);

ALTER TABLE `Schools`
  ADD PRIMARY KEY (`SchoolID`);

ALTER TABLE `Sessions`
  ADD PRIMARY KEY (`SessionID`);

ALTER TABLE `UserAgents`
  ADD PRIMARY KEY (`UserAgentID`);

ALTER TABLE `UserDebts`
  ADD PRIMARY KEY (`DebtID`);

ALTER TABLE `UserIpTable`
  ADD PRIMARY KEY (`UserID`,`IPID`) USING BTREE;

ALTER TABLE `Users`
  ADD PRIMARY KEY (`UserID`);

ALTER TABLE `VerificationCodes`
  ADD PRIMARY KEY (`Code`);