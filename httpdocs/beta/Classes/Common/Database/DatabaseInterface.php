<?php

namespace Classes\Common\Database;

/**
 * Defines the interface for the database access.
 *
 * @author Felix Kastner <felix@chapterfain.com>
 */
interface DatabaseInterface
{
    /**
     * Connects to the chosen database
     *
     * @throws \Exception Generic exception containing any errors caught
     */
    public function connect();

    /**
     * Prepares a statement for execution.
     * Will connect to the database if the connection has yet to be established.
     *
     * @param string $sql The SQL Query to prepare
     * @throws \Exception Generic exception containing any errors caught
     */
    public function prepare($sql);

    /**
     * Executes a previously prepared query.
     * Populates public property affectedRows which contains the number of affected rows by a UPDATE, INSERT or DELETE statement.
     *
     * @param array $data Contains the data to pass to the database
     * @param string $types Required for MySQLi - contains the dataTypes of the array elements see mysqli_stmt_bind_param
     * @throws \Exception Generic exception containing any errors caught
     */
    public function execute($data = array(), $types = '');

    /**
     * @return int Last Insert ID.
     */
    public function lastInsert();

    /**
     * Fetches and returns data from the database after execution.
     *
     * @return array Database result set
     */
    public function fetch();

    /**
     * Closes a prepared statement and frees any memory associated with it.
     */
    public function close();

    /**
     * @return int Returns the number of affected rows.
     */
    public function getAffected();

    /**
     * @return mixed Returns a (reference) to the active database connection.
     */
    public function &getLink();

    /**
     * @return mixed Returns a (reference) to the prepared statement.
     */
    public function &getStmt();
}