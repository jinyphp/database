<?php
require "../../../../vendor/autoload.php";

// DB를 초기화 합니다.
const DBINFO = "dbconf.php";
$dbinfo = \Jiny\Database\db_conf(DBINFO);
$db = \Jiny\Database\db_init($dbinfo);


if ($rows = $db->databases()) {
    print_r($rows);
}

