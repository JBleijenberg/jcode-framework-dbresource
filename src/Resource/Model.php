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
namespace Jcode\Db\Resource;

use Jcode\Application;
use Jcode\DataObject;
use Jcode\Db\Resource;
use \PDOException;
use \Exception;

abstract class Model extends DataObject
{

    private $isLoaded = false;

    /**
     * @return Resource|object|\Countable
     */
    public function getResource() :Resource
    {
        $resourceClass = sprintf('\\%s\\Resource', get_called_class());

        return Application::getClass($resourceClass);
    }

    protected function beforeSave()
    {

    }

    /**
     * Save new or update object into DB
     * @param bool $forceInsert
     * @return Model
     * @throws Exception
     */
    public function save($forceInsert = false) :Model
    {
        $this->beforeSave();

        if ($this->hasChangedData()) {
            /* @var \Jcode\Db\Resource $resource */
            $resource = $this->getResource();

            $columns = array_column($resource->execute("DESCRIBE {$resource->getTable()}"), 'Field');

            if ($this->getData($resource->getPrimaryKey()) && $forceInsert === false) {
                $update = "UPDATE {$resource->getTable()} SET ";

                foreach ($this->getAllData() as $key => $value) {
                    if ($key != $resource->getPrimaryKey() && $value !== $this->getOrigData($key) && in_array($key, $columns)) {
                        $update .= "{$key} = :{$key}, ";
                    }
                }

                $update = substr_replace($update, " ", -2);

                $update .= " WHERE {$resource->getPrimaryKey()} = :{$resource->getPrimaryKey()}";

                /* @var \Jcode\Db\AdapterInterface|\Jcode\DBAdapter\Mysql $adapter */
                $adapter = $resource->getAdapter();

                try {
                    $adapter->beginTransaction();

                    $stmt = $adapter->prepare($update);

                    $stmt->bindValue($resource->getPrimaryKey(), $this->getData($resource->getPrimaryKey()));

                    foreach (array_diff_assoc($this->getAllData(), $this->getOrigData()) as $id => $value) {
                        if (in_array($id, $columns)) {
                            $stmt->bindValue(":{$id}", $value);
                        }
                    }

                    $stmt->execute();
                    $adapter->commit();
                } catch (PDOException $e) {
                    debug($this, true);
                    $adapter->rollBack();

                    Application::logException($e);

                    throw new \Exception($e->getMessage());
                } catch (Exception $e) {
                    debug($this, true);
                    $adapter->rollBack();

                    Application::logException($e);

                    throw new \Exception($e->getMessage());
                }
            } else {
                $columns = implode(',', array_keys($this->getAllData()));
                $binds = implode(',:', array_keys($this->getAllData()));

                $insert = "INSERT INTO {$resource->getTable()} ({$columns}) VALUES (:{$binds})";

                /* @var \Jcode\DBAdapter\Mysql|\Jcode\Db\AdapterInterface $adapter */
                $adapter = $resource->getAdapter();

                try {
                    $adapter->beginTransaction();

                    $stmt = $adapter->prepare($insert);

                    foreach ($this->getAllData() as $id => $value) {
                        $stmt->bindValue(":{$id}", $value);
                    }

                    $stmt->execute();
                    $lastInsertId = $adapter->lastInsertId();
                    $adapter->commit();

                    if ($resource->getPrimaryKey()) {
                        $this->setData($resource->getPrimaryKey(), $lastInsertId);
                    }
                } catch (PDOException $e) {
                    $adapter->rollBack();

                    Application::logException($e);

                    throw new \Exception($e->getMessage());
                } catch (Exception $e) {
                    $adapter->rollBack();

                    Application::logException($e);

                    throw new \Exception($e->getMessage());
                }
            }
        }

        $this->afterSave();

        return $this;
    }

    protected function afterSave()
    {

    }

    protected function beforeLoad()
    {

    }

    /**
     * Load object from DB
     *
     * @param $id
     * @return $this
     * @throws Exception
     */
    public function load($id = null)
    {
        /* @var \Jcode\Db\Resource $resource */
        $resource = $this->getResource();

        if ($id == null) {
            if ($this->getData($resource->getPrimaryKey())) {
                $id = $this->getData($resource->getPrimaryKey());
            } else {
                throw new \Exception('No ID given nor a primary key is set');
            }
        }

        $this->beforeLoad();

        $resource->addFilter($resource->getPrimaryKey(), $id);

        if ($resource->count() === 1) {
            $this->importObject($resource->getItemByIndex(0));
        }

        $this->isLoaded = true;

        $this->afterLoad();

        return $this;
    }

    public function isLoaded()
    {
        return $this->isLoaded;
    }

    protected function afterLoad()
    {

    }

    protected function beforeDelete()
    {

    }

    /**
     * Delete record from DB
     */
    public function delete()
    {
        if ($this->isLoaded()) {
            /* @var \Jcode\Db\Resource $resource */
            $resource = $this->getResource();

            $this->beforeDelete();

            if ($this->getData($resource->getPrimaryKey())) {
                $query = "DELETE FROM {$resource->getTable()} WHERE {$resource->getPrimaryKey()} = '{$this->getData($resource->getPrimaryKey())}'";

                try {
                    $resource->execute($query);
                } catch (Exception $e) {
                    Application::logException($e);
                }
            }

            $this->afterDelete();
        } else {
            throw new \Exception('Cannot delete resource because it is not loaded.');
        }
    }

    protected function afterDelete()
    {

    }
}