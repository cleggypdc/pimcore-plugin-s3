<?php

/**
 * Service
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md
 * file distributed with this source code.
 *
 * @copyright  Copyright (c) 2014-2016 Gather Digital Ltd (https://www.gatherdigital.co.uk)
 * @license    https://www.gatherdigital.co.uk/license     GNU General Public License version 3 (GPLv3)
 */

namespace S3\Model\S3Asset;


use Aws\Credentials\Credentials;
use Aws\S3\S3Client;

use Pimcore\Model\Asset;
use Pimcore\Config;

use S3\Model\S3Asset;

class Service
{

    /**
     * Enables Overwriting of existing files in S3 without producing an error
     * @var bool $overwrite
     */
    public static $enableOverwrite;

    /**
     * @var \Pimcore\Model\Asset $asset
     */
    public $asset;

    /**
     * @var S3Asset\Config $config;
     */
    public $config;

    /**
     * @var S3Asset $S3Asset
     */
    public $S3Asset;

    /**
     * @var S3Asset $oldS3Asset
     */
    private $oldS3Asset;

    /**
     * @var \Aws\Credentials\Credentials $credentials
     */
    private $credentials;

    /**
     * @var bool $forceUpdates
     */
    private $forceUpdates;

    /**
     * @var S3Client $S3Client
     */
    private $S3Client;


    const ASSET_DELETE = 'delete';
    const ASSET_CREATE = 'create';
    const ASSET_MOVE = 'move';
    const ASSET_MOVE_UPDATE = 'moveupdate';
    const ASSET_UPDATE = 'update';
    const ASSET_NO_CHANGE = 'nochange';


    /**
     * Service constructor.
     *
     * @param \Pimcore\Model\Object\Concrete $localObject
     * @param S3Asset $S3Asset
     */
    public function __construct(S3Asset $S3Asset)
    {
        $this->S3Asset = $S3Asset;
        $this->asset = $S3Asset->getAsset();
        $this->config = $S3Asset->getConfig();

        $client = $this->getS3Client();
        $client->registerStreamWrapper(); //provides the magic ;-)

        //attempt to authenticate
        if (!$client->doesBucketExist($this->config->getDefaultBucketName())) {
            throw new \Exception('Specified S3 Bucket does not exist.');
        }

        //if previously saved then initialise the previous version
        if ($this->S3Asset->getId()) {
            $this->oldS3Asset = S3Asset::getById($this->S3Asset->getId());
        }

    }


    /**
     * Returns the service, only if credentials can be obtained to sync
     * Useful helper method to check if an asset is syncable
     * @param Asset|S3Asset $assetOrS3Asset
     * @return Service|bool
     */
    public static function getForAsset(Asset $asset)
    {
        $S3Asset = S3Asset::getByAssetId($asset->getId());
        if (!$S3Asset) {
            $S3Asset = new S3Asset();
            $S3Asset->setAsset($asset);
        }

        $service = new self($S3Asset);

        if ($service->getCredentials()) {
            $S3Asset->setService($service);
            return $service;
        }

        return false;
    }

    public function setAsset(Asset $asset)
    {
        $this->asset = $asset;

        return $this;
    }

    public function getAsset()
    {
        return $this->asset;
    }

    public function setS3Asset(S3Asset $S3Asset)
    {
        $this->S3Asset = $S3Asset;
    }

    public function getS3Asset()
    {
        return $this->S3Asset;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function setConfig(S3Asset\Config $config)
    {
        $this->config = $config;
    }

    /**
     * @return boolean
     */
    public function getForceUpdates()
    {
        return $this->forceUpdates;
    }

    /**
     * @param boolean $forceUpdates
     */
    public function setForceUpdates($forceUpdates)
    {
        $this->forceUpdates = $forceUpdates;
    }



    public function sync()
    {
        switch ($this->detectChange()) {

            case self::ASSET_CREATE :
                $this->createS3AssetPhysicalFile();
            break;

            case self::ASSET_MOVE :
                $this->moveS3AssetPhysicalFile();
            break;

            case self::ASSET_UPDATE :
                $this->updateS3AssetPhysicalFile();
            break;

            case self::ASSET_MOVE_UPDATE :
                $this->moveAndUpdateS3AssetPhysicalFile();
            break;

            default :
                //
            break;

        }
    }

    /**
     * Returns the credentials for the asset
     * @return \Aws\Credentials\Credentials|bool
     */
    private function getCredentials()
    {
        if($this->credentials) {
            return $this->credentials;
        } else {
            $this->credentials = new Credentials($this->config->getAwsKey(), $this->config->getAwsSecret());
        }

        return $this->credentials;
    }


    private function getS3Client()
    {
        if (!$this->S3Client) {
            $this->S3Client = new S3Client([
                'version' => 'latest',
                'region' => $this->config->getAwsRegion(),
                'credentials' => $this->getCredentials()
            ]);
        }

        return $this->S3Client;
    }


    /**
     * Detects the change that has happened to an S3Asset
     * @return string
     */
    private function detectChange()
    {
        if (!$this->S3Asset->getId()) {
            return self::ASSET_CREATE;
        }

        if (    $this->S3Asset->getAsset()->getType() != 'folder'
            &&  $this->S3Asset->getChecksum() !== $this->oldS3Asset->getChecksum()) {

            if ($this->S3Asset->getFileSystemPath() !== $this->oldS3Asset->getFileSystemPath()) {
                return self::ASSET_MOVE_UPDATE;
            } else {
                return self::ASSET_UPDATE;
            }

        } else if ($this->S3Asset->getFileSystemPath() !== $this->oldS3Asset->getFileSystemPath()) {
            return self::ASSET_MOVE;
        }

        if ($this->getForceUpdates()) {
            return self::ASSET_UPDATE;
        }

        return self::ASSET_NO_CHANGE;
    }

    /**
     * Deletes an asset from S3
     * @return bool
     */
    public function deleteS3AssetPhysicalFile()
    {
        $asset = $this->S3Asset->getAsset();
        if ($asset->getType() === 'folder') {
            if ($asset->hasChilds()) {
                return $this->recursivelyDeleteChildren($asset->getChilds());
            } else {
                return true;
            }
        } else {
            return unlink($this->S3Asset->getFileSystemPath());
        }
    }

    public function recursivelyDeleteChildren($children)
    {
        foreach($children as $child) {
            if ($child->getType() === 'folder') {
                if ($child->hasChilds()) {
                    return $this->recursivelyDeleteChildren($child->getChilds());
                } else {
                    return true;
                }
            } else {
                $S3Asset = S3Asset::getByAssetId($child->getId());
                if($S3Asset) {
                    $S3Asset->delete();
                } else {
                    return true;
                }
            }
        }
    }

    /**
     * Creates a file
     * @return bool|int
     */
    private function createS3AssetPhysicalFile()
    {
        $asset = $this->S3Asset->getAsset();

        if ($asset->getType() != 'folder') {
            return file_put_contents($this->S3Asset->getFileSystemPath(), $this->S3Asset->getAsset()->getData());
        } else {
            //asset folders will be automatically created by S3
            //trying to add manually throws an error
            //try recursing children
            if ($asset->hasChilds()) {
                return $this->recursivelyCreateChildren($asset->getChilds());
            }
        }
    }


    public function recursivelyCreateChildren($children)
    {
        foreach($children as $child) {
            if ($child->getType() === 'folder') {
                if ($child->hasChilds()) {
                    return $this->recursivelyCreateChildren($child->getChilds());
                } else {
                    return true;
                }
            } else {
                $service = self::getForAsset($child);
                $S3Asset = $service->getS3Asset();
                $S3Asset->setPath($child->getPath());
                $S3Asset->setFilename($child->getFilename());
                $S3Asset->save();
            }
        }
    }

    /**
     * Moves a file
     * @throws \Exception
     */
    private function moveS3AssetPhysicalFile()
    {
        $oldPath = $this->oldS3Asset->getFileSystemPath();
        $newFolder = $this->S3Asset->getFileSystemFolder();
        $newPath = $this->S3Asset->getFileSystemPath();

        $asset = $this->S3Asset->getAsset();
        if ($asset->getType() === 'folder') {
            if ($asset->hasChilds()) {
                return $this->recursivelyMoveChildren($asset->getChilds());
            } else {
                return true;
            }
        }

        if (!is_file($oldPath)) {
            $this->createS3AssetPhysicalFile();
        }

        if (is_file($newPath)) {
            throw new \Exception("Could not move S3Asset, file destination already exists [{$this->S3Asset->getFileSystemPath()}]");
        }

        rename($oldPath, $newPath);

    }


    /**
     * Recursively Moves child assets when a folder is altered
     * @param $children
     * @return mixed
     * @throws \Exception
     */
    private function recursivelyMoveChildren($children)
    {
        foreach($children as $child) {
            if ($child->getType() === 'folder') {
                if ($child->hasChilds()) {
                    return $this->recursivelyMoveChildren($child->getChilds());
                } else {
                    return true;
                }
            } else {
                $service = self::getForAsset($child);
                $S3Asset = $service->getS3Asset();
                $S3Asset->setPath($child->getPath());
                $S3Asset->setFilename($child->getFilename());
                $S3Asset->save();
            }
        }
    }



    /**
     * Updates a file
     * @return int
     */
    private function updateS3AssetPhysicalFile()
    {
        $asset = $this->S3Asset->getAsset();
        if ($asset->getType() === 'folder') {
            if ($asset->hasChilds()) {
                return $this->recursivelyMoveChildren($asset->getChilds());
            }
        } else {
            return file_put_contents($this->S3Asset->getFileSystemPath(), $this->S3Asset->getAsset()->getData());
        }
    }

    /**
     * Moves and updates a file
     * @throws \Exception
     */
    private function moveAndUpdateS3AssetPhysicalFile()
    {
        $oldPath = $this->oldS3Asset->getFileSystemPath();
        $newFolder = $this->S3Asset->getFileSystemFolder();
        $newPath = $this->S3Asset->getFileSystemPath();

        if (is_file($newPath)) {
            throw new \Exception("Could not move S3Asset, file destination already exists [{$this->S3Asset->getFileSystemPath()}]");
        }

        file_put_contents($newPath, $this->S3Asset->getAsset()->getData());
        unlink($oldPath);
    }

}