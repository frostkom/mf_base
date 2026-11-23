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
		onError: function(jqXHR, textStatus, errorThrown)
		{
			jQuery("#overlay_lost_connection").removeClass('hide');
		},
		onAfterSend: function()
		{
			jQuery("#overlay_loading").addClass('hide');
		}
	}, o);

	jQuery.ajax(
	{
		url: op.base_url + op.url,
		type: op.send_type,
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
		error: function(jqXHR, textStatus, errorThrown)
		{
			op.onError(jqXHR, textStatus, errorThrown);
			op.onAfterSend();
		}
	});
};