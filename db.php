<?php

namespace driverok;

/**
 * Class for database functions
 *
 * @author driverok <driverok@gmail.com>
 */
class Database
{
    private $host;
    private $user;
    private $password;
    private $database;
    private $connectId;
    private $result;
    private $query;

    public function __construct()
    {
        $this->host = DB_HOST;
        $this->user = DB_USER;
        $this->password = DB_PASS;
        $this->setDB(DB_NAME);
        $this->connect();
    }

    private function connect()
    {
        $this->connectId = mysql_connect($this->host, $this->user, $this->password);
        if ($this->connectId) {
            $this->selectDB();
            mysql_query("SET NAMES ".DB_ENCODING);
            return $this->connectId;
        }
        die("Can't connect to MySQL");
    }

    private function selectDB()
    {
        mysql_select_db($this->database, $this->connectId);
    }

    private function setDB($database)
    {
        $this->database = $database;
    }

    private function setQuery($query)
    {
        $this->query = $query;
    }

    public function execQuery($query)
    {
        $this->setQuery($query);
        $this->result = mysql_query($this->query);
        if ($this->result) {
            return $this->result;
        }

        return false;
    }

    public function exec()
    {
        $this->result = mysql_query($this->query);
        if ($this->result) {
            return $this->result;
        }

        return false;
    }

    public function error()
    {
        if (mysql_errno() != 0) {
            return mysql_errno().": ".mysql_error().PHP_EOL;
        }
        return false;
    }

    public function fetchArray()
    {
        if ($this->result != 0) {
            return mysql_fetch_array($this->result);
        } else {
            echo $this->error();
            return false;
        }
    }

    public function fetchAssoc()
    {
        if ($this->result != 0) {
            return mysql_fetch_Assoc($this->result);
        } else {
            echo $this->error();
            return false;
        }
    }

    public function fetchRow()
    {
        if ($this->result != 0) {
            return mysql_fetch_row($this->result);
        } else {
            echo $this->error();
            return false;
        }
    }

    public function fetchObject()
    {
        if ($this->result != 0) {
            return mysql_fetch_object($this->result);
        } else {
            echo $this->error();
            return false;
        }
    }
}
