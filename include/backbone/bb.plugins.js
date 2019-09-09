jQuery.fn.callAPI = function(o)
{
	var op = jQuery.extend(
	{
		base_url: '',
		url: '',
		data: '',
		send_type: 'post',
		onBeforeSend: function()
		{
			jQuery("#overlay_loading").removeClass('hide');
		},
		onSuccess: function(data){},
		onAfterSend: function()
		{
			jQuery("#overlay_loading").addClass('hide');
		},
		onError: function(data)
		{
			/*setTimeout(function()
			{
				jQuery("#overlay_loading").addClass('hide');
				jQuery("#overlay_lost_connection").removeClass('hide');
			}, 2000);*/
		}
	}, o);

	jQuery.ajax(
	{
		url: op.base_url + op.url,
		type: op.send_type,
		processData: false,
		data: op.data,
		dataType: 'json',
		beforeSend: function()
		{
			op.onBeforeSend();
		},
		success: function(data)
		{
			op.onSuccess(data);
			op.onAfterSend();

			if(data.mysqli_error && data.mysqli_error == true)
			{
				jQuery("#overlay_lost_connection").removeClass('hide');
			}

			else
			{
				jQuery("#overlay_lost_connection").addClass('hide');
			}
		},
		error: function(data)
		{
			op.onError(data);
		}
	});
};