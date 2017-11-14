jQuery.fn.callAPI = function(o)
{
	var op = jQuery.extend(
	{
		base_url: '',
		url: '',
		data: '',
		send_type: 'post',
		onBeforeSend: function(){},
		onSuccess: function(data){},
		onAfterSend: function(){},
		onError: function(data)
		{
			setTimeout(function()
			{
				jQuery("#overlay_lost_connection").show();
			}, 2000);
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
				jQuery("#overlay_lost_connection").show();
			}

			else
			{
				jQuery("#overlay_lost_connection").hide();
			}
		},
		error: function(data)
		{
			op.onError(data);
		}
	});
};