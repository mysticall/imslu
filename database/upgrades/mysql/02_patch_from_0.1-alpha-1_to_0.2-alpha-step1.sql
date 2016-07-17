--
-- Table structure for table 'kind_traffic'
--
CREATE TABLE kind_traffic (
  `kind_trafficid` int(11)      UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`           varchar(64)  NOT NULL,
  `notes`          text         NOT NULL DEFAULT '',
  PRIMARY KEY (kind_trafficid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
INSERT INTO `kind_traffic` VALUES (1,'peer','BGP peer - national traffic'), (2,'int','International traffic');

--
-- Table structure for table 'Services'
--
CREATE TABLE `services` (
  `serviceid`      int(11)      UNSIGNED NOT NULL AUTO_INCREMENT,
  `kind_trafficid` int(11)      UNSIGNED NOT NULL,
  `name`           varchar(64)  NOT NULL,
  `price`          double(10,2) NOT NULL DEFAULT '0.00',
  `in_min`         varchar(32)  NOT NULL DEFAULT '32kbit',
  `in_max`         varchar(32)  NOT NULL,
  `out_min`        varchar(32)  NOT NULL DEFAULT '32kbit',
  `out_max`        varchar(32)  NOT NULL,
  PRIMARY KEY (serviceid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table 'ip'
--
CREATE TABLE `ip` (
  `id`       int(11)       UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid`   int(11)       NOT NULL,
  `ip`       varchar(39)   NOT NULL DEFAULT '',
  `vlan`     varchar(17)   NOT NULL DEFAULT '',
  `mac`      varchar(17)   NOT NULL DEFAULT '',
  `free_mac` enum('n','y') NOT NULL DEFAULT 'n',
  `username` varchar(64)   NOT NULL DEFAULT '',
  `pass`     varchar(64)   NOT NULL DEFAULT '',
  `pool`     varchar(64)   NOT NULL DEFAULT '',
  `protocol` varchar(10)   NOT NULL DEFAULT 'IP',
  `stopped`  enum('n','y') NOT NULL DEFAULT 'n',
  `notes`    text          NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  INDEX (ip(15)),
  INDEX (pool(9))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- users
--
ALTER TABLE users DROP INDEX users_1;
CREATE INDEX name ON users (name(32));
CREATE INDEX address ON users (address(32));
CREATE INDEX phone_number ON users (phone_number(15));
ALTER TABLE users ADD service varchar(64) NOT NULL AFTER created;

--
-- payments
--
CREATE INDEX userid ON payments (userid);
CREATE INDEX expires ON payments (expires);

--
-- FreeRadius
--
ALTER TABLE `radcheck` DROP FOREIGN KEY `c_radcheck_1`;
ALTER TABLE `radcheck` DROP INDEX `c_radcheck_1`;
ALTER TABLE `radcheck` DROP INDEX `radcheck_1`;
CREATE INDEX `username` ON `radcheck` (username(32));
ALTER TABLE `radcheck` ADD CONSTRAINT `c_radcheck_1` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE;
DELETE FROM `radcheck` WHERE attribute='Simultaneous-Use' OR attribute='Expiration';

ALTER TABLE `radusergroup` DROP FOREIGN KEY `c_radusergroup_1`;
ALTER TABLE `radusergroup` DROP FOREIGN KEY `c_radusergroup_2`;
ALTER TABLE `radusergroup` DROP FOREIGN KEY `c_radusergroup_3`;
ALTER TABLE `radusergroup` DROP INDEX `c_radusergroup_1`;
ALTER TABLE `radusergroup` DROP INDEX `c_radusergroup_2`;
ALTER TABLE `radusergroup` DROP INDEX `c_radusergroup_3`;
ALTER TABLE `radusergroup` DROP INDEX username;
ALTER TABLE `radusergroup` ADD PRIMARY KEY (username(32));
ALTER TABLE `radusergroup` MODIFY userid int(11) unsigned NOT NULL AFTER priority;
ALTER TABLE `radusergroup` ADD CONSTRAINT `c_radusergroup_1` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE;
UPDATE `radusergroup` SET groupname='default';

DELETE FROM radgroupcheck;
ALTER TABLE `radgroupcheck` DROP INDEX `radgroupcheck_1`;
CREATE INDEX `groupname` ON `radgroupcheck` (groupname(32));
INSERT INTO `radgroupcheck` (`groupname`, `attribute`, `op`, `value`) VALUES ('default','Pool-Name',':=','PPPoE');

DELETE FROM radgroupreply;
CREATE INDEX `groupname` ON `radgroupreply` (groupname(32));
INSERT INTO `radgroupreply`  (`groupname`, `attribute`, `op`, `value`) VALUES ('default','Framed-Protocol','=','PPP'), ('default','Service-Type','=','Framed-User'), ('default','Framed-MTU','=','1500'), ('default','Framed-Compression','=','None'), ('default','Acct-Interim-Interval','=','60');

