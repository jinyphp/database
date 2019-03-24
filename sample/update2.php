<?php
const ROOT = "";
require __DIR__.ROOT."/vendor/autoload.php";

// DB를 초기화 합니다.
const DBINFO = "dbconf.php";
$db = \Jiny\Database\db_init(DBINFO);

$table = "board5";
$data1 = [
    'id' => 6,
    'regdate' => date('Y-m-d H-i:s'),
    'title' => 'rawSQL 수정..'
];

$db->update("UPDATE $table SET regdate=:regdate, title=:title where id=:id",$data1);

if ($rows = $db->table($table)->select()) {
    print_r($rows);
}