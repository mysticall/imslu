
CREATE TABLE `sessions` (
  `sessionid`    char(128) NOT NULL DEFAULT '',
  `set_time`     char(10)  NOT NULL,
  `data`         blob      NOT NULL,
  `login_string` char(128) NOT NULL,
  PRIMARY KEY (sessionid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `opergrp` (
  `opergrpid` int(4)      UNSIGNED NOT NULL,
  `name`      varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (opergrpid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
INSERT INTO opergrp VALUES (1,'Cashiers'), (2,'Network Technicians'), (3,'Administrators'), (4,'LINUX Administrators');

CREATE TABLE `operators` (
  `operid`  int(4)       UNSIGNED  NOT NULL AUTO_INCREMENT,
  `alias`   varchar(50)  NOT NULL  DEFAULT '',
  `name`    varchar(64)  NOT NULL  DEFAULT '',
  `passwd`  char(32)    NOT NULL  DEFAULT '',
  `url`     varchar(128) NOT NULL  DEFAULT '',
  `lang`    varchar(5)   NOT NULL  DEFAULT 'en_US',
  `theme`   varchar(32)  NOT NULL  DEFAULT 'originalgreen',
  `type`    int(4)       UNSIGNED  NULL,
  PRIMARY KEY (operid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE UNIQUE INDEX `operators_1` ON `operators` (`alias`,`type`);
INSERT INTO operators (operid, alias, name, passwd, lang, theme, type) VALUES (1, 'sadmin', 'System Administrator', MD5('sadmin'), 'en_US', 'originalgreen', 4), (2, 'admin', 'Administrator', MD5('admin'), 'en_US', 'originalgreen', 3);

CREATE TABLE `operators_groups` (
  `id`        int(4) UNSIGNED NOT NULL AUTO_INCREMENT,
  `opergrpid` int(4) UNSIGNED NOT NULL,
  `operid`    int(4) UNSIGNED NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE UNIQUE INDEX `operators_groups_1` ON `operators_groups` (`opergrpid`,`operid`);
INSERT INTO operators_groups (id, opergrpid, operid) VALUES ('1','4','1'), ('2','3','2');

CREATE TABLE `login_attempts` (
  `attempt_failed` int(11)     NOT NULL DEFAULT '1',
  `attempt_time`   varchar(30) NOT NULL DEFAULT '',
  `attempt_ip`     varchar(50) NOT NULL DEFAULT '',
  `alias`          varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (attempt_ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `auditlog` (
  `auditid`    bigint       UNSIGNED NOT NULL AUTO_INCREMENT,
  `actionid`   int(4)       UNSIGNED NOT NULL,
  `resourceid` int(4)       UNSIGNED NOT NULL,
  `operid`     int(4)       UNSIGNED NOT NULL,
  `oper_alias` varchar(50)  NOT NULL,
  `date_time`  datetime     NOT NULL,
  `ip`         varchar(39)  NOT NULL,
  `details`    varchar(255) NOT NULL,
  `oldvalue`   text         NOT NULL,
  `newvalue`   text         NOT NULL,
  PRIMARY KEY (auditid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table 'kind_traffic'
--
CREATE TABLE kind_traffic (
  `id` int(11)     UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`           varchar(64) NOT NULL,
  `notes`          text        NOT NULL DEFAULT '',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
INSERT INTO `kind_traffic` VALUES (1,'peer','BGP peer - national traffic'), (2,'int','International traffic');

--
-- Table structure for table 'Services'
--
CREATE TABLE `services` (
  `serviceid` int(11)      UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`      varchar(64)  NOT NULL,
  `price`     double(10,2) NOT NULL DEFAULT '0.00',
  `in_min0`   varchar(32)  NOT NULL DEFAULT '32kbit',
  `in_max0`   varchar(32)  NULL,
  `out_min0`  varchar(32)  NOT NULL DEFAULT '32kbit',
  `out_max0`  varchar(32)  NULL,
  `in_min1`   varchar(32)  NOT NULL DEFAULT '32kbit',
  `in_max1`   varchar(32)  NULL,
  `out_min1`  varchar(32)  NOT NULL DEFAULT '32kbit',
  `out_max1`  varchar(32)  NULL,
  `in_min2`   varchar(32)  NOT NULL DEFAULT '32kbit',
  `in_max2`   varchar(32)  NULL,
  `out_min2`  varchar(32)  NOT NULL DEFAULT '32kbit',
  `out_max2`  varchar(32)  NULL,
  `in_min3`   varchar(32)  NOT NULL DEFAULT '32kbit',
  `in_max3`   varchar(32)  NULL,
  `out_min3`  varchar(32)  NOT NULL DEFAULT '32kbit',
  `out_max3`  varchar(32)  NULL,
  `in_min4`   varchar(32)  NOT NULL DEFAULT '32kbit',
  `in_max4`   varchar(32)  NULL,
  `out_min4`  varchar(32)  NOT NULL DEFAULT '32kbit',
  `out_max4`  varchar(32)  NULL,
  PRIMARY KEY (serviceid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
INSERT INTO `services` (`serviceid`, `name`, `price`, `in_max0`, `out_max0`, `in_max1`, `out_max1`) VALUES (1, 'LOW', 15, '30mbit', '15mbit', '15mbit', '10mbit'), (2, 'HIGH', 20, '50mbit', '35mbit', '25mbit', '20mbit');

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
-- Table structure for table 'The location'
--
CREATE TABLE `location` (
  `id`   int(11)      UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table 'users'
--
CREATE TABLE `users` (
  `userid`        int(11)       UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          varchar(64)   NOT NULL DEFAULT '',
  `locationid`    int(11)       NOT NULL DEFAULT '0',
  `address`       varchar(128)  NOT NULL DEFAULT '',
  `phone_number`  varchar(32)   NOT NULL DEFAULT '',
  `notes`         text          NOT NULL DEFAULT '',
  `created`       datetime      NOT NULL,
  `serviceid`     int(11)       NOT NULL DEFAULT '0',
  `pay`           double(10,2)  NOT NULL DEFAULT '0.00',
  `free_access`   enum('n','y') NOT NULL DEFAULT 'n',
  `not_excluding` enum('n','y') NOT NULL DEFAULT 'n',
  `expires`       datetime     NOT NULL,
  PRIMARY KEY (userid),
  INDEX (name(32)),
  INDEX (address(32)),
  INDEX (phone_number(15))
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
INSERT INTO `users` (`userid`,`name`, `serviceid`) VALUES ('10','test', '1');

--
-- Table structure for table 'payments'
--
CREATE TABLE `payments` (
  `id`            bigint       UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid`        int(11)      NOT NULL,
  `name`          varchar(64)  NOT NULL DEFAULT '',
  `unpaid`        TINYINT(1)   NOT NULL DEFAULT '0',
  `limited`       TINYINT(1)   NOT NULL DEFAULT '0',
  `reported`      TINYINT(1)   NOT NULL DEFAULT '0',
  `operator1`     varchar(128) NOT NULL DEFAULT '',
  `operator2`     varchar(128) NOT NULL DEFAULT '',
  `date_payment1` datetime     NULL,
  `date_payment2` datetime     NULL,
  `expires`       datetime     NOT NULL,
  `sum`           double(10,2) NOT NULL DEFAULT '0.00',
  `notes`         text         NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  INDEX (userid),
  INDEX (expires)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table 'requests'
--
CREATE TABLE `requests` (
  `requestid`    int(11)      UNSIGNED NOT NULL AUTO_INCREMENT,
  `operid`       int(4)       UNSIGNED NOT NULL,
  `status`       TINYINT(1)   UNSIGNED NOT NULL,
  `add`          datetime     NOT NULL,
  `assign`       datetime     NOT NULL,
  `end`          datetime     NOT NULL,
  `created`      varchar(64)  NOT NULL DEFAULT '',
  `changed`      varchar(64)  NOT NULL DEFAULT '',
  `closed`       varchar(64)  NOT NULL DEFAULT '',
  `name`         varchar(64)  NOT NULL DEFAULT '',
  `address`      varchar(128) NOT NULL DEFAULT '',
  `phone_number` varchar(32)  NOT NULL DEFAULT '',
  `notes`        text         NOT NULL DEFAULT '',
  PRIMARY KEY (requestid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table 'tickets'
--
CREATE TABLE `tickets` (
  `ticketid` int(11)     UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid`   int(11)     UNSIGNED NOT NULL,
  `operid`   int(4)      UNSIGNED NOT NULL,
  `status`   TINYINT(1)  UNSIGNED NOT NULL,
  `add`      datetime    NOT NULL,
  `assign`   datetime    NOT NULL,
  `end`      datetime    NOT NULL,
  `created`  varchar(64) NOT NULL DEFAULT '',
  `changed`  varchar(64) NOT NULL DEFAULT '',
  `closed`   varchar(64) NOT NULL DEFAULT '',
  `notes`    text        NOT NULL DEFAULT '',
  PRIMARY KEY (ticketid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `operators` ADD CONSTRAINT `c_operators_1` FOREIGN KEY (`type`) REFERENCES `opergrp` (`opergrpid`) ON DELETE SET NULL;
ALTER TABLE `operators_groups` ADD CONSTRAINT `c_operators_groups_1` FOREIGN KEY (`opergrpid`) REFERENCES `opergrp` (`opergrpid`) ON DELETE CASCADE;
ALTER TABLE `operators_groups` ADD CONSTRAINT `c_operators_groups_2` FOREIGN KEY (`operid`) REFERENCES `operators` (`operid`) ON DELETE CASCADE;

