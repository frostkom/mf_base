jQuery(function($)
{
	$(".overlay_container.modal:not(.disable_close) > div").each(function()
	{
		$(this).append("<i class='fa fa-times'></i>");
	});

	$(document).on('click', ".overlay_container.modal .fa-times", function()
	{
		$(this).parents(".overlay_container.modal").fadeOut();
	});
});