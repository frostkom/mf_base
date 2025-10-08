jQuery(function($)
{
	$("a[target='_blank']").each(function()
	{
		if($(this).children("*").length == 0)
		{
			$(this).append("&nbsp;<sup><i class='fas fa-external-link-alt'></i></sup>");
		}
	});
});