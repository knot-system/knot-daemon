<?php


// Spec: https://indieweb.org/Microsub-spec#Following

class Feeds {

	public $channel;
	public $folder;
	public $source_id; // filter by a specific feed, only show posts in this source_id
	public $feeds = [];
	private $items;

	function __construct( $channel, $source_id = false ) {

		$this->channel = $channel;

		$folder = $channel['_path'];

		if( ! is_dir($folder) ) return false; // this should not happen (if this folder is missing, we create the folder in the Channels class and abort if this fails, but better make a sanity check here .. )

		$this->folder = $folder;

		if( $source_id ) $this->source_id = $source_id;

		$this->refresh_feeds();

	}


	function refresh_feeds() {
		$feeds = [];

		$folder = new Folder( $this->folder );

		$subfolders = $folder->get_subfolders();

		if( $this->source_id ) {
			// only show one source

			if( array_key_exists($this->source_id, $subfolders) ) {
				$subfolders = [ $subfolders[$this->source_id] ];
			} else {
				$subfolders = [];
			}

		}
		
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
			// not 200 OK

			return false;
		}


		$feed_preview = new FeedPreview( $url );

		if( ! $feed_preview->is_valid_feed() ) {
			// invalid feed

			// TODO: return a meaningful error message
			return false;
		}

		$feed = $feed_preview->get_info();


		global $core;

		$folder_path = $this->folder.$id;

		if( mkdir( $folder_path, 0774, true ) === false ) {
			$core->error( 'internal_server_error', 'could not create feed (folder error)', 500, null, $id, $url, $folder_path, $status_code );
		}

		$file = new File( $folder_path.'/_feed.txt' );

		$feed['_id'] = $id;

		if( $original_url ) {
			$feed['_original_url'] = $original_url;
		}
		if( $redirect_url ) {
			$feed['_redirect_url'] = $redirect_url;
		}

		$feed['_date_subscribed'] = date( 'c', time() );

		if( ! $file->exists() ) {

			if( ! $file->create($feed) ) {
				$core->error( 'internal_server_error', 'could not create feed (file write error)', 500, null, $folder_path, $feed, $file );
			}

		}

		$content = $file->get();
		if( $content['_id'] != $id || $content['url'] != $url ) {
			$core->error( 'internal_server_error', 'could not create feed (file retreive error)', 500, null, $folder_path, $content, $id, $url, $feed );
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
			global $core;
			$limit = get_config( 'item_limit_count' );
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
					$core->error( 'internal_server_error', 'could not retreive feed, limit is below 0', 500, null, $limit, $before_position, $before, $last_item_id, count($items) );
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
			$posts = $feed->get_posts();
			if( is_array($posts) && count($posts) ) {
				$items = array_merge( $items, $posts );
			}
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
