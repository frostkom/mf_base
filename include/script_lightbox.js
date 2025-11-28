jQuery(function($)
{
	$(".wp-post-image, .wp-block-image > img").each(function()
	{
		var srcset = $(this).attr('srcset');

		if(!srcset)
		{
			return;
		}

		var sources = srcset.split(','),
			largest = { url: '', width: 0 };

		sources.forEach(function(source)
		{
			var parts = source.trim().split(' ');
			var url = parts[0];
			var width = parseInt(parts[1].replace('w',''), 10);

			if (width > largest.width)
			{
				largest.url = url;
				largest.width = width;
			}
		});

		$(this).parent("figure").addClass('mf_lightbox relative').attr('href', largest.url);
	});

	$(".image > a").each(function()
	{
		$(this).addClass('mf_lightbox');
	});

	$(document).on('click', ".mf_lightbox", function()
	{
		var dom_href = $(this).attr('href');

		$("#overlay_lightbox").removeClass('hide').find("div > div").html("<img src='" + dom_href + "'>");

		return false;
	});
});