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

	$(".widget > .tabs").each(function()
	{
		var list = this,
			$list = $(list),
			$left = $('<button type="button" class="tabs_arrow tabs_arrow_left" aria-label="Scroll tabs left" hidden>&#8249;</button>'),
			$right = $('<button type="button" class="tabs_arrow tabs_arrow_right" aria-label="Scroll tabs right" hidden>&#8250;</button>');

		$list.after($left, $right);

		function update_arrows()
		{
			var max = list.scrollWidth - list.clientWidth;

			// Match the arrows' height to the tab bar
			$left.add($right).css('height', list.offsetHeight);

			$left.prop('hidden', list.scrollLeft <= 1);
			$right.prop('hidden', list.scrollLeft >= max - 1);
		}

		function scroll_active_into_view()
		{
			var active = list.querySelector("li.active");

			if(active)
			{
				list.scrollTo({left: active.offsetLeft - (list.clientWidth - active.offsetWidth) / 2, behavior: 'smooth'});
			}
		}

		$left.on('click', function()
		{
			list.scrollBy({left: -list.clientWidth * .75, behavior: 'smooth'});
		});

		$right.on('click', function()
		{
			list.scrollBy({left: list.clientWidth * .75, behavior: 'smooth'});
		});

		$list.on('scroll', update_arrows);
		$list.on('click', 'a', function()
		{
			setTimeout(scroll_active_into_view, 0);
		});
		$(window).on('resize', update_arrows);

		update_arrows();
		setTimeout(function()
		{
			scroll_active_into_view();
			update_arrows();
		}, 50);
	});
});