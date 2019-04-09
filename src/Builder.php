<?php

namespace Jiny\Database;

use PDO;

class Builder
{
    private $conn;
    private $db;
    private $table;

    private $_fields = [];
    private $_wheres = [];

    public function __construct($table)
    {
        $this->table = $table;
        $this->conn = $table->conn();
        $this->db = $table->db();
    }

    private $query = "";

    public function clear()
    {
        $this->query = "";
        return $this;
    }

    public function fields($fields)
    {
        if($fields) {
            foreach ($fields as $f => $v) {
                $this->_fields[$f] = $v;
            }
        }
        
        return $this;
    }

    public function wheres($fields)
    {
        foreach ($fields as $f => $v) {
            $this->_wheres[$f] = $v;
        }
        return $this;
    }

    public function insert(array $data)
    {
        $query = "INSERT INTO ".$this->table->name()." SET ";
        foreach ($data as $field => $value) {
                $query .= $field."= :".$field." ,";
        }

        $query .= $this->table::CREATED_AT."= '".date("Y-m-d H:i:s")."' ,";
        $query .= $this->table::UPDATED_AT."= '".date("Y-m-d H:i:s")."' ,";

        $this->query = rtrim($query,',');
        return $this;
    }


    public function select($field = [])
    {
        $this->fields($field);

        $query = 'SELECT ';

        if ($field) {
            $s = "";
            foreach ($field as $f) $s .= "$f,";
            $s = rtrim($s, ',');
            $query .= $s;
        } else {
            $query .= "*";
        }

        $query .= ' FROM '.$this->table->name();
        
        $this->query .= $query;
        return $this;
    }

    function where($where=[])
    {
        if ($where) {
            $this->wheres($where);

            $query1 = " WHERE";
            foreach ($where as $k => $v) {
                $query1 .= " ".$k." = :".$k." and";
            }
            $this->query .= rtrim($query1, 'and');
        }

        return $this;
    }

    function limit($num, $start=null)
    {
        if ($start) {
            $this->query .= " LIMIT $num , $start";
        } else {
            $this->query .= " LIMIT $num";
        }
        return $this;
    }


    // 업데이트 처리 매소드
    public function update(array $data)
    {
        $this->fields($data);
        $query = "UPDATE ".$this->table->name()." SET ";
        foreach ($data as $field => $value) {
                $query .= $field."= :".$field." ,";
        }

        $query .= $this->table::UPDATED_AT."= '".date("Y-m-d H:i:s")."' ,";

        $this->query .= rtrim($query,',');
        return $this;
    }

    /**
     * Id로 갱신
     */
    public function id($id)
    {   
        $this->_wheres['id'] = $id;
        $this->query .= " WHERE id = :id";

        return $this;
    }

    public function exec()
    {
        $stmt = $this->conn->prepare($this->query);

        $this->table->bindParams($stmt, $this->_wheres);
        $this->table->bindParams($stmt, $this->_fields);

        if(($e = $this->db->exec($stmt)) !== true) {
            // 오류 처리
        }
    }

    public function delete()
    {
        $this->query = "DELETE FROM ".$this->table->name()." ";
        return $this;
    }




    /**
     * 
     */

    public function get()
    {
        $stmt = $this->conn->prepare($this->query);

        if ($this->_wheres) {
            $this->table->bindParams($stmt, $this->_wheres);
        }

        $stmt->execute();
        return $this->db->fetchAll($stmt);
    }

    /**
     * 
     */
    public function __toString()
    {
        $query = $this->query;
        $this->clear();
        return $query;
    }

    /**
     * 
     */


}
