jQuery(function($)
{
	$(document).on('click', ".overlay_container.modal", function()
	{
		$(this).addClass('hide');
	});

	$(document).on("keyup", function(e)
	{
		/* Key up Esc */
		if((e.which || e.keyCode) == 27)
		{
			$(".overlay_container.modal:not(.hide)").addClass('hide');
		}
	});
});