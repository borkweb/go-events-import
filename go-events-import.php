<?php
// increase the memory limit
ini_set( 'memory_limit', '512M' );

define( 'WP_ADMIN', true );

$options = array(
	'wp::',
	'event::',
	'blog::',
	'type::',
);

$args = getopt( '', $options );

$_SERVER['HTTP_HOST'] = isset( $args['blog'] ) ? $args['blog'] : 'ev.wp.local.gostage.it';

require empty( $args['wp'] ) ? dirname( dirname( dirname( __DIR__ ) ) ) . '/wp-load.php' : $args['wp'] .  '/wp-load.php';

error_reporting( E_ALL );

// include and activate the core components
require_once ABSPATH . '/wp-admin/includes/file.php';
require_once ABSPATH . '/wp-admin/includes/media.php';
require_once ABSPATH . '/wp-admin/includes/image.php';
require_once __DIR__ . '/components/class-go-events-import.php';
require_once __DIR__ . '/components/class-go-events-import-abstract.php';
require_once __DIR__ . '/components/class-go-events-import-sponsors.php';
require_once __DIR__ . '/components/class-go-events-import-speakers.php';
require_once __DIR__ . '/components/class-go-events-import-sessions.php';

fwrite( STDOUT, 'Loading manifest' );

$handle = fopen( 'csv/event-manifest.csv', 'r' );
$columns = array();
$column_indexes = array();
$column_keys = array();

if ( FALSE === $handle )
{
	fwrite( STDOUT, 'Cound not open event manifest file' );
	die;
}//end if

for ( $count = 0; $line = fgetcsv( $handle, 0, ',' ); $count++ )
{
	fwrite( STDOUT, print_r( $line, TRUE ) );
	if ( 0 == $count )
	{
		if ( ! is_array( $line ) || 0 == count( $line ) )
		{
			fwrite( STDOUT, 'Could not read manifest file columns' );
			die;
		}//end if

		$keys = array_keys( $line );
		$values = array_values( $line );

		$column_indexes = array_combine( $values, $keys );
		$column_keys = array_combine( $keys, $values );
		continue;
	}//end if

	fwrite( STDOUT, print_r( $line, TRUE ) );

	$event_name = $line[ $column_indexes['event'] ];
	$event_slug = $line[ $column_indexes['slug'] ];
	$event_start = $line[ $column_indexes['start'] ];
	$event_end = $line[ $column_indexes['end'] ];
	$tagline = $line[ $column_indexes['tagline'] ];
	$building = $line[ $column_indexes['Location'] ];
	$city = $line[ $column_indexes['City'] ];
	$timezone = $line[ $column_indexes['timezone'] ];

	if ( ! $event_start )
	{
		continue;
	}//end if

	$event_start = explode( '/', $event_start );
	$event_start = $event_start[2] . '-' . str_pad( $event_start[0], 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $event_start[1], 2, '0', STR_PAD_LEFT ) . ' 12:00:00 AM';

	$event_end = explode( '/', $event_end );
	$event_end = $event_end[2] . '-' . str_pad( $event_end[0], 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $event_end[1], 2, '0', STR_PAD_LEFT ) . ' 11:00:00 PM';

	$args = array(
		'name' => $event_slug,
		'post_status' => array( 'publish', 'future' ),
		'post_type' => 'go-events-event',
	);

	$query = new WP_Query( $args );

	fwrite( STDOUT, print_r( $query->posts, TRUE ) );

	if ( ! $query->posts )
	{
		$post = array(
			'post_title' => $event_name,
			'post_name' => $event_slug,
			'post_status' => 'publish',
			'post_type' => go_events()->event()->post_type_name,
		);

		$post_id = wp_insert_post( $post );

		if ( ! $post_id )
		{
			fwrite( STDOUT, 'Unable to create the ' . $event_name . ' event post' );
			continue;
		}//end if

		$meta = array(
			'start' => $event_start,
			'end' => $event_end,
			'tagline' => $tagline,
			'building' => $building,
			'city' => $city,
			'timezone' => $timezone,
		);

		go_events()->event()->admin()->update_meta( $post_id, $meta );

		event_import( 'sponsors', $event_slug );
		event_import( 'speakers', $event_slug );
		event_import( 'schedule', $event_slug );
		die;
	}//end if
}//end for
fclose( $handle );

function event_import( $type, $event )
{
	$function = 'go_events_import_' . ( 'schedule' == $type ? 'sessions' : $type );
	$function()->type = $type;
	$function()->load_event( $event );
	$function()->file = "{$event}-{$type}.csv";
	$function()->process();
}//end event_import
