<?php

// Spec: https://indieweb.org/Microsub-spec#Following

class Feeds {

	public $channel;
	public $folder;
	public $feeds = [];
	private $items;

	function __construct( $channel ) {

		$this->channel = $channel;

		$folder = $channel['_path'];

		if( ! is_dir($folder) ) return false; // this should not happen (if this folder is missing, we create the folder in the Channels class and abort if this fails, but better make a sanity check here .. )

		$this->folder = $folder;

		$this->refresh_feeds();

	}


	function refresh_feeds() {
		$feeds = [];

		$folder = new Folder( $this->folder );

		$subfolders = $folder->get_subfolders();

		foreach( $subfolders as $subfolder ) {

			$path = $subfolder['path'];

			$feed = new Feed( $path );

			if( ! $feed ) continue;

			$feeds[$feed->get_id()] = $feed;

		}

		$this->feeds = $feeds;

		return $this;
	}


	function create_id( $url ) {

		$id = str_replace( array('https://www.', 'http://www.', 'https://', 'http://'), '', $url );
		$id = strtolower(trim($id));
		$id = un_trailing_slash_it($id);
		$id = sanitize_folder_name( $id );

		return $id;
	}


	function feed_exists( $id = false, $url = false ) {

		if( ! $url && ! $id ) return 'error';

		if( $id ) {

			$id = trim($id);

			if( ! $id ) return 'error';

			return array_key_exists( $id, $this->feeds );
		}

		if( $url ) {

			$url = trim($url);

			if( ! $url ) return 'error';

			foreach( $this->feeds as $feed ) {
				if( $feed->has_url($url) ) return true;
			}

			return false;
		}

		return 'error';
	}


	function create_feed( $url ) {

		$url = trim($url);

		if( ! $url ) return false;


		$request = new Request( $url );
		$request->curl_request( false, true );
		$status_code = $request->get_status_code();

		$original_url = false;
		if( $status_code == 301 || $status_code == 308 ) {
			// 301 Moved Permanently
			// 308 Permanent Redirect

			$original_url = $url;
			$headers = $request->get_headers();

			if( ! empty($headers['location']) ) {
				$url = $headers['location'];
				$status_code = 200; // TODO / FIXME: maybe make request to the new url? or call 'create_feed' again with new url, and a counter to catch endless redirects? for now, we just assume the new url is valid and returns 200
			}
		}

		$id = $this->create_id( $url );

		if( ! $id ) return false;

		if( $this->feed_exists( $id ) ) {
			// a feed with this id does already exist!
			return false;
		}

		$redirect_url = false;
		if( $status_code == 302 || $status_code == 303 || $status_code == 307 ) {
			// 302 Found
			// 303 See Other
			// 307 Temporary Redirect

			$headers = $request->get_headers();
			if( ! empty($headers['location']) ) $redirect_url = $headers['location'];

			if( $this->feed_exists( $this->create_id($redirect_url) ) ) {
				// redirected url already exists as a feed!
				return false;
			}

		} elseif( $status_code != 200 ) {
			// 200 OK

			return false;

		}

		// TODO: check, if the url is a valid feed
		// (start with RSS, add other feeds later)

		global $postamt;

		$folder_path = $this->folder.$id;

		if( mkdir( $folder_path, 0777, true ) === false ) {
			$postamt->error( 'internal_server_error', 'could not create feed (folder error)', 500 );
		}

		$file = new File( $folder_path.'/_feed.txt' );

		$feed = [
			'_id' => $id,
			'type' => 'feed',
			'url' => $url
		];

		if( $original_url ) {
			$feed['_original_url'] = $original_url;
		}
		if( $redirect_url ) {
			$feed['_redirect_url'] = $redirect_url;
		}

		if( ! $file->exists() ) {

			if( ! $file->create($feed) ) {
				$postamt->error( 'internal_server_error', 'could not create feed (file write error)', 500 );
			}

		}

		$content = $file->get();
		if( $content['_id'] != $id || $content['url'] != $url ) {
			$postamt->error( 'internal_server_error', 'could not create feed (file retreive error)', 500 );
		}

		$this->refresh_feeds();

		return $this->get( $id );
	}


	function remove_feed( $url ) {

		$url = trim($url);

		if( ! $url ) return false;

		$id = $this->create_id( $url );

		if( ! $id ) return false;

		if( ! $this->feed_exists( $id ) ) return false;

		$feed = $this->feeds[$id];

		$path = $feed->get_path();

		if( ! is_dir($path) ) return false; // just a small sanity check here ..

		$path = trailing_slash_it( $path );

		$folder = new Folder( $path );
		$all_files = $folder->get_content( true );

		// first, delete files
		foreach( $all_files as $file ) {
			if( is_dir($path.$file) ) continue;
			@unlink( $path.$file );
		}

		// then, delete folders
		foreach( $all_files as $file ) {
			if( ! is_dir($path.$file) ) continue;
			@rmdir( $path.$file );
		}

		$return = rmdir( $path );

		$this->refresh_feeds();

		return $return;
	}


	function get_items( $before = false, $after = false, $limit = false ) {

		// NOTE: we use the internal _id of items as $before or $after values

		$this->refresh_items();

		$items_sorted = $this->items;

		$items = [];
		foreach( $items_sorted as $item ) {
			$items[$item['_id']] = $item;
		}

		$last_item_id = array_key_last($items);

		if( ! $limit ) {
			global $postamt;
			$limit = $postamt->config->get( 'item_limit_count' );
		}

		if( $limit < 1 ) $limit = 1;
		if( $limit > 100 ) $limit = 100; // set a hard upper bound


		if( $before ) {

			if( ! array_key_exists($before, $items) ) {
				return [ 'before' => false, 'after' => false, 'items' => [] ]; // $before does not exist, return no items
			}

			$before_position = array_search( $before, array_keys($items) );

			$before_position -= $limit;

			if( $before_position < 0 ) {
				$limit = $limit + $before_position;
				$before_position = 0;

				if( $limit < 0 ) {
					$postamt->error( 'internal_server_error', 'could not retreive feed, limit is below 0', 500 );
					return [ 'before' => false, 'after' => false, 'items' => [] ];
				}
			}

			$items = array_slice( $items, $before_position, $limit );

		}


		if( $after ) {

			if( ! array_key_exists($after, $items) ) {
				return [ 'before' => false, 'after' => false, 'items' => [] ]; // $after does not exist, return no items
			}

			$after_position = array_search( $after, array_keys($items) );
			$after_position += 1;

			$items = array_slice( $items, $after_position, $limit );

		} 

		if( count($items) > $limit ) {
			$items = array_slice( $items, 0, $limit );
		}


		$next_before = false;
		if( count($items) ) {
			$next_before = array_key_first($items);
		}

		$next_after = array_key_last($items);
		if( $next_after == $last_item_id ) {
			$next_after = false;
		}

		return [
			'before' => $next_before,
			'after' => $next_after,
			'items' => $items
		];
	}

	
	function refresh_items() {

		if( $this->items ) return $this;

		$items = [];

		foreach( $this->feeds as $feed ) {
			$items = array_merge( $items, $feed->get_posts() );
		}

		krsort($items);

		$this->items = $items;

		return $this;
	}


	function get( $id = false ) {

		$feeds = $this->feeds;

		if( $id ) {
			if( ! $this->feed_exists($id) ) return false;

			$feed = $feeds[$id];

			return $feed;
		}

		return $feeds;
	}
	
}
