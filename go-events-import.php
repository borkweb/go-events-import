<?php
// increase the memory limit
ini_set( 'memory_limit', '512M' );

define( 'WP_ADMIN', true );

$options = array(
	'wp::',
	'csv:',
	'event:',
	'blog:',
	'type:',
);

$args = getopt( '', $options );

$_SERVER['HTTP_HOST'] = isset( $args['blog'] ) ?: 'ev.wp.local.gostage.it';

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

if ( 'sponsor' == $args['type'] )
{
	go_events_import_sponsors()->type = $args['type'];
	go_events_import_sponsors()->load_event( $args['event'] );
	go_events_import_sponsors()->file = $args['csv'];
	go_events_import_sponsors()->process();
}//end if
elseif ( 'speaker' == $args['type'] )
{
	go_events_import_speakers()->type = $args['type'];
	go_events_import_speakers()->load_event( $args['event'] );
	go_events_import_speakers()->file = $args['csv'];
	go_events_import_speakers()->process();
}//end if
elseif ( 'session' == $args['type'] )
{
	go_events_import_sessions()->type = $args['type'];
	go_events_import_sessions()->load_event( $args['event'] );
	go_events_import_sessions()->file = $args['csv'];
	go_events_import_sessions()->process();
}//end if
