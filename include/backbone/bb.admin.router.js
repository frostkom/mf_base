var AdminApp = Backbone.Router.extend(
{
	routes:
	{
		"admin/base/:actions": "handle"
	},

	handle: function(action_type)
	{
		/*myAdminView.loadPage(action_type);*/
	}
});

new AdminApp();