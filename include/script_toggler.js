jQuery(function($)
{
	$(document).on('click', ".toggler", function()
	{
		$(this).toggleClass('is_open');
	});
});