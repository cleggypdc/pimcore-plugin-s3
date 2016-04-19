<?php

/**
 * Config
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md
 * file distributed with this source code.
 *
 * @copyright  Copyright (c) 2014-2016 Gather Digital Ltd (https://www.gatherdigital.co.uk)
 * @license    https://www.gatherdigital.co.uk/license     GNU General Public License version 3 (GPLv3)
 */

namespace S3\Model\S3Asset;

use S3\Model\S3Asset;
use Pimcore\Model\Asset;
use Pimcore\Config as PimcoreConfig;

class Config
{

    /**
     * @var string $awsKey
     */
    public $awsKey;

    /**
     * @var string $awsSecret
     */
    public $awsSecret;

    /**
     * @var string $awsRegion
     */
    public $awsRegion;


    /**
     * @var string $defaultBucketName
     */
    public $defaultBucketName;

    /**
     * @var bool $keepDeletedAssets
     */
    public $keepDeletedAssets;

    /**
     * @var bool $s3Enabled
     */
    public $s3Enabled;


    /**
     * @var bool $generateS3Thumbnails
     */
    public $generateS3Thumbnails;


    /**
     * @var array $S3ThumbnailConfigNames
     */
    public $S3ThumbnailConfigNames;


    /**
     * Config constructor.
     *
     * @param \Pimcore\Model\Asset $asset
     */
    public function __construct(Asset $asset)
    {

        $this->awsKey = $asset->getProperty('plugin_s3_aws_key');
        if (!$this->awsKey) {
            $this->awsKey = PimcoreConfig::getWebsiteConfig()->get('plugin_s3_aws_key');
            if (!$this->awsKey) {
                throw new \Exception('AWS Key is required in S3 Configuration');
            }
        }

        $this->awsSecret = $asset->getProperty('plugin_s3_aws_secret');
        if (!$this->awsSecret) {
            $this->awsSecret = PimcoreConfig::getWebsiteConfig()->get('plugin_s3_aws_secret');
            if (!$this->awsSecret) {
                throw new \Exception('AWS Secret is required in S3 Configuration');
            }
        }

        $this->awsRegion = $asset->getProperty('plugin_s3_aws_region');
        if (!$this->awsRegion) {
            $this->awsRegion = PimcoreConfig::getWebsiteConfig()->get('plugin_s3_aws_region');
            if (!$this->awsRegion) {
                $this->awsRegion = 'eu-west-1';
            }
        }

        $this->defaultBucketName = $asset->getProperty('plugin_s3_aws_bucket');
        if (!$this->defaultBucketName) {
            $this->defaultBucketName = PimcoreConfig::getWebsiteConfig()->get('plugin_s3_aws_bucket');
            if (!$this->defaultBucketName) {
                throw new \Exception('Bucket name is required in S3 Configuration');
            }
        }

        $this->keepDeletedAssets = $asset->getProperty('plugin_s3_keep_deleted_assets');
        if (!$this->keepDeletedAssets === null) {
            $this->keepDeletedAssets = PimcoreConfig::getWebsiteConfig()->get('plugin_s3_keep_deleted_assets');
        }

        if ($asset->getProperty('plugin_s3_disable') !== null) {
            $this->s3Enabled = ($asset->getProperty('plugin_s3_disable')) ? false : true;
        } else {
            $this->s3Enabled = (PimcoreConfig::getWebsiteConfig()->get('plugin_s3_disable')) ? false : true;
        }

        if ($asset->getProperty('plugin_s3_generate_s3thumbnails') !== null) {
            $this->generateS3Thumbnails = (bool) $asset->getProperty('plugin_s3_generate_s3thumbnails');
        } else {
            $this->generateS3Thumbnails = (bool) PimcoreConfig::getWebsiteConfig()->get('plugin_s3_generate_s3thumbnails') ?: false;
        }

        if ($asset->getProperty('plugin_s3_s3thumbnail_config_names') !== null) {
            $this->S3ThumbnailConfigNames = $asset->getProperty('plugin_s3_s3thumbnail_config_names');
        } else {
            $this->S3ThumbnailConfigNames = PimcoreConfig::getWebsiteConfig()->get('plugin_s3_s3thumbnail_config_names') ?: false;
        }

        if ($this->S3ThumbnailConfigNames) {
            $this->S3ThumbnailConfigNames = array_map('trim', array_filter(explode(',', $this->S3ThumbnailConfigNames)));
        }

    }


    /**
     * @return string
     */
    public function getAwsKey()
    {
        return $this->awsKey;
    }

    /**
     * @param string $awsKey
     * @return Config
     */
    public function setAwsKey($awsKey)
    {
        $this->awsKey = $awsKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getAwsSecret()
    {
        return $this->awsSecret;
    }

    /**
     * @param string $awsSecret
     * @return Config
     */
    public function setAwsSecret($awsSecret)
    {
        $this->awsSecret = $awsSecret;

        return $this;
    }

    /**
     * @return string
     */
    public function getAwsRegion()
    {
        return $this->awsRegion;
    }

    /**
     * @param string $awsRegion
     * @return Config
     */
    public function setAwsRegion($awsRegion)
    {
        $this->awsRegion = $awsRegion;

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultBucketName()
    {
        return $this->defaultBucketName;
    }

    /**
     * @param string $defaultBucketName
     * @return Config
     */
    public function setDefaultBucketName($defaultBucketName)
    {
        $this->defaultBucketName = $defaultBucketName;

        return $this;
    }

    /**
     * @return bool
     */
    public function getKeepDeletedAssets()
    {
        return $this->keepDeletedAssets;
    }

    /**
     * @param bool $keepDeletedAssets
     */
    public function setKeepDeletedAssets($keepDeletedAssets)
    {
        $this->keepDeletedAssets = (bool) $keepDeletedAssets;
    }

    /**
     * @return boolean
     */
    public function isS3Enabled()
    {
        return $this->s3Enabled;
    }

    /**
     * @param boolean $s3Enabled
     * @return Config
     */
    public function setS3Enabled($s3Enabled)
    {
        $this->s3Enabled = (bool) $s3Enabled;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getGenerateS3Thumbnails()
    {
        return $this->generateS3Thumbnails;
    }

    /**
     * @param boolean $generateS3Thumbnails
     * @return Config
     */
    public function setGenerateS3Thumbnails($generateS3Thumbnails)
    {
        $this->generateS3Thumbnails = $generateS3Thumbnails;

        return $this;
    }

    /**
     * @return array
     */
    public function getS3ThumbnailConfigNames()
    {
        return ($this->S3ThumbnailConfigNames) ?: [];
    }

    /**
     * @param array $S3ThumbnailConfigNames
     * @return Config
     */
    public function setS3ThumbnailConfigNames($S3ThumbnailConfigNames)
    {
        $this->S3ThumbnailConfigNames = $S3ThumbnailConfigNames;

        return $this;
    }

}



