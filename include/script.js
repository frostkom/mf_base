jQuery(function($)
{
	$(document).on('click', 'a[rel=external]', function(e)
	{
		if(e.which != 3)
		{
			window.open($(this).attr('href'));
			return false;
		}
	});

	$('.mf_form :required').each(function()
	{
		$(this).siblings('label').append(' *');
	});

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