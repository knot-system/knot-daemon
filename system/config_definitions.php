<?php

// these options are displayed in the 'knot-control' module

return [
	'force_refresh_posts' => [
		'type' => 'bool',
		'description' => 'set this to <code>true</code> if you want to refresh all post content when refreshing feeds; or set to <code>false</code> if you want to skip all existing posts, even if they changed',
	],
	'refresh_on_connect' => [
		'type' => 'bool',
		'description' => 'this will refresh all items of all feeds of a channel, if you call the <code>timeline</code> endpoint to get the feeds. set to <code>false</code> if you use a cronjob, to make the system faster, otherwise set to <code>true</code>',
	],
];
