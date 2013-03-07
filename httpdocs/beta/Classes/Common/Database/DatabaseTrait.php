<?php

namespace Classes\Common\Database;

/**
 * DatabaseTrait Defines the traits for the database access.
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

    private $config;
    public $affectedRows = 0;

    /**
     * Constructor.
     *
     * @param array $config Config array contains: host, user, pass, schema, port, persistent, engine, and options
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

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
