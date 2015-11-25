jQuery(function($)
{
	$('#mf_import').on('submit', function()
	{
		var type = $(this).attr('rel'),
			form_data = $(this).serialize();

		$.ajax(
		{
			url: script_base_wp.plugins_url + '/mf_base/include/ajax.php?type=' + type,
			type: 'post',
			data: form_data,
			dataType: 'json',
			success: function(data)
			{
				if(data.success)
				{
					$('#import_result').html(data.result);
				}

				else
				{
					alert(data.error);
				}
			}
		});

		return false;
	});
});