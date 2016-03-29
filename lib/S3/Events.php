<?php

/**
 * Events
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md
 * file distributed with this source code.
 *
 * @copyright  Copyright (c) 2014-2016 Gather Digital Ltd (https://www.gatherdigital.co.uk)
 * @license    https://www.gatherdigital.co.uk/license     GNU General Public License version 3 (GPLv3)
 */

namespace S3;

use S3\Model\S3Asset;
use Pimcore\Log\Simple as SimpleLog;

class Events
{


    public static function assetPostAdd($e)
    {
        $asset = $e->getTarget();

        try {
            if ($service = S3Asset\Service::getForAsset($asset)) {
                if ($service->getConfig()->isS3Enabled()) {
                    $service->getS3Asset()->save();
                }
            }
        } catch (\Exception $e) {

            if (PIMCORE_DEBUG) {
                throw new \Exception($e->getMessage());
            }

            SimpleLog::log('plugin_s3', 'assetPostAdd: ' . $e->getMessage());
        }

    }

    public static function assetPostUpdate($e)
    {
        /**
         * @var \Pimcore\Model\Asset $asset
         */
        $asset = $e->getTarget();


        try {
            if ($service = S3Asset\Service::getForAsset($asset)) {
                if ($service->getConfig()->isS3Enabled()) {
                    $S3Asset = $service->getS3Asset();
                    $S3Asset->setFilename($asset->getFilename());
                    $S3Asset->setPath($asset->getPath());
                    $service->getS3Asset()->save();
                }
            }
        } catch (\Exception $e) {

            if (PIMCORE_DEBUG) {
                throw new \Exception($e->getMessage());
            }

            SimpleLog::log('plugin_s3', 'assetPostUpdate: ' .  $e->getMessage());
        }

    }

    public static function assetPreDelete($e)
    {
        $asset = $e->getTarget();

        try {
            if ($service = S3Asset\Service::getForAsset($asset)) {
                if ($service->getConfig()->isS3Enabled()) {
                    if ($service->getConfig()->getKeepDeletedAssets()) {
                        $service->getS3Asset()->delete(false);
                    } else {
                        $service->getS3Asset()->delete(true);
                    }
                }

            }
        } catch (\Exception $e) {

            if (PIMCORE_DEBUG) {
                throw new \Exception($e->getMessage());
            }

            SimpleLog::log('plugin_s3', 'assetPreDelete: ' .  $e->getMessage());
        }

    }


}