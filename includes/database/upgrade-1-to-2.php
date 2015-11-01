<?php

if (!$upgrade) {
    die();
}
multi_query(<<<SQL
CREATE TABLE IF NOT EXISTS `chrome_tokens` (
  `uid` varchar(23) NOT NULL,
  `subscription_id` varchar(256) NOT NULL,
  `endpoint` varchar(2048) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `chrome_tokens`
 ADD KEY `uid` (`uid`), ADD KEY `subscription_id` (`subscription_id`), ADD KEY `endpoint` (`endpoint`(767));
 
ALTER TABLE `chrome_tokens` ADD PRIMARY KEY( `uid`, `subscription_id`);
SQL
);
