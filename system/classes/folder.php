<?php


class Folder {
	
	public $folder_path;

	function __construct( $folder_path ){

		if( ! is_dir( $folder_path) ) return false;

		$folder_path = trailing_slash_it($folder_path);

		$this->folder_path = $folder_path;

	}


	function get_subfolders( $use_order = false ) {

		$handle = opendir( $this->folder_path );

		$subfolders = [];

		while( ($entry = readdir($handle)) !== false ) {

			if( str_starts_with( $entry, '.' ) ) continue;

			if( ! is_dir($this->folder_path.$entry) ) continue;

			$name = $entry;

			$order = false;
			if( $use_order ) {

				$name_exp = explode( '_', $entry );
				if( count($name_exp) > 1 ) {
					$order = (int) array_shift($name_exp);
					$name = implode( '_', $name_exp );
				} else {
					$order = 0;
					$name = $entry;
				}

				$order_pad = str_pad( $order, 8, '0', STR_PAD_LEFT );

			}

			$subfolder =  [
				'name' => $name,
				'path' => trailing_slash_it($this->folder_path.$entry)
			];

			$subfolder_id = $name;

			if( $order !== false ) {
				$subfolder['order'] = $order;
				$subfolder_id = $order_pad.'-'.$subfolder_id;
			}

			$subfolders[$subfolder_id] = $subfolder;

		}

		ksort($subfolders);

		return $subfolders;
	}


	function get_content( $recursive = false ) {

		$handle = opendir( $this->folder_path );

		$entries = [];

		while( ($entry = readdir($handle)) !== false ) {

			if( str_starts_with( $entry, '.' ) ) continue;

			if( is_dir( $this->folder_path.$entry ) ) $entry = trailing_slash_it($entry);

			if( $recursive && is_dir( $this->folder_path.$entry) ) {
				$subfolder = new Folder( $this->folder_path.$entry );
				$subitems = $subfolder->get_content( true );

				foreach( $subitems as $subitem ) {
					$entries[] = $entry.$subitem;
				}
			}

			$entries[] = $entry;

		}

		return $entries;
	}

}
