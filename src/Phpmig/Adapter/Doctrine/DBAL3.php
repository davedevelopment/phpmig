<?php
/**
 * @package    Phpmig
 * @subpackage Phpmig\Adapter
 */
namespace Phpmig\Adapter\Doctrine;

use \Doctrine\DBAL\Connection,
    \Doctrine\DBAL\Schema\Schema,
    \Phpmig\Migration\Migration,
    \Phpmig\Adapter\AdapterInterface;

/**
 * Phpmig adapter for doctrine dbal 3 connection
 */
class DBAL3 implements AdapterInterface
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $tableName;

    public function __construct(Connection $connection, string $tableName)
    {
        $this->connection = $connection;
        $this->tableName  = $tableName;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll()
    {
        $tableName = $this->connection->quoteIdentifier($this->tableName);
        $sql = "SELECT version FROM $tableName ORDER BY version ASC";
        $all = $this->connection->fetchAllAssociative($sql);

        return array_map(function($v) {return $v['version'];}, $all);
    }

    /**
     * {@inheritdoc}
     */
    public function up(Migration $migration)
    {
        $this->connection->insert($this->tableName, array(
            'version' => $migration->getVersion(),
        ));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function down(Migration $migration)
    {
        $this->connection->delete($this->tableName, array(
            'version' => $migration->getVersion(),
        ));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasSchema()
    {
        $sm = $this->connection->createSchemaManager();
        $tables = $sm->listTables();
        foreach($tables as $table) {
            if ($table->getName() == $this->tableName) {
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
        $schema  = new \Doctrine\DBAL\Schema\Schema();
        $table   = $schema->createTable($this->tableName);
        $table->addColumn("version", "string", array("length" => 255));
        $queries = $schema->toSql($this->connection->getDatabasePlatform());
        foreach($queries as $sql) {
            $this->connection->executeQuery($sql);
        }

        return $this;
    }
}
