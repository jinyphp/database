<?php

namespace Jiny\Database;

use PDO;

use \Jiny\Database\Builder;

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


    // 쿼리빌더
    private $builder;
    public function table($table)
    {
        if (!$this->conn) $this->connect();
        $this->builder = new Builder($this->conn, $this);
        return $this->builder->setTable($table);
    }


    // Raw 쿼리 메소드
    public function select($query, $value=null)
    {
        if (!$this->conn) $this->connect();

        $stmt = $this->conn->prepare($query);
        if($value) {
            $this->bindParams($stmt, $value);
        }  

        $stmt->execute();
        return $stmt->fetchAll();
    }

    // RawSQL 삽입처리
    public function insert($query, $value)
    {
        if (!$this->conn) $this->connect();

        $stmt = $this->conn->prepare($query);
        if($value) {
            $this->bindParams($stmt, $value);
        }  

        $stmt->execute();
    }

    public function update($query, $value)
    {
        if (!$this->conn) $this->connect();

        $stmt = $this->conn->prepare($query);
        if($value) {
            $this->bindParams($stmt, $value);
        }  

        $stmt->execute();
    }

    public function delete($query, $value)
    {
        if (!$this->conn) $this->connect();

        $stmt = $this->conn->prepare($query);
        if($value) {
            $this->bindParams($stmt, $value);
        }        

        $stmt->execute();
    }

    /**
     * 쿼리를 실행합니다.
     */
    public function execute($query)
    {
        if (!$this->conn) $this->connect();
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
    }


    /**
     * 
     */


    public function show($type)
    {
        if (!$this->conn) $this->connect();

        $query = "SHOW ".$type;

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
     * 테이블을 삭제합니다.
     */

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
     * 테이블 정보
     */
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

    /**
     * 쿼리를 실행합니다.
     */
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
