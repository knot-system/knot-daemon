<?php

// Spec: https://indieweb.org/Microsub-spec#Channels

class Channels {
	
	public $channels = [];

	function __construct( $postamt ) {

		$me = $postamt->session->canonical_me;

		if( ! $me ) return false;

		$folder = $postamt->abspath.$postamt->session->me_folder;

		if( ! is_dir($folder) ) return false; // this should not happen (we create the folder in the session class, if it is missing; but better make a sanity check here ..

		$this->folder = $folder;

		$this->channels = $this->read_channels();

		if( $this->check_default_folders() ) {
			// refresh channel list
			$this->channels = $this->read_channels();
		}

	}


	function read_channels(){
		$channels = [];

		// TODO: use Database class instead of directly reading the folder here

		if( $handle = opendir($this->folder) ) {
			while( false !== ($entry = readdir($handle)) ) {
				if( str_starts_with( $entry, '.' ) ) continue;

				$file = new File( $this->folder.$entry.'/channel.txt' );

				$channel = $file->get();

				if( ! $channel ) continue;

				// $channel['sources'] = []; // TODO: return list of sources, see https://github.com/indieweb/microsub/issues/44

				// $channel['unread'] = 0; // TODO: return number of unread posts

				$channels[$entry] = $channel;
			}
		}

		return $channels;
	}


	function check_default_folders() {

		global $postamt;

		$updated = false;

		if( ! array_key_exists('notifications', $this->channels) ) {
			// the spec says we _must_ have a channel with uid 'notifications'
			$new_channel = $this->create_channel( 'Notifications', 'notifications' );
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

			return is_dir( $this->folder.$uid );
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


	function create_channel( $name, $uid = false ) {

		$folder = $this->folder;

		$name = trim($name);

		if( ! $folder ) return false;

		if( ! $uid ) $uid = sanitize_string_for_url($name);

		if( ! trim($uid) ) return false;

		$uid = trim($uid);

		if( strtolower($uid) == 'global' ) return false; // uid of global is reserved

		if( $this->channel_exists( $uid ) ) {
			// channel does already exist!
			return false;
		}

		global $postamt;

		if( mkdir( $folder.$uid, 0777, true ) === false ) {
			$postamt->error( 'internal_server_error', 'could not create channel (folder error)', 500 );
		}

		$file = new File( $folder.$uid.'/channel.txt' );

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


		return [
			'uid' => $uid,
			'name' => $name
		];

	}


	function delete_channel( $uid ) {

		if( ! $this->channel_exists( $uid ) ) {
			return false;
		}

		if( $uid == 'notifications' ) {
			return false;
		}

		// TODO: delete all files in this folder
		@unlink( $this->folder.$uid.'/channel.txt' );

		return rmdir( $this->folder.$uid );
	}


	function update_channel( $uid, $new_name ) {

		if( ! $this->channel_exists( $uid ) ) {
			return false;
		}

		if( $uid == 'notifications' ) {
			return false;
		}

		if( $name == 'global' ) {
			return false;
		}

		$file = new File( $this->folder.$uid.'/channel.txt' );

		if( ! $file->exists() ) return false;
		
		$content = $file->get();

		if( $content['name'] == $new_name ) {
			return $content;
		}

		$content['name'] = $new_name;

		if( ! $file->create($content) ) return false;

		return $content;
	}


	function get() {

		$channels = $this->channels;

		return array_values($channels); // remove keys
	}

}
