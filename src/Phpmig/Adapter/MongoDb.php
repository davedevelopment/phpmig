<?php

namespace Phpmig\Adapter;

use MongoDB\Database;
use Phpmig\Migration\Migration;

/**
 * @author Carlos Barrero https://github.com/Zeyckler
 */
class MongoDb implements AdapterInterface
{

    /**
     * @var Database
     */
    protected $connection;

    /**
     * @var string
     */
    protected $tableName;

    public function __construct(Database $connection, string $tableName)
    {
        $this->connection = $connection;
        $this->tableName = $tableName;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll()
    {
        $cursor = $this->connection->selectCollection($this->tableName)->find(
            [],
            ['$project' => ['version' => 1]]
        );

        return array_map(
            static function ($document) {
                return $document['version'];
            },
            $cursor->toArray()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function up(Migration $migration)
    {
        $this->connection->selectCollection($this->tableName)
            ->insertOne(['version' => $migration->getVersion()]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function down(Migration $migration)
    {
        $this->connection->selectCollection($this->tableName)
            ->deleteOne(['version' => $migration->getVersion()]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasSchema()
    {
        foreach ($this->connection->listCollections() as $collection) {
            if ($collection->getName() === $this->tableName) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function createSchema()
    {
        $this->connection->selectCollection($this->tableName)
            ->createIndex(['version' => 1], ['unique' => true]);

        return $this;
    }
}
