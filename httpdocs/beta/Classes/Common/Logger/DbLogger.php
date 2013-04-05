<?php

namespace Classes\Common\Logger;

use Classes\Common\Database\DatabaseInterface;

/**
 * Provides a easy way to log messages into a database.
 *
 * @author Felix Kastner <felix@chapterfain.com>
 */
class DbLogger implements LoggerInterface
{
    /**
     * Holds a array of configuration values used by the Databases.
     * @var array
     */
    private $config;

    /**
     * Holds the microtime at which the logger was started, for timing purposes.
     * @var float
     */
    private $startTime;

    /**
     * Holds the ID of the overarching debugLog entry.
     * @var int
     */
    private $id;

    /**
     * Holds the userID so log entries can be associated to user accounts.
     * @var int
     */
    private $userId;

    /**
     * Holds the IP address of the current client.
     * @var string
     */
    private $ip;

    /**
     * The database connection.
     * @var DatabaseInterface $db
     */
    private $db;

    /**
     * Constructor.
     *
     * @param $config
     * @param DatabaseInterface $db
     * @param int $userId
     * @param string $ip
     */
    public function __construct($config, DatabaseInterface $db, $userId = 0, $ip = '0.0.0.0')
    {
        $this->config = $config;
        $this->db = $db;
        $this->userId = $userId;
        $this->ip = $ip;

        if ($this->config['debugLog']) {
            $this->startLog();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setUser($userId)
    {
        $this->userId = $userId;
    }

    /**
     * {@inheritdoc}
     */
    public function setIP($ip)
    {
        $this->ip = $ip;
    }

    /**
     * Starts logging
     */
    private function startLog()
    {
        $this->startTime = microtime(true);
    }

    /**
     * {@inheritdoc}
     */
    public function loginOutEntry($type)
    {
        if(!$this->config['userLog'] || !$type || !is_numeric($type) || $type > 2 || $type < 0) {
            return false;
        }

        try {
            $this->db->prepare('INSERT INTO `userLog` SET `ts` = now(), `ipn` = INET_ATON(?), `type` = ?, `userid` = ?');
            $this->db->execute(array($this->ip, $type, $this->userId), 'iis');
        } catch (\Exception $e) {
            throw $e;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function addEntry($message, $timestamp = true)
    {
        if (!$this->config['debugLog']) {
            return false;
        }

        try {
            if (!$this->id) {
                $this->db->prepare('INSERT INTO `debugLog` SET `ts` = now(), `ipn` = INET_ATON(?), `userid` = ?');
                $data[0] = $this->ip;
                $data[1] = $this->userId;
                $this->db->execute($data, 'is');
                unset($data);
                $this->id = $this->db->lastInsert();
                $this->addEntry('Debug log init', false);
            }
            $this->db->prepare('INSERT INTO `debugLogEntries` SET `debugLogId` = ?, `message` = ?, `elapsed` = ?');
            $now = microtime(true);
            $data[0] = $this->id;
            $data[1] = $message;
            $data[2] = ($timestamp) ? ($now - $this->startTime) : 0;
            $this->db->execute($data, 'isf');
        } catch (\Exception $e) {
            throw $e;
        }
        return true;
    }

}