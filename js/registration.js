// JavaScript Document

jQuery(document).ready(function($)
{

	var seats_left = parseInt($('#capacity').val()) - parseInt($('#total_attendees').val());

	$('#cem-registration-page .date').datepicker();
	
	$("#cem-registration-form").validate({
		errorClass: 'cem-error',
		wrapper: 'span',
		rules: {
			attendees: {
				required: true,
				min: 1,
				max: parseInt($('#max_party_size').val()),
				range: [1,  seats_left]
			}
		},
		messages: {
			attendees: {
				min: "We're sorry, the minimum party size is 1",
				max: jQuery.format("We're sorry, the maximum party size is {0}."),
				range: jQuery.format("We're sorry, there are only {1} seats remaining.")
			}
		},
		submitHandler: function(form) {
				form.submit();
		}
	});
	
});