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

	global $core;

	$refresh_delay = $core->config->get('refresh_delay');
	ksort($refresh_delay);

	$min_seconds_delay = $core->config->get('refresh_delay_min_seconds');

	foreach( $active_feeds as $active_feed ) {

		// NOTE: we check, when the last post was written; we also check the last time,
		// this feed was refreshed. we then use $refresh_delay to find out if we want
		// to refresh this feed now, or skip it (and maybe refresh the next time)

		$now_datetime = new DateTime();

		$posts = $active_feed->get_posts();
		$latest_post = reset($posts);
		$latest_post_update = $latest_post['_updated'];

		$latest_post_update_datetime = new DateTime( $latest_post_update );
		$refresh_threshold_interval = $latest_post_update_datetime->diff($now_datetime);
		$refresh_threshold_weeks = floor($refresh_threshold_interval->format('%a')/7);

		$delay_hours = 0;
		foreach( $refresh_delay as $weeks_treshold => $hours_delay ) {
			if( $weeks_treshold > $refresh_threshold_weeks ) break;
			$delay_hours = $hours_delay;
		}

		$last_feed_refresh = $active_feed->get_info('_last_refresh');
		$last_feed_refresh_datetime = new DateTime($last_feed_refresh);
		$last_feed_refresh_interval = $last_feed_refresh_datetime->diff($now_datetime);
		$last_feed_refresh_hours = ($last_feed_refresh_interval->format('%a')*24)+$last_feed_refresh_interval->format('%h');

		if( $last_feed_refresh_hours < $delay_hours ) {
			// don't refresh yet
			continue;
		}

		// safety margin: check if refresh was more than X seconds ago
		$last_feed_refresh_seconds_ago = $now_datetime->getTimestamp() - $last_feed_refresh_datetime->getTimestamp();
		if( $last_feed_refresh_seconds_ago < $min_seconds_delay ) {
			// don't refresh, was already refreshed a few seconds ago
			continue;
		}

		$active_feed->refresh_posts();
		$active_feed->cleanup_posts();

		$active_feed->save_info( '_last_refresh', $now_datetime->format('c') );

	}

	return true;
}
