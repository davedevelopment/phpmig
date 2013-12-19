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
    protected $connection = null;

    /**
     * @var string
     */
    protected $tableName = null;

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
        $this->connection = $connection;
        $this->tableName  = $tableName;
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
        $sql = "SELECT `version` FROM {$this->quotedTableName()} ORDER BY `version` ASC";
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
        $sql = "INSERT into {$this->quotedTableName()} set version = :version";
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
        $sql = "DELETE from {$this->quotedTableName()} where version = :version";
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
        $tables = $this->connection->query("show tables");
        while($table = $tables->fetchColumn()) {
            if ($table == $this->tableName) {
                return true;
            }
        }
        return false;
    }


    /**
     * Create Schema
     *
     * @return DBAL
     */
    public function createSchema()
    {
        $sql = "CREATE table {$this->quotedTableName()} (version %s NOT NULL)";
        $driver = $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        $sql = sprintf($sql,in_array($driver,array('mysql','pgsql'))? 'VARCHAR(255)' : '');
        $this->connection->exec($sql);
        return $this;
    }

}

