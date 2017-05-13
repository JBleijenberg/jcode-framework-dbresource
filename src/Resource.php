<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the General Public License (GPL 3.0)
 * that is bundled with this package in the file LICENSE
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/GPL-3.0
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category    J!Code: Framework
 * @package     J!Code: Framework
 * @author      Jeroen Bleijenberg <jeroen@jcode.nl>
 *
 * @copyright   Copyright (c) 2017 J!Code (http://www.jcode.nl)
 * @license     http://opensource.org/licenses/GPL-3.0 General Public License (GPL 3.0)
 */
namespace Jcode\Db;

use Jcode\Application;
use Jcode\Object\Collection;
use \Exception;

abstract class Resource extends Collection
{

    /**
     * @var object
     */
    protected $adapter;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $primaryKey;

    /**
     * @var string
     */
    protected $modelClass;

    /**
     * Array of columns to select
     * @var array
     */
    protected $select = ['main_table.*'];

    /**
     * Array of JOIN() to perform
     * @var array
     */
    protected $join = [];

    /**
     * Array of WHERE() statements
     *
     * @var array
     */
    protected $filter = [];

    protected $expressions = [];

    /**
     * Array of ORDER() statements
     * @var array
     */
    protected $order = [];

    /**
     * Array of LIMIT() statements
     * @var array
     */
    protected $limit = [];

    protected $distinct;

    protected $group = [];

    /**
     * Array of allowed conditions
     * @var array
     */
    protected $conditions = [
        'eq', // =
        'neq',// !=
        'gt', // >
        'lt', // <
        'gteq', // >=
        'lteq', // <=
        'like', // LIKE()
        'nlike',// NOT LIKE()
        'in', // IN()
        'nin', // NOT IN()
        'null', // IS NULL
        'not-null', // NOT NULL
    ];

    public function init()
    {
        if (!$this->table || !$this->primaryKey) {
            throw new Exception('Tablename and primary key are required for ' . __CLASS__);
        }

        if (!$this->modelClass) {
            $this->modelClass = str_replace('\Resource\\', '\Resource\Model\\', get_called_class());
        }

        /* @var \Jcode\Db\\Adapter $adapter */
        $adapter = Application::objectManager()->get('\Jcode\Db\Adapter');

        $this->adapter = $adapter->getInstance();
    }

    public function getAdapter()
    {
        return $this->adapter;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getSelect()
    {
        return $this->select;
    }

    public function getJoin()
    {
        return $this->join;
    }

    public function getFilter()
    {
        return $this->filter;
    }

    public function getExpression()
    {
        return $this->expressions;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function getLimit($key = null)
    {
        return ($key !== null) ? $this->limit[$key] : $this->limit;
    }

    public function getDistinct()
    {
        return $this->distinct;
    }

    public function getGroupBy()
    {
        return $this->group;
    }

    /**
     * Add column to select statement
     * If columns of main_table are added, remove main_table.*
     *
     * @param $column
     * @return $this;
     */
    public function addColumnToSelect($column)
    {
        if (strpos($column, '.') === false) {
            $column = sprintf('main_table.%s', $column);

            $search = array_search('main_table.*', $this->select);

            if ($search !== false) {
                unset($this->select[$search]);
            }
        }

        array_push($this->select, $column);

        return $this;
    }

    /**
     * Mass add column to select statement
     *
     * @param $columns
     * @return $this
     */
    public function addColumnsToSelect($columns)
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        foreach ($columns as $column) {
            $this->addColumnToSelect($column);
        }

        return $this;
    }

    /**
     * Remove column from select statement
     *
     * @param $column
     * @return $this
     */
    public function removeColumnFromSelect($column)
    {
        if (strpos($column, '.') === false) {
            $column = sprintf('main_table.%s', $column);
        }

        $search = array_search($column, $this->select);

        if ($search !== false) {
            unset($this->select[$search]);
        }

        return $this;
    }

    /**
     * Mass remove columns from select statement
     *
     * @param $columns
     * @return $this
     */
    public function removeColumnsFromSelect($columns)
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        foreach ($columns as $column) {
            $this->removeColumnFromSelect($column);
        }

        return $this;
    }

    /**
     * Add filter to query. If condition is not an array, filter defaults to $column = $condition
     *
     * @param string $column
     * @param string|array $filter
     * @return $this
     */
    public function addFilter($column, $filter)
    {
        if (!is_array($filter)) {
            $filter = ['eq' => $filter];
        }

        if (!strstr($column, '.')) {
            $column = sprintf('main_table.%s', $column);
        }

        reset($filter);

        if (in_array(key($filter), $this->conditions)) {
            $this->filter[$column][] = [key($filter) => current($filter)];
        }

        return $this;
    }

    /**
     * Add custom expression to filter
     *
     * @param $column
     * @param $expression
     * @param $values
     * @return $this
     */
    public function addExpressionFilter($column, $expression, $values)
    {
        if (strpos($column, '.') === false) {
            $column = sprintf('main_table.%s', $column);
        }

        $this->expressions[$column][] = [$expression => $values];

        return $this;
    }

    public function addJoin(array $tables, $clause, array $args = [], $type = 'inner')
    {
        reset($tables);

        $this->join[key($tables)] = [
            'tables' => $tables,
            'clause' => $clause,
            'args' => $args,
            'type' => $type,
        ];

        if (empty($args)) {
            array_push($this->select, sprintf('%s.*', current($tables)));
        } else {
            foreach ($args as $arg) {
                array_push($this->select, sprintf('%s.%s', current($tables), $arg));
            }
        }

        return $this;
    }

    /**
     * Add Limit to select query
     *
     * @param int $offset
     * @param null $limit
     * @return $this
     */
    public function addLimit($offset = 0, $limit = null)
    {
        if ($limit === null) {
            $this->limit = ['offset' => 0, 'limit' => $offset];
        } else {
            $this->limit = ['offset' => $offset, 'limit' => $limit];
        }

        return $this;
    }

    /**
     * Add order to select query
     *
     * @param $column
     * @param string $direction
     * @return $this
     * @throws Exception
     */
    public function addOrder($column, $direction = 'ASC')
    {
        if ($direction != 'ASC' && $direction != 'DESC') {
            throw new Exception(
                "Invalid direction supplied for "
                . __FUNCTION__
                . ". ASC or DESC expected, {$direction} given"
            );
        }

        if (strpos($column, '.') === false) {
            $column = sprintf('main_table.%s', $column);
        }

        array_push($this->order, [$column => $direction]);

        return $this;
    }

    /**
     * Add distinct to select query
     *
     * @param $column
     * @return $this
     */
    public function addDistinct($column)
    {
        if (strpos($column, '.') === false) {
            $column = sprintf('main_table.%s', $column);
        }

        $this->distinct = $column;

        return $this;
    }

    /**
     * Add group by to select query
     *
     * @param $column
     * @return $this
     */
    public function addGroupBy($column)
    {
        if (strpos($column, '.') === false) {
            $column = sprintf('main_table.%s', $column);
        }

        $this->group = $column;

        return $this;
    }

    public function getItemByIndex($index)
    {
        if (!$this->items) {
            $this->getAllItems();
        }

        return $this->items[$index];
    }

    public function getAllItems()
    {
        if (empty($this->items)) {
            $this->getAdapter()->build($this);

            $result = $this->getAdapter()->execute();

            if (!empty($result)) {
                foreach ($result as $item) {
                    /* @var \Jcode\Object $itemObject */
                    $itemObject = Application::objectManager()->get($this->modelClass);
                    $itemObject->importArray($item);
                    $itemObject->copyToOrigData();
                    $itemObject->hasChangedData(false);

                    $this->addItem($itemObject);
                }
            }
        }

        return $this->items;
    }

    public function getQuery()
    {
        return $this->getAdapter()
            ->build($this)
            ->getQuery();
    }

    public function count()
    {
        return count($this->getAllItems());
    }

    public function rewind()
    {
        if (empty($this->items)) {
            $this->getAllItems();
        }

        parent::rewind();
    }

    public function setModelClass($modelClass)
    {
        $this->modelClass = $modelClass;
    }

    public function execute($query)
    {
        return $this->getAdapter()
            ->setQuery($query)
            ->execute();
    }
}