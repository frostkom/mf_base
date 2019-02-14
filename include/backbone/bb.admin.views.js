var AdminView = Backbone.View.extend(
{
	el: jQuery("body"),

	initialize: function()
	{
		this.model.on("change:redirect", this.do_redirect, this);
		this.model.on('change:message', this.display_message, this);
		this.model.on("change:next_request", this.next_request, this);
		this.model.on("change:admin_response", this.admin_response, this);
	},

	events:
	{
		"click nav a": "change_view",
		"submit form": "submit_form",
	},

	change_view: function(e)
	{
		var dom_obj = jQuery(e.currentTarget),
			api_url = dom_obj.attr('data-api-url') || '';

		dom_obj.addClass('active').siblings("a").removeClass('active');
		dom_obj.parents("li").addClass('active').siblings("li").removeClass('active').children("a").removeClass('active');

		if(api_url != '')
		{
			var action = dom_obj.attr('href').replace('#', '');

			this.loadPage(api_url, action);

			/*return false;*/
		}
	},

	do_redirect: function()
	{
		var response = this.model.get('redirect');

		if(response != '')
		{
			location.href = response; /* + (response.match(/\?/) ? "&" : "?") + "redirect_to=" + location.href*/

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
			var api_url = this.model.get("api_url") || '';

			if(api_url != '')
			{
				this.loadPage(api_url, response);

				this.model.set({"next_request" : ""});
			}
		}
	},

	display_container: function(dom_container)
	{
		dom_container.removeClass('hide').siblings("div").addClass('hide');
	},

	loadPage: function(api_url, action)
	{
		this.hide_message();

		var dom_container = jQuery("#" + action.replace(/\//g, '_'));

		if(dom_container.length > 0)
		{
			this.display_container(dom_container);
		}

		else
		{
			jQuery(".admin_container .loading").removeClass('hide').siblings("div").addClass('hide');
		}

		this.model.getPage(api_url, action);
	},

	submit_form: function(e)
	{
		var dom_obj = jQuery(e.currentTarget),
			action = dom_obj.attr('data-action'),
			api_url = dom_obj.attr('data-api-url') || '';

		if(api_url != '')
		{
			this.model.submitForm(api_url, action, dom_obj.serialize());

			/*dom_obj.find("button[type='submit']").addClass('disabled').attr('disabled', true);*/

			return false;
		}
	},

	admin_response: function()
	{
		var response = this.model.get('admin_response'),
			template = response.template,
			container = response.container,
			dom_template = jQuery("#template_" + template),
			dom_container = jQuery("#" + container);

		var html = _.template(dom_template.html())(response);

		dom_container.children("div").html(html);

		this.display_container(dom_container);
	}
});

var myAdminView = new AdminView({model: new AdminModel()});