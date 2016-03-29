<?php

/**
 * Plugin
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md
 * file distributed with this source code.
 *
 * @copyright  Copyright (c) 2014-2016 Gather Digital Ltd (https://www.gatherdigital.co.uk)
 * @license    https://www.gatherdigital.co.uk/license     GNU General Public License version 3 (GPLv3)
 */

namespace S3;

use Pimcore\API\Plugin as PluginLib;
use S3\Console\Command\SyncPathCommand;
use Pimcore\Model\WebsiteSetting;

class Plugin extends PluginLib\AbstractPlugin implements PluginLib\PluginInterface
{

    use \Pimcore\Console\ConsoleCommandPluginTrait;

    /**
     * @throws \Zend_EventManager_Exception_InvalidArgumentException
     */
    public function init()
    {
        parent::init();
        if(!$this->isInstalled()) {
            return;
        }

        $this->initConsoleCommands();

        \Pimcore::getEventManager()->attach("asset.postAdd", ["\\S3\\Events", "assetPostAdd"]);
        \Pimcore::getEventManager()->attach("asset.postUpdate", ["\\S3\\Events", "assetPostUpdate"]);
        \Pimcore::getEventManager()->attach("asset.preDelete", ["\\S3\\Events", "assetPreDelete"]);

    }


    /**
     * @return bool
     */
    public static function install()
    {
        $sql = "CREATE TABLE `plugin_s3_assets` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `assetId` int(11) unsigned NOT NULL,
          `bucketName` varchar(255) NULL,
          `path` varchar(255) NULL,
          `filename` varchar(255) NULL,
          `checksum` varchar(255) NULL,
          `creationDate` bigint(20) NOT NULL,
          `modificationDate` bigint(20) NOT NULL
        ) COMMENT='';";

        $db = \Pimcore\Db::get();
        $result = $db->query($sql);

        self::installSettings();

        return 'S3 Plugin Installed Successfully! S3 Sync is initially disabled in website settings. Add S3 credentials then enable where appropriate';
    }

    public static function uninstall()
    {
        $sql = "DROP TABLE `plugin_s3_assets`;";
        $db = \Pimcore\Db::get();
        $result = $db->query($sql);

        return 'S3 Plugin Uninstalled Successfully!';
    }

    public static function isInstalled()
    {
        $db = \Pimcore\Db::get();

        //check the table it present
        return $db->query("SHOW TABLES LIKE 'plugin_s3_assets'")->rowCount() > 0;
    }

    public function getConsoleCommands()
    {
        return [
            new SyncPathCommand()
        ];
    }

    public static function installSettings() {

        $settings = [
            [
                'name' => 'plugin_s3_disable',
                'type' => 'bool',
                'data' => true
            ],
            [
                'name' => 'plugin_s3_aws_key',
                'type' => 'text',
                'data' => ''
            ],
            [
                'name' => 'plugin_s3_aws_secret',
                'type' => 'text',
                'data' => ''
            ],
            [
                'name' => 'plugin_s3_aws_region',
                'type' => 'text',
                'data' => ''
            ],
            [
                'name' => 'plugin_s3_aws_bucket',
                'type' => 'text',
                'data' => ''
            ],
            [
                'name' => 'plugin_s3_keep_deleted_assets',
                'type' => 'bool',
                'data' => false
            ]
        ];

        foreach ($settings as $config) {

            $setting = WebsiteSetting::getByName($config['name']);
            if (!$setting) {
                $setting = new WebsiteSetting();
                $setting->setName($config['name']);
            }
            $setting->setType($config['type']);
            $setting->setData($config['data']);
            $setting->save();
        }

    }

}
