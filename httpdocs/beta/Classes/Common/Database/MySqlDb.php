<?php

namespace Classes\Common\Database;

use \mysqli_driver;
use \mysqli;
use \mysqli_sql_exception;
use \mysqli_result;
use \Exception;

/**
 * MySqlDb provides a set of intuitive methods that use the MySQL Improved Extension.
 *
 * @author Felix Kastner <felix@chapterfain.com>
 */
class MySqlDb implements DatabaseInterface 
{   
    use DatabaseTrait;

    /** @var mysqli_driver $driver */
    protected $driver;

    /** @var mysqli_result $result */
    protected $result;

    /**
     * Constructor
     *
     * @param string $host      hostname to use for database connection
     * @param string $username  username to use for database connection
     * @param string $password  password to use for database connection
     * @param string $database  database schema to connect to
     * @param string $port      port to use for database connection
     * @param bool $persistent  flag that determines whether a persistent connection will be used
     */
    public function __construct($host, $username, $password, $database, $port = '3306', $persistent = true)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->port = $port;
        $this->persistent = $persistent;
    }

    /**
     * Returns all array contents as a reference. Necessary to pass a array to bind_param.
     * 
     * @param array $param Array to be referenced
     * 
     * @return array containing the reference values
     */
    private function refValues($param) 
    {
        $refs = array();
        foreach($param as $key => $value) {
            $refs[$key] = &$param[$key];
        }
         return $refs;
    }    
    
    /**
     * {@inheritdoc}
     */
    public function connect() 
    {
        try {
            $this->driver = new mysqli_driver();
            $this->driver->report_mode = MYSQLI_REPORT_STRICT;
            if($this->persistent) {
                $this->host = 'p:' . $this->host;
            }
            $this->link = new mysqli($this->host, $this->username, $this->password, $this->database, $this->port);
        } catch (mysqli_sql_exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }        
    }
    
    /**
     * {@inheritdoc}
     */
    public function prepare($sql) 
    {
        try {
            if(!$this->link) {
                $this->connect();
            }
            if($sql !== $this->lastPrepared) {
                if($this->stmt) {
                    $this->close();
                }
                $this->stmt = $this->link->stmt_init();
                $this->stmt->prepare($sql);
                $this->lastPrepared = $sql;
            }
        } catch (mysqli_sql_exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function execute($data = array(), $types = '')
    {
        try {
            if(!$this->link || !$this->stmt) {
                throw new Exception('MySQLi database statement has not yet been prepared');
            }
            
            if($data) {
                if(!is_array($data)) {
                    $data = array($data);
                }
                if(!call_user_func_array('mysqli_stmt_bind_param', array_merge(array($this->stmt, $types), $this->refValues($data)))) {
                    throw new Exception('MySQLi database binding parameters failed');
                }
            }
            $this->stmt->execute();
            if($this->stmt->affected_rows) {
                $this->affectedRows = $this->stmt->affected_rows;
            }
            //$this->stmt->store_result();
            $this->result = $this->stmt->get_result();
        } catch (mysqli_sql_exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function fetch($option = NULL)
    {
        return $this->result->fetch_assoc();
    }    
    
    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if($this->result) {
            $this->result->free_result();
        }
        
        if($this->stmt) {
            $this->stmt->close();
        }
        $this->stmt = NULL;
        $this->lastPrepared = NULL;
        $this->affectedRows = 0;
    }
}
?>
