jQuery(function($)
{
	$(document).on('keyup', "input[type='url']", function()
	{
		var dom_obj = $(this),
			dom_val = dom_obj.val();

		if(dom_val.length > 7)
		{
			if(dom_val.substring(0, 4) != 'http' && dom_val.substring(0, 2) != '//' && dom_val.substring(0, 4) != 'tel:' && dom_val.substring(0, 7) != 'mailto:' && dom_val.substring(0, 1) != '#')
			{
				dom_obj.val("https://" + dom_val);
			}
		}
	});
});