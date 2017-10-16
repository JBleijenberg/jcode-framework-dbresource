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

interface TableInterface
{

    public function setTableName($name);

    public function getTableName();

    public function setEngine($engine);

    public function getEngine();

    public function setCharSet($charset);

    public function getCharSet();

    public function addColumn($name, $type, $length = null, array $options = []);

    public function alterColumn($name, array $options);

    public function dropColumn($name);

    public function getColumns();

    public function getColumn($name);

    public function setPrimaryKey($key);

    public function getPrimaryKey();

}