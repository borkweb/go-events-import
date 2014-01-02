<?php

abstract class GO_Events_Import_Abstract
{
	public $current_item = array();
	public $event = NULL;
	public $id = 0;
	public $file = NULL;
	public $column_indexes = array();
	public $column_keys = array();
	public $slug = '';
	public $title = '';
	public $description = '';

	abstract protected function insert_data( $data );
	abstract protected function parse_line( $line );

	/**
	 * constructor
	 */
	public function __construct()
	{
	}//end __construct

	public function load_event( $event )
	{
		$args = array(
			'name' => $event,
			'post_status' => array( 'publish', 'future' ),
			'post_type' => 'go-events-event',
		);

		$query = new WP_Query( $args );

		if ( ! $query->posts )
		{
			throw new Exception( 'Could not get event post with slug: ' . $event );
		}//end if

		$this->event = $query->posts[0];

		// set the parent id so go_events() can initialize the correct object
		$_REQUEST['parent_id'] = $this->event->ID;
	}//end load_event

	public function process()
	{
		if ( ! $this->file )
		{
			throw new Exception( "Could not find {$this->file} file to process" );

			return FALSE;
		}//end if

		preg_match( '/(.*)-([0-9]+)-([^\-]+).csv$/', $this->file, $matches );

		$this->file = 'csv/' . $matches[1] . '/' . $matches[2] . '/' . $this->file;

		if ( ! file_exists( $this->file ) )
		{
			fwrite( STDOUT, "Can't find {$this->file}" );
			throw new Exception( "Could not find {$this->file} file to process" );

			return FALSE;
		}//end elseif

		$results = $this->process_file();
	}//end process

	public function process_file()
	{
		$this->data = array();

		$handle = fopen( $this->file, 'r' );

		if ( FALSE === $handle )
		{
			throw new Exception( 'Failed to open file.' );

			return FALSE;
		}//end if

		for ( $count = 0; $line = fgetcsv( $handle, 0, ',' ); $count++ )
		{
			$item = array();

			if ( 0 == $count )
			{
				$this->parse_columns( $line );
				continue;
			}//end if

			if (
				! $this->get_line_data( $line, 'Publish?' ) &&
				! $this->get_line_data( $line, 'Publish' ) &&
				! $this->get_line_data( $line, 'Date' ) &&
				! $this->get_line_data( $line, 'Title' ) &&
				! $this->get_line_data( $line, 'Panelist' )
			)
			{
				continue;
			}//end if

			$this->current_key = apply_filters( 'go_events_import_current_key', $count, $line );

			// if the current key has been overridden to false, let's just continue
			if ( FALSE === $this->current_key )
			{
				continue;
			}//end if

			$parsed_line = $this->parse_line( $line );

			if ( ! isset( $this->data[ $this->current_key ] ) )
			{
				$item = $parsed_line;
			}//end if
			else
			{
				$item = array_merge( $this->data[ $this->current_key ], $parsed_line );
			}//end else

			$this->data[ $this->current_key ] = $item;
		}//end for

		fclose( $handle );

		$this->insert_data( $this->data );
	}//end process_file

	public function parse_columns( $line )
	{
		if ( ! is_array( $line ) || 0 == count( $line ) )
		{
			return FALSE;
		}//end if

		$keys = array_keys( $line );
		$values = array_values( $line );

		$this->column_indexes = array_combine( $values, $keys );
		$this->column_keys = array_combine( $keys, $values );
	}//end parse_columns

	public function get_line_data( $line, $key )
	{
		if ( ! isset( $this->column_indexes ) || ! is_array( $line ) || 0 == count( $line ) )
		{
			return FALSE;
		}//end if

		if ( ! isset( $this->column_indexes[ $key ] ) )
		{
			return FALSE;
		}//end if

		$index = $this->column_indexes[ $key ];

		if ( ! isset( $line[ $index ] ) || empty( $line[ $index ] ) )
		{
			return FALSE;
		}//end if

		$value = trim( $line[ $index ] );
		unset( $line[ $index ] );

		return $value;
	}//end get_line_data
}//end class
