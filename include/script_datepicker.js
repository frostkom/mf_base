jQuery(function($)
{
	$('input.mf_datepicker, div.mf_datepicker input').datepicker(
	{
		dateFormat : 'yy-mm-dd',
		constrainInput: true,
		showOtherMonths: true,
		selectOtherMonths: true,
		showWeek: true,
		//changeYear: true,
		//yearRange: '-2:2',
		//changeMonth: true,
		firstDay: 1
	});
});