<?php

namespace Phpmig\Adapter\PDO;

use Phpmig\Migration\Migration,
    PDO;

/**
 * Simple PDO adapter to work with ORACLE database in particular.
 * @author omenrpg https://github.com/omenrpg
 */
class SqlOci extends Sql {

    /**
     * Constructor
     *
     * @param \PDO $connection
     * @param string $tableName
     *
     * @throws \Exception
     */
    public function __construct(\PDO $connection, string $tableName)
    {
        parent::__construct($connection, $tableName);

        $driver = $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        if (!in_array( $driver, ['oci', 'oci8'])) {
            throw new \Exception('Please install OCI drivers for PDO!');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll()
    {
        $sql = 'SELECT "version" FROM "' . $this->tableName . '" ORDER BY "version" ASC';

        return $this->connection->query( $sql, PDO::FETCH_COLUMN, 0 )->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function up(Migration $migration)
    {
        $sql         = 'INSERT INTO "' . $this->tableName . '" ("version") VALUES (:version)';
        $this->connection->prepare( $sql )
                         ->execute( array(
                             ':version'      => $migration->getVersion()
                         ) );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function down(Migration $migration)
    {
        $sql = 'DELETE from "' . $this->tableName . '" where "version" = :version';
        $this->connection->prepare( $sql )
                         ->execute( array( ':version' => $migration->getVersion() ) );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasSchema()
    {
        $sql    = 'SELECT count(*) FROM user_tables WHERE table_name = :tableName';
        $sth = $this->connection->prepare( $sql );
        $sth->execute( array(
            ':tableName' => $this->tableName
        ) );

        return !($sth->fetchColumn() == 0);
    }

    /**
     * {@inheritdoc}
     */
    public function createSchema()
    {
        $sql = 'CREATE table "' . $this->tableName . '" ("version" VARCHAR2(4000) NOT NULL, "migrate_date" TIMESTAMP DEFAULT CURRENT_TIMESTAMP)';
        $this->connection->exec( $sql );

        return $this;
    }
}
