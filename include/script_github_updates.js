jQuery(function($)
{
	$(".plugin-update-tr").each(function()
	{
		$(this).prev("tr").addClass('update');

		if($(this).prev("tr").hasClass('active') == false)
		{
			$(this).removeClass('active');
		}
	});
});