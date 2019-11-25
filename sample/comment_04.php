<?php
require "../../../../vendor/autoload.php";

// DB를 초기화 합니다.
const DBINFO = "dbconf.php";
$dbinfo = \Jiny\Database\db_conf(DBINFO);
$db = \Jiny\Database\db_init($dbinfo);

$db->setFieldComment("apitest","board","regdate","message3333");

if ($rows = $db->fieldComment("apitest","board")) {
    print_r($rows);
}

