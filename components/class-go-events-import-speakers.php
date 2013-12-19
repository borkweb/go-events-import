<?php

class GO_Events_Import_Speakers extends GO_Events_Import_Abstract
{
	public $slug = 'event-speakers';
	public $title = 'Event Speakers';
	public $description = 'Import speakers into an existing event';

	protected function parse_line( $line )
	{
		$item['name'] = $this->get_line_data( $line, 'Name' );
		$item['gender'] = $this->get_line_data( $line, 'Gender' );
		$item['first_name'] = $this->get_line_data( $line, 'First Name' );
		$item['last_name'] = $this->get_line_data( $line, 'Last Name' );
		$item['title'] = $this->get_line_data( $line, 'Title' );
		$item['company'] = $this->get_line_data( $line, 'Company' );
		$item['menu_order'] = $this->get_line_data( $line, 'Order (front page)' );
		$item['bio'] = $this->get_line_data( $line, 'Biography' );
		$item['featured'] = !! $this->get_line_data( $line, 'Featured' );
		$item['excerpt'] = $this->get_line_data( $line, 'Short Desc' );
		$item['twitter'] = $this->get_line_data( $line, 'P Twitter' );
		$item['facebook'] = $this->get_line_data( $line, 'P Facebook' );
		$item['linkedin'] = $this->get_line_data( $line, 'P Linkedin' );

		$item['full_name'] = "{$item['first_name']} {$item['last_name']}";
		$item['sort_name'] = "{$item['last_name']}, {$item['first_name']}";
		$item['slug'] = sanitize_title_with_dashes( $item['full_name'] );

		return $item;
	}//end parse_line

	protected function insert_data( $data )
	{
		foreach ( $data as $speaker )
		{
			$post = get_posts( array(
				'name' => $speaker['slug'],
				'post_type' => go_events()->event()->speaker()->post_type_name,
				'post_status' => array( 'draft', 'publish', 'future' ),
				'post_parent' => $this->event->ID,
			) );

			if ( $post )
			{
				continue;
			}//end if

			$post = array(
				'post_title' => $speaker['full_name'],
				'post_name' => $speaker['slug'],
				'post_parent' => $this->event->ID,
				'post_status' => 'publish',
				'post_content' => $speaker['bio'],
				'post_type' => go_events()->event()->speaker()->post_type_name,
				'menu_order' => $speaker['menu_order'] ? absint( $speaker['menu_order'] ) : 1,
			);

			if ( $speaker['excerpt'] )
			{
				$post['post_excerpt'] = $speaker['excerpt'];
			}//end if

			$post_id = wp_insert_post( $post );

			if ( ! $post_id )
			{
				throw new Exception( 'Could not create speaker post for: ' . $speaker['name'] );
				continue;
			}//end if

			$meta = array(
				'company_name' => $speaker['company'],
				'facebook' => $speaker['facebook'],
				'is-featured' => $speaker['featured'],
				'gender' => 'm' == $speaker['gender'] ? 'Male' : 'Female',
				'linkedin' => $speaker['linkedin'],
				'sort_name' => $speaker['sort_name'],
				'title' => $speaker['title'],
				'twitter' => $speaker['twitter'],
			);

			go_events()->event()->speaker()->admin()->update_meta( $post_id, $meta, FALSE );
		}//end foreach
	}//end insert_data
}//end class

function go_events_import_speakers()
{
	global $go_events_import_speakers;

	if ( ! $go_events_import_speakers )
	{
		$go_events_import_speakers = new GO_Events_Import_Speakers;
	}//end if

	return $go_events_import_speakers;
}//end go_events_impor_speakerst
