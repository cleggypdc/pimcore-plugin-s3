# pimcore-plugin-s3
An AWS S3 plugin for Pimcore Assets

# Installation

 ... via composer ...

# Basic Configuration
Upon installation this plugin will create a number of "Website Settings"
 
1. **plugin_s3_disable** - Disables the S3 Plugin Globally (true by default)
2. **plugin_s3_aws_key** - Set this to be your AWS Key
3. **plugin_s3_aws_secret** - Set this to be your AWS Secret
4. **plugin_s3_aws_region** - Set this to be your AWS REgion (default is eu-west-1)
5. **plugin_s3_aws_bucket** - Set this as the name of your S3 bucket (no need for any S3 URL)
6. **plugin_s3_keep_deleted_assets** - Check to stop assets from being deleted from S3, when deleted in Pimcore (useful for debugs)
7. EXPERIMENTAL **plugin_s3_generate_s3thumbnails** - Check to automatically generate thumbnails for any image/video assets, and save them to S3
8. EXPERIMENTAL **plugin_s3_s3thumbnail_config_names** - Provide a CSV of the thumbnail configurations to be generated for synced assets

# Advanced Configuration
It is also possible to configure folders & individual assets to sync to entireley different buckets / regions and credentials

To do this simply add any of the above basic configurations as a **property** of the asset / asset folder.

This is handy for quickly migrating assets between S3 buckets through the Pimcore admin panel.


# License
Copyright (C) 2016  Gather Digital Ltd

This software is licensed under GNU General Public License version 3 (GPLv3)

For more info see: [gatherdigital.co.uk/about/license](https://www.gatherdigital.co.uk/about/license)