<?php

class GO_Events_Import_Sessions extends GO_Events_Import_Abstract
{
	public $slug = 'event-sessions';
	public $title = 'Event Sessions';
	public $description = 'Import sessions into an existing event';
	public $current_date = NULL;
	public $session = array();

	public function __construct()
	{
		add_filter( 'go_events_import_current_key', array( $this, 'go_events_import_current_key' ), 10, 2 );
	}//end __construct

	public function go_events_import_current_key( $key, $line )
	{
		// if we are on a date line, we're beginning a new set of records
		if ( $date = $this->get_line_data( $line, 'Date' ) )
		{
			$date = explode( '/', $date );
			$this->current_date = $date[2] . '-' . str_pad( $date[0], 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $date[1], 2, '0', STR_PAD_LEFT );

			return FALSE;
		}//end if

		$title = $this->get_line_data( $line, 'Title' );

		if ( ! ( $time = $this->get_line_data( $line, 'Time' ) ) && $title )
		{
			if ( preg_match( '/^([0-9]{1,2}:[0-9]{2} ?[AP]M) *- *(.*)/i', $title, $matches ) )
			{
				$this->current_time = $matches[1];
				$this->current_title = $matches[2];
				$this->sub_session = TRUE;
			}//end if
			else
			{
				throw new Exception( 'Not a standard session format' );
			}//end else
		}//end if
		elseif ( $time && $title )
		{
			$this->current_time = $time;
			$this->current_title = $title;
			$this->sub_session = FALSE;
		}//end elseif
		elseif ( $title )
		{
			$this->current_title = $title;
			$this->sub_session = FALSE;
		}//end elseif

		$slug = sanitize_title_with_dashes( $this->current_title );
		$slug = substr( $slug, 0, 15 );

		return "{$this->current_date} {$this->current_time} {$slug}";
	}//end go_events_import_current_key

	protected function parse_line( $line )
	{
		if ( ! $this->get_line_data( $line, 'Date' ) || ! $this->get_line_data( $line, 'Title' ) )
		{
			$item = $this->data[ $this->current_key ];
		}//end if
		else
		{
			$item = array();
		}//end else

		$title = $this->get_line_data( $line, 'Title' );

		if ( $title )
		{
			if ( preg_match( '/^([0-9]{1,2}:[0-9]{2} ?[AP]M) - (.*)/i', $title, $matches ) )
			{
				$time = $matches[1];
				$title = $matches[2];
			}//end if

			$item['title'] = $title;
			$item['subtitle'] = $this->get_line_data( $line, 'Subtitle' );
			$item['description'] = $this->get_line_data( $line, 'Description' );
			$item['slug'] = sanitize_title_with_dashes( $item['title'] );

			if ( preg_match( '/^(.+) Sponsor Workshop *- *(.*)/i', $title, $matches ) )
			{
				$item['sponsors'] = array(
					$matches[1],
				);

				$item['location'] = $matches[2];
			}//end if
		}//end if

		$speaker = $this->get_line_data( $line, 'Speaker' );

		if ( $speaker )
		{
			if ( ! isset( $item['speakers'] ) )
			{
				$item['speakers'] = array();
			}//end if

			$item['speakers'][] = $speaker;
		}//end if

		$panelist = $this->get_line_data( $line, 'Panelist' );

		if ( $panelist )
		{
			if ( ! isset( $item['panelists'] ) )
			{
				$item['panelists'] = array();
			}//end if

			$item['panelists'][] = $panelist;
		}//end if

		$item['sub-session'] = $this->sub_session;

		return $item;
	}//end parse_line

	protected function insert_data( $data )
	{
		$speaker_type_term = get_term_by( 'slug', 'moderator', go_events()->event()->speaker()->post_type_name . '-attribute' );
		$panelist_type_term = get_term_by( 'slug', 'speaker', go_events()->event()->speaker()->post_type_name . '-attribute' );
		$keys = array_keys( $data );

		foreach ( $data as $key => $session )
		{
			preg_match( '/^.+ [AP]M/', $key, $matches );
			$datetime = new DateTime( $matches[0] );

			$key_index = array_search( $key, $keys );

			$length = NULL;

			for ( $i = $key_index + 1; $i < count( $keys ); $i++ )
			{
				if ( ! $data[ $keys[ $i ] ]['sub_session'] )
				{
					preg_match( '/^.+ [AP]M/', $keys[ $i ], $matches );
					$next_datetime = new DateTime( $matches[0] );

					$length = $next_datetime->getTimestamp() - $datetime->getTimestamp();

					if ( $length > 7200 )
					{
						$length = NULL;
					}//end if

					break;
				}//end if
			}//end for

			if ( ! $length )
			{
				$length = 3600;
			}//end if

			$end_date = $datetime->getTimestamp();
			$end_date += $length;

			$post = get_posts( array(
				'name' => $session['slug'],
				'post_type' => go_events()->event()->session()->post_type_name,
				'post_status' => array( 'draft', 'publish', 'future' ),
				'post_parent' => $this->event->ID,
				'post_date' => $datetime->format( 'Y-m-d H:i:s' ),
			) );

			if ( $post )
			{
				continue;
			}//end if

			$post = array(
				'post_title' => $session['title'],
				'post_parent' => $this->event->ID,
				'post_status' => 'publish',
				'post_content' => $session['description'],
				'post_type' => go_events()->event()->session()->post_type_name,
			);

			if ( $session['subtitle'] )
			{
				$post['post_content'] = $session['subtitle'] . "\n\n" . $post['post_content'];
			}//end if

			$post_id = wp_insert_post( $post );

			if ( ! $post_id )
			{
				throw new Exception( 'Could not create speaker post for: ' . $session['title'] );
				continue;
			}//end if

			$meta = array(
				'session_start' => $datetime->format( 'Y-m-d H:i:s' ),
				'session_end' => date( 'Y-m-d H:i:s', $end_date ),
			);

			if ( isset( $session['location'] ) )
			{
				$meta['session_location'] = $session['location'];
			}//end if

			if ( $session['sponsors'] )
			{
				foreach ( $session['sponsors'] as $name )
				{
					$sponsor = get_posts( array(
						'name' => sanitize_title_with_dashes( $name ),
						'post_status' => array( 'publish', 'future' ),
						'post_type' => go_events()->event()->sponsor()->post_type_name,
						'post_parent' => $this->event->ID,
					) );

					if ( ! $sponsor[0] )
					{
						fwrite( STDOUT, 'Could not find a sponsor with the name: ' . $name . ' to attach to ' . $session['title'] );
						continue;
					}//end if

					$sponsor = array(
						'id' => $sponsor[0]->ID,
					);

					$meta['sponsors'][] = $sponsor;
				}//end foreach
			}//end if

			if ( $session['speakers'] )
			{
				foreach ( $session['speakers'] as $speaker_name )
				{
					$speaker = get_posts( array(
						'name' => sanitize_title_with_dashes( $speaker_name ),
						'post_status' => array( 'publish', 'future' ),
						'post_type' => go_events()->event()->speaker()->post_type_name,
						'post_parent' => $this->event->ID,
					) );

					if ( ! $speaker[0] )
					{
						fwrite( STDOUT, 'Could not find a speaker with the name: ' . $speaker_name . ' to attach to ' . $session['title'] );
						continue;
					}//end if

					$term_var = 'speaker_type_term';

					$speaker = $speaker[0];
					$speaker = array(
						'id' => $speaker->ID,
						'attribute' => $$term_var->term_id,
					);

					$meta['speakers'][] = $speaker;
				}//end foreach
			}//end if

			if ( $session['panelists'] )
			{
				foreach ( $session['panelists'] as $panelist_name )
				{
					$panelist = get_posts( array(
						'name' => sanitize_title_with_dashes( $panelist_name ),
						'post_status' => array( 'publish', 'future' ),
						'post_type' => go_events()->event()->speaker()->post_type_name,
						'post_parent' => $this->event->ID,
					) );

					if ( ! $panelist[0] )
					{
						fwrite( STDOUT, 'Could not find a panelist with the name: ' . $panelist_name . ' to attach to ' . $session['title'] );
						continue;
					}//end if

					$term_var = 'panelist_type_term';

					$panelist = $panelist[0];
					$panelist = array(
						'id' => $panelist->ID,
						'attribute' => $$term_var->term_id,
					);

					$meta['speakers'][] = $panelist;
				}//end foreach
			}//end if

			go_events()->event()->session()->admin()->update_meta( $post_id, $meta, FALSE );
		}//end foreach
	}//end insert_data
}//end class

function go_events_import_sessions()
{
	global $go_events_import_sessions;

	if ( ! $go_events_import_sessions )
	{
		$go_events_import_sessions = new GO_Events_Import_Sessions;
	}//end if

	return $go_events_import_sessions;
}//end go_events_impor_sessions
