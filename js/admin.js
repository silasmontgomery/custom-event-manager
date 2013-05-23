// JavaScript Document

jQuery(document).ready(function($)
{

	$('.date').datepicker();
	
	$('#assign_field_button').click(function()
	{
		if($('#event_fields').val() != '')
		{
			$required = 0;
			if($('#required').is(":checked"))
				$required = 1;
			$ticket_include = 0;
			if($('#ticket_include').is(":checked"))
				$ticket_include = 1;	
			window.location="?page=custom_events_manager_edit&cem_a=assign&required=" + $required + "&ticket_include=" + $ticket_include + "&cem_id=" + $('#cem_id').val() + "&cem_field_id=" + $('#event_fields').val();
		}
	});
 
 	$("#cem_edit_form").validate({
		errorClass: 'cem-error',
		wrapper: 'span',
		submitHandler: function(form) {
			form.submit();
		}
	});
 
});