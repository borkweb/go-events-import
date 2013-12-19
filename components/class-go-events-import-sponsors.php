<?php

class GO_Events_Import_Sponsors extends GO_Events_Import_Abstract
{
	public $slug = 'event-sponsors';
	public $title = 'Event Sponsors';
	public $description = 'Import sponsors into an existing event';

	protected function parse_line( $line )
	{
		$item['name'] = $this->get_line_data( $line, 'Name' );
		$item['slug'] = $this->get_line_data( $line, 'Slug' );
		$item['type'] = $this->get_line_data( $line, 'Type' );
		$item['url'] = $this->get_line_data( $line, 'URL' );

		return $item;
	}//end parse_line

	protected function insert_data( $data )
	{
		foreach ( $data as $sponsor )
		{
			// skip reception types.  sponsors get added to sessions now
			if ( 'reception' == $sponsor['type'] )
			{
				continue;
			}//end if

			$post = get_posts( array(
				'name' => $sponsor['slug'],
				'post_type' => go_events()->event()->sponsor()->post_type_name,
				'post_status' => array( 'draft', 'publish', 'future' ),
				'post_parent' => $this->event->ID,
			) );

			if ( $post )
			{
				continue;
			}//end if

			$post = array(
				'post_title' => $sponsor['name'],
				'post_name' => $sponsor['slug'],
				'post_parent' => $this->event->ID,
				'post_status' => 'publish',
				'post_type' => go_events()->event()->sponsor()->post_type_name,
			);

			$post_id = wp_insert_post( $post );

			if ( ! $post_id )
			{
				throw new Exception( 'Could not create sponsor post for: ' . $sponsor['name'] );
				continue;
			}//end if

			if ( 'partners' == $sponsor['type'] )
			{
				$sponsor['type'] = 'partner';
			}//end if

			if ( 'primetime' == $sponsor['type'] )
			{
				$file_url = 'http://wp.gigaom.com/assets/sponsors/primetimehome/' . str_replace( '-', '_', $sponsor['slug'] ) . '.gif';
				go_events_import()->attach_image( $file_url, $post_id );
			}//end if
			else
			{
				$file_url = 'http://wp.gigaom.com/assets/sponsors/showtimehome/' . str_replace( '-', '_', $sponsor['slug'] ) . '.gif';
				go_events_import()->attach_image( $file_url, $post_id );
			}//end else

			wp_set_object_terms( $post_id, $sponsor['type'], go_events()->event()->sponsor()->level_taxonomy_name, FALSE );

			$meta = array(
				'url' => $sponsor['url'],
			);

			go_events()->event()->sponsor()->admin()->update_meta( $post_id, $meta );
		}//end foreach
	}//end insert_data
}//end class

function go_events_import_sponsors()
{
	global $go_events_import_sponsors;

	if ( ! $go_events_import_sponsors )
	{
		$go_events_import_sponsors = new GO_Events_Import_Sponsors;
	}//end if

	return $go_events_import_sponsors;
}//end go_events_impor_sponsorst
