<?php

namespace Jiny\Database;

class queryTable
{
    private $_fields = [];
    private $_engine;
    private $_charset;
    private $_name;

    const PRIMARYKEY = 'id';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function create($table=null)
    {
        // 테이블이름 설정, 입력값 우선적용
        if(!$table) {
            if($this->_name) {
                $table = $this->_name; 
            } else {
                echo "테이블 이름이 없습니다.";
                exit;
            }
        }

        // 기본 필드 중복 생성 방지
        unset($this->_fields[self::PRIMARYKEY]);
        unset($this->_fields[self::CREATED_AT]);
        unset($this->_fields[self::UPDATED_AT]);

        // 쿼리 조합
        $query = "CREATE TABLE ".$table;
        $query .= " (`".self::PRIMARYKEY."` INT NOT NULL AUTO_INCREMENT,";
        
        foreach ($this->_fields as $f => $v) {
            $query .= "`$f` $v,";
        }

        $query .= "`".self::CREATED_AT."` datetime,";
        $query .= "`".self::UPDATED_AT."` datetime,";
        $query .= "PRIMARY KEY(id)) ";

        if ($this->_engine) {
            $query .= "ENGINE=".$this->_engine." ";
        }

        if ($this->_charset) {
            $query .= "CHARSET=".$this->_charset." ";
        }

        return $query;
    }

    public function field($name, $value)
    {
        $this->_fields[$name] = $value;
        return $this;
    }

    public function fields($fields)
    {
        foreach ($fields as $f => $v) {
            $this->_fields[$f] = $v;
        }
        return $this;
    }

    public function remove($name)
    {
        unset($this->_feilds[$name]);
        return $this;
    }

    public function engine($engine)
    {
        $this->_engine = $engine;
        return $this;
    }

    public function charset($charset)
    {
        $this->_charset = $charset;
        return $this;
    }

    public function name($name)
    {
        $this->_name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->_name;
    }

    /**
     * 
     */
}