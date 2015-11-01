<?php

if (!$upgrade) {
    die();
}
multi_query(<<<SQL
ALTER TABLE `ignore_lists` ADD `ignored_names` MEDIUMTEXT NOT NULL;
SQL
);
