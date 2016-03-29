<?php

/**
 * Dao
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md
 * file distributed with this source code.
 *
 * @copyright  Copyright (c) 2014-2016 Gather Digital Ltd (https://www.gatherdigital.co.uk)
 * @license    https://www.gatherdigital.co.uk/license     GNU General Public License version 3 (GPLv3)
 */

namespace S3\Model\S3Asset;

use Pimcore\Model\Dao\AbstractDao;

class Dao extends AbstractDao
{

    /**
     * @var string $tableName
     */
    protected $tableName = 'plugin_s3_assets';

    public function save()
    {

        $vars           = get_object_vars($this->model);
        $buffer         = [];
        $validColumns   = $this->getValidTableColumns($this->tableName);

        if(count($vars)) {
            foreach ($vars as $k => $v) {

                if (!in_array($k, $validColumns)) {
                    continue;
                }

                if ($k == 'id') {
                    continue;
                }


                $getter = "get" . ucfirst($k);

                if (!is_callable([$this->model, $getter])) {
                    continue;
                }

                $value = $this->model->$getter();

                if (is_bool($value)) {
                    $value = (int) $value;
                }

                $buffer[$k] = $value;

            }
        }

        if ($this->model->getId() !== null) {
            $where = ['id = ?' => $this->model->getId()];
            $result = $this->db->update($this->tableName, $buffer, $where);
            return;
        }

        $this->db->insert($this->tableName, $buffer);
        $this->model->setId($this->db->lastInsertId());

        return;
    }

    public function delete()
    {
        $this->db->delete($this->tableName, $this->db->quoteInto("id = ?", $this->model->getId()));
    }

    /**
     * @param integer $id
     * @throws \Exception
     */
    public function getById($id)
    {

        if ($id === null) {
            throw new \Exception('getById requirements not met');
        }

        $this->model->setId($id);

        $data = $this->db->fetchRow(
            "SELECT * FROM {$this->tableName} WHERE id = ?",
            [$this->model->getId()]
        );

        if (!$data["id"]) {
            throw new \Exception('No S3Asset was found with the given id');
        }

        $this->assignVariablesToModel($data);
    }

    /**
     * @param integer $localId
     * @throws \Exception
     */
    public function getByAssetId($assetId)
    {
        if ($assetId === null) {
            throw new \Exception('getByAssetId requirements not met');
        }

        $this->model->setAssetId($assetId);

        $data = $this->db->fetchRow(
            "SELECT * FROM {$this->tableName} WHERE assetId = ?",
            [$this->model->getAssetId()]
        );

        if (!$data["id"]) {
            throw new \Exception('No S3Asset was found with the given assetId');
        }

        $this->assignVariablesToModel($data);
    }

    /**
     * @param integer $remoteId
     * @throws \Exception
     */
    public function getByRemotePath($remotePath)
    {
        if ($remotePath === null) {
            throw new \Exception('getByRemotePath requirements not met');
        }

        $data = $this->db->fetchRow(
            "SELECT * FROM {$this->tableName} WHERE CONCAT(remotePath,remoteFilename) = ?",
            [$remotePath]
        );

        if (!$data["id"]) {
            throw new \Exception('No S3Asset was found with the given remotePath');
        }

        $this->assignVariablesToModel($data);
    }


    /**
     * Overrrides the deafult assignVariablesToModel to allow for tags
     *
     * @param array $data
     */
    protected function assignVariablesToModel($data)
    {
        foreach($data as $key => $value) {
            $this->model->setValue($key, $value);
        }
    }


}