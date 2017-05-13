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
use Jcode\Object;
use \PDOException;
use \Exception;

abstract class Model extends Object
{

    /**
     * @var \Jcode\Db\Resource
     */
    protected $resource;

    /**
     * @return \Jcode\Db\Resource
     * @throws \Exception
     */
    public function getResource()
    {
        if (!$this->resource) {
            $resourceClass = str_replace('\Resource\Model\\', '\Resource\\', get_called_class());

            $this->resource = Application::objectManager()->get($resourceClass);
        }

        return $this->resource;
    }

    protected function beforeSave()
    {

    }

    /**
     * Save new or update object into DB
     */
    public function save()
    {
        $this->beforeSave();

        if ($this->hasChangedData()) {
            /* @var \Jcode\Db\Resource $resource */
            $resource = $this->getResource();

            if ($this->getData($resource->getPrimaryKey())) {
                $update = "UPDATE {$resource->getTable()} SET ";

                foreach ($this->getData() as $key => $value) {
                    if ($key != $resource->getPrimaryKey() && $value !== $this->getOrigData($key)) {
                        $update .= "{$key} = :{$key}, ";
                    }
                }

                $update = substr_replace($update, " ", -2);

                $update .= " WHERE {$resource->getPrimaryKey()} = :{$resource->getPrimaryKey()}";

                /* @var \Jcode\Db\AdapterInterface|\Jcode\Db\Adapter\Mysql $adapter */
                $adapter = $resource->getAdapter();

                try {
                    $adapter->beginTransaction();

                    $stmt = $adapter->prepare($update);

                    foreach ($this->getData() as $id => $value) {
                        $stmt->bindValue(":{$id}", $value);
                    }

                    $stmt->execute();
                    $adapter->commit();
                } catch (PDOException $e) {
                    $adapter->rollBack();

                    Application::logException($e);
                } catch (Exception $e) {
                    $adapter->rollBack();

                    Application::logException($e);
                }
            } else {
                $columns = implode(',', array_keys($this->getData()));
                $binds = implode(',:', array_keys($this->getData()));

                $insert = "INSERT INTO {$resource->getTable()} ({$columns}) VALUES (:{$binds})";

                /* @var \Jcode\Db\Adapter\Mysql|\Jcode\Db\AdapterInterface $adapter */
                $adapter = $resource->getAdapter();

                try {
                    $adapter->beginTransaction();

                    $stmt = $adapter->prepare($insert);

                    foreach ($this->getData() as $id => $value) {
                        $stmt->bindValue(":{$id}", $value);
                    }

                    $stmt->execute();
                    $lastInsertId = $adapter->lastInsertId();
                    $adapter->commit();

                    $this->setData($resource->getPrimaryKey(), $lastInsertId);
                } catch (PDOException $e) {
                    $adapter->rollBack();

                    Application::logException($e);
                } catch (Exception $e) {
                    $adapter->rollBack();

                    Application::logException($e);
                }
            }
        }

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
     *
     * @return $this
     */
    public function load($id)
    {
        /* @var \Jcode\Db\Resource $resource */
        $resource = $this->getResource();

        $this->beforeLoad();

        $resource->addFilter($resource->getPrimaryKey(), $id);

        if ($resource->count() === 1) {
            $this->importObject($resource->getItemByIndex(0));
        }

        $this->afterLoad();

        return $this;
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
    }

    protected function afterDelete()
    {

    }
}