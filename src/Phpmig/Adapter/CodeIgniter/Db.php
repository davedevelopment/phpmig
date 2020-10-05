<?php
/**
 * @package    Phpmig
 * @subpackage Phpmig\Adapter
 */
namespace Phpmig\Adapter\CodeIgniter;

use \Phpmig\Migration\Migration,
    \Phpmig\Adapter\AdapterInterface;

class Db implements AdapterInterface
{
    /**
     * @var \CI_Controller
     */
    protected $ci;

    /**
     * @var \CI_DB_query_builder
     */
    protected $connection;

    /**
     * @var string
     */
    protected $tableName;

    public function __construct(string $tableName)
    {
        $this->ci = &get_instance();
        $this->ci->load->dbforge();
        $this->tableName = $tableName;
        $this->connection = $this->ci->db;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll()
    {
        $rows = array();

        $query = $this->connection->get($this->tableName);

        foreach ($query->result() as $row) {
           $rows[] = $row->version;
        }

        return $rows;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Migration $migration)
    {
        $this->connection->insert(
            $this->tableName,
            array(
                'version' => $migration->getVersion()
            )
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function down(Migration $migration)
    {
        $this->connection->where('version', $migration->getVersion());
        $this->connection->delete($this->tableName);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasSchema()
    {
        return $this->connection->table_exists($this->tableName);
    }

    /**
     * {@inheritdoc}
     */
    public function createSchema()
    {
        $fields = array(
            "`version` bigint(20) unsigned NOT NULL"
        );

        $this->ci->dbforge->add_field($fields);
        $this->ci->dbforge->create_table($this->tableName);

        return $this;
    }
}

