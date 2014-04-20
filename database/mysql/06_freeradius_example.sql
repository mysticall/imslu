
INSERT INTO `nas`
	VALUES	(NULL, '127.0.0.1', 'localhost', 'other', NULL, 'my_isp_radius_secret', NULL, NULL, 'RADIUS Client');

INSERT INTO `radgroupcheck` (`groupname`, `attribute`, `op`, `value`) 
	VALUES	('15Mbit_Down_5Mbit_Up','Auth-Type',':=','CHAP'),
			('15Mbit_Down_5Mbit_Up','Pool-Name',':=','main_pool');

INSERT INTO `radgroupreply`  (`groupname`, `attribute`, `op`, `value`)
	VALUES	('15Mbit_Down_5Mbit_Up','Framed-Protocol','=','PPP'),
			('15Mbit_Down_5Mbit_Up','Service-Type','=','Framed-User'),
			('15Mbit_Down_5Mbit_Up','Framed-MTU','=','1500'),
			('15Mbit_Down_5Mbit_Up','Framed-Compression','=','None'),
			('15Mbit_Down_5Mbit_Up','Acct-Interim-Interval','=','180'),
			('15Mbit_Down_5Mbit_Up','PPPD-Downstream-Speed-Limit','=','15360'),
			('15Mbit_Down_5Mbit_Up','PPPD-Upstream-Speed-Limit','=','5120');
