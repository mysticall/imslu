ALTER TABLE `auditlog` ALTER `date_time` SET DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `users` ALTER `created` SET DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `users` ALTER `expires` SET DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `payments` ALTER `date_payment1` SET DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `payments` ALTER `date_payment2` SET DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `payments` ALTER `expires` SET DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `requests` ALTER `add` SET DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `requests` ALTER `assign` SET DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `requests` ALTER `end` SET DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `tickets` ALTER `add` SET DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `tickets` ALTER `assign` SET DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `tickets` ALTER `end` SET DEFAULT CURRENT_TIMESTAMP;
