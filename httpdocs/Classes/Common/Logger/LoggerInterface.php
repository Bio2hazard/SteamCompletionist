<?php

namespace Classes\Common\Logger;

/**
 * Defines the interface for the database access.
 *
 * @author Felix Kastner <felix@chapterfain.com>
 */
interface LoggerInterface
{


    /**
     * Sets the loggers userId.
     *
     * @param int $userId
     */
    public function setUser($userId);

    /**
     * Sets the loggers IP.
     * @param string $ip
     */
    public function setIP($ip);

    /**
     * Writes $message as a entry into the log.
     *
     * @param string $message Message to write into the debug log
     * @param bool $timestamp whether to use a timestamp or to use 0 ( for start entry )
     *
     * @return bool true if log entry succeeded, false if logging is not enabled
     */
    public function addEntry($message, $timestamp = true);

    /**
     * Writes a login / logout message into a login / logout database
     *
     * @param int $type The type of action performed ( 1 = login, 2 = logout )
     *
     * @return bool true if log entry succeeded, false if user logging is not enabled
     */
    public function loginOutEntry($type);
}