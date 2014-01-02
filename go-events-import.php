<?php
// increase the memory limit
ini_set( 'memory_limit', '512M' );

define( 'WP_ADMIN', true );

$options = array(
	'wp::',
	'event:',
	'blog:',
	'type:',
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

if ( 'sponsor' == $args['type'] || 'sponsors' == $args['type'] )
{
	go_events_import_sponsors()->type = $args['type'];
	go_events_import_sponsors()->load_event( $args['event'] );
	go_events_import_sponsors()->file = $args['event'] . '-sponsors.csv';
	go_events_import_sponsors()->process();
}//end if
elseif ( 'speaker' == $args['type'] || 'speakers' == $args['type'] )
{
	go_events_import_speakers()->type = $args['type'];
	go_events_import_speakers()->load_event( $args['event'] );
	go_events_import_speakers()->file = $args['event'] . '-speakers.csv';
	go_events_import_speakers()->process();
}//end if
elseif ( 'session' == $args['type'] || 'schedule' == $args['type'] )
{
	go_events_import_sessions()->type = $args['type'];
	go_events_import_sessions()->load_event( $args['event'] );
	go_events_import_sessions()->file = $args['event'] . '-schedule.csv';
	go_events_import_sessions()->process();
}//end if
