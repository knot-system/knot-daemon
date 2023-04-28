<?php

// NOTE: you can overwrite these options via the config.php in the root folder

return [
	'debug' => false, // show additional information when an error occurs
	'logging' => true, // write logfiles into the /log directory
	'cache_lifetime' => 60*60*24*30, // 30 days, in seconds
	'auth_cache_lifetime' => 60*15, // 15 minutes, in seconds
	'item_limit_count' => 20, // how many items to return per page; should not be too high
	'force_refresh_posts' => false, // set this to true if you want to refresh all post content when refreshing feeds, or set to false if you want to skip all existing posts, even if they changed
	'refresh_on_connect' => true, // this will refresh all items of all feeds of a channel, if you call the 'timeline' endpoint to get the feeds. set to false if you use a cronjob, to make the system faster
	'allowed_urls' => [], // an array with urls of allowed users ('me' parameters)
	'user_agent' => 'maxhaesslein/postamt/', // version will be automatically appended
];
