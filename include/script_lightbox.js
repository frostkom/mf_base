jQuery(function($)
{
	$(".wp-post-image").each(function()
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

		$(this).parent("figure").addClass('mf_lightbox').attr('href', largest.url);
	});

	$(".image > a").each(function()
	{
		$(this).addClass('mf_lightbox');
	});

	$(document).on('click', ".mf_lightbox", function()
	{
		var dom_href = $(this).attr('href');

		console.log(dom_href);

		return false;
	});
});