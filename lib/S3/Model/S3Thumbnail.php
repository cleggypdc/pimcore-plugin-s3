<?php

/**
 * S3Thumbnail
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md
 * file distributed with this source code.
 *
 * @copyright  Copyright (c) 2014-2016 Gather Digital Ltd (https://www.gatherdigital.co.uk)
 * @license    https://www.gatherdigital.co.uk/license     GNU General Public License version 3 (GPLv3)
 */

namespace S3\Model;

use S3\Model\S3Asset;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image\Thumbnail\Config as ImageThumbConfig;
use Pimcore\Model\Asset\Video\Thumbnail\Config as VideoThumbConfig;

class S3Thumbnail
{

    const S3THUMBNAIL_FOLDER = '/_thumbs/';

    /**
     * @var S3Asset $S3Asset
     */
    protected $S3Asset;

    /**
     * @var string $config
     */
    protected $config;

    /**
     * @var string
     */
    protected $assetType;


    public function __construct($S3Asset, $config=null)
    {
        $this->S3Asset = $S3Asset;
        $this->assetType = $this->S3Asset->getAsset()->getType();

        if (is_null($config) || !is_string($config)) {
            throw new \Exception('S3Thumbnail only supports named thumbnail configurations');
        }

        $this->config = $config;
    }

    public function save()
    {
        if ($this->assetType === 'image') {
            $config = ImageThumbConfig::getByName($this->config);
        } else if ($this->assetType === 'video') {
            $config = VideoThumbConfig::getByName($this->config);
        } else {
            \Logger::debug("S3Thumbnail cannot be generated for asset type {$this->assetType}");
            return false;
        }

        if (!$config) {
            \Logger::warning("S3Thumbnail could not find thumbnail configuration for named configuration {$this->config}");
            return false;
        }

        /**
         * @var Asset\Image|Asset\Video $asset
         */
        $asset = $this->S3Asset->getAsset();
        $thumbnail = $asset->getThumbnail($this->config, false);

        if (!$thumbnail) {
            \Logger::warning("S3Thumbnail could not fetch thumbnail {$this->config} for asset id {$asset->getId()}");
            return false;
        }

        //ensure we initialise the stream wrapper for this request
        $service = $this->S3Asset->getService();

        try {
            $result = file_put_contents($this->getThumbnailSavePath(), file_get_contents($thumbnail->getFileSystemPath()));

            \Logger::debug("S3Thumbnail successfully generated for asset [{$this->getThumbnailSavePath()}]");

        } catch(\Exception $e) {
            \Logger::error("S3Thumbnail could not save thumbnail to S3 path {$this->getThumbnailSavePath()}");
            return false;
        }

    }

    protected function getThumbnailSavePath()
    {
        return sprintf('s3://%s%s', $this->S3Asset->getBucketName(), (self::S3THUMBNAIL_FOLDER . $this->config . $this->S3Asset->getFullPath()));
    }



    public static function generate($S3Asset, $config)
    {
        $self = new self($S3Asset, $config);
        return $self->save();
    }


}