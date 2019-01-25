var AdminApp = Backbone.Router.extend(
{
	routes:
	{
		"*actions": "the_rest"
	},

	the_rest: function(action_type)
	{
		console.log("Base: " , id);

		/*myAdminView.loadPage(action_type);*/
	}
});

new AdminApp();