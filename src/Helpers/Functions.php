<?php
/*
 * This file is part of the jinyPHP package.
 *
 * (c) hojinlee <infohojin@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jiny\Database;

function filename()
{
    $path = "..".DIRECTORY_SEPARATOR; 
    $path .= "dbconf.php"; // root

    return $path;
}

function setup()
{
    // DB를 초기화 합니다.
    $filename = \Jiny\Database\filename();
    $dbconf = \Jiny\Database\db_conf($filename);   
    return \Jiny\Database\db_init($dbconf);
}

/**
 * DB접속 환경파일을 읽어 옵니다.
 */
if (! function_exists('db_conf')) {
    function db_conf($filename) {
        if (file_exists($filename)) {
            return include $filename;
        } else {
            echo "DB 환경설정 파일이 존재 하지 않습니다.";
            exit;
        }
    }
}

/**
 * DB접속을 초기화 합니다.
 */
if (! function_exists('db_init')) {
    function db_init($dbconf) {
        if ($dbconf) {
            return new \Jiny\Database\Database($dbconf);
        } else {
            echo "DB 설정이 없습니다.";
            exit;
        }
    }
}


if (! function_exists('bindParams')) {
    function bindParams($stmt, $data)
    {
        foreach ($data as $field => &$value) {
            $stmt->bindParam(':'.$field, $value);
        }
        return $stmt;
    }
}

/**
 * 연상배열 여부 체크
 */
if (! function_exists('isAssoArray')) {
    function isAssoArray($arr)
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
