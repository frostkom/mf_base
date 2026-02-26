jQuery(function($)
{
	var dom_obj = $(".widget .tabs");

	function open_tab(hash, update_hash)
	{
		dom_obj.find("a[href='" + hash + "']").parent("li").addClass('active').siblings("li").removeClass('active');
		$(hash).addClass('active').siblings(".tab_content").removeClass('active');

		if(update_hash == true)
		{
			history.replaceState(null, null, (location.hash != '' ? '' : '') + hash);
		}

		return false;
	}

	dom_obj.find("a").on('click', function(e)
	{
		e.preventDefault();

		var dom_obj_tab = $(this);

		open_tab(dom_obj_tab.attr('href'), (dom_obj_tab.parents(".tab_content").length == 0));
	});

	if(location.hash != '')
	{
		open_tab(location.hash, false);
	}

	dom_obj.each(function()
	{
		if($(this).children("li.active").length == 0)
		{
			var dom_obj_tab = $(this).find("li:first-child a");

			open_tab(dom_obj_tab.attr('href'), (dom_obj_tab.parents(".tab_content").length == 0));
		}
	});
});