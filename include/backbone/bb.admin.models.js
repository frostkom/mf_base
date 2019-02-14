var AdminModel = Backbone.Model.extend(
{
	getPage: function(api_url, action)
	{
		var self = this,
			url = '';

		if(action)
		{
			url += '?type=' + action;
		}

		jQuery().callAPI(
		{
			base_url: api_url + 'api/',
			url: url + "&timestamp=" + Date.now(),
			send_type: 'get',
			onSuccess: function(data)
			{
				self.set(data);
			}
		});
	},

	submitForm: function(api_url, action, form_data)
	{
		var self = this,
			url = '';

		if(action)
		{
			url += '?type=' + action;
		}

		jQuery().callAPI(
		{
			base_url: api_url + 'api/',
			url: url,
			data: form_data,
			onSuccess: function(data)
			{
				self.set(data);
			}
		});
	}
});