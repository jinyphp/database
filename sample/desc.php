<?php
const ROOT = "";
require __DIR__.ROOT."/vendor/autoload.php";

// DB를 초기화 합니다.
const DBINFO = "dbconf.php";
$db = \Jiny\Database\db_init(DBINFO);

// board2 테이블의 정보를 확인합니다.
if(!$desc = $db->desc('board2')) {
    echo "테이블의 정보를 읽어 올 수 없습니다.";
    exit;
}
print_r($desc);