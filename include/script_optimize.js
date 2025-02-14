jQuery(function($)
{
	function run_ajax(obj)
	{
		obj.button.addClass('is_disabled');
		obj.selector.html("<i class='fa fa-spinner fa-spin fa-2x'></i>");

		$.ajax(
		{
			url: script_base_optimize.ajax_url,
			type: 'post',
			dataType: 'json',
			data: {
				action: obj.action
			},
			success: function(data)
			{
				obj.selector.empty();

				obj.button.removeClass('is_disabled');

				if(data.success)
				{
					obj.selector.html(data.message);
				}

				else
				{
					obj.selector.html(data.error);
				}
			}
		});

		return false;
	}

	$(document).on('click', "button[name='btnBaseOptimize']:not(.is_disabled)", function(e)
	{
		run_ajax(
		{
			'button': $(e.currentTarget),
			'action': 'optimize_theme',
			'selector': $("#optimize_debug")
		});
	});
});