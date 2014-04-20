
CREATE TABLE `sessions` (
  `sessionid`		char(128) 	DEFAULT ''	NOT NULL,
  `set_time` 		char(10) 				NOT NULL,
  `data` 			blob 					NOT NULL,
  `login_string` 	char(128) 				NOT NULL,
  PRIMARY KEY (sessionid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `operators` (
	`operid`		int(4)			UNSIGNED			NOT NULL AUTO_INCREMENT,
	`alias`			varchar(50)		DEFAULT ''			NOT NULL,
	`name`			varchar(128)	DEFAULT ''			NOT NULL,
	`passwd`		char(128)		DEFAULT ''			NOT NULL,
	`salt`			char(128)		DEFAULT ''			NOT NULL,
	`url`			varchar(255)	DEFAULT '' 			NOT NULL,
	`lang`			varchar(5)		DEFAULT 'en_US' 	NOT NULL,
	`theme`			varchar(128)    DEFAULT 'originalgreen'	NOT NULL,
	`refresh`		int(4)			DEFAULT '30'		NOT NULL,
	`type`			int(4)			UNSIGNED			NULL,
  PRIMARY KEY (operid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE UNIQUE INDEX `operators_1` ON `operators` (`alias`,`type`);

CREATE TABLE `opergrp` (
  `opergrpid`		int(4)		UNSIGNED	NOT NULL,
  `name`			varchar(64)	DEFAULT ''	NOT NULL,
  PRIMARY KEY (opergrpid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `operators_groups` (
  `id`			int(4)		UNSIGNED	NOT NULL	AUTO_INCREMENT,
  `opergrpid`	int(4)		UNSIGNED	NOT NULL,
  `operid`		int(4)		UNSIGNED	NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE UNIQUE INDEX `operators_groups_1` ON `operators_groups` (`opergrpid`,`operid`);

CREATE TABLE `login_attempts` (
	`attempt_failed`	int(11)			DEFAULT '1'		NOT NULL,
	`attempt_time`		varchar(30)     DEFAULT ''		NOT NULL,
	`attempt_ip`		varchar(50)     DEFAULT ''		NOT NULL,
	`alias`				varchar(50)		DEFAULT ''		NOT NULL,
  PRIMARY KEY (attempt_ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `auditlog` (
	`auditid`				bigint UNSIGNED				NOT NULL	AUTO_INCREMENT,
	`actionid`				int(4) UNSIGNED				NOT NULL,
	`resourceid`			int(4) UNSIGNED				NOT NULL,
	`operid`				int(4) UNSIGNED				NOT NULL,
	`oper_alias`			varchar(50)					NOT NULL,
	`date_time`				datetime					NOT NULL,
	`ip`					varchar(39)					NOT NULL,
	`details`				varchar(255)				NOT NULL,
	`oldvalue`				text						NOT NULL,
	`newvalue`				text						NOT NULL,

	PRIMARY KEY (auditid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Table structure for table 'Static IPPOOL'
#
CREATE TABLE `static_ippool` (
	`id`			int(11)			UNSIGNED			NOT NULL AUTO_INCREMENT,
	`userid`		int(11)			DEFAULT '0'			NOT NULL,
	`trafficid`		int(11)			DEFAULT '0'			NOT NULL,
	`ipaddress`		varchar(128)	DEFAULT ''			NOT NULL,
	`subnet`		int(2)			DEFAULT '32'		NOT NULL,
	`vlan`			varchar(128)	DEFAULT ''			NOT NULL,
	`mac`			varchar(128)	DEFAULT ''			NOT NULL,
	`mac_info`		varchar(128)	DEFAULT ''			NOT NULL,
	`free_mac`		TINYINT(1)		DEFAULT '0'			NOT NULL,
	`pool_name`		varchar(128)	DEFAULT ''			NOT NULL,
	`network_type`	varchar(128)	DEFAULT ''			NOT NULL,
	`name`			varchar(128)	DEFAULT ''			NOT NULL,
	`notes`			text			DEFAULT ''			NOT NULL,
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Table structure for table 'Traffic'
#
 -- local_in - Country based local traffic download speed
 -- local_out - Country based local traffic upload speed
 -- int_in - Internacional traffic download speed
 -- int_out - Internacional traffic upload speed
CREATE TABLE `traffic` (
	`trafficid`		int(11)			UNSIGNED			NOT NULL AUTO_INCREMENT,
	`name`			varchar(128)						NOT NULL,
	`price`			double(10,2)	DEFAULT '0.00'		NOT NULL,
	`local_in`		int(11)								NOT NULL,
	`local_out`		int(11)								NOT NULL,
	`int_in`		int(11)								NOT NULL,
	`int_out`		int(11)								NOT NULL,
	PRIMARY KEY (trafficid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Table structure for table 'The location'
#
CREATE TABLE `location` (
	`id`			int(11)			UNSIGNED			NOT NULL AUTO_INCREMENT,
	`name`			varchar(128)	DEFAULT ''			NOT NULL,
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Table structure for table 'Switches'
#
CREATE TABLE `switches` (
	`id`			int(11)			UNSIGNED			NOT NULL AUTO_INCREMENT,
	`name`			varchar(128)	DEFAULT ''			NOT NULL,
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Table structure for table 'users'
#
CREATE TABLE `users` (
	`userid`		int(11)			UNSIGNED			NOT NULL AUTO_INCREMENT,
	`name`			varchar(128)	DEFAULT ''			NOT NULL,
	`locationid`	int(11)			DEFAULT '0'			NOT NULL,
	`address`		varchar(128)	DEFAULT ''			NOT NULL,
	`phone_number`	varchar(128)	DEFAULT ''			NOT NULL,
	`notes`			text			DEFAULT ''			NOT NULL,
	`created`		datetime 				 			NOT NULL,
	`trafficid`		int(11)								NOT NULL,
	`pay`			double(10,2)	DEFAULT '0.00'		NOT NULL,
	`free_access`	TINYINT(1)		DEFAULT '0'			NOT NULL,
	`not_excluding`	TINYINT(1)		DEFAULT '0'			NOT NULL,
	`switchid`		int(11)			DEFAULT '0'			NOT NULL,
	`pppoe`			TINYINT(1)		DEFAULT '0'			NOT NULL,
	PRIMARY KEY (userid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE UNIQUE INDEX `users_1` ON `users` (`userid`,`name`, `address`, `phone_number`);


#
# Table structure for table 'payments'
#
CREATE TABLE `payments` (
	`id`			bigint			UNSIGNED			NOT NULL AUTO_INCREMENT,
	`userid`		int(11)								NOT NULL,
	`name`			varchar(128)	DEFAULT ''			NOT NULL,
	`username`		varchar(128)	DEFAULT ''			NOT NULL,
	`unpaid`		TINYINT(1)		DEFAULT '0'			NOT NULL,
	`limited`		TINYINT(1)		DEFAULT '0'			NOT NULL,
	`reported`		TINYINT(1)		DEFAULT '0'			NOT NULL,
	`operator1`		varchar(128)	DEFAULT ''			NULL,
	`operator2`		varchar(128)	DEFAULT ''			NULL,
	`date_payment1`	datetime							NULL,
	`date_payment2`	datetime							NULL,
	`expires`		datetime							NOT NULL,
	`sum`			double(10,2)	DEFAULT '0.00'		NOT NULL,
	`notes`			text			DEFAULT ''			NULL,
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `operators` ADD CONSTRAINT `c_operators_1` FOREIGN KEY (`type`) REFERENCES `opergrp` (`opergrpid`) ON DELETE SET NULL;
ALTER TABLE `operators_groups` ADD CONSTRAINT `c_operators_groups_1` FOREIGN KEY (`opergrpid`) REFERENCES `opergrp` (`opergrpid`) ON DELETE CASCADE;
ALTER TABLE `operators_groups` ADD CONSTRAINT `c_operators_groups_2` FOREIGN KEY (`operid`) REFERENCES `operators` (`operid`) ON DELETE CASCADE;

