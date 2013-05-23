<?php

class CEM_Installation
{
	
	function cem_install() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				
		dbDelta( CEM_Installation::create_events_table() );
		dbDelta( CEM_Installation::create_event_fields_table() );
		dbDelta( CEM_Installation::create_fields_table() );
		dbDelta( CEM_Installation::create_options_table() );
		dbDelta( CEM_Installation::create_responses_table() );
		dbDelta( CEM_Installation::create_values_table() );
		
		add_option( "cem_version", CEM_VERSION );
	}
	
	function create_events_table()
	{
		global $wpdb;
		
		$table_name = $wpdb->prefix . "cem_events";
		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			title text NOT NULL,
			description text NOT NULL,
			confirmation text NOT NULL,
			closed text NOT NULL,
			capacity_msg text NOT NULL,
			capacity int(11) DEFAULT 0 NOT NULL,
			show_capacity tinyint(4) DEFAULT 0 NOT NULL,
			party_size int(11) NOT NULL,
			show_party_size tinyint(4) NOT NULL,
			start_date date NOT NULL,
			end_date date NOT NULL,
			start_time text NOT NULL,
			end_time text NOT NULL,
			registration_start_date date NOT NULL,
			registration_end_date date NOT NULL,
			location_name text NOT NULL,
			address_1 text NOT NULL,
			address_2 text NOT NULL,
			city text NOT NULL,
			state text NOT NULL,
			zip text NOT NULL,
			phone text NOT NULL,
			notification_emails text NOT NULL,
			UNIQUE KEY id (id)
			);";
		
		return $sql;
	}
	
	function create_event_fields_table()
	{
		global $wpdb;
		
		$table_name = $wpdb->prefix . "cem_event_fields";
		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			event_id int(11) NOT NULL,
			field_id int(11) NOT NULL,
			required tinyint(4) DEFAULT 0 NOT NULL,
			ticket_include tinyint(4) DEFAULT 0 NOT NULL,
			UNIQUE KEY id (id)
			);";
					
		return $sql;
	}
	
	function create_fields_table()
	{
		global $wpdb;
		
		$table_name = $wpdb->prefix . "cem_fields";
		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			label text NOT NULL,
			type text NOT NULL,
			UNIQUE KEY id (id)
			);";
					
		return $sql;
	}
	
	function create_options_table()
	{
		global $wpdb;
		
		$table_name = $wpdb->prefix . "cem_options";
		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			event_field_id int(11) NOT NULL,
			value text NOT NULL,
			UNIQUE KEY id (id)
			);";
					
		return $sql;
	}
	
	function create_responses_table()
	{
		global $wpdb;
		
		$table_name = $wpdb->prefix . "cem_responses";
		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			event_id int(11) NOT NULL,
			attendees int(11) NOT NULL,
			received datetime NOT NULL,
			UNIQUE KEY id (id)
			);";
					
		return $sql;
	}
	
	function create_values_table()
	{
		global $wpdb;
		
		$table_name = $wpdb->prefix . "cem_values";
		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			response_id int(11) NOT NULL,
			event_field_id int(11) NOT NULL,
			value text NOT NULL,
			UNIQUE KEY id (id)
			);";
					
		return $sql;
	}

}
?>