<?php

/**
 * S3Asset
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md
 * file distributed with this source code.
 *
 * @copyright  Copyright (c) 2014-2016 Gather Digital Ltd (https://www.gatherdigital.co.uk)
 * @license    https://www.gatherdigital.co.uk/license     GNU General Public License version 3 (GPLv3)
 */

namespace S3\Model;

use Pimcore\Model\AbstractModel;
use Pimcore\Model\Asset;
use S3\Model\S3Asset\Service;

class S3Asset extends AbstractModel
{

    /**
     * @var int $id
     */
    public $id;

    /**
     * @var int $assetId
     */
    public $assetId;

    /**
     * @var string $bucketName
     */
    public $bucketName;

    /**
     * @var string $path
     */
    public $path;

    /**
     * @var string $filename
     */
    public $filename;

    /**
     * @var string $checksum
     */
    public $checksum;

    /**
     * @var int $creationDate
     */
    public $creationDate;

    /**
     * @var int $modificationDate
     */
    public $modificationDate;

    /**
     * @var S3Asset\Service $service
     */
    private $service;

    /**
     * @var S3Asset\Config $config
     */
    private $config;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return S3Asset
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getAssetId()
    {
        return $this->assetId;
    }

    /**
     * @param int $assetId
     * @return S3Asset
     */
    public function setAssetId($assetId)
    {
        $this->assetId = (int) $assetId;

        return $this;
    }

    /**
     * @param Asset $asset
     * @return $this
     */
    public function setAsset($asset)
    {
        $this->assetId = $asset->getId();

        if (!$this->getPath()) {
            $this->setPath($this->getAsset()->getPath());
        }
        if (!$this->getFilename()) {
            $this->setFilename($this->getAsset()->getFilename());
        }
        if (!$this->getBucketName()) {
            $this->setBucketName($this->getConfig()->getDefaultBucketName());
        }

        return $this;
    }

    /**
     * @return \Pimcore\Model\Asset
     */
    public function getAsset()
    {
        return Asset::getById($this->assetId);
    }

    /**
     * @return string
     */
    public function getBucketName()
    {
        return $this->bucketName;
    }

    /**
     * @param string $bucketName
     * @return S3Asset
     */
    public function setBucketName($bucketName)
    {
        $this->bucketName = $bucketName;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return S3Asset
     */
    public function setPath($path)
    {
        $this->path = rtrim($path, '/') . '/';

        return $this;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param string $remoteFilename
     * @return S3Asset
     */
    public function setFilename($remoteFilename)
    {
        $this->filename = $remoteFilename;

        return $this;
    }

    /**
     * @return string
     */
    public function getFullPath()
    {
        return $this->path.$this->filename;
    }

    /**
     * @return string
     */
    public function getChecksum()
    {
        return $this->checksum;
    }

    /**
     * @param string $checksum
     * @return S3Asset
     */
    public function setChecksum($checksum)
    {
        $this->checksum = $checksum;

        return $this;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param int $creationDate
     * @return S3Asset
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * @return int
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param int $modificationDate
     * @return S3Asset
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getFileSystemPath()
    {
        return sprintf('s3://%s%s', $this->getBucketName(), $this->getFullPath());
    }

    public function getFileSystemFolder()
    {
        return sprintf('s3://%s%s', $this->getBucketName(), $this->getPath());
    }

    /**
     * Returns the URL that this asset will reside on
     * @return string
     */
    public function getUri()
    {
        return sprintf('https://s3-%s.amazonaws.com/%s%s',
            $this->getConfig()->getAwsRegion(),
            $this->getBucketName(),
            $this->getFullPath()
        );
    }


    /**
     * Returns an S3Sync by it's id
     * @param integer $id
     * @return S3Asset
     */
    public static function getById($id)
    {
        $obj = new self;

        try {
            $obj->getDao()->getById($id);
        } catch(\Exception $w) {
            return false;
        }

        return $obj;
    }

    /**
     * Returns an S3Sync by it's assetId
     * @param integer $assetId
     * @return S3Asset
     */
    public static function getByAssetId($assetId)
    {
        $obj = new self;

        try {
            $obj->getDao()->getByAssetId($assetId);
        } catch(\Exception $w) {
            return false;
        }

        return $obj;
    }

    /**
     * Returns an S3Sync by it's full S3 path
     * @param integer $id
     * @return S3Asset
     */
    public static function getByPath($path)
    {
        $obj = new self;

        try {
            $obj->getDao()->getByPath($path);
        } catch(\Exception $w) {
            return false;
        }

        return $obj;
    }

    public function save()
    {
        if (!$this->getAsset() instanceof Asset) {
            throw new \Exception('Cannot save an S3Asset without giving it an Asset!');
        }

        if (!$this->getId()) {
            $this->setCreationDate(time());
        }

        if (!$this->getPath()) {
            $this->setPath($this->getAsset()->getPath());
        }
        if (!$this->getFilename()) {
            $this->setFilename($this->getAsset()->getFilename());
        }
        if (!$this->getBucketName()) {
            $this->setBucketName($this->getConfig()->getDefaultBucketName());
        }

        if ($this->getAsset()->getType() != 'folder') {
            $this->setChecksum(md5_file($this->getAsset()->getFileSystemPath()));
        } else {
            $this->setChecksum(md5($this->getAsset()->getFullPath()));
        }

        $this->setModificationDate(time());

        if(PIMCORE_CONSOLE && \Zend_Registry::isRegistered('Plugin_S3_Console')) {
            //sorry for this ;-(
            $consoleOutput = \Zend_Registry::get('Plugin_S3_Console');
            $consoleOutput->writeln("Syncing S3Asset [{$this->getFileSystemPath()}]");
        }
        $this->getService()->sync();

        if ($this->getConfig()->getGenerateS3Thumbnails()) {
            foreach ($this->getConfig()->getS3ThumbnailConfigNames() as $config) {
                S3Thumbnail::generate($this, $config);
            }
        }

        return $this->getDao()->save();
    }

    /**
     * Delete the S3Asset
     * @return mixed
     * @throws \Exception
     */
    public function delete($deleteFromS3=true)
    {
        if ($deleteFromS3) {
            $this->getService()->deleteS3AssetPhysicalFile();
        }

        return $this->getDao()->delete();
    }

    public function getService()
    {
        if (!$this->service) {
            $this->service = new S3Asset\Service($this);
        }

        return $this->service;
    }

    public function setService(Service $service)
    {
        $this->service = $service;
    }

    public function getConfig()
    {
        if (!$this->config) {
            $this->config = new S3Asset\Config($this->getAsset());
        }

        return $this->config;
    }

    public function setConfig(S3Asset\Config $config)
    {
        $this->config = $config;
    }



}