<?php

namespace Jiny\Database;

use PDO;
use \Jiny\Database\Builder;

class DBException
{
    private $Table;
    private $Error;
    public function __construct($e, $table)
    {
        $this->Error = $e;
        $this->Table = $table;
        
        echo "\n";
        echo $e->getCode()." ";
        echo $e->getMessage();

        $method = "Error".$e->getCode();
        $this->$method($e);
    }

    // 테이블 없음
    public function Error42S02($e)
    {
        echo "테이블이 존재하지 않습니다.";

    }

    // 컬럼 불일치
    public function Error42S22()
    {
        echo "컬럼이 일치하지 않습니다.";
    }
}