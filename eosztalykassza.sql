CREATE DATABASE eosztalykassza CHARACTER SET utf8 COLLATE utf8_hungarian_ci;
USE eosztalykassza;

CREATE TABLE `ClassGroupMembers` (
  `GroupID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  PRIMARY KEY (`GroupID`,`UserID`)
);

CREATE TABLE `EmailIgnoreList` (
  `Address` varchar(64) NOT NULL,
  `Date` datetime NOT NULL,
  UNIQUE KEY `address` (`Address`)
);

CREATE TABLE `IpAddresses` (
  `IPID` int(11) NOT NULL AUTO_INCREMENT,
  `IP` varchar(45) NOT NULL,
  PRIMARY KEY (`IPID`)
);

CREATE TABLE `Schools` (
  `SchoolID` int(11) NOT NULL AUTO_INCREMENT,
  `SchoolName` varchar(64) NOT NULL,
  PRIMARY KEY (`SchoolID`)
);

CREATE TABLE `Users` (
  `UserID` int(11) NOT NULL AUTO_INCREMENT,
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
  `DOBHidden` int(1) DEFAULT NULL,
  PRIMARY KEY (`UserID`)
);

CREATE TABLE `ClassGroups` (
  `GroupID` int(11) NOT NULL AUTO_INCREMENT,
  `GroupName` varchar(32) NOT NULL,
  `ClassID` int(11) NOT NULL,
  PRIMARY KEY (`GroupID`)
);

CREATE TABLE `ClassMembers` (
  `ClassID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Permissions` int(11) DEFAULT NULL
);

CREATE TABLE `Classrooms` (
  `ClassID` int(11) NOT NULL AUTO_INCREMENT,
  `OwnerID` int(11) NOT NULL,
  `SchoolID` int(11) NOT NULL,
  `ClassName` varchar(32) NOT NULL,
  `Description` varchar(1024) NOT NULL,
  `Balance` int(11) NOT NULL DEFAULT 0,
  `MaxMembers` int(11) NOT NULL DEFAULT 35,
  PRIMARY KEY (`ClassID`)
);

CREATE TABLE `FailedLogins` (
  `LoginID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `SessionID` varchar(40) NOT NULL,
  `Date` datetime NOT NULL,
  PRIMARY KEY (`LoginID`)
);

CREATE TABLE `Invites` (
  `InviteID` int(11) NOT NULL AUTO_INCREMENT,
  `InviteCode` varchar(64) NOT NULL,
  `InvitedBy` int(11) NOT NULL,
  `ClassID` int(11) NOT NULL,
  `Email` varchar(64) NOT NULL,
  `Date` datetime NOT NULL,
  `Status` enum('pending','canceled','accepted','declined') NOT NULL,
  PRIMARY KEY (`InviteID`),
  UNIQUE KEY `InviteCode` (`InviteCode`)
);

CREATE TABLE `PayLog` (
  `PayID` int(11) NOT NULL AUTO_INCREMENT,
  `DebtID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Type` int(11) NOT NULL,
  `Date` datetime NOT NULL,
  `Amount` int(11) NOT NULL,
  PRIMARY KEY (`PayID`)
);


CREATE TABLE `PayRequests` (
  `RequestID` int(11) NOT NULL AUTO_INCREMENT,
  `ClassID` int(11) NOT NULL,
  `RequestedBy` int(11) NOT NULL,
  `Subject` varchar(32) NOT NULL,
  `Description` text NOT NULL,
  `Date` datetime NOT NULL,
  `Deadline` date DEFAULT NULL,
  PRIMARY KEY (`RequestID`)
);

CREATE TABLE `Sessions` (
  `SessionID` varchar(40) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `IPID` int(11) NOT NULL,
  `FirstInteraction` datetime NOT NULL,
  `LastInteraction` datetime NOT NULL,
  `UserAgentID` int(11) NOT NULL,
  PRIMARY KEY (`SessionID`)
);

CREATE TABLE `UserAgents` (
  `UserAgentID` int(11) NOT NULL AUTO_INCREMENT,
  `UserAgent` text NOT NULL,
  PRIMARY KEY (`UserAgentID`)
);

CREATE TABLE `UserDebts` (
  `DebtID` int(11) NOT NULL AUTO_INCREMENT,
  `RequestID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Amount` int(11) DEFAULT NULL,
  `RequiredAmount` int(11) NOT NULL,
  `IsDone` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`DebtID`)
);

CREATE TABLE `UserIpTable` (
  `UserID` int(11) NOT NULL,
  `IPID` int(11) NOT NULL
);

CREATE TABLE `VerificationCodes` (
  `Code` varchar(16) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Date` datetime NOT NULL,
  `Type` enum('email','password') NOT NULL,
  PRIMARY KEY (`Code`)
);

ALTER TABLE `ClassGroups` ADD FOREIGN KEY (`ClassID`) REFERENCES `Classrooms`(`ClassID`) ON DELETE CASCADE;
ALTER TABLE `ClassMembers` ADD FOREIGN KEY (`UserID`) REFERENCES `Users`(`UserID`) ON DELETE CASCADE;
ALTER TABLE `ClassMembers` ADD FOREIGN KEY (`ClassID`) REFERENCES `Classrooms`(`ClassID`) ON DELETE CASCADE;
ALTER TABLE `Classrooms` ADD FOREIGN KEY (`SchoolID`) REFERENCES `Schools`(`SchoolID`) ON DELETE CASCADE;
ALTER TABLE `Classrooms` ADD FOREIGN KEY (`OwnerID`) REFERENCES `Users`(`UserID`) ON DELETE CASCADE;
ALTER TABLE `FailedLogins` ADD FOREIGN KEY (`UserID`) REFERENCES `Users`(`UserID`) ON DELETE CASCADE;
ALTER TABLE `FailedLogins` ADD FOREIGN KEY (`SessionID`) REFERENCES `Sessions`(`SessionID`) ON DELETE CASCADE;
ALTER TABLE `Invites` ADD FOREIGN KEY (`InvitedBy`) REFERENCES `Users`(`UserID`) ON DELETE CASCADE;
ALTER TABLE `PayLog` ADD FOREIGN KEY (`DebtID`) REFERENCES `UserDebts`(`DebtID`) ON DELETE CASCADE;
ALTER TABLE `PayLog` ADD FOREIGN KEY (`UserID`) REFERENCES `Users`(`UserID`) ON DELETE CASCADE;
ALTER TABLE `PayRequests` ADD FOREIGN KEY (`ClassID`) REFERENCES `Classrooms`(`ClassID`) ON DELETE CASCADE;
ALTER TABLE `PayRequests` ADD FOREIGN KEY (`RequestedBy`) REFERENCES `Users`(`UserID`) ON DELETE CASCADE;
ALTER TABLE `Sessions` ADD FOREIGN KEY (`UserID`) REFERENCES `Users`(`UserID`) ON DELETE CASCADE;
ALTER TABLE `Sessions` ADD FOREIGN KEY (`IPID`) REFERENCES `IpAddresses`(`IPID`) ON DELETE CASCADE;
ALTER TABLE `Sessions` ADD FOREIGN KEY (`UserAgentID`) REFERENCES `UserAgents`(`UserAgentID`) ON DELETE CASCADE;
ALTER TABLE `UserDebts` ADD FOREIGN KEY (`RequestID`) REFERENCES `PayRequests`(`RequestID`) ON DELETE CASCADE;
ALTER TABLE `UserDebts` ADD FOREIGN KEY (`UserID`) REFERENCES `Users`(`UserID`) ON DELETE CASCADE;
ALTER TABLE `UserIpTable` ADD FOREIGN KEY (`UserID`) REFERENCES `Users`(`UserID`) ON DELETE CASCADE;
ALTER TABLE `UserIpTable` ADD FOREIGN KEY (`IPID`) REFERENCES `IpAddresses`(`IPID`) ON DELETE CASCADE;
ALTER TABLE `VerificationCodes` ADD FOREIGN KEY (`UserID`) REFERENCES `Users`(`UserID`) ON DELETE CASCADE;