<?php

namespace Phpmig\Adapter;

use Phpmig\Migration\Migration;

/**
 * @author Samuel Laulhau https://github.com/lalop
 */

class Mongo implements AdapterInterface
{
    /**
     * @var \MongoDb
     */
    protected $connection;

    /**
     * @var string
     */
    protected $tableName;

    public function __construct(\MongoDb $connection, $tableName)
    {
        $this->connection    = $connection;
        $this->tableName     = $tableName;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll()
    {
        $cursor = $this->connection->selectCollection($this->tableName)->find();
        $versions = [];
        foreach($cursor as $version) {
            $versions[] = $version['version'];
        }

        return $versions;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Migration $migration)
    {
        $this->connection->selectCollection($this->tableName)
            ->insert(['version' => $migration->getVersion()]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function down(Migration $migration)
    {
        $this->connection->selectCollection($this->tableName)
            ->remove(['version' => $migration->getVersion()]);

        return $this;
    }


    /**
     * {@inheritdoc}
     */
    public function hasSchema()
    {
        return !empty(array_filter(
            $this->connection->getCollectionNames(),
            function($collection) {
                return $collection === $this->tableName;
        }));
    }


    /**
     * {@inheritdoc}
     */
    public function createSchema()
    {
        $this->connection->selectCollection($this->tableName)
            ->ensureIndex('version', ['unique' => 1]);

        return $this;
    }
}

