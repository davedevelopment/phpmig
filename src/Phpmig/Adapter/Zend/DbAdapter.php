<?php
/**
 * @package    Phpmig
 * @subpackage Phpmig\Adapter
 */
namespace Phpmig\Adapter\Zend;

use Phpmig\Adapter\AdapterInterface;
use Phpmig\Migration\Migration;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Metadata\Metadata;
use Zend\Db\Sql\Ddl\Column\Varchar;
use Zend\Db\Sql\Ddl\CreateTable;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

/**
 * Phpmig adapter for zendframework/zend-db
 *
 * @package Phpmig\Adapter\Zend
 */
class DbAdapter implements AdapterInterface
{
    /**
     * @var TableGateway
     */
    private $tableGateway;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var Adapter
     */
    private $adapter;

    public function __construct(Adapter $adapter, string $tableName)
    {
        $this->adapter   = $adapter;
        $this->tableName = $tableName;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll()
    {
        $result = $this->tableGateway()->select(function (Select $select) {
            $select->order('version ASC');
        })->toArray();

        // imitate fetchCol
        return array_map(static function ($item) {
            return $item['version'];
        }, $result);
    }

    /**
     * {@inheritdoc}
     */
    public function up(Migration $migration)
    {
        $this->tableGateway()->insert(['version' => $migration->getVersion()]);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function down(Migration $migration)
    {
        $this->tableGateway()->delete(['version' => $migration->getVersion()]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasSchema()
    {
        try {
            $metadata = new Metadata($this->adapter);
            $metadata->getTable($this->tableName);

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createSchema()
    {
        $ddl = new CreateTable($this->tableName);
        $ddl->addColumn(new Varchar('version', 255));

        $sql = new Sql($this->adapter);

        $this->adapter->query(
            $sql->buildSqlString($ddl),
            Adapter::QUERY_MODE_EXECUTE
        );

        return $this;
    }

    /**
     * @return TableGateway
     */
    private function tableGateway()
    {
        if (!$this->tableGateway) {
            $this->tableGateway = new TableGateway($this->tableName, $this->adapter);
        }

        return $this->tableGateway;
    }
}
