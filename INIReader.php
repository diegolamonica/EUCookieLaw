<?php

/**
 * Created by PhpStorm.
 * User: diego
 * Date: 07/12/15
 * Time: 17.32
 */
class INIReader {
	private $_file;

	public function __construct($file) {

		$this->file = file($file);


	}

	public function getKey( $key){
		$startIndex = 0;
		$endIndex   = count($this->file);

		$value = null;
		for($rowIndex = $startIndex; $rowIndex < $endIndex; $rowIndex++ ) {
			$row = $this->file[$rowIndex];
			if ( !in_array( $row[0], array( ';', "#" ) ) ) {

				list( $_key, $_pair ) = preg_split( '#=#', $row, 2 );

				if ( trim( $_key ) == trim( $key ) ) {
					$value = trim( $_pair );
				}

				if ( ! is_null( $value ) && ! empty( $value ) ) {
					if ( $value[0] == '"' ) {
						$value = substr( $value, 1, strlen( $value ) - 2 );
						$value = str_replace( '""', '"', $value );
					}
				}
			}

		}

		return $value;
	}

}