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
