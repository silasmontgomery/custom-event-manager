<?php

class Registration_Page
{
	function __construct()
	{
		add_shortcode('cem', array($this, 'init'));
	}

	function init( $atts )
	{	
		wp_register_style('cemStylesheet', CEM_CSS_PATH . 'style.css');
		wp_enqueue_style('cemStylesheet');
		wp_register_style('cemJqueryCss', CEM_CSS_PATH . 'jquery-ui.min.css');
		wp_enqueue_style('cemJqueryCss');
		wp_register_script('jquery', CEM_JS_PATH . 'jquery.min.js');
		wp_enqueue_script('jquery');
		wp_register_script('jquery-ui', CEM_JS_PATH . 'jquery-ui.min.js');
		wp_enqueue_script('jquery-ui');
		wp_register_script('jquery-validate', CEM_JS_PATH . 'jquery.validate.min.js');
		wp_enqueue_script('jquery-validate');
		wp_register_script('cemRegistrationJs', CEM_JS_PATH . 'registration.js');
		wp_enqueue_script('cemRegistrationJs');
		return $this->get_html($atts['event']);
	}
	
	function get_html($event_id)
	{
		global $wpdb;
		
		$event = $this->get_event($event_id);
		
		$html = "<div id='cem-registration-page'>
			<h1>{$event->title}</h1><div id='cem-event-left'>";
		
		$html .= $this->when_html($event);
		$html .= "<div id='cem-event-description'><h2>Description</h2><p>".nl2br($event->description)."</p></div>";
		
		$capacity = $event->capacity;
		if ($capacity == 0)
		{
			$capacity = "No Limit";
		}
		
		if ($event->show_capacity)
			$html .= "<div id='cem-event-capacity'><h2>Event Capacity</h2><p>".($capacity)."</p></div>";
		if ($event->show_party_size)	
			$html .= "<div id='cem-event-party'><h2>Max Party Size</h2><p>".($event->party_size)."</p></div>";
		
		
		$tableName = $wpdb->prefix . "cem_responses";
		$responses = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(attendees) FROM $tableName WHERE event_id = {$event->id}" ) );
		
		if(date("m/d/Y", strtotime($event->registration_start_date)) > date("m/d/Y") || date("m/d/Y", strtotime($event->registration_end_date)) < date("m/d/Y"))
		{
			$html .= "<h2>Registration Closed</h2><p>".nl2br($event->closed)."</p>";
		}
		else if ($capacity == 0 || $capacity > $responses)
		{
			if($_POST['cem-a'] != "register")
			{
				$html .= $this->registration_html($event);
			}
			else if($capacity != 0 && $capacity < ($responses + $_POST['attendees']))
			{
				$html .= "<div id='cem-event-capacity_msg'><h2>Event is at Capacity</h2>".nl2br($event->capacity_msg)."</div>";
				
			}
			else
			{
				$html .= $this->confirmation_html($event);
			}
		}
		else
		{
			$html .= "<div id='cem-event-capacity_msg'><h2>Event is at Capacity</h2>".nl2br($event->capacity_msg)."</div>";
		}
		$html .= "</div><div id='cem-event-right'>";
		$html .= $this->where_html($event);
		$html .= $this->map_html($event);
		
		$html .= "</div></div>";
		
		return $html;
	}
	
	function get_event($event_id)
	{
		global $wpdb;

		$tableName = $wpdb->prefix . "cem_events";
		return $wpdb->get_row("SELECT * FROM $tableName WHERE id = {$event_id}");
	}

	function when_html($event)
	{
		$html = "
		<div id='cem-event-when'>
		<h2>When</h2>
		<p>
		<strong>".date("l, F jS, Y", strtotime($event->start_date))."</strong><br />";
		
		if($event->end_date > $event->start_date)
		{
			$html .= "Through ".date("F jS, Y", strtotime($event->end_date))."<br />";
		}
		
		$html .= "{$event->start_time} - {$event->end_time}</p></div>";
		
		return $html;
	}
	
	function where_html($event)
	{
		$location = '';
		$html = '';
		
		if($event->location_name != '')
			$location .= "<strong>{$event->location_name}</strong><br />";
		if($event->address_1 != '')
			$location .= "{$event->address_1}<br />";
		if($event->address_2 != '')
			$location .= "{$event->address_2}<br />";
		if($event->city != '')
			$location .= "{$event->city}";
		if($event->state != '')
			$location .= ", {$event->state}";
		if($event->zip != '')
			$location .= " {$event->zip}";

		if($location != '')
		{
			$html = "<div id='cem-event-where'>
			<h2>Where</h2>
			<p>{$location}</p>
			</div>";
		}
		
		return $html;
	}
	
	function map_html($event)
	{
		$url = "https://maps.google.com/maps?q=".str_replace(' ', '+', $event->address_1).",".str_replace(' ', '+', $event->city).",".str_replace(' ', '+', $event->state)."+".str_replace(' ', '+', $event->zip)."&output=embed";
		
		$html = "<div id='cem-event-map'>";
		$html .= "<iframe width=\"400\" height=\"400\" frameborder=\"0\" scrolling=\"no\" marginheight=\"0\" marginwidth=\"0\" src=\"$url\" style=\"color:#0000FF;text-align:left\"></iframe>";
		$html .= "</div>";
		
		return $html;
	}
	
	function registration_html($event)
	{
		global $wpdb;

		$efTable = $wpdb->prefix . "cem_event_fields";
		$fTable = $wpdb->prefix . "cem_fields";
		$fields = $wpdb->get_results("SELECT $efTable.id, $fTable.label, $fTable.type, $efTable.required FROM $efTable, $fTable WHERE $efTable.field_id = $fTable.id AND $efTable.event_id = {$event->id} ORDER BY $efTable.id ASC");
		
		$tableName = $wpdb->prefix . "cem_responses";
		$responses = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(attendees) FROM $tableName WHERE event_id = {$event->id}" ) );
		
		if(is_null($responses))
			$responses = 0;
		
		$html = "<form id='cem-registration-form' method='post'><div id='cem-event-registration'><h2>Registration</h2>
		<input type='hidden' name='capacity' id='capacity' value='{$event->capacity}' />
		<input type='hidden' name='total_attendees' id='total_attendees' value='{$responses}' />
		<input type='hidden' name='max_party_size' id='max_party_size' value='{$event->party_size}' />
		";
		
		if($event->capacity > 0 || $event->party_size > 0) {
			$html .= "<div class='clear'></div><label># of Attendees *</label><div>";
			$html .= "<input type='text' name='attendees' id='attendees' class='required number' /></div>";
		}
		else
		{
			//$html .= "<input type='text' name='attendees' id='attendees' class='required number' />'1'</div>";
		}
		
		foreach($fields as $field)
		{
			$required = array("", "");
			if($field->required == 1)
				$required = array("*", "required");
				
			$field_name = "field_".$field->id;
			
			$html .= "<div class='clear'></div><label>{$field->label} {$required[0]}</label><div>";
			
			switch($field->type)
			{
				case 'text':
					$html .= "<input type='text' name='$field_name' id='$field_name' class='{$required[1]}' />";
					break;
				case 'textarea':
					$html .= "<textarea name='$field_name' id='$field_name' class='{$required[1]}'></textarea>";
					break;
				case 'select':
					$html .= "<select name='{$field_name}' id='{$field_name}' class='{$required[1]}'>" . $this->get_options($field->id) . "</select>";
					break;
				case 'multiselect':
					$html .= "<select name='{$field_name}[]' id='{$field_name}' multiple='multiple' class='{$required[1]}'>" . $this->get_options($field->id) . "</select>";
					break;
				case 'email':
					$html .= "<input type='text' name='$field_name' id='$field_name' class='{$required[1]} email' />";
			}
			
			$html .= "</div>";
			
		}
		
		$html .= "<button type='submit'>Register</button><input type='hidden' name='cem-a' value='register' /></div></form>";
		
		return $html;
	}
	
	function get_options($id)
	{
		global $wpdb;

		$tableName = $wpdb->prefix . "cem_options";
		$options = $wpdb->get_results("SELECT * FROM $tableName WHERE event_field_id = $id ORDER BY value ASC");
		
		foreach($options as $option)
		{
			$html .= "<option>{$option->value}</option>";
		}
		
		return $html;
	}
	
	function confirmation_html($event)
	{
		global $wpdb;
		
		$_POST = stripslashes_deep($_POST);
		$tableName = $wpdb->prefix . "cem_responses";
		
		if($wpdb->insert($tableName, array('event_id' => $event->id, 'attendees' => $_POST['attendees'], 'received' => date("Y-m-d H:i:s")), array('%d', '%d', '%s')))
		{
			$response_id = $wpdb->insert_id;
		
			foreach($_POST as $key => $value)
			{
				if(stristr($key, 'field_'))
				{
					$field = explode('_', $key);
					
					if(is_array($value))
						$value = implode(", ", $value);
					
					$tableValues = $wpdb->prefix . "cem_values";
					$wpdb->insert($tableValues, array('response_id' => $response_id, 'event_field_id' => $field[1], 'value' => $value), array('%d', '%d', '%s'));
				}
				
				$html = "<div id='cem-event-confirmation'><h2>Registration Successful</h2>".nl2br($event->confirmation)."</div>";
			}
			
			//PRINTS TICKET
			$tableValues = $wpdb->prefix . "cem_values";
			$efTable = $wpdb->prefix . "cem_event_fields";
			$fTable = $wpdb->prefix . "cem_fields";

			$sql = "SELECT $tableValues.value, $fTable.label 
					FROM $tableValues, $fTable, $efTable 
					WHERE $tableValues.event_field_id = $efTable.id 
					AND $fTable.id = $efTable.field_id 
					AND $tableValues.response_id = $response_id 
					AND $efTable.ticket_include = 1";
			$values = $wpdb->get_results($sql);
			
			$initial_ticket = array_merge(range(0,9), range('a','z'), range('A','Z') );
			shuffle($initial_ticket);
			$random_ticket = implode('', array_slice($initial_ticket,0,7));
			$ticket_number = $response_id . $random_ticket;
			
			$html .= "<br /><br /><div id = 'cem-event-ticket-order'>
				<h2>Ticket ID - {$ticket_number}</h2>
				<table>
					<tr>
						<th>{$event->title}</th>
						<th>{$event->location_name}</th>
					</tr>
					<tr>
						<th>".date("m/d/Y", strtotime($event->start_date))." - ".date("m/d/Y", strtotime($event->end_date))."<br />
							{$event->start_time} - {$event->end_time}</th>
						<th>{$event->address_1}<br />
							{$event->city}, {$event->state} {$event->zip}</th>
					</tr>
					<tr>
						<th>";
			foreach($values as $value)
			{
				$html .= "{$value->label}: {$value->value}<br />";
			}			
						
			$html .=		"Your Party Size: {$_POST['attendees']}</th>
						<th></th>
					</tr>
				</table>
			</div>";
			
			if($event->notification_emails != '')
			{
				$emails = explode(",", $event->notification_emails);
				$domain = explode(".", $_SERVER['HTTP_HOST']);
				$from = 'custom-events-manager@' . $domain[count($domain)-2] . '.' .  $domain[count($domain)-1];
				
				foreach($emails as $email)
				{
					$subject = "New online registration for '{$event->title}'";
					$body = "New online registration for '{$event->title}'
					
	View your registrations at:
	http://" . $_SERVER['HTTP_HOST'] . "/wp-admin/admin.php?page=custom_events_manager&cem_a=view&cem_id={$event->id}";
					$headers = 'From: ' . $from . "\r\n" .
						'X-Mailer: Custom Event Management Plugin For Wordpress';
					
					mail($email, $subject, $body, $headers);
				}
			}
		
		}
		else
		{
			$html .= "Problem submitting your event registration.";
		}
		
		return $html;
	}
}

?>