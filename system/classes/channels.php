<?php

// Spec: https://indieweb.org/Microsub-spec#Channels

class Channels {
	
	public $folder;
	public $channels = [];

	function __construct( $postamt, $folder = false ) {

		if( ! $folder ) {

			$me = $postamt->session->canonical_me;

			if( ! $me ) return false;

			$folder = $postamt->abspath.$postamt->session->me_folder;

		}


		if( ! is_dir($folder) ) return false;

		$this->folder = $folder;

		$this->refresh_channels();

		if( $this->check_default_folders() ) {
			$this->refresh_channels();
		}

	}


	function refresh_channels(){
		$channels = [];

		$folder = new Folder( $this->folder );

		$subfolders = $folder->get_subfolders( true );

		foreach( $subfolders as $subfolder ) {

			$path = $subfolder['path'];

			$order = $subfolder['order'];

			$file = new File( $path.'_channel.txt' );

			$channel = $file->get();

			if( ! $channel ) continue;

			$channel['_path'] = $path;
			$channel['_order'] = $order;

			// TODO: see https://github.com/indieweb/microsub/issues/44
			// and https://indieweb.org/Microsub-spec#Channels_2
			//$feeds = new Feeds( $channel );
			//$items = $feeds->get();
			//$channel['sources'] = $items;

			// $channel['unread'] = 0; // TODO: return number of unread posts

			$channels[$channel['uid']] = $channel;

		}

		$this->channels = $channels;

		return $this;
	}


	function check_default_folders() {

		global $postamt;

		$updated = false;

		if( ! array_key_exists('notifications', $this->channels) ) {
			// the spec says we _must_ have a channel with uid 'notifications'; it has always order 0
			$new_channel = $this->create_channel( 'Notifications', 'notifications', 0, true );
			if( ! $new_channel ) {
				$postamt->error( 'internal_server_error', 'default notifications channel not found', 500 );
			}

			$updated = true;
		}

		if( count($this->channels) < 2 ) {
			// the spec says we _must_ have at least two channels
			$new_channel = $this->create_channel( 'Home' );
			if( ! $new_channel ) {
				$postamt->error( 'internal_server_error', 'default channel not found', 500 );
			}

			$updated = true;
		}

		return $updated;
	}


	function channel_exists( $uid = false, $name = false ) {

		if( ! $name && ! $uid ) return 'error';

		if( $uid ) {

			$uid = trim($uid);

			if( ! $uid ) return 'error';

			return array_key_exists( $uid, $this->channels );
		}

		if( $name ) {

			$name = trim($name);

			if( ! $name ) return 'error';

			foreach( $this->channels as $channel ) {
				if( $channel['name'] == $name ) {
					return true;
				}
			}

			return false;
		}

		return 'error';
	}


	function create_uid( $name ) {
		$uid = sanitize_string_for_url($name);
		$uid = trim($uid);

		return $uid;
	}


	function create_channel( $name, $uid = false, $order = false, $skip_check = false ) {

		$name = trim($name);

		if( ! $name ) return false;

		if( $uid ) $uid = trim($uid);

		if( ! $uid ) $uid = $this->create_uid( $name );

		if( ! $uid ) return false;

		if( ! $skip_check && strtolower($uid) == 'notifications' ) {
			return false;
		}

		if( strtolower($uid) == 'global' ) { // uid 'global' is reserved
			return false;
		}

		if( $this->channel_exists( $uid ) ) {
			// channel does already exist!
			return false;
		}

		if( $order === false ) { // $order could also be 0, so explicitely check for false
			$order = count($this->channels);
		}

		global $postamt;

		$folder_path = $this->folder.$order.'_'.$uid;

		if( mkdir( $folder_path, 0777, true ) === false ) {
			$postamt->error( 'internal_server_error', 'could not create channel (folder error)', 500 );
		}

		$file = new File( $folder_path.'/_channel.txt' );

		if( ! $file->exists() ) {

			if( ! $file->create([
				'uid' => $uid,
				'name' => $name
			]) ) {
				$postamt->error( 'internal_server_error', 'could not create channel (file write error)', 500 );
			}

		}

		$content = $file->get();
		if( $content['uid'] != $uid || $content['name'] != $name ) {
			$postamt->error( 'internal_server_error', 'could not create channel (file retreive error)', 500 );
		}

		$this->refresh_channels();

		return $this->get( $uid );
	}


	function delete_channel( $uid ) {

		if( ! $this->channel_exists( $uid ) ) {
			return false;
		}

		if( strtolower($uid) == 'notifications' ) {
			return false;
		}

		$channel = $this->channels[$uid];

		$path = $channel['_path'];

		if( ! is_dir($path) ) {
			return false;
		}

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

		$this->refresh_channels();

		return $return;
	}


	function update_channel( $uid, $new_name ) {

		if( ! $this->channel_exists( $uid ) ) {
			return false;
		}

		if( strtolower($uid) == 'notifications' ) {
			return false;
		}

		if( strtolower($uid) == 'global' ) {
			return false;
		}

		$channel = $this->channels[$uid];

		$path = $channel['_path'];

		if( ! is_dir($path) ) {
			return false;
		}

		$file = new File( $path.'/_channel.txt' );

		if( ! $file->exists() ) return false;
		
		$content = $file->get();

		if( $content['name'] == $new_name ) {
			return $content;
		}

		$content['name'] = $new_name;

		$new_uid = $this->create_uid( $new_name );

		$content['uid'] = $new_uid;

		if( ! $file->create($content) ) return false;

		$new_path = $this->folder.$channel['_order'].'_'.$new_uid;

		if( ! rename( $path, $new_path ) ) return false;

		$this->refresh_channels();

		return $this->get( $new_uid );
	}


	function reorder( $reorder_channels ) {

		// see https://indieweb.org/Microsub-spec#Order_Algorithm

		$map = [];

		global $postamt;

		foreach( $reorder_channels as $reorder_channel_uid ) {
			if( ! $this->channel_exists( $reorder_channel_uid ) ) {
				$postamt->error( 'invalid_request', 'at least one channel does not exist' );
			}

			$channel = $this->get( $reorder_channel_uid, true );
			$order = $channel['_order'];

			$map[$order] = $reorder_channel_uid;

		}

		ksort($map);

		$i = 0;
		foreach( $map as $map_order => $map_uid ) {
			$map[$map_order] = $reorder_channels[$i];
			$i++;
		}

		foreach( $map as $new_order => $uid ) {

			$channel = $this->get( $uid, true );

			$path = $channel['_path'];

			$new_path = $this->folder.$new_order.'_'.$uid;

			if( ! rename( $path, $new_path ) ) return false;

		}

		$this->refresh_channels();

		return true;
	}


	function get( $uid = false, $skip_cleanup = false ) {

		$channels = $this->channels;

		if( $uid ) {

			if( ! $this->channel_exists($uid) ) return false;

			$channel = $channels[$uid];

			if( ! $skip_cleanup ) {
				$channel = $this->cleanup_channel( $channel );
			}

			return $channel;
		}

		if( ! $skip_cleanup ) {
			$channels = array_map( function($channel){
				return $this->cleanup_channel($channel);
			}, $channels );
			$channels = array_values($channels); // remove keys
		}

		return $channels;
	}


	function get_active_feeds() {

		$active_feeds = [];

		$channels = $this->get( false, true );

		if( empty($channels) ) return $active_feeds;

		foreach( $channels as $channel ) {

			$feeds_obj = new Feeds( $channel );
			$feeds = $feeds_obj->get( false, true );

			if( empty($feeds) ) continue;

			foreach( $feeds as $feed ) {
				$active_feeds[] = $feed;
			}

		}

		return $active_feeds;
	}


	function cleanup_channel( $channel ) {
		unset($channel['_order']);
		unset($channel['_path']);

		return $channel;
	}

}
