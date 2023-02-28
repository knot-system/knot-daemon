<?php

// NOTE: you can overwrite these options via the config.php in the root folder

return [
	'debug' => false,
	'logging' => false,
	'cache_lifetime' => 60*60*24*30, // 30 days, in seconds
	'item_limit_count' => 20, // how many items to return per page; should not be too high
	'force_refresh_posts' => false, // force refresh existing posts
	'refresh_on_connect' => true, // refresh feeds when you connect; set this to false, if you use a cronjob to make the system faster
	'allowed_urls' => [], // an array with urls of allowed users ('me' parameters)
];
