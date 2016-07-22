<?php
/**
 * @package    Phpmig
 * @subpackage Phpmig\Adapter
 */
namespace Phpmig\Adapter\Bitrix;

use Bitrix\Main\DB\Connection;
use Bitrix\Main\DB\SqlHelper;
use Bitrix\Main\Entity\StringField;
use Phpmig\Adapter\AdapterInterface;
use Phpmig\Migration\Migration;

/**
 * Phpmig adapter for Bitrix framework
 *
 * @package Phpmig\Adapter\Bitrix
 */
class Database implements AdapterInterface
{
    /**
     * @var string
     */
    private $tableName;

    /**
     * @var Connection
     */
    private $db;

    /**
     * @var SqlHelper
     */
    private $helper;

    /**
     * @param Connection $connection
     * @param string $tableName
     */
    public function __construct(Connection $connection, $tableName)
    {
        $this->db   = $connection;
        $this->helper = $connection->getSqlHelper();
        $this->tableName = $tableName;
    }
    
    /**
     * Get all migrated version numbers
     *
     * @return array
     */
    public function fetchAll()
    {
        $helper = $this->helper;
        $dbResult = $this->db->query(sprintf(
            'SELECT %s FROM %s ORDER BY %1$s ASC',
            $helper->quote('version'), $helper->quote($this->tableName)));
        $versions = array();
        while ($row = $dbResult->fetch()) {
            $versions[] = $row['version'];
        }

        return $versions;
    }

    /**
     * Up
     *
     * @param Migration $migration
     * @return AdapterInterface
     */
    public function up(Migration $migration)
    {
        $this->db->add($this->tableName, array('version' => $migration->getVersion(), 'name' => $migration->getName()), null);
        return $this;
    }

    /**
     * Down
     *
     * @param Migration $migration
     * @return AdapterInterface
     */
    public function down(Migration $migration)
    {
        $helper = $this->helper;
        $this->db->queryExecute(sprintf(
            'DELETE FROM %s WHERE %s = \'%s\'',
            $helper->quote($this->tableName), $helper->quote('version'), $helper->forSql($migration->getVersion())
        ));

        return $this;
    }

    /**
     * Is the schema ready?
     *
     * @return bool
     */
    public function hasSchema()
    {
        return $this->db->isTableExists($this->tableName);
    }

    /**
     * Create Schema
     *
     * @return AdapterInterface
     */
    public function createSchema()
    {
        $this->db->createTable($this->tableName, array(new StringField('version'), new StringField('name')));
        return $this;
    }
}
