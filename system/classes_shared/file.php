<?php

// update: 2023-04-12


class File {
	
	public $filepath;
	public $content;

	function __construct( $filepath ) {

		$this->filepath = $filepath;

	}

	function exists() {
		return file_exists( $this->filepath);
	}

	function create( $content ) {
		// we assume that $content is a array with key => value pairs

		if( ! is_array($content) || ! count($content) ) {
			return false;
		}


		$fields = [];
		foreach( $content as $key => $value ) {
			$fields[] = $key.': '.$value;
		}

		$file_content = implode( "\r\n\r\n----\r\n\r\n", $fields );

		$write_return = file_put_contents( $this->filepath, $file_content );

		if( ! $write_return ) return false;

		$this->content = $content;

		return true;
	}

	function get() {

		if( $this->content ) return $this->content;

		$file_content = file_get_contents( $this->filepath );

		$file_content = str_replace("\r\n", "\n", $file_content); // convert windows line endings to unix line endings
		$file_content = str_replace("\r", "\n", $file_content); // convert (very old) mac line endings to unix line endings

		$file_content = explode( "\n\n----\n\n", $file_content );

		if( ! is_array($file_content) || ! count($file_content) ) {
			return false;
		}

		$content = [];
		foreach( $file_content as $field ) {
			$pos = strpos( $field, ':' );

			if( $pos === false ) continue;

			$field_name = substr( $field, 0, $pos );
			$field_content = substr( $field, $pos+1 );

			$field_name = trim($field_name);
			$field_content = trim($field_content);

			$content[$field_name] = $field_content;
		}

		$this->content = $content;

		return $this->content;
	}

}
