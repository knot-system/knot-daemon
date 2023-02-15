<?php

class Folder {
	
	public $folder_path;
	public $subfolders = [];

	function __construct( $folder_path ){

		if( ! is_dir( $folder_path) ) return false;

		$folder_path = trailing_slash_it($folder_path);

		$this->folder_path = $folder_path;

	}


	function load_subfolders() {

		$handle = opendir( $this->folder_path );

		$subfolders = [];

		while( ($entry = readdir($handle)) !== false ) {

			if( str_starts_with( $entry, '.' ) ) continue;

			if( ! is_dir($this->folder_path.$entry) ) continue;

			$name_exp = explode( '_', $entry );

			if( count($name_exp) > 1 ) {
				$order = (int) array_shift($name_exp);
				$name = implode( '_', $name_exp );
			} else {
				$order = 0;
				$name = $entry;
			}

			$order_pad = str_pad( $order, 8, '0', STR_PAD_LEFT );

			$subfolders[$order_pad.'-'.$name] = [
				'order' => $order,
				'name' => $name,
				'path' => trailing_slash_it($this->folder_path.$entry)
			];

		}

		ksort($subfolders);

		$this->subfolders = $subfolders;

		return $this;
	}


	function get_subfolders() {
		return $this->subfolders;
	}

}
