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
     * Writes $message as a entry into the log.
     *
     * @param string $message Message to write into the debug log
     * @param bool $timestamp whether to use a timestamp or to use 0 ( for start entry )
     *
     * @return bool true if log entry succeeded, false if logging is not enabled
     */
    public function addEntry($message, $timestamp = true);
}