<?php

class GO_Events_Import
{
	protected $sessions = NULL;
	protected $speakers = NULL;
	protected $sponsors = NULL;

	public function __construct()
	{
		require_once __DIR__ . '/class-go-events-import-abstract.php';

		$this->sponsors();
	}//end __construct

	public function importer( $which )
	{
		if ( ! $this->$which )
		{
			require_once __DIR__ . '/class-go-events-import-' . $which . '.php';
			$class = 'GO_Events_Import_' . ucfirst( $which );
			$this->$which = new $class;
		}//end if

		return $this->$which;
	}//end importer

	public function sessions()
	{
		return $this->importer( 'sessions' );
	}//end sessions

	public function speakers()
	{
		return $this->importer( 'speakers' );
	}//end speakers

	public function sponsors()
	{
		return $this->importer( 'sponsors' );
	}//end sponsors

	public function pixel_increase( $filename )
	{
		$min_height = 600;
		$min_width = 800;

		$size = getimagesize( $filename );
		list( $width, $height ) = $size;

		$mime_types = array(
			'image/jpeg',
			'image/gif',
			'image/png',
		);

		if ( ! in_array( $size['mime'], $mime_types ) )
		{
			return "ERROR: bad mime type: {$size['mime']}\n";
		}// end if

		if ( $width >= $min_width && $height >= $min_height )
		{
			return "OK: dimensions already large enough: $width x $height\n";
		}// end if

		$multiplier = 2;
		if ( $width <= ( $min_width / 2 ) && $height <= ( $min_height / 2 ) )
		{
			$multiplier = 4;
		}// end if

		$new_width = $width * $multiplier;
		$new_height = $height * $multiplier;

		// load
		$doubled = imagecreatetruecolor( $new_width, $new_height );
		switch ( $size['mime'] )
		{
			case 'image/jpeg':
				$original = imagecreatefromjpeg( $filename );
				imagecopyresized( $doubled, $original, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
				imagejpeg( $doubled, $filename );
				break;
			case 'image/png':
				$original = imagecreatefrompng( $filename );
				imagecopyresized( $doubled, $original, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
				imagepng( $doubled, $filename );
				break;
			case 'image/gif':
				$original = imagecreatefromgif( $filename );
				imagecopyresized( $doubled, $original, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
				imagegif( $doubled, $filename );
				break;
		}//end switch

		return "OK - resized x{$multiplier} \n";
	}//end pixel_increase

	public function attach_image( $url, $post_id )
	{
		$file = download_url( $url );

		preg_match('/[^\?]+\.(jpe|jpe?g|gif|png)/i', $url, $matches);

		$file_array = array(
			'name' => basename( $matches[0] ),
			'tmp_name' => $file,
		);

		if ( ! is_wp_error( $file ) )
		{
			go_events_import()->pixel_increase( $file );

			$id = media_handle_sideload( $file_array, $post_id );
		}//end else

		if ( $id )
		{
			set_post_thumbnail( $post_id, $id );
			$id = NULL;
		}//end if

		@unlink( $file_array['tmp_name'] );
	}//end attach_image
}//end class

function go_events_import()
{
	global $go_events_import;

	if ( ! $go_events_import )
	{
		$go_events_import = new GO_Events_Import;
	}//end if

	return $go_events_import;
}//end go_events_import
