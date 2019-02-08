var AdminView = Backbone.View.extend(
{
	el: jQuery("body"),

	initialize: function(){},

	events:
	{
		"click nav a": "change_view"
	},

	change_view: function(e)
	{
		var dom_obj = jQuery(e.currentTarget);

		dom_obj.addClass('active').siblings("a").removeClass('active');
		dom_obj.parents("li").addClass('active').siblings("li").removeClass('active').children("a").removeClass('active');
	}
});

var myAdminView = new AdminView({model: new AdminModel()});