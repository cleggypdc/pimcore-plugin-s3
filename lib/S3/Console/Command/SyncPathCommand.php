<?php

/**
 * SyncPathCommand
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md
 * file distributed with this source code.
 *
 * @copyright  Copyright (c) 2014-2016 Gather Digital Ltd (https://www.gatherdigital.co.uk)
 * @license    https://www.gatherdigital.co.uk/license     GNU General Public License version 3 (GPLv3)
 */

namespace S3\Console\Command;

use S3\Model\S3Asset;
use Pimcore\Model\Asset;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncPathCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('S3:sync:path')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                "Path of asset to sync"
            )
            ->setDescription('S3 Plugin Sync Asset Path');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');
        $asset = Asset::getByPath($path);

        if (!$asset) {
            $this->writeError('Asset path does not exist');
            return 0;
        }

        try {

            \Zend_Registry::set('Plugin_S3_Console', $output);

            if ($service = S3Asset\Service::getForAsset($asset)) {
                if ($service->getConfig()->isS3Enabled()) {
                    $output->writeln('Starting Sync for path...');
                    $service->setForceUpdates(true);
                    $S3Asset = $service->getS3Asset();
                    $S3Asset->setFilename($asset->getFilename());
                    $S3Asset->setPath($asset->getPath());
                    $service->getS3Asset()->save();
                    $output->writeln('Finished ...');
                } else {
                    $this->writeError("Syncing has been disabled for path [{$path}]");
                    return 0;
                }
            } else {
                $this->writeError("Cannot sync path [{$path}], check credentials and bucket exists");
                return 0;
            }
        } catch (\Exception $e) {
            $this->writeError($e->getMessage());
        }

    }
}