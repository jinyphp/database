<?php

namespace Jiny\Database;

class Builder
{
    private $conn;
    private $db;
    private $_table;

    private $_fields = [];
    private $_engine;
    private $_charset;

    const PRIMARYKEY = 'id';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * 빌더 초기화
     */
    public function __construct($conn, $db)
    {
        $this->conn = $conn;
        $this->db = $db;
    }

    /**
     * 테이블 이름 설정
     */
    public function setTable($table)
    {
        $this->_table = $table;

        //쿼리 빌더의 인스턴스를 반환
        return $this;
    }

    /**
     * 테이블 생성
     */
    public function create()
    {
        $tables = $this->db->show('TABLES');
        if ($this->db->isTable($this->_table, $tables)) {
            echo "중복 테이블: 생성을 할 수 없습니다.";
            exit;
        }

        // 기본 필드 중복 생성 방지
        unset($this->_fields[self::PRIMARYKEY]);
        unset($this->_fields[self::CREATED_AT]);
        unset($this->_fields[self::UPDATED_AT]);

        // 쿼리 조합
        $query = "CREATE TABLE ".$this->_table;
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

        $stmt = $this->conn->prepare($query);

        $this->exec($stmt);
    }

    // 필드설정
    public function field($name, $value)
    {
        $this->_fields[$name] = $value;
        return $this;
    }

    // 복수 필드 설정
    public function fields($fields)
    {
        foreach ($fields as $f => $v) {
            $this->_fields[$f] = $v;
        }
        return $this;
    }

    // 필드 삭제
    public function remove($name)
    {
        unset($this->_feilds[$name]);
        return $this;
    }

    // 엔진 설정
    public function engine($engine)
    {
        $this->_engine = $engine;
        return $this;
    }

    // 문자셋 설정
    public function charset($charset)
    {
        $this->_charset = $charset;
        return $this;
    }


    /**
     * 조회 쿼리를 생성합니다.
     */
    public function select($field = null, $where=null)
    {
        if ($this->_table) {
            $query = $this->_select($this->_table, $field, $where);
            $stmt = $this->conn->prepare($query);

            if ($where) {
                $this->bindParams($stmt, $where);
            }

            $stmt->execute();
            return $stmt->fetchAll();
            
        } else {
            echo "선택출력할 DB 테이블명이 없습니다.";
            exit;
        }
    }

    /**
     * select 쿼리 String
     */
    public function _select($table, $field = null, $where=null)
    {
        if(!$table) {
            echo "테이블명이 없습니다.\n";
            exit;
        }

        $query = 'SELECT ';

        if ($field) {
            $s = "";
            foreach ($field as $f) $s .= "$f,";
            $s = rtrim($s, ',');
            $query .= $s;
        } else {
            $query .= "*";
        }

        $query .= ' FROM '.$this->_table;
        
        if ($where) {
            $query1 = " WHERE";
            foreach ($where as $k => $v) {
                $query1 .= " ".$k." = :".$k." and";
            }
            $query .= rtrim($query1,'and');

        }

        return $query;
    }

    /**
     * 복수 bind 처리
     */
    private function bindParams($stmt, $data)
    {
        foreach ($data as $field => &$value) {
            $stmt->bindParam(':'.$field, $value);
        }
        return $stmt;
    }

    /**
     * 연상배열 여부 체크
     */  
    private function isAssoArray($arr)
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    private function queryInsert(array $data) : string
    {
        $query = "INSERT INTO ".$this->_table." SET ";
        foreach ($data as $field => $value) {
                $query .= $field."= :".$field." ,";
        }
        return rtrim($query,',');
    }
   


    public function insert(array $data, $matching=false, $create=false)
    {
        // 연상배열 여부 체크
        if(!$this->isAssoArray($data)) {
            // echo "배열 타입이 틀립니다.";
            // return false;
            // 다중처리
            foreach($data as $d) $this->insert($d);
        } else {
            $query = $this->queryInsert($data);
            $stmt = $this->conn->prepare($query);
            $this->bindParams($stmt, $data);
            if(($e = $this->exec($stmt)) !== true) {
                switch($e->getCode()) {
                    case '42S22':
                        // 컬럼 필드 매칭오류
                        if($matching) {
                            // 자동으로 필드를 추가합니다.
                            $this->autoField($data);

                            // 다시 재귀 실행으로 데이터를 삽입을 처리합니다.
                            $this->insert($data);
                            return;
                        } 
                        break;
                    
                    // 테이블 없음.
                    case '42S02':
                        if($create) {
                            // 필드 생성
                            foreach(array_keys($data) as $key) {
                                $fields[$key] = 'text';
                            }

                            // 테이블을 생성합니다.
                            $this->fields($fields);
                            if($create['ENGINE']) $tb->engine("InnoDB");
                            if($create['CHARSET']) $tb->charset("utf8");
                            $this->create();

                            // 다시 재귀 실행으로 데이터를 삽입을 처리합니다.
                            $this->insert($data);
                            return;

                        }
                        break;
                    default:
                }

                echo "Database Error: 코드".$e->getCode()."\n";
                echo $e->getMessage()."\n";
                exit;
            }
        }
    }

    public function exec($stmt, $value=null)
    {
        return $this->db->exec($stmt, $value=null);

        /*
        try {
            if($stmt->execute($value)) {
                return true;
            } else {
                return false;
            }
        } catch (\PDOException $e) {
            return $e;
        }
        */
    }



    /**
     * 입력 데이터 기준, 자동 필드추가
     */
    private function autoField($data)
    {
        // 컬럼 필드 정보를 읽어 옵니다.
        $desc = $this->db->desc($this->_table);

        foreach(array_keys($data) as $key) {
            if(!array_key_exists($key, $desc)) {
                $this->addField($key);
            }
        }
    }

    public function addField($field, $type='text')
    {
        if (!$this->conn) $this->connect();

        $query = "ALTER TABLE ".$this->_table." ADD ".$field." ".$type;
        $stmt = $this->conn->prepare($query);
        if(($e = $this->exec($stmt)) !== true) {
            // 오류처리
        }
    }

    
    /**
     * 데이터를 삭제합니다.
     */
    public function delete($where=null)
    {
        if (!$where) {
            echo "삭제 조건이 없습니다.";
            exit;

        } else if (is_string($where)) {
            switch ($where) {
                case '*':
                    // 전체 삭제
                    $query = "DELETE FROM ".$this->_table." ";
                    $stmt = $this->conn->prepare($query);
                    $this->exec($stmt);
                    break;
                
                default:
                    $query = "DELETE FROM ".$this->table." WHERE ".$where;
                    $stmt = $this->conn->prepare($query);
                    $this->exec($stmt);
                    break;
            }
        } else if (is_numeric($where)) {
            // 단일 아이디 선택
            $query = "DELETE FROM ".$this->_table." WHERE id= :id";
            $stmt = $this->conn->prepare($query);
            $id = htmlspecialchars(strip_tags($where));
            $stmt->bindParam(':id', $id);

            $this->exec($stmt);

        } else if (is_array($where)) {
            $query = "DELETE FROM ".$this->_table." WHERE ";

            foreach ($where as $id) {
                $query .= '`id` = ? or ';
            }

            $query = rtrim($query,'or ');
            $stmt = $this->conn->prepare($query);

            $this->exec($stmt,$where);
        }
    }

    
    // 업데이트 처리 매소드
    public function queryUpdate(array $data) : string
    {
        $query = "UPDATE ".$this->_table." SET ";
        foreach ($data as $field => $value) {
                $query .= $field."= :".$field." ,";
        }
        return rtrim($query,',');
    }

    public function update($data, $where=null)
    {
        if($this->isAssoArray($data) && $where) {
            if (!$this->conn) $this->connect();

            // 연관배열 데이터
            if(is_numeric($where)) {
                $id = intval($where);
                $stmt = $this->updateId($data, $id);
            } else 
            if(is_string($where)) {
                switch ($where) {
                    case '*':
                        $stmt = $this->updateAll($data);
                        break;
                    default:
                        $stmt = $this->updateQuery($data, $where);
                }
            } else 
            if(is_array($where)) {
                $stmt = $this->updateWhere($data, $where);
            }

            $this->bindParams($stmt, $data);
            if(($e = $this->exec($stmt)) !== true) {
                // 오류 처리
            }

        } else {
            // 숫자 배열
            // 재귀호출 반복실행
            foreach ($data as $k => $v) {
                if(is_numeric($k)) $this->update($v, $k);
            }
        }     
    }

    private function updateQuery($data, $where)
    {
        $query = $this->queryUpdate($data);
        $query .= " WHERE ".$where;

        $stmt = $this->conn->prepare($query);
        return $stmt;
    }

    private function updateAll($data)
    {
        $query = $this->queryUpdate($data);
        $stmt = $this->conn->prepare($query);
      
        return $stmt;
    }

    /**
     * 조건문으로 갱신
     */
    private function updateWhere($data, $where)
    { 
        $query = $this->queryUpdate($data);
        $query .= " WHERE ";

        foreach ($where as $k => $v) {
            $query .= $k."= :".$k." and ";
        }
        $query = rtrim($query,'and ');

        $stmt = $this->conn->prepare($query);
        $this->bindParams($stmt, $where);   // 조건

        return $stmt;
    }

    /**
     * Id로 갱신
     */
    private function updateId($data, $id)
    {     
        $query = $this->queryUpdate($data);
        $query .= " WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        return $stmt;
    }


    /**
     * 테이블을 삭제합니다.
     */
    public function drop()
    {
        $query = "DROP TABLES IF EXISTS ".$this->_table;   
        $stmt = $this->conn->prepare($query);
        $this->exec($stmt);
    }

    /**
     * 
     */
}
