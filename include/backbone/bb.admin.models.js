var AdminModel = Backbone.Model.extend(
{
	defaults: {
		/*'': 0*/
	}/*,

	getPage: function(dom_href)
	{
		var self = this,
			url = (dom_href ? '?' + dom_href.replace('#', '') : "");

		jQuery().callAPI(
		{
			base_url: script_base_admin_models.plugin_url + 'api/',
			url: url,
			send_type: 'get',
			onSuccess: function(data)
			{
				self.set(data);
			}
		});
	}*/
});