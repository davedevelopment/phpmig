<?php

namespace Phpmig\Adapter\PDO;

use Phpmig\Migration\Migration,
    Phpmig\Adapter\AdapterInterface,
    PDO;

/**
 * Simple PDO adapter to work with SQL database
 *
 * @author Samuel Laulhau https://github.com/lalop
 */

class Sql implements AdapterInterface
{

    /**
     * @var \PDO
     */
    protected $connection    = null;

    /**
     * @var string
     */
    protected $tableName     = null;

    /**
     * @var string
     */
    protected $pdoDriverName = null;

    /**
     * Constructor
     *
     * @param \PDO $connection
     * @param string $tableName
     */
    public function __construct(\PDO $connection, $tableName)
    {
        $this->connection    = $connection;
        $this->tableName     = $tableName;
        $this->pdoDriverName = $connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    private function quotedTableName()
    {
        return "`{$this->tableName}`";
    }

    /**
     * Fetch all
     *
     * @return array
     */
    public function fetchAll()
    {
        // get the appropriate query
        //
        $sql = $this->queries['fetchAll'];

        // return the results of the query
        //
        return $this->connection->query($sql, PDO::FETCH_COLUMN, 0)->fetchAll();
    }

    /**
     * Up
     *
     * @param Migration $migration
     * @return self
     */
    public function up(Migration $migration)
    {
        // get the appropriate query
        //
        $sql = $this->queries['up'];

        // prepare and execute the query
        //
        $this->connection->prepare($sql)
                ->execute(array(':version' => $migration->getVersion()));
        return $this;
    }

    /**
     * Down
     *
     * @param Migration $migration
     * @return self
     */
    public function down(Migration $migration)
    {
        // get the appropriate query
        //
        $sql = $this->queries['down'];

        // prepare and execute the query
        //
        $this->connection->prepare($sql)
                ->execute(array(':version' => $migration->getVersion()));
        return $this;
    }


    /**
     * Is the schema ready?
     *
     * @return bool
     */
    public function hasSchema()
    {
        // get the appropriate query
        //
        $sql = $this->queries['hasSchema'];

        // loop through the list of tables
        //
        while($table = $tables->fetchColumn()) {
            if ($table == $this->tableName) {
                return true;
            }
        }
        // we made it all the way through the list of tables without finding the
        // one we're looking for. Return false.
        //
        return false;
    }


    /**
     * Create Schema
     *
     * @return DBAL
     */
    public function createSchema()
    {
        // get the appropriate query
        //
        $sql = $this->queries['createSchema'];

        // execute the query
        //
        $this->connection->exec($sql);
        return $this;
    }

}

