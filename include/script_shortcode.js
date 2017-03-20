jQuery(function($)
{
	$(document).on('change', '.mf_shortcode_wrapper select', function()
	{
		$(this).parent('.form_select').siblings('.form_select').children('select').val('');
	});

	$(document).on('click', '.mf_shortcode_wrapper .button-primary', function()
	{
		$('.mf_shortcode_wrapper select').each(function()
		{
			var value = $(this).val(),
				type = $(this).attr('rel');

			if(value != '')
			{
				var type_id = '';

				if(parseInt(value) == value)
				{
					type_id = ' id=' + value;
				}

				window.send_to_editor('[' + type + type_id + ']');
			}
		});
	});

	$(document).on('click', '.mf_shortcode_wrapper .button-secondary', function()
	{
		tb_remove();
	});
});