<?php
const ROOT = "";
require __DIR__.ROOT."/vendor/autoload.php";

// DB를 초기화 합니다.
const DBINFO = "dbconf.php";
$db = \Jiny\Database\db_init(DBINFO);

$query = "CREATE TABLE board11 (
    `id` INT NOT NULL AUTO_INCREMENT,
    `title` varchar(255),
    `CREATED_AT` datetime,
    `UPDATED_AT` datetime,
    PRIMARY KEY(id)
    ) ENGINE=InnoDB CHARSET=utf8";

$db->execute($query);

$tables = $db->show('TABLES');
print_r($tables);
