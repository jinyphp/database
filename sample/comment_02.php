<?php
require "../../../../vendor/autoload.php";

// DB를 초기화 합니다.
const DBINFO = "dbconf.php";
$dbinfo = \Jiny\Database\db_conf(DBINFO);
$db = \Jiny\Database\db_init($dbinfo);

$db->setTableComment("apitest","api_log","로그 기록 테이블입니다.");

if ($rows = $db->tableComment("apitest")) {
    print_r($rows);
}

