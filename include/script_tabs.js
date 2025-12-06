jQuery(function($)
{
	function open_tab(hash)
	{
		$(".widget.list_rights .tabs a[href='" + hash + "']").parent("li").addClass('active').siblings("li").removeClass('active');
		$(hash).addClass('active').siblings(".tab_content").removeClass('active');

		return false;
	}

	$(document).on('click', ".widget.list_rights .tabs a", function(e)
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
		open_tab($(".tabs li:first-child a").attr('href'));
	}
});