#
# Table structure for table 'requests'
#
CREATE TABLE `requests` (
	`requestid`		int(11)			UNSIGNED			NOT NULL AUTO_INCREMENT,
	`operid`		int(4)			UNSIGNED			NOT NULL,
	`status`		TINYINT(1)		UNSIGNED			NOT NULL,
	`add`			datetime 				 			NOT NULL,
	`assign`		datetime 				 			NOT NULL,
	`end`			datetime 				 			NOT NULL,
	`created`		varchar(128)	DEFAULT ''			NOT NULL,
	`changed`		varchar(128)	DEFAULT ''			NOT NULL,
	`closed`		varchar(128)	DEFAULT ''			NOT NULL,
	`name`			varchar(128)	DEFAULT ''			NOT NULL,
	`address`		varchar(128)	DEFAULT ''			NOT NULL,
	`phone_number`	varchar(128)	DEFAULT ''			NOT NULL,
	`notes`			text			DEFAULT ''			NOT NULL,
	PRIMARY KEY (requestid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Table structure for table 'tickets'
#
CREATE TABLE `tickets` (
	`ticketid`		int(11)			UNSIGNED			NOT NULL AUTO_INCREMENT,
	`userid`		int(11)			UNSIGNED			NOT NULL,
	`operid`		int(4)			UNSIGNED			NOT NULL,
	`status`		TINYINT(1)		UNSIGNED			NOT NULL,
	`add`			datetime 				 			NOT NULL,
	`assign`		datetime 				 			NOT NULL,
	`end`			datetime 				 			NOT NULL,
	`created`		varchar(128)	DEFAULT ''			NOT NULL,
	`changed`		varchar(128)	DEFAULT ''			NOT NULL,
	`closed`		varchar(128)	DEFAULT ''			NOT NULL,
	`notes`			text			DEFAULT ''			NOT NULL,
	PRIMARY KEY (ticketid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
