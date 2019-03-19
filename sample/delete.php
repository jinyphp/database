<?php
const ROOT = "";
require __DIR__.ROOT."/vendor/autoload.php";

// DB를 초기화 합니다.
const DBINFO = "dbconf.php";
$db = \Jiny\Database\db_init(DBINFO);

$table = "board5";

$db->table($table)->delete(4);

if ($rows = $db->table($table)->select()) {
    print_r($rows);
}