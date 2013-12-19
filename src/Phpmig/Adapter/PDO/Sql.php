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
            // did we find the table we're looking for? if so, return true
            //
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

    /**
     * The magic getter for custom properties
     *
     * This getter currently handles one property: queries. This is a list of
     * queries used by the Sql adapter and varies depending on the value of
     * $this->pdoDriverName. At present, only queries for sqlite, mysql, & pgsql
     * are specified; if a different PDO driver is used, the mysql/pgsql queries
     * will be returned, which may or may not work for the given database.
     *
     * @param string $name
     * The name of the property to retrieve
     *
     * @return mixed
     */
    public function __get($name)
    {
        // is this a request for the "queries" array? if so, return the
        // appropriate list
        //
        if($name==='queries')
        {
            switch($this->pdoDriverName)
            {
                case 'sqlite':
                    return array(

                            'fetchAll'     => "SELECT `version` FROM {$this->quotedTableName()} ORDER BY `version` ASC",

                            'up'           => "INSERT INTO {$this->quotedTableName()} VALUES (:version);",

                            'down'         => "DELETE FROM {$this->quotedTableName()} WHERE version = :version",

                            'hasSchema'    => "SELECT `name` FROM `sqlite_master` WHERE `type`='table';",

                            'createSchema' => "CREATE table {$this->quotedTableName()} (`version` NOT NULL);",

                        );

                case 'mysql':
                case 'pgsql':
                default:
                    return array(

                            'fetchAll'     => "SELECT `version` FROM {$this->quotedTableName()} ORDER BY `version` ASC",

                            'up'           => "INSERT into {$this->quotedTableName()} set version = :version",

                            'down'         => "DELETE from {$this->quotedTableName()} where version = :version",

                            'hasSchema'    => "SHOW TABLES;",

                            'createSchema' => "CREATE TABLE {$this->quotedTableName()} (`version` VARCHAR(255) NOT NULL);",

                        );
            }
        }

        // it's a request for something else. Let the parent class handle it
        //
        return parent::__get($name);
    }
}

