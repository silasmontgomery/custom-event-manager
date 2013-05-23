<?php

class Administration_Page
{
	var $html = "";
	var $action = "";

	function __construct()
	{
		if(isset($_POST['cem_a']))
		{
			$this->action = $_POST['cem_a'];
		}
		else if(isset($_REQUEST['cem_a']))
		{
			$this->action = $_REQUEST['cem_a'];
		}
		
		if($this->action == "download_csv")
			add_action('admin_init', array($this, 'download_csv'));
		
		add_action('admin_menu', array($this, 'init'));
	}

	function init()
	{
		add_menu_page('Custom Events Manager', 'Events', 'edit_pages', 'custom_events_manager', array($this, 'main'));
		add_submenu_page('custom_events_manager', 'Custom Events Manager - New Event', 'New Event', 'edit_pages', 'custom_events_manager_edit', array($this, 'edit'));
		add_submenu_page('custom_events_manager', 'Custom Events Manager - Settings', 'Settings', 'edit_pages', 'custom_events_manager_settings', array($this, 'settings'));
		wp_register_style('cemAdminStylesheet', CEM_CSS_PATH . 'admin.css');
		wp_enqueue_style('cemAdminStylesheet');
		wp_register_style('jqueryAdminCss', CEM_CSS_PATH . 'jquery-ui.min.css');
		wp_enqueue_style('jqueryAdminCss');
		wp_enqueue_style('cemAdminStylesheet');
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui');
		wp_enqueue_script('jquery-ui-datepicker');
		wp_register_script('jquery-validate', CEM_JS_PATH . 'jquery.validate.min.js');
		wp_enqueue_script('jquery-validate');
		wp_register_script('cem-admin', CEM_JS_PATH . 'admin.js');
    	wp_enqueue_script('cem-admin');
		
		$this->html = "<h2>Custom Events Manager</h2><span id='cem-page'>";
	}
		
	function main()
	{	
		switch($this->action)
		{
			case 'view':
				$this->main_view($_REQUEST['cem_id']);
				break;
			case 'delete_respondent':
				$this->main_delete_respondent();
				break;	
			default:
				$this->main_events();
				break;
		}
		
		$this->write();
	}
	
	function main_events()
	{
		global $wpdb;

		$tableName = $wpdb->prefix . "cem_events";
		$events = $wpdb->get_results("SELECT * FROM $tableName ORDER BY start_date ASC");
		
		$this->html .= "<table><tr><th>Title</th><th>Start Date</th><th>Short Code</th><th>Responses</th><th>Attendees Total</th></tr>";
		foreach($events as $one)
		{
			$tableName = $wpdb->prefix . "cem_responses";
			$responses = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tableName WHERE event_id = {$one->id}" ) );
			$attendees = "n/a";
			if($one->capacity > 0 || $one->party_size > 0)
				$attendees = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(attendees) FROM $tableName WHERE event_id = {$one->id}" ) );
			
			$this->html .= "<tr><td><a href='?page=custom_events_manager&cem_a=view&cem_id={$one->id}'>{$one->title}</a></td>
								<td>".date("m/d/Y", strtotime($one->start_date))."</td>
								<td>[cem event=\"{$one->id}\"]</td>
								<td><a href='?page=custom_events_manager&cem_a=view&cem_id={$one->id}#responses'>$responses</a></td>
								<td>{$attendees}</td></tr>";
		}
		$this->html .= "</table><p><a href='?page=custom_events_manager_edit'>Add Event</a></p>";
	}
	
	function main_view($event_id)
	{
		global $wpdb;

		$tableName = $wpdb->prefix . "cem_events";
		$event = $wpdb->get_row("SELECT * FROM $tableName WHERE id = {$event_id}");
		
		$emails = explode(",", $event->notification_emails);
		
		$capacity = "No limit";
		if ($event->capacity > 0)
			$capacity = $event->capacity;
			
		$max_party_size = "No maximum";
		if ($event->party_size > 0)
			$max_party_size = $event->party_size;
		
		$this->html .= "<table>
		<tr><th>Title</th><td>{$event->title}</td></tr>
		<tr><th>Description</th><td>".nl2br($event->description)."</td></tr>
		<tr><th>Event Capacity</th><td>{$capacity}</td></tr>
		<tr><th>Max Party Size</th><td>{$max_party_size}</td></tr>
		<tr><th>Date</th><td>Start: ".date("m/d/Y", strtotime($event->start_date))." - End: ".date("m/d/Y", strtotime($event->end_date))."</td></tr>
		<tr><th>Time</th><td>Start: {$event->start_time} - End: {$event->end_time}</td></tr>";
		
		if($event->phone != '')		
			$this->html .= "<tr><th>Phone</th><td>{$event->phone}</td></tr>";
		
		$this->html .= "
		<tr><th colspan='2'>Location Information</th></tr>
		<tr><td colspan='2'>";
		
		if($event->location_name != '')
			$this->html .= "<strong>{$event->location_name}</strong><br />";
		if($event->address_1 != '')
			$this->html .= "{$event->address_1}<br />";
		if($event->address_2 != '')
			$this->html .= "{$event->address_2}<br />";
		if($event->city != '')
			$this->html .= "{$event->city}, ";
		if($event->state != '')
			$this->html .= "{$event->state} ";
		if($event->zip != '')
			$this->html .= "{$event->zip}";
			
		$this->html .= "
		</td></tr>
		<tr><th colspan='2'>Notification Emails</th></tr>
		<tr><td colspan='2'>";
		
		foreach($emails as $one)
		{
			$this->html .= "$one<br />";
		}
		
		$this->html .= "
		</td></tr>
		</table>
		<p><a href='?page=custom_events_manager_edit&cem_id={$event->id}'>Edit Event</a></p>";
		
		$tableName = $wpdb->prefix . "cem_responses";
		$responses = $wpdb->get_results("SELECT * FROM $tableName WHERE event_id = {$event->id}");
		
		if(count($responses) > 0)
		{
			$this->html .= "<h3 id='responses'>Event Responses</h3><table>";
			
			$fields_table = $wpdb->prefix . "cem_fields";
			$event_fields_table = $wpdb->prefix . "cem_event_fields";
			$labels = $wpdb->get_results("SELECT $fields_table.label FROM $event_fields_table, $fields_table WHERE $fields_table.id=$event_fields_table.field_id AND $event_fields_table.event_id = {$event->id}");
			
			$this->html .= "<tr>";
			foreach($labels as $label)
			{
				$this->html .= "<th>{$label->label}</th>";
			}
			$this->html .= "<th>Party Size</th>";
			$this->html .= "<th>Delete Response</th>";
			$this->html .= "</tr><tr>";
			
			foreach($responses as $response)
			{
				$values_table = $wpdb->prefix . "cem_values";
				$values = $wpdb->get_results("SELECT * FROM $values_table WHERE response_id = {$response->id}");
				
				foreach($values as $value)
				{
					$this->html .= "<td>{$value->value}</td>";
				}
				
				$response_table = $wpdb->prefix . "cem_responses";
				$party_size = $wpdb->get_var("SELECT attendees FROM $response_table WHERE id = {$response->id}");
				$this->html .= "<td>{$party_size}</td>";

				$this->html .= "<td><a onclick='if(!confirm(\"Are you sure you want to delete this attendee?\")) { return false; }' 
				href='?page=custom_events_manager&cem_a=delete_respondent&cem_id={$response->id}&cem_event_id={$event_id}'>Delete</a></td>";
				$this->html .= "</tr>";
			}
			$this->html .= "</table><a href='?page=custom_events_manager&cem_a=download_csv&cem_id={$event->id}' target='_blank'>Download CSV</a>";
		}
	}
	
	function download_csv()
	{	
		$lines = array_merge($this->csv_header($_REQUEST['cem_id']), $this->csv_records($_REQUEST['cem_id']));
		
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename={$_REQUEST['cem_id']}.csv");
		header("Pragma: no-cache");
		header("Expires: 0");
		echo implode("\n", $lines);
		exit;
	}
	
	function csv_header($event_id)
	{
		global $wpdb;

		$fTable = $wpdb->prefix . "cem_fields";
		$efTable = $wpdb->prefix . "cem_event_fields";
		
		$sql = "SELECT {$fTable}.label FROM {$fTable}, {$efTable} WHERE {$fTable}.id = {$efTable}.field_id AND {$efTable}.event_id = {$event_id}";
		$fields = $wpdb->get_results($sql);
		
		foreach($fields as $field)
		{
			$labels[] = str_replace("\"", "", $field->label);
		}
		$labels[] = "Attendees";
		
		return array("\"" . implode("\",\"", $labels) . "\"");
	}
	
	function csv_records($event_id)
	{
		global $wpdb;
		
		$rTable = $wpdb->prefix . "cem_responses";
		$sql = "SELECT * FROM {$rTable} WHERE event_id = {$event_id}";
		$responses = $wpdb->get_results($sql);
		$lines = array();
		
		foreach($responses as $response)
		{
			$vTable = $wpdb->prefix . "cem_values";
			$sql = "SELECT * FROM {$vTable} WHERE response_id = {$response->id} ORDER BY id ASC";
			$rows = $wpdb->get_results($sql);
			$values = array();
			
			foreach($rows as $row)
			{
				$values[] = str_replace("\"", "", $row->value);
			}
			$values[] = $response->attendees;
			
			$lines[] = "\"" . implode("\",\"", $values) . "\"";
		}
		
		return $lines;
	}
	
	function edit()
	{
		switch($this->action)
		{
			case 'assign':
				$this->edit_assign_field();
				break;
			case 'unassign':
				$this->edit_unassign_field();
				break;
			case 'add_option':
				$this->edit_add_option();
				break;
			case 'delete_option':
				$this->edit_delete_option();
				break;
			case 'delete':
				$this->edit_delete();
				break;
			case 'save':
				if(isset($_POST['cem_id']) && $_POST['cem_id'] > 0)
				{
					$this->edit_update();
				}
				else
				{
					$this->edit_save();
				}
				break;
			default:
				$this->edit_event();
				break;
		}
		
		$this->write();
	}

	function edit_delete()
	{
		if(isset($_REQUEST['cem_id']) && $_REQUEST['cem_id'] > 0)
		{
			global $wpdb;
		
			$tableName = $wpdb->prefix . "cem_events";
			$deleted = $wpdb->query($wpdb->prepare("DELETE FROM $tableName WHERE id = %d", $_REQUEST['cem_id']));
			if($deleted == 1)
			{
				$this->html .= $this->redirect("Event deleted successully", "?page=custom_events_manager");
			}
			else
			{
				$this->html .= $this->redirect("Problem deleting event", "?page=custom_events_manager_edit&cem_id=".$_REQUEST['cem_id']);
			}
		}
		else
		{
			$this->html .= $this->redirect("Event ID required", "?page=custom_events_manager");
		}
	}

	function edit_update()
	{
		global $wpdb;
		
		$_POST = stripslashes_deep($_POST);
		
		$tableName = $wpdb->prefix . "cem_events";
		
		$update = $wpdb->query($wpdb->prepare(
		"UPDATE $tableName SET 
			title=%s,
			description=%s,
			confirmation=%s,
			closed=%s,
			capacity_msg=%s,
			capacity=%s,
			show_capacity=%s,
			party_size=%s,
			show_party_size=%s,
			start_date=%s,
			end_date=%s,
			start_time=%s,
			end_time=%s,
			registration_start_date=%s,
			registration_end_date=%s,
			phone=%s,
			location_name=%s,
			address_1=%s,
			address_2=%s,
			city=%s,
			state=%s,
			zip=%s,
			notification_emails=%s
		 WHERE id=%d", 
		array($_POST['title'], 
		$_POST['description'], 
		$_POST['confirmation'],
		$_POST['closed'], 
		$_POST['capacity_msg'],
		$_POST['capacity'],
		$_POST['show_capacity'],
		$_POST['party_size'],
		$_POST['show_party_size'],
		date("Y-m-d", strtotime($_POST['start_date'])),
		date("Y-m-d", strtotime($_POST['end_date'])),
		$_POST['start_time'],
		$_POST['end_time'],
		date("Y-m-d", strtotime($_POST['registration_start_date'])),
		date("Y-m-d", strtotime($_POST['registration_end_date'])),
		$_POST['phone'],
		$_POST['location_name'],
		$_POST['address_1'],
		$_POST['address_2'],
		$_POST['city'],
		$_POST['state'],
		$_POST['zip'],
		$_POST['notification_emails'],
		$_POST['cem_id'])
		));
		
		if($update === FALSE)
		{
			$this->html .= $this->redirect("Problem Updating Event", "?page=custom_events_manager_edit&cem_id={$_POST['cem_id']}");

		}
		else if($update === 0)
		{
			$this->html .= $this->redirect("No changes made to event", "?page=custom_events_manager");
		}
		else
		{
			$this->html .= $this->redirect("Event updated successfully", "?page=custom_events_manager");
		}
	}	

	function edit_save()
	{
		global $wpdb;
		
		$_POST = stripslashes_deep($_POST);
		
		$tableName = $wpdb->prefix . "cem_events";
		
		$insert = $wpdb->query($wpdb->prepare(
		"INSERT INTO $tableName ( 
			title,
			description,
			confirmation,
			closed,
			capacity_msg,
			capacity,
			show_capacity,
			party_size,
			show_party_size,
			start_date,
			end_date,
			start_time,
			end_time,
			registration_start_date,
			registration_end_date,
			phone,
			location_name,
			address_1,
			address_2,
			city,
			state,
			zip,
			notification_emails
		 ) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)", 
		array(
			$_POST['title'], 
			$_POST['description'],
			$_POST['confirmation'],
			$_POST['closed'],
			$_POST['capacity_msg'],
			$_POST['capacity'],
			$_POST['show_capacity'],
			$_POST['party_size'],
			$_POST['show_party_size'],
			date("Y-m-d", strtotime($_POST['start_date'])),
			date("Y-m-d", strtotime($_POST['end_date'])),
			$_POST['start_time'],
			$_POST['end_time'],
			date("Y-m-d", strtotime($_POST['registration_start_date'])),
			date("Y-m-d", strtotime($_POST['registration_end_date'])),
			$_POST['phone'],
			$_POST['location_name'],
			$_POST['address_1'],
			$_POST['address_2'],
			$_POST['city'],
			$_POST['state'],
			$_POST['zip'],
			$_POST['notification_emails']
			)
		));
		
		if($insert)
		{
			$this->html .= $this->redirect("Event saved successfully", "?page=custom_events_manager");
		}
		else
		{
			$this->html .= $this->redirect("Problem saving event", "?page=custom_events_manager_edit");
		}
	}
	
	function edit_assign_field()
	{
		global $wpdb;
		
		if($_REQUEST['cem_id'] > 0 && $_REQUEST['cem_field_id'] > 0)
		{		
			$tableName = $wpdb->prefix . "cem_event_fields";
			
			$insert = $wpdb->query($wpdb->prepare(
			"INSERT INTO $tableName ( 
				event_id,
				field_id,
				required,
				ticket_include
			 ) VALUES (%d,%d,%d,%d)", 
			array(
				$_REQUEST['cem_id'], 
				$_REQUEST['cem_field_id'],
				$_REQUEST['required'],
				$_REQUEST['ticket_include']
				)
			));
		}
		
		$this->edit_event();
	}
	
	function edit_unassign_field()
	{
		global $wpdb;
		
		if($_REQUEST['cem_id'] > 0 && $_REQUEST['cem_event_field_id'] > 0)
		{		
			$tableName = $wpdb->prefix . "cem_event_fields";
			
			$insert = $wpdb->query($wpdb->prepare("DELETE FROM $tableName WHERE id = %d", 
			array(
				$_REQUEST['cem_event_field_id']
				)
			));
		}
		
		$this->edit_event();
	}
	
	function edit_add_option()
	{
		global $wpdb;
		
		if($_POST['cem_event_field_id'] > 0)
		{		
			$tableName = $wpdb->prefix . "cem_options";
			
			$insert = $wpdb->query($wpdb->prepare(
			"INSERT INTO $tableName ( 
				event_field_id,
				value
			 ) VALUES (%d,%s)", 
			array(
				$_POST['cem_event_field_id'],
				$_POST['cem_value']
				)
			));
		}
		
		$this->edit_event();
	}
	
	function edit_delete_option()
	{
		global $wpdb;
		
		if($_REQUEST['cem_id'] > 0 && $_REQUEST['cem_option_id'] > 0)
		{		
			$tableName = $wpdb->prefix . "cem_options";
			
			$delete = $wpdb->query($wpdb->prepare("DELETE FROM $tableName WHERE id = %d", 
			array(
				$_REQUEST['cem_option_id']
				)
			));
		}
		
		$this->edit_event();
	}
	
	function edit_event()
	{
		$start_date = date("m/d/Y");
		$end_date = date("m/d/Y");
		
		if(isset($_REQUEST['cem_id']))
		{
			global $wpdb;
			
			$tableName = $wpdb->prefix . "cem_events";
			$event = $wpdb->get_row("SELECT * FROM $tableName WHERE id = {$_REQUEST['cem_id']}");
			
			$start_date = date("m/d/Y", strtotime($event->start_date));
			$end_date = date("m/d/Y", strtotime($event->end_date));
			$registration_start_date = date("m/d/Y", strtotime($event->registration_start_date));
			$registration_end_date = date("m/d/Y", strtotime($event->registration_end_date));
		}
		
		$this->html .= "<div style='float: left;'><form id='cem_edit_form' class='edit-table' method='post' action='?page=custom_events_manager_edit&cem_a=save'><table>
		<tr><th>Title *</th><td><input type='text' name='title' id='title' value='{$event->title}' class='required' /></td></tr>
		<tr><th>Description</th><td><textarea name='description' id='description'>{$event->description}</textarea></td></tr>
		<tr><th>Confirmation Message *</th><td><textarea name='confirmation' id='confirmation' class='required'>{$event->confirmation}</textarea><br />(displayed after registration)</td></tr>
		<tr><th>Registration Closed Message *</th><td><textarea name='closed' id='closed'>{$event->closed}</textarea><br />(displayed before registration start date and after registration end date)</td></tr>
		<tr><th>Capacity Limit Hit Message *</th><td><textarea name='capacity_msg' id='capacity_msg'>{$event->capacity_msg}</textarea><br /></td></tr>
		<tr><th>Event Capacity </th><td><input class='short-text' type='text' name='capacity' id='capacity' value='{$event->capacity}' /> <input type='checkbox' name='show_capacity' id='show_capacity' value='1' /> Show respondent the capacity? <br /> (Enter 0 in this field if there is no capacity limit)</td></tr>
		<tr><th>Max Party Size </th><td><input class='short-text' type='text' name='party_size' id='party_size' value='{$event->party_size}' /> <input type='checkbox' name='show_party_size' id='show_party_size' value='1' /> Show respondent the max party size? <br /> (Enter 0 in this field if there is no limit to party size)</td></tr>
		<tr><th>Event Start Date *</th><td><input class='required date short-text' type='text' name='start_date' id='start_date' value='{$start_date}' /></td></tr>
		<tr><th>Event End Date</th><td><input class='date short-text' type='text' name='end_date' id='end_date' value='{$end_date}' /></td></tr>
		<tr><th>Event Start Time *</th><td><input class='required short-text' type='text' name='start_time' id='start_time' value='{$event->start_time}' /></td></tr>
		<tr><th>Event End Time *</th><td><input class='required short-text' type='text' name='end_time' id='end_time' value='{$event->end_time}' /></td></tr>
		<tr><th>Registration Start Date *</th><td><input class='required date short-text' type='text' name='registration_start_date' id='registration_start_date' value='{$registration_start_date}' /> (first day to register)</td></tr>
		<tr><th>Registration End Date *</th><td><input class='required date short-text' type='text' name='registration_end_date' id='registration_end_date' value='{$registration_end_date}' /> (last day to register)</td></tr>
		<tr><th>Phone</th><td><input type='text' class='phone short-text' name='phone' id='phone' value='{$event->phone}' /></td></tr>
		<tr><th>Location Name *</th><td><input class='required' type='text' name='location_name' id='location_name' value='{$event->location_name}' /></td></tr>
		<tr><th>Address 1 *</th><td><input class='required' type='text' name='address_1' id='address_1' value='{$event->address_1}' /></td></tr>
		<tr><th>Address 2</th><td><input type='text' name='address_2' id='address_2' value='{$event->address_2}' /></td></tr>
		<tr><th>City *</th><td><input class='required' type='text' name='city' id='city' value='{$event->city}' /></td></tr>
		<tr><th>State *</th><td><input class='required short-text' type='text' name='state' id='state' value='{$event->state}' /></td></tr>
		<tr><th>Zip *</th><td><input class='required short-text' type='text' name='zip' id='zip' value='{$event->zip}' /></td></tr>
		<tr><th>Notification Emails</th><td><textarea name='notification_emails' id='notification_emails'>{$event->notification_emails}</textarea><br />(separate multiple by using commas)</td></tr>
		<tr><td>* Required Fields</td><td><input type='submit' value='Save Event' /><input type='hidden' id='cem_id' name='cem_id' value='{$_REQUEST['cem_id']}' /> &nbsp; <a href='?page=custom_events_manager_edit&cem_a=delete&cem_id={$event->id}' onclick='if(!confirm(\"Are you sure you want to delete this event and all related information?\")) { return false; }'>delete event</a></td></tr>
		</table></form></div>";
		if(isset($_REQUEST['cem_id']))
		{
			$fTable = $wpdb->prefix . "cem_fields";
			$efTable = $wpdb->prefix . "cem_event_fields";
			
			$fields = $wpdb->get_results("SELECT * FROM $fTable WHERE id NOT IN (SELECT field_id FROM $efTable WHERE event_id = {$_REQUEST['cem_id']}) ORDER BY label ASC");
			
			$eventFields = $wpdb->get_results("SELECT $fTable.label, $efTable.id, $fTable.type, $efTable.required, $efTable.ticket_include FROM $fTable, $efTable WHERE $efTable.event_id = {$_REQUEST['cem_id']} AND $efTable.field_id = $fTable.id ORDER BY $efTable.id ASC");
			
			$this->html .= "<div id='cem-event-fields'><table><tr><td colspan='3'><strong>Assigned Fields</strong></td></tr>";
								
			foreach($eventFields as $field)
			{
				$required = '';
				if($field->required == 1)
					$required = '<strong>*</strong>';
					
				$ticket_include = '';
				if($field->ticket_include == 1)
					$ticket_include = '<strong>^</strong>';	
				
				$this->html .= "<tr><td>{$field->label} {$required} {$ticket_include}</td><td>{$field->type}</td><td><a href='?page=custom_events_manager_edit&cem_a=unassign&cem_id={$_REQUEST['cem_id']}&cem_event_field_id={$field->id}'>remove</a></td></tr>";
				
				if($field->type == 'select' || $field->type == 'multiselect')
				{
					$tableName = $wpdb->prefix . "cem_options";
					$options = $wpdb->get_results("SELECT * FROM $tableName WHERE event_field_id = $field->id ORDER BY value ASC");
					
					foreach($options as $option)
					{
						$this->html .= "<tr><td colspan='2' class='cem-option-value'> &nbsp; &nbsp; {$option->value}</td><td><a href='?page=custom_events_manager_edit&cem_a=delete_option&cem_id={$_REQUEST['cem_id']}&cem_option_id={$option->id}'>remove</a></td></tr>";
					}
				}
				
			}
			
			if($fields != NULL)
			{
				$this->html .= "<tr><td colspan='3'><select name='event_fields' id='event_fields'>";
					
				foreach($fields as $field)
				{
					$this->html .= "<option value='{$field->id}'>{$field->label} ({$field->type})</option>";
				}
					
				$this->html .= "</select></td></tr><tr><td><input type='checkbox' name='required' id='required' value='1' /> Required</td></tr>";
				$this->html .= "<tr><td><input type='checkbox' name='ticket_include' id='ticket_include' value='1' /> Include Field On Ticket?</td></tr>
								<tr><td colspan='3'><input type='button' id='assign_field_button' value='Assign Field' /></td></tr>";
			}

			$this->html .= "<tr><td colspan='2'><strong><em>* required fields</em></strong></td></tr>";
			$this->html .= "<tr><td colspan='2'><strong><em>^ included on guest's ticket</em></strong></td></tr></table>";
			
			$fTable = $wpdb->prefix . "cem_fields";
			$efTable = $wpdb->prefix . "cem_event_fields";			
			$selectFields = $wpdb->get_results("SELECT $efTable.id, $fTable.label FROM $efTable, $fTable WHERE $efTable.field_id = $fTable.id AND $efTable.event_id = {$event->id} AND ($fTable.type = 'select' OR $fTable.type = 'multiselect')");
			
			if($selectFields != NULL)
			{
				$this->html .= "<form method='post' action='?page=custom_events_manager_edit&cem_a=add_option&cem_id={$event->id}'><p><strong>Add Field Options</strong> (<em>select</em> and <em>multiselect</em>)</p>
				<p>Field</p>
				<p>
				<select name='cem_event_field_id' id='cem_event_field_id'>";
				
				foreach($selectFields as $field)
				{
					$this->html .= "<option value='{$field->id}'>{$field->label}</option>";
				}
				
				$this->html .= "</select></p>
				<p>Value</p>
				<p><input class='short-text' type='text' name='cem_value' id='cem_value' /></p>
				<p><input type='submit' value='Add Option' /></p>
				</form>
				";
			}
			
			$this->html .= "</div>";
			
		}
	}
	
	function settings()
	{
		switch($this->action)
		{
			case 'update':
				$this->settings_update();
				break;
			case 'add_field':
				$this->settings_add_field();
				break;
			case 'delete_field':
				$this->settings_delete_field();
				break;
			default:
				$this->settings_list();
				break;
		}
		
		$this->write();
	}
	
	function settings_list()
	{
		global $wpdb;

		$tableName = $wpdb->prefix . 'cem_fields';
		$fields = $wpdb->get_results("SELECT * FROM $tableName ORDER BY label ASC");
		
		$this->html .= "<h3>Event Fields</h3><table><tr><th>Label</th><th>Type</th><th>Actions</th></tr>";
		foreach($fields as $field)
		{
			$this->html .= "<tr><td>{$field->label}</td><td>{$field->type}</td><td><a onclick='if(!confirm(\"Are you sure you want to delete this field and ALL of the response values?\")) { return false; }' href='?page=custom_events_manager_settings&cem_a=delete_field&cem_id={$field->id}'>delete</a></td></tr>";
		}
		$this->html .= "</table>
		<form method='post' action='?page=custom_events_manager_settings&cem_a=add_field'>
		<h3>Add Event Field</h3>
		<table class='edit-table'>
			<tr><th>Field Label</th><td><input class='short-text' type='text' name='label' id='label' /></td></tr>
			<tr><th>Field Type</th><td><select name='type' id='type'>
			<option value='text'>Single Line Text Field (text)</option>
			<option value='textarea'>Multi-Line Text Field (textarea)</option>
			<option value='select'>Select List (select)</option>
			<option value='multiselect'>Multi-Select List (multiselect)</option>
			<option value='email'>Email Address (email)</option>
			</select></td></tr>
			<tr><td colspan='2'><input type='submit' value='Add Field' /></td></tr>
			<tr><td colspan='2'><strong>NOTE:</strong> The 'Email Address' field type will make sure a valid email<br />is entered and can be used to send a registration confirmation.</td></tr>
		</table>
		</form>	
		";
	}	

	function settings_add_field()
	{
		global $wpdb;
		
		$tableName  = $wpdb->prefix . 'cem_fields';
		$insert = $wpdb->query($wpdb->prepare("INSERT INTO $tableName (label, type) VALUES (%s,%s)", array($_REQUEST['label'], $_REQUEST['type'])));
		
		$this->settings_list();
	}

	function settings_delete_field()
	{
		global $wpdb;
		
		$tableName = $wpdb->prefix . "cem_fields";
		$deleted = $wpdb->query($wpdb->prepare("DELETE FROM $tableName WHERE id = %d", $_REQUEST['cem_id']));
		
		$this->settings_list();
	}
	
	function main_delete_respondent()
	{
		global $wpdb;
		
		$tableName = $wpdb->prefix . "cem_responses";
		$deleted = $wpdb->query($wpdb->prepare("DELETE FROM $tableName WHERE id = %d", $_REQUEST['cem_id']));
		
		$this->main_view($_REQUEST['cem_event_id']);
	}
	
	function settings_update()
	{
		
	}

	function redirect($msg, $url)
	{
		$js = "
		<script type='text/javascript'>
			alert('$msg');
			location.href='$url';
		</script>
		";
		
		return $js;
	}
	
	function write()
	{
		echo '</span>' . $this->html;
	}

}

?>