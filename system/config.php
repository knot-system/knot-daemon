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
	'refresh_delay' => [ // refresh feeds, where the last item was published 'x weeks' ago only every 'y hours'
		52 => 7*24, // ~1 year   =>  1 week
		26 => 2*24, // ~6 months =>  2 days
		8  => 24,   // ~2 month  =>  1 day
		4  => 12,   // ~1 month  => 12 hours
		2  => 6,    //  2 weeks  =>  6 hours
		1  => 1,    //  1 week   =>  1 hour
	],
	'refresh_delay_min_seconds' => 60, // refresh a feed at most every X seconds
	'blacklist' => [ // don't show posts with these strings:
		'title' => [
			'[sponsor]',
		],
		'content' => [
		],
	],
];
