<?php
require "../../../../vendor/autoload.php";

// DB를 초기화 합니다.
const DBINFO = "dbconf.php";
$dbinfo = \Jiny\Database\db_conf(DBINFO);
$db = \Jiny\Database\db_init($dbinfo);

if ($db->isDatabase("apitest")) {
    echo "데이터베이스가 있습니다.";
} else {
    echo "데이터베이스 없음.";
}

