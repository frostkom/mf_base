jQuery(function($)
{
	var dom_obj = $(".widget .tabs");

	function open_tab(hash)
	{
		dom_obj.find("a[href='" + hash + "']").parent("li").addClass('active').siblings("li").removeClass('active');
		$(hash).addClass('active').siblings(".tab_content").removeClass('active');

		history.replaceState(null, null, (location.hash != '' ? '' : '') + hash);

		return false;
	}

	dom_obj.find("a").on('click', function(e)
	{
		e.preventDefault();

		open_tab($(this).attr('href'));
	});

	if(location.hash != '')
	{
		open_tab(location.hash);
	}

	else
	{
		open_tab(dom_obj.find("li:first-child a").attr('href'));
	}
});