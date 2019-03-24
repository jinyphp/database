<?php
const ROOT = "";
require __DIR__.ROOT."/vendor/autoload.php";

// DB를 초기화 합니다.
const DBINFO = "dbconf.php";
$db = \Jiny\Database\db_init(DBINFO);

$table = "board5";

$titleText = "raw 셈플입력입니다...";
$data = [
    'regdate' => date('Y-m-d H:i:s'),
    'title' => htmlspecialchars(strip_tags($titleText))
];

// Raw 쿼리 실행
$db->insert("INSERT INTO $table SET regdate=:regdate, title=:title", $data);

if ($rows = $db->table($table)->select(['regdate','title'])) {
    print_r($rows);
}