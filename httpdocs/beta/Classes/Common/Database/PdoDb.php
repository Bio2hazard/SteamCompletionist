<?php

namespace Classes\Common\Database;

use \PDO;
use \PDOException;
use \Exception;

/**
 * PdoDb provides a set of intuitive methods that use the PDO Database Extension.
 * 
 * @author Felix Kastner <felix@chapterfain.com>
 */
class PdoDb implements DatabaseInterface
{
    use DatabaseTrait;

    protected $dbengine, $options = array ();

    /**
     * Constructor.
     *
     * @param string $host      hostname to use for database connection
     * @param string $username  username to use for database connection
     * @param string $password  password to use for database connection
     * @param string $database  database schema to connect to
     * @param string $port      port to use for database connection
     * @param bool $persistent  flag that determines whether a persistent connection will be used
     * @param string $dbengine  name of the database engine to use; defaults to mysql
     * @param array $options    DBO options to use, defaults to throwing exceptions and fetching data by association
     */
    public function __construct($host, $username, $password, $database, $port = '3306', $persistent = true, $dbengine = 'mysql', $options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                                                                                                              PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC))
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->port = $port;
        $this->persistent = $persistent;
        $this->dbengine = $dbengine;
        $this->options = $options;
    }
    
    /**
     * {@inheritdoc}
     */
    public function connect() 
    {
        try {
            if($this->persistent) {
                $this->options[PDO::ATTR_PERSISTENT] = true;
            }
            $this->link = new PDO($this->dbengine . ':host=' . $this->host . ';port=' . $this->port . ';dbname=' . $this->database . ';charset=utf8', $this->username, $this->password, $this->options);
        } catch (PDOException $e) {
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
                $this->stmt = $this->link->prepare($sql);
                $this->lastPrepared = $sql;
            }
            
        } catch (PDOException $e) {
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
                throw new Exception('PDO database statement has not yet been prepared');
            }
            
            if($data) {
                if(!is_array($data)) {
                    $data = array($data);
                }
                $this->stmt->execute($data);
            } else {
                $this->stmt->execute();
            }
            
            if($this->stmt->rowCount()) {
                $this->affectedRows = $this->stmt->rowCount();
            }
            
        } catch (PDOException $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function fetch($option = NULL)
    {
        return $this->stmt->fetch($option);
    }    
    
    /**
     * {@inheritdoc}
     */
    public function close()
    {
        
    }  
}

?>
