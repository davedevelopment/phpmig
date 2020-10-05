<?php
/**
 * @package    Phpmig
 * @subpackage Phpmig\Adapter
 */
namespace Phpmig\Adapter\Illuminate;

use PDO;
use \Phpmig\Migration\Migration,
    \Phpmig\Adapter\AdapterInterface;
use RuntimeException;

/**
 * @author Andrew Smith http://github.com/silentworks
 */
class Database implements AdapterInterface
{
    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var \Illuminate\Database\Connection
     */
    protected $adapter;

    public function __construct($adapter, string $tableName, string $connectionName = '')
    {
        $this->adapter = $adapter->connection($connectionName);
        $this->tableName = $tableName;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll()
    {
        $fetchMode = (method_exists($this->adapter, 'getFetchMode')) ?
            $this->adapter->getFetchMode() : PDO::FETCH_OBJ;

        $all = $this->adapter
            ->table($this->tableName)
            ->orderBy('version')
            ->get();

        if(!is_array($all)) {
            $all = $all->toArray();
        }

        return array_map(function($v) use($fetchMode) {

            switch ($fetchMode) {

                case PDO::FETCH_OBJ:
                    return $v->version;

                case PDO::FETCH_ASSOC:
                    return $v['version'];

                default:
                    throw new RuntimeException("The PDO::FETCH_* constant {$fetchMode} is not supported");
            }
        }, $all);
    }

    /**
     * {@inheritdoc}
     */
    public function up(Migration $migration)
    {
        $this->adapter
            ->table($this->tableName)
            ->insert(array(
                'version' => $migration->getVersion()
            ));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function down(Migration $migration)
    {
        $this->adapter
            ->table($this->tableName)
            ->where('version', $migration->getVersion())
            ->delete();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasSchema()
    {
        return $this->adapter->getSchemaBuilder()->hasTable($this->tableName);
    }

    /**
     * {@inheritdoc}
     */
    public function createSchema()
    {
        /* @var \Illuminate\Database\Schema\Blueprint $table */
        $this->adapter->getSchemaBuilder()->create($this->tableName, function ($table) {
            $table->string('version');
        });

        return $this;
    }
}
