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
