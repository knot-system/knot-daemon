<?php


function streamline_date( $input ) {

	$date = strtotime($input);

	if( $date === false ) {
		global $core;
		$core->debug( 'could not convert date', $input );
	}

	$return = date('c', $date);

	return $return;
}



function refresh_feed_items( $active_feeds ){

	if( empty($active_feeds) ) return false;

	// TODO: build some type of queue, so we don't loop over _all_ active feeds every time, but split it up into multiple requests to cron

	foreach( $active_feeds as $active_feed ) {
		$active_feed->refresh_posts();
		$active_feed->cleanup_posts();
	}

	return true;
}
