var AdminView = Backbone.View.extend(
{
	el: jQuery('body'),

	initialize: function()
	{
		
	},

	events:
	{
		/*"click nav a": "change_view"*/
	},

	change_view: function(e)
	{
		console.log("Clicked: " , e);

		return false;
	}
});

var myAdminView = new AdminView({model: new AdminModel()});