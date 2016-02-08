jQuery(function($)
{
	function hash_action()
	{
		var hash = location.hash.replace('#', '');

		if(hash == '')
		{
			hash = "settings_base";
		}

		if(hash != '')
		{
			var dom_section = $('.wrap > form > div#' + hash);

			$('.wrap > ul').children('li#' + hash + '_li').addClass('active').siblings('li').removeClass('active');

			dom_section.show().siblings('div').hide();
			dom_section.next('table').show().siblings('table').hide();
		}
	}

	$('.wrap form > div > a').each(function()
	{
		var dom_obj = $(this),
			dom_id = $(this).attr('href').replace('#', ''),
			dom_name = $(this).children('h3').text();

		$('.wrap > ul').append("<li id='" + dom_id + "_li'><a href='#" + dom_id + "'>" + dom_name + "</a></li>");

		dom_obj.hide();
	});

	hash_action();

	$(window).on('hashchange', function()
	{
		hash_action();
	});
});