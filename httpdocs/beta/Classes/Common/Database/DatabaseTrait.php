<?php

namespace Classes\Common\Database;

/**
 * Defines the trait for the database access.
 *
 * @author Felix Kastner <felix@chapterfain.com>
 */
trait DatabaseTrait
{
    /** @var \mysqli|\PDO $link */
    protected $link;

    /** @var \mysqli_stmt|\PDOStatement $stmt */
    protected $stmt;

    protected $lastPrepared = NULL;

    private $host, $username, $password, $database, $port, $persistent;
    public $affectedRows = 0;
    
    /**
     * {@inheritdoc}
     */
    public function &getLink() 
    {
        return $this->link;
    }
    
    /**
     * {@inheritdoc}
     */
    public function &getStmt()
    {
        return $this->stmt;
    }
}
?>
