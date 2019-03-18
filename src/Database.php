<?php

namespace Jiny\Database;

use PDO;

class Database
{
    private $_dbhost = 'localhost';
    private $_dbtype = 'mysql';
    private $_dbcharset = 'utf8';
    
    private $_dbname = null;
    private $_dbuser = null;
    private $_dbpass = null;

    private $conn = null;

    public function __construct($args=null)
    {
        // echo __CLASS__;
        if ($args) {
            extract($args);

            if (isset($host) && $host) $this->_dbhost = $host;
            if (isset($type) && $type) $this->_dbtype = $type;
            if (isset($charset) && $charset) $this->_dbcharset = $charset;

            if (isset($dbname) && $dbname) $this->_dbname = $dbname;
            if (isset($dbuser) && $dbuser) $this->_dbuser = $dbuser;
            if (isset($dbpass) && $dbpass) $this->_dbpass = $dbpass;
        }      
    }

    /**
     * DB 연결
     */
    public function connect()
    {
        if (!$this->conn) {
            if(!$this->_dbtype) {
                echo "DB타입이 선택되어 있지 않습니다.";
                exit;
            } else $host = $this->_dbtype;

            if(!$this->_dbhost) {
                echo "DB 접속 호스트가 설정되어 있지 않습니다.";
                exit;
            } else $host .= ":host=".$this->_dbhost;

            if(!$this->_dbcharset) {
                echo "DB 문자셋이 설정되어 있지 않습니다.";
                exit;
            } else $host .= ";charset=".$this->_dbcharset;

            if(!$this->_dbname) {
                echo "DB명이 설정되어 있지 않습니다.";
                exit;
            } else $host .= ";dbname=".$this->_dbname;

            if(!$this->_dbuser) {
                echo "DB 사용자가 설정되어 있지 않습니다.";
                exit;
            }

            if(!$this->_dbpass) {
                echo "DB 접속암호가 설정되어 있지 않습니다.";
                exit;
            }

            try {
                $this->conn = new PDO($host, $this->_dbuser, $this->_dbpass);
                
                //오류 숨김모드 해제, Exception을 발생시킨다.
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // echo "DB 접속 성공\n";
    
            } catch(PDOException $e) {
                echo "실패\n";
                echo $e->getMessage();
            }
        }       
        
        // 접속 DB connector를 반환합니다.
        return $this->conn;
    }

    /**
     * DB이름 getter/setter
     */
    public function setDBName($dbname)
    {   
        $this->_dbname = $dbname;
    }

    public function getDBName()
    {
        return $this->_dbname;
    }

    /**
     * 권한접속자 setter
     */
    public function setUser($user)
    {
        $this->_dbuser = $user;
    }

    /**
     * 패스워드 setter
     */
    public function setPassword($pass)
    {
        $this->_dbpass = $pass;
    }


    // 데이터 조회

    /**
     * 
     */
    public function select($table = null, $field = null, $where=null)
    {
        if (!$this->conn) $this->connect();
        if ($table) {
            $query = 'SELECT ';

            if ($field) {
                $s = "";
                foreach ($field as $f) $s .= "$f,";
                $s = rtrim($s, ',');
                $query .= $s;
            } else {
                $query .= "*";
            }

            $query .= ' FROM '.$table;
            
            if ($where) {
                $query1 = " WHERE";
                foreach ($where as $k => $v) {
                    $query1 .= " ".$k." = :".$k." and";
                }
                $query .= rtrim($query1,'and');

            }


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

    public function queryInsert(string $table, array $data) : string
    {
        $query = "INSERT INTO ".$table." SET ";
        foreach ($data as $field => $value) {
                $query .= $field."= :".$field." ,";
        }
        return rtrim($query,',');
    }

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

    public function insert(string $table, array $data, $matching=false, $create=false)
    {
        if (!$this->conn) $this->connect();

        // 연상배열 여부 체크
        if(!$this->isAssoArray($data)) {
            // echo "배열 타입이 틀립니다.";
            // return false;
            // 다중처리
            foreach($data as $d) $this->insert($table, $d);
        } else {
            $query = $this->queryInsert($table, $data);
            $stmt = $this->conn->prepare($query);
            $this->bindParams($stmt, $data);
            if(($e = $this->exec($stmt)) !== true) {
                switch($e->getCode()) {
                    case '42S22':
                        // 컬럼 필드 매칭오류
                        if($matching) {
                            // 자동으로 필드를 추가합니다.
                            $this->autoField($table, $data);

                            // 다시 재귀 실행으로 데이터를 삽입을 처리합니다.
                            $this->insert($table, $data);
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
                            $tb = ( new queryTable() )->name($table)->fields($fields);
                            if($create['ENGINE']) $tb->engine("InnoDB");
                            if($create['CHARSET']) $tb->charset("utf8");
                            $this->createTable($tb);

                            // 다시 재귀 실행으로 데이터를 삽입을 처리합니다.
                            $this->insert($table, $data);
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

    /**
     * 입력 데이터 기준, 자동 필드추가
     */
    private function autoField($table, $data)
    {
        // 컬럼 필드 정보를 읽어 옵니다.
        $desc = $this->desc($table);

        foreach(array_keys($data) as $key) {
            if(!array_key_exists($key, $desc)) {
                $this->addField($table, $key);
            }
        }
    }

    public function addField($table, $field, $type='text')
    {
        if (!$this->conn) $this->connect();

        $query = "ALTER TABLE ".$table." ADD ".$field." ".$type;
        $stmt = $this->conn->prepare($query);
        if(($e = $this->exec($stmt)) !== true) {
            // 오류처리
        }
    }

    public function desc($table) {
        if (!$this->conn) $this->connect();

        $query = "DESC ".$table;
        $stmt = $this->conn->prepare($query);
        
        $stmt->execute();
        $arr = $stmt->fetchAll();

        foreach($arr as $d) {
            $key = $d['Field'];
            $desc[$key] = $d;
        }

        return $desc;
    }


    public function exec($stmt, $value=null)
    {
        try {
            if($stmt->execute($value)) {
                return true;
            } else {
                return false;
            }
        } catch (\PDOException $e) {
            return $e;
        }
    }


    // 업데이트 처리 매소드
    public function queryUpdate(string $table, array $data) : string
    {
        $query = "UPDATE ".$table." SET ";
        foreach ($data as $field => $value) {
                $query .= $field."= :".$field." ,";
        }
        return rtrim($query,',');
    }

    public function update($table, $data, $where=null)
    {
        if($this->isAssoArray($data) && $where) {
            if (!$this->conn) $this->connect();

            // 연관배열 데이터
            if(is_numeric($where)) {
                $id = intval($where);
                $stmt = $this->updateId($table, $data, $id);
            } else 
            if(is_string($where)) {
                switch ($where) {
                    case '*':
                        $stmt = $this->updateAll($table, $data);
                        break;
                    default:
                        $stmt = $this->updateQuery($table, $data, $where);
                }
            } else 
            if(is_array($where)) {
                $stmt = $this->updateWhere($table, $data, $where);
            }

            $this->bindParams($stmt, $data);
            if(($e = $this->exec($stmt)) !== true) {
                // 오류 처리
            }

        } else {
            // 숫자 배열
            // 재귀호출 반복실행
            foreach ($data as $k => $v) {
                if(is_numeric($k)) $this->update($table, $v, $k);
            }
        }     
    }

    private function updateQuery($table, $data, $where)
    {
        $query = $this->queryUpdate($table, $data);
        $query .= " WHERE ".$where;

        $stmt = $this->conn->prepare($query);
        return $stmt;
    }

    private function updateAll($table, $data)
    {
        $query = $this->queryUpdate($table, $data);
        $stmt = $this->conn->prepare($query);
      
        return $stmt;
    }

    /**
     * 조건문으로 갱신
     */
    private function updateWhere($table, $data, $where)
    { 
        $query = $this->queryUpdate($table, $data);
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
    private function updateId($table, $data, $id)
    {     
        $query = $this->queryUpdate($table, $data);
        $query .= " WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        return $stmt;
    }

    //
    // 삭제처리 매소드

    /**
     * 데이터를 삭제합니다.
     */
    public function delete($table, $where=null)
    {
        if (!$this->conn) $this->connect();
        
        if (!$where) {
            echo "삭제 조건이 없습니다.";
            exit;

        } else if (is_string($where)) {
            switch ($where) {
                case '*':
                    // 전체 삭제
                    $query = "DELETE FROM $table ";
                    $stmt = $this->conn->prepare($query);
                    $this->exec($stmt);
                    break;
                
                default:
                    $query = "DELETE FROM $table WHERE ".$where;
                    $stmt = $this->conn->prepare($query);
                    $this->exec($stmt);
                    break;
            }
        } else if (is_numeric($where)) {
            // 단일 아이디 선택
            $query = "DELETE FROM board WHERE id= :id";
            $stmt = $this->conn->prepare($query);
            $id = htmlspecialchars(strip_tags($where));
            $stmt->bindParam(':id', $id);

            $this->exec($stmt);

        } else if (is_array($where)) {
            $query = "DELETE FROM $table WHERE ";

            foreach ($where as $id) {
                $query .= '`id` = ? or ';
            }

            $query = rtrim($query,'or ');
            $stmt = $this->conn->prepare($query);

            $this->exec($stmt,$where);
        }
    }


    // 테이블 처리 메소드

    /**
     * 
     */

    public function showTables()
    {
        if (!$this->conn) $this->connect();

        $query = "SHOW TABLES";
        $stmt = $this->conn->prepare($query);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function isTable($table, $list)
    {
        foreach($list as $tbname) {
            if($table == $tbname[0]) return true;
        }

        return false;
    }

    /**
     * 테이블 생성
     * 테이블 객체값을 전송합니다.
     */
    public function createTable($tbObj)
    {
        $tables = $this->showTables();
        if ($this->isTable($tbObj->getName(), $tables)) {
            echo "중복 테이블: 생성을 할 수 없습니다.";
            exit;
        }
        
        $query = $tbObj->create();
        $stmt = $this->conn->prepare($query);

        $this->exec($stmt);

    }

    public function dropTable($table)
    {
        if (!$this->conn) $this->connect();

        if (is_array($table)) {
            // 다중 테이블
            $query = "DROP TABLES IF EXISTS ";
            foreach ($table as $name) {
                $query .= $name.",";
            }
            $query = rtrim($query,',');
        } else {
            // 단일 테이블
            $query = "DROP TABLES IF EXISTS ".$table;
        }
        
        $stmt = $this->conn->prepare($query);
        $this->exec($stmt);
    }

    private function isTableName($tableName)
    {
        if ($tableName) {
            return true;
        } else {
            echo "테이블 명이 없습니다.";
            exit;
        }
    }

    private function isDataArray($data)
    {
        if ($data) {
            return true;
        } else {
            echo "Query 삽입 데이터형식이 맞지 않습니다.";
            exit;
        }
    }

    /**
     * 
     */
}
