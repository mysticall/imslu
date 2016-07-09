--
-- Table structure for table 'radacct'
--
CREATE TABLE radacct (
  radacctid         bigint(21)         NOT NULL     auto_increment,
  acctsessionid     varchar(64)     NOT NULL     default '',
  acctuniqueid         varchar(32)     NOT NULL     default '',
  username             varchar(64)     NOT NULL     default '',
  groupname         varchar(64)     NOT NULL     default '',
  realm             varchar(64)                 default '',
  nasipaddress         varchar(15)     NOT NULL     default '',
  nasportid         varchar(15)                 default NULL,
  nasporttype         varchar(32)                 default NULL,
  acctstarttime     datetime         NULL         default NULL,
  acctstoptime         datetime         NULL         default NULL,
  acctsessiontime     int(12)                     default NULL,
  acctauthentic     varchar(32)                 default NULL,
  connectinfo_start varchar(50)                 default NULL,
  connectinfo_stop     varchar(50)                 default NULL,
  acctinputoctets     bigint(20)                     default NULL,
  acctoutputoctets     bigint(20)                     default NULL,
  calledstationid     varchar(50)     NOT NULL     default '',
  callingstationid     varchar(50)     NOT NULL     default '',
  acctterminatecause varchar(32)     NOT NULL     default '',
  servicetype         varchar(32)                 default NULL,
  framedprotocol     varchar(32)                 default NULL,
  framedipaddress     varchar(15)     NOT NULL     default '',
  acctstartdelay     int(12)                     default NULL,
  acctstopdelay     int(12)                     default NULL,
  xascendsessionsvrkey varchar(10)                 default NULL,
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

--
-- Table structure for table 'radcheck'
--
CREATE TABLE radcheck (
  id        int(11)      unsigned NOT NULL auto_increment,
  userid    int(11)      unsigned NOT NULL,
  username  varchar(64)  NOT NULL DEFAULT '',
  attribute varchar(64)  NOT NULL DEFAULT '',
  op        char(2)      NOT NULL DEFAULT '==',
  value     varchar(253) NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  INDEX (username(32))
) ENGINE=InnoDB;
ALTER TABLE `radcheck` ADD CONSTRAINT `c_radcheck_1` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE;

--
-- Table structure for table 'radgroupcheck'
--
CREATE TABLE radgroupcheck (
  id        int(11)      UNSIGNED NOT NULL AUTO_INCREMENT,
  groupname varchar(64)  NOT NULL DEFAULT '',
  attribute varchar(64)  NOT NULL DEFAULT '',
  op        char(2)      NOT NULL DEFAULT '==',
  value     varchar(253) NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  INDEX (groupname(32))
) ENGINE=InnoDB;
INSERT INTO `radgroupcheck` (`groupname`, `attribute`, `op`, `value`) VALUES ('default','Pool-Name',':=','PPPoE');

--
-- Table structure for table 'radgroupreply'
--
CREATE TABLE radgroupreply (
  id        int(11)      UNSIGNED NOT NULL AUTO_INCREMENT,
  groupname varchar(64)  NOT NULL DEFAULT '',
  attribute varchar(64)  NOT NULL DEFAULT '',
  op        char(2)      NOT NULL DEFAULT '=',
  value     varchar(253) NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  INDEX (groupname(32))
) ENGINE=InnoDB;
INSERT INTO `radgroupreply`  (`groupname`, `attribute`, `op`, `value`) VALUES ('default','Framed-Protocol','=','PPP'), ('default','Service-Type','=','Framed-User'), ('default','Framed-MTU','=','1500'), ('default','Framed-Compression','=','None'), ('default','Acct-Interim-Interval','=','60');

--
-- Table structure for table 'radusergroup'
--
CREATE TABLE radusergroup (
  username  varchar(64) NOT NULL DEFAULT '',
  groupname varchar(64) NOT NULL DEFAULT '',
  priority  int(11)     NOT NULL DEFAULT '1',
  userid    int(11)     UNSIGNED NOT NULL,
  PRIMARY KEY (username(32))
) ENGINE=InnoDB;
ALTER TABLE `radusergroup` ADD CONSTRAINT `c_radusergroup_1` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE;

--
-- Table structure for table 'radpostauth'
--
CREATE TABLE radpostauth (
  id             int(11)         NOT NULL     AUTO_INCREMENT,
  username         varchar(64)     NOT NULL     DEFAULT '',
  pass             varchar(64)     NOT NULL     DEFAULT '',
  reply         varchar(32)     NOT NULL     DEFAULT '',
  authdate         timestamp         NOT NULL,
  PRIMARY KEY  (id)
) ENGINE=InnoDB;

--
-- Table structure for table 'nas'
--
CREATE TABLE nas (
  id          int(10)      NOT NULL AUTO_INCREMENT,
  nasname     varchar(128) NOT NULL,
  shortname   varchar(32),
  type        varchar(30)           DEFAULT 'other',
  ports       int(5),
  secret      varchar(60)  NOT NULL DEFAULT 'secret',
  server      varchar(64),
  community   varchar(50),
  description varchar(200)          DEFAULT 'RADIUS Client',
  PRIMARY KEY (id)
) ENGINE=InnoDB;
INSERT INTO `nas` VALUES (NULL, '127.0.0.1', 'localhost', 'other', NULL, 'my_isp_radius_secret', NULL, NULL, 'RADIUS Client');
