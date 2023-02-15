<?php

// Spec: https://indieweb.org/Microsub-spec#Following

class Feeds {

	public $channel;
	public $folder;
	public $feeds = [];

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

		$subfolders = $folder->load_subfolders()->get_subfolders();

		foreach( $subfolders as $subfolder ) {

			$path = $subfolder['path'];

			$file = new File( $path.'_feed.txt' );

			$feed = $file->get();

			if( ! $feed ) continue;

			$feed['_path'] = $path;

			$feeds[$feed['_id']] = $feed;

		}

		$this->feeds = $feeds;

		return $this;
	}


	function create_id( $url ) {

		$id = str_replace( array('https://www.', 'http://www.', 'https://', 'http://'), '', $url );
		$id = strtolower(trim($id));
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
				if( $feed['url'] == $url ) {
					return true;
				}
			}

			return false;
		}

		return 'error';
	}


	function create_feed( $url ) {

		$url = trim($url);

		if( ! $url ) return false;

		$id = $this->create_id( $url );

		if( ! $id ) return false;


		if( $this->feed_exists( $id ) ) {
			// a feed with this id does already exist!
			return false;
		}


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


	function get( $id = false ) {

		$feeds = $this->feeds;

		if( $id ) {
			if( ! $this->feed_exists($id) ) return false;

			return $this->cleanup_feed( $feeds[$id] );
		}


		$feeds = array_map( function($feed){
			return $this->cleanup_feed($feed);
		}, $feeds );
		$feeds = array_values($feeds); // remove keys
		return $feeds;
	}


	function cleanup_feed( $feed ) {
		unset($feed['_id']);
		unset($feed['_path']);

		return $feed;
	}
	
}
