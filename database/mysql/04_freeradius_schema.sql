#
# Table structure for table 'radacct'
#
CREATE TABLE radacct (
  radacctid 		bigint(21) 		NOT NULL 	auto_increment,
  acctsessionid 	varchar(64) 	NOT NULL 	default '',
  acctuniqueid 		varchar(32) 	NOT NULL 	default '',
  username 			varchar(64) 	NOT NULL 	default '',
  groupname 		varchar(64) 	NOT NULL 	default '',
  realm 			varchar(64) 				default '',
  nasipaddress 		varchar(15) 	NOT NULL 	default '',
  nasportid 		varchar(15) 				default NULL,
  nasporttype 		varchar(32) 				default NULL,
  acctstarttime 	datetime 		NULL 		default NULL,
  acctstoptime 		datetime 		NULL 		default NULL,
  acctsessiontime 	int(12) 					default NULL,
  acctauthentic 	varchar(32) 				default NULL,
  connectinfo_start varchar(50) 				default NULL,
  connectinfo_stop 	varchar(50) 				default NULL,
  acctinputoctets 	bigint(20) 					default NULL,
  acctoutputoctets 	bigint(20) 					default NULL,
  calledstationid 	varchar(50) 	NOT NULL 	default '',
  callingstationid 	varchar(50) 	NOT NULL 	default '',
  acctterminatecause varchar(32) 	NOT NULL 	default '',
  servicetype 		varchar(32) 				default NULL,
  framedprotocol 	varchar(32) 				default NULL,
  framedipaddress 	varchar(15) 	NOT NULL 	default '',
  acctstartdelay 	int(12) 					default NULL,
  acctstopdelay 	int(12) 					default NULL,
  xascendsessionsvrkey varchar(10) 				default NULL,
  PRIMARY KEY  (radacctid),
  UNIQUE KEY acctuniqueid (acctuniqueid),
  KEY username (username),
  KEY framedipaddress (framedipaddress),
  KEY acctsessionid (acctsessionid),
  KEY acctsessiontime (acctsessiontime),
  KEY acctstarttime (acctstarttime),
  KEY acctstoptime (acctstoptime),
  KEY nasipaddress (nasipaddress)
) ENGINE=InnoDB;

#
# Table structure for table 'radcheck'
#
CREATE TABLE radcheck (
  id 			int(11) 	unsigned 	NOT NULL 	auto_increment,
  userid 		int(11) 	unsigned 	NOT NULL,
  username 		varchar(64) 			NOT NULL 	default '',
  attribute 	varchar(64)  			NOT NULL 	default '',
  op 			char(2) 				NOT NULL 	DEFAULT '==',
  value 		varchar(253) 			NOT NULL 	default '',
  PRIMARY KEY  (id)
) ENGINE=InnoDB;
CREATE INDEX `radcheck_1` ON `radcheck` (`username`);

#
# Table structure for table 'radgroupcheck'
#
CREATE TABLE radgroupcheck (
  id 			int(11) 	unsigned 	NOT NULL 	auto_increment,
  groupname 	varchar(64) 			NOT NULL 	default '',
  attribute 	varchar(64)  			NOT NULL 	default '',
  op 			char(2) 				NOT NULL 	DEFAULT '==',
  value 		varchar(253)  			NOT NULL 	default '',
  PRIMARY KEY  (id)
) ENGINE=InnoDB;
CREATE INDEX `radgroupcheck_1` ON `radgroupcheck` (`groupname`);

#
# Table structure for table 'radgroupreply'
#
CREATE TABLE radgroupreply (
  id 			int(11)		unsigned 	NOT NULL 	auto_increment,
  groupname 	varchar(64)		 		NOT NULL 	default '',
  attribute 	varchar(64)  			NOT NULL 	default '',
  op 			char(2) 				NOT NULL 	DEFAULT '=',
  value 		varchar(253)  			NOT NULL 	default '',
  PRIMARY KEY  (id)
) ENGINE=InnoDB;

#
# Table structure for table 'radreply'
#
CREATE TABLE radreply (
  id 			int(11)		unsigned 	NOT NULL 	auto_increment,
  username 		varchar(64) 			NOT NULL 	default '',
  attribute 	varchar(64) 			NOT NULL 	default '',
  op 			char(2) 				NOT NULL 	DEFAULT '=',
  value 		varchar(253) 			NOT NULL 	default '',
  PRIMARY KEY  (id),
  KEY username (username(32))
) ENGINE=InnoDB;

#
# Table structure for table 'radusergroup'
#
CREATE TABLE radusergroup (
  username 		varchar(64) 	NOT NULL 	default '',
  userid 		int(11) 		unsigned 	NOT NULL, 
  groupname 	varchar(64) 	NOT NULL 	default '',
  priority 		int(11) 		NOT NULL 	default '1',
  KEY username (username(32))
) ENGINE=InnoDB;

#
# Table structure for table 'radpostauth'
#
CREATE TABLE radpostauth (
  id 			int(11) 		NOT NULL 	auto_increment,
  username 		varchar(64) 	NOT NULL 	default '',
  pass 			varchar(64) 	NOT NULL 	default '',
  reply 		varchar(32) 	NOT NULL 	default '',
  authdate 		timestamp 		NOT NULL,
  PRIMARY KEY  (id)
) ENGINE=InnoDB;

#
# Table structure for table 'nas'
#
CREATE TABLE nas (
  id 			int(10) 		NOT NULL 	auto_increment,
  nasname 		varchar(128) 	NOT NULL,
  shortname 	varchar(32),
  type 			varchar(30) 				DEFAULT 'other',
  ports 		int(5),
  secret		varchar(60)		NOT NULL 	DEFAULT 'secret',
  server		varchar(64),
  community		varchar(50),
  description 	varchar(200) 				DEFAULT 'RADIUS Client',
  PRIMARY KEY (id)
) ENGINE=InnoDB;

#
# Table structure for table 'radippool'
#
CREATE TABLE radippool ( 
  id				int(11) unsigned NOT NULL 	auto_increment,
  pool_name         varchar(30) 	NOT NULL,
  framedipaddress   varchar(15) 	NOT NULL 	DEFAULT '',
  nasipaddress      varchar(15) 	NOT NULL 	DEFAULT '',
  calledstationid   VARCHAR(30) 	NOT NULL,
  callingstationid  VARCHAR(30) 	NOT NULL,
  expiry_time       DATETIME NULL 				DEFAULT NULL,
  username          varchar(64) 	NOT NULL 	DEFAULT '',
  pool_key          varchar(30) 	NOT NULL	,
  PRIMARY KEY (id)
) ENGINE=InnoDB;


ALTER TABLE `radcheck` ADD CONSTRAINT `c_radcheck_1` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `radusergroup` ADD CONSTRAINT `c_radusergroup_1` FOREIGN KEY (`username`) REFERENCES `radcheck` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `radusergroup` ADD CONSTRAINT `c_radusergroup_2` FOREIGN KEY (`groupname`) REFERENCES `radgroupcheck` (`groupname`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `radusergroup` ADD CONSTRAINT `c_radusergroup_3` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

INSERT INTO `radgroupcheck` VALUES (1,'expired','Auth-Type',':=','CHAP'),
								(2,'expired','Pool-Name',':=','expired');
								
INSERT INTO `radgroupreply` VALUES (1,'expired','Framed-Protocol','=','PPP'),
								(2,'expired','Service-Type','=','Framed-User'),
								(3,'expired','Framed-MTU','=','1500'),
								(4,'expired','Framed-Compression','=','None'),
								(5,'expired','Acct-Interim-Interval','=','180');
