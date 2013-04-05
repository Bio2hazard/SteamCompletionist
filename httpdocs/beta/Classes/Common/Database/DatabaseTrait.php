<?php

namespace Classes\Common\Database;

/**
 * DatabaseTrait Defines the traits for the database access.
 *
 * @author Felix Kastner <felix@chapterfain.com>
 */
trait DatabaseTrait
{
    /**
     * Contains the link to the database.
     * @var \mysqli|\PDO $link
     */
    protected $link;

    /**
     * Contains the statement link.
     * @var \mysqli_stmt|\PDOStatement $stmt
     */
    protected $stmt;

    /**
     * Holds the last prepared query, is used so we can reuse the same statement link if the query is identical.
     * @var string
     */
    protected $lastPrepared = NULL;

    /**
     * Holds a array of configuration values used by the Databases.
     * @var array
     */
    private $config;

    /**
     * Holds the number of rows that were affected by the last query.
     * @var int
     */
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
    public function getAffected()
    {
        return $this->affectedRows;
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
