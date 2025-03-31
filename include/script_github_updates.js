jQuery(function($)
{
	$(".plugin-update-tr").each(function()
	{
		$(this).prev("tr").addClass('update');
	});
});