# Postamt

A small microsub server, without dependencies.

This is currently in a early alpha stage. **You should not use this for now. THINGS WILL BREAK!**

Here be dragons:

## Initial Setup

Your server needs to run at least PHP 8.0 or later.

Copy all the files into a directory on your webserver, then open the url to this path in a webbrowser. Follow the installation instructions.

This will create a `config.php` in the root folder that will hold the configuration of your system. This file is unique to your website and very important - keep a backup around, if you want to make sure to not lose anything.

The setup also creates some other files that are needed, like a (hidden) `.htaccess` file and a `cache/` folder. When you delete those item, they will be re-created as needed. They will also be automatically deleted and recreated when you make a system update.

You can now use the url as a microsub server (you need to use a microsub client to connect to it).

## Additional Options

You may want to edit the `config.php` a bit after the initial setup and add additional information:

```php
<?php

return [
	'debug' => false,
	'logging' => false,
	
	// for more config options, see the file system/config.php
	
];

```

This section of the README will be expanded later, when we reach a stable state.

The loading order of the config is as follows:
1) `system/config.php`
   gets overwritten by:
2) `config.php` (in the root folder)

## Updating

**Important:** Before updating, backup your `config.php`. Better be safe than sorry.

Create a new empty file called `update` (or `update.txt`) in the root folder of your installation. Then open the website, and append `?update` to the URL to trigger the update process. **Important:** if you don't finish the update, manually delete the `update` (or `update.txt`) file (if the update process finishes, this file gets deleted automatically).

Follow the steps of the updater. It will show you all of the new release notes that happened since your currently installed version - read them carefully, especially at the moment, because some things will change and may need manual intervention.

After updating, open your website; this will trigger the setup process again, that creates some missing files. Then check if everything works as expected.

### manual update

If you want to perform a manual update, delete the `system/` and `cache/` folders, as well as the `index.php`, `.htaccess`, `README.md` and `changelog.txt` files from the root folder. Then download the latest (or an older) release from the releases page. Upload the `system/` folder and the `index.php`, `README.md` and `changelog.txt` file from the downloaded release zip-file into your web directory. Then open the url in a webbrowser; this will trigger the setup process again and create some missing files.

### system reset

If you want to reset the whole system, delete the following files and folders and open the url in a webbrowser to re-trigger the setup process:
- `.htaccess`
- `config.php`
- the `cache/` folder

## Backup

You should routinely backup your content. To do so, copy these files to a secure location:

- the `config.php`. This contains the theme you use and other settings

When you want to restore a backup, delete the current folders & files from your webserver, and upload your backup. You should also delete the `cache/` folder, so everything gets re-cached and is up to date. If you also want to update or reset your system, see the *Update* section above.
