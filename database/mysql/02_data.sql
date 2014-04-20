-- PLEASE DON'T CHANGE ID's
INSERT INTO `opergrp`
	VALUES	(1,'Cashiers'),
			(2,'Network Technicians'),
			(3,'Administrators'),
			(4,'LINUX Administrators');

-- alias: sadmin , password: sadmin, group: LINUX Administrators 
-- alias: admin , password: admin, group: Administrators
INSERT INTO `operators` (`operid`,`alias`,`name`,`passwd`,`salt`,`url`,`lang`,`theme`,`refresh`,`type`)
	VALUES	(1,'sadmin','System Administrator','f894c95881f76bc0cec2be7ed2e273e3d9aa9787e9e94b6583f51f43cc097de01017db6ebe5d4b459650eab9eb4a60c3bcb13a2fe539f3478d0c61c10c02251f','ec12fb04da1bf363d5ddcf7d44514d823b02193feabbf82b85bc0f620b94ad474de34ec3800ad79ebf2e3e38ddee9d2f546d30831bd8c276a517fb029c48ffd7','','en_US','originalgreen',60,4),
			(2,'admin','Administrator','9aaadabe4a308d4d7a3f7df4c109a426a7e0821f938f808036347f64f55b161e04fd1f88886ca981ea777357367c3e3835de73df85b7adf43a4e3be164febaf3','3eb95e238e150a26a8b5e74d901cad9cb51a6bf19014d4a8682a79f535adc0e893933268972931218e02fe4d1204d8267822ccd28e988939524846da7c91ca1d','','en_US','originalgreen',60,3);

INSERT INTO `operators_groups` (`id`,`opergrpid`,`operid`)
	VALUES	('1','4','1'),
			('2','3','2');
