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
    private $config, $startTime, $id, $userId, $ip;

    /** @var DatabaseInterface $db */
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

        if($this->config['debugLog']){
            $this->startLog();
        }
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
    public function addEntry($message, $timestamp = true)
    {
        if(!$this->config['debugLog']) {
            return false;
        }

        try {
            if(!$this->id) {
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
        } catch(\Exception $e) {
            throw $e;
        }
        return true;
    }

}