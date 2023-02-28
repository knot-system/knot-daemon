# Postamt

A small microsub server

This is currently in a early alpha stage. **You should not use this for now. THINGS WILL BREAK!**

Here be dragons:

## currently implemented

(everything is still work in progress and may change in the future, or not work fully yet)

- [x] channels
	- [x] get
	- [x] create
	- [x] update
	- [x] delete
	- [x] order
- [x] follow
	- [x] get
	- [x] post
- [x] unfollow
- [ ] search
- [ ] preview
- [ ] timeline
	- [x] get
	- [ ] post
		- [ ] mark_read
		- [ ] mark_unread
		- [ ] remove
- [ ] mute
	- [ ] get
	- [ ] post
- [ ] unmute
- [ ] block
	- [ ] get
	- [ ] post
- [ ] unblock
- [ ] collecting of feeds
	- [x] rss
	- [x] atom
	- [x] json
	- [ ] microformats2
	- [ ] activitypub
- [x] cronjob to collect in the background

## Requirements

A default webserver install / shared hosting service _should_ meet all requirements.

- PHP, at least version 8.0
- support for .htaccess files, with mod_rewrite
- support for SimpleXML (should be enabled by default)
- support for PHP Sessions (should be enabled by default)
- a cronjob (could also be implemented via an external service)
- write-access to the folder where this service is installed

## Initial Setup

Your server needs to run at least PHP 8.0 or later.

Copy all the files into a directory on your webserver, then open the url to this path in a webbrowser. Follow the installation instructions.

This will create a `config.php` in the root folder that will hold the configuration of your system, as well as a `content/` folder where all the feeds you follow live. These two items, the `config.php` and `content/` folder, are unique to your website and very important - keep a backup around, if you want to make sure to not lose anything.

The setup also creates some other files that are needed, like a (hidden) `.htaccess` file and a `cache/` folder. When you delete those item, they will be re-created as needed. They will also be automatically deleted and recreated when you make a system update.

You can now add the url as a microsub endpoint to your website, and then use a microsub client to login. To add the microsub endpoint, add something like this to your HTML \<head\>:

```html
<link rel="microsub" href="https://www.example.com/postamt/">
```

### Cronjob

By default, the `refresh_on_connect` option is set to true, so whenever you get a list of posts, the system refreshes all feeds to get new items. This takes some time (especcially if you follow a lot of feeds) and makes the system slow, so a better option is to set up a cronjob to handle feed refreshing in the background.

The best solution is to add a cronjob directly on the server, but you could also use an external service if this is not possible.

After installing, open the `config.php` in the root folder. There you find a line `'cron_secret' => '…'` with a random string. Copy this random string, this is the secret that makes sure that we are allowed to call the cronjob. You should also set the `refresh_on_connect` option to false, to disable feed refreshing when listing posts and make the system faster.

Add a cronjob, and point it to the `cron.php` in the root directory. Append the secret string as a parameter: `cron.php?secret=…`. This could be an example configuration:

```
$ crontab -e

# postamt cronjob, every hour at minute 37:
37 */1 * * * curl -s 'https://www.example.com/postamt/cron.php?secret=…'
```

If you use an external service, point it to the `cron.php` in your base url, and append the secret string: `https://www.example.com/postamt/cron.php?secret=…`

The recommended frequency is 1 hour. You should not use a frequency lower than 5 minutes to not overwhelm your own server or the servers of people you follow.

## Additional Options

You may want to edit the `config.php` a bit after the initial setup and add additional settings:

```php
<?php

return [
	'debug' => true, // should be true while in alpha
	'logging' => true, // should be true while in alpha; writes logfiles into the /log directory
	'cron_secret' => '[a random string]',
	'refresh_on_connect' => true, // this will refresh all items of all feeds of a channel, if you call the 'timeline' endpoint to get the feeds. set to false if you use a cronjob, to make the system faster
	'allowed_urls' => [ // a list of 'me' URLs that are allowed to use this microsub server. every user has their own folder with their own channels and feeds
		'https://www.example.com/eigenheim/',
		'https://www.example.com/other-identity/',
	],
	
	// for more config options, see the file system/config.php
	
];

```

This section of the README will be expanded later, when we reach a stable state.

The loading order of the config is as follows:
1) `system/config.php`
   gets overwritten by:
2) `config.php` (in the root folder)

## Updating

**Important:** Before updating, backup your `config.php` and the `content/` folder. Better be safe than sorry.

Create a new empty file called `update` (or `update.txt`) in the root folder of your installation. Then open the website, and append `?update` to the URL to trigger the update process. **Important:** if you don't finish the update, manually delete the `update` (or `update.txt`) file (if the update process finishes, this file gets deleted automatically).

Follow the steps of the updater. It will show you all of the new release notes that happened since your currently installed version - read them carefully, especially at the moment, because some things will change and may need manual intervention.

After updating, check if everything works as expected.

### manual update

If you want to perform a manual update, delete the `system/` and `cache/` folders, as well as the `index.php`, `.htaccess`, `README.md` and `changelog.txt` files from the root folder. Then download the latest (or an older) release from the releases page. Upload the `system/` folder and the `index.php`, `README.md` and `changelog.txt` file from the downloaded release zip-file into your web directory. Then open the url in a webbrowser; this will trigger the setup process again and create some missing files.

### system reset

If you want to reset the whole system, delete the following files and folders and open the url in a webbrowser to re-trigger the setup process:
- `.htaccess`
- `config.php`
- the `cache/` folder
- the `content/` folder

## Backup

You should routinely backup your content. To do so, copy these files and folders to a secure location:

- the `config.php`. This contains the settings for this server
- the `content/` folder. This contains the user(s) with their channels and feeds

When you want to restore a backup, delete the current folders & files from your webserver, and upload your backup. You should also delete the `cache/` folder, so everything gets re-cached and is up to date. If you also want to update or reset your system, see the *Update* section above.
