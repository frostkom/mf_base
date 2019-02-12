var AdminView = Backbone.View.extend(
{
	el: jQuery("body"),

	initialize: function()
	{
		this.model.on("change:redirect", this.do_redirect, this);
		this.model.on('change:message', this.display_message, this);
		this.model.on("change:next_request", this.next_request, this);
		this.model.on("change:admin_base_response", this.view_response, this);
	},

	events:
	{
		"click nav a": "change_view"
	},

	change_view: function(e)
	{
		var dom_obj = jQuery(e.currentTarget);

		dom_obj.addClass('active').siblings("a").removeClass('active');
		dom_obj.parents("li").addClass('active').siblings("li").removeClass('active').children("a").removeClass('active');
	},

	do_redirect: function()
	{
		var response = this.model.get('redirect');

		if(response != '')
		{
			location.href = response + (response.match(/\?/) ? "&" : "?") + "redirect_to=" + location.href;

			this.model.set({'redirect': ''});
		}
	},

	hide_message: function()
	{
		jQuery(".error:not(.hide), .updated:not(.hide)").addClass('hide');
	},

	display_message: function()
	{
		var response = this.model.get('message');

		if(response != '')
		{
			this.hide_message();

			if(this.model.get('success') == true)
			{
				jQuery(".updated.hide").removeClass('hide').children("p").html(response);
			}

			else
			{
				jQuery(".error.hide").removeClass('hide').children("p").html(response);
			}

			scroll_to_top();

			jQuery(".mf_form button[type='submit']").removeClass('disabled').removeAttr('disabled');

			this.model.set({'message': ''});
		}
	},

	next_request: function()
	{
		var response = this.model.get("next_request");

		if(response != '')
		{
			this.model.getPage(response);

			this.model.set({"next_request" : ""});
		}
	},

	loadPage: function(tab_active)
	{
		this.hide_message();

		jQuery(".admin_container .loading").removeClass('hide').siblings("div").addClass('hide');

		this.model.getPage(tab_active);
	},

	submit_form: function(e)
	{
		/*var dom_obj = jQuery(e.currentTarget),
			dom_action = dom_obj.attr('data-action');

		this.model.submitForm(dom_action, dom_obj.serialize());

		dom_obj.find("button[type='submit']").addClass('disabled').attr('disabled', true);*/

		return false;
	},

	view_response: function()
	{
		var response = this.model.get('admin_base_response'),
			template = response.template,
			type = response.type,
			html = '';

		var amount = 1;

		if(amount > 0)
		{
			html = _.template(jQuery("#template_" + template).html())(response);
		}

		else
		{
			html = _.template(jQuery("#template_" + template + "_message").html())('');
		}

		jQuery("#" + type).html(html).removeClass('hide').siblings("div").addClass('hide');
	}
});

var myAdminView = new AdminView({model: new AdminModel()});