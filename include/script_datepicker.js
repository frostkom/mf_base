jQuery(function($)
{
	$("input.mf_datepicker.date, div.mf_datepicker.date input").datepicker(
	{
		dateFormat : 'yy-mm-dd',
		constrainInput: true,
		showOtherMonths: true,
		selectOtherMonths: true,
		showWeek: true,
		showButtonPanel: true,
		/*changeYear: true,
		yearRange: '-2:2',
		changeMonth: true,*/
		firstDay: 1
	});

	$("input.mf_datepicker.month, div.mf_datepicker.month input").datepicker(
	{
		dateFormat : 'yy-mm',
		constrainInput: true,
		showOtherMonths: true,
		selectOtherMonths: true,
		showWeek: true,
		showButtonPanel: true,
		/*changeYear: true,
		yearRange: '-2:2',
		changeMonth: true,*/
		firstDay: 1
	});
});