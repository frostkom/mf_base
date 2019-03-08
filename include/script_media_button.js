var dom_urls,
	dom_list,
	dom_max_file_uploads = 0,
	dom_buttons,
	arr_attachments = [];

function render_attachment_list()
{
	var output_list = '',
		output_urls = '',
		amount_file_uploads = 0;

	jQuery.each(arr_attachments, function(index, value)
	{
		arr_value = value.split("|");

		var file_name = (arr_value[0] ? arr_value[0] : script_media_button.unknown_title),
			file_url = arr_value[1];

		if(file_url != '' && (dom_max_file_uploads == 0 || amount_file_uploads < dom_max_file_uploads))
		{
			var is_image = file_url.match(/\.(png|gif|jpg|jpeg)/);

			output_list += "<tr>"
				+ "<td>";

					if(is_image)
					{
						output_list += "<img src='" + file_url + "' alt='" + file_name + "'>";
					}

					else
					{
						output_list += "<a href='" + file_url + "'>" + file_name + "</a>";
					}

				output_list += "</td>"
				+ "<td>";

					if(is_image)
					{
						output_list += "<span>" + file_name + "</span><br>";
					}

					output_list += "<i class='fa fa-trash fa-2x red' data-id='" + index + "'></i>"
				+ "</td>"
			+ "</tr>";

			output_urls += (output_urls != '' ? "," : "") + value;

			amount_file_uploads++;
		}
	});

	dom_list.html(output_list);
	dom_urls.val(output_urls);

	if(dom_max_file_uploads > 0)
	{
		dom_buttons.children("span").html(amount_file_uploads + " / " + dom_max_file_uploads);

		if(amount_file_uploads >= dom_max_file_uploads)
		{
			dom_buttons.children(".button").addClass('disabled');
		}

		else
		{
			dom_buttons.children(".button").removeClass('disabled');
		}
	}

	arr_attachments = [];
}

function init_media_button()
{
	jQuery(".wp-admin .mf_media_button .wp-media-buttons").removeClass('form_button');

	jQuery(".mf_media_button").each(function()
	{
		var self = jQuery(this);

		dom_list = self.children(".mf_media_list");
		dom_urls = self.children(".mf_media_urls");
		dom_max_file_uploads = self.attr('data-max_file_uploads');
		dom_buttons = self.children(".wp-media-buttons");

		if(dom_urls.val() != '')
		{
			arr_attachments = dom_urls.val().split(",");
		}

		render_attachment_list();
	});
}

jQuery(function($)
{
	/* Multiple */
	$(document).on('click', ".mf_media_button .mf_media_list .fa", function()
	{
		var confirm_text = script_media_button.confirm_question;

		if(confirm(confirm_text))
		{
			var self = $(this),
				dom_urls = self.parents(".mf_media_list").siblings(".mf_media_urls"),
				dom_rel = parseInt(self.attr('data-id'));

			arr_attachments = dom_urls.val().split(",");

			arr_attachments.splice(dom_rel, 1);

			render_attachment_list();
		}

		else
		{
			return false;
		}
	});

	$(document).on('click', ".mf_media_button", function()
	{
		var self = $(this),
			restore_send_to_editor = window.send_to_editor;

		window.send_to_editor = function(html)
		{
			var dom_raw = self.children(".mf_media_raw");

			dom_list = self.children(".mf_media_list");
			dom_urls = self.children(".mf_media_urls");
			dom_max_file_uploads = self.attr('data-max_file_uploads');
			dom_buttons = self.children(".wp-media-buttons");

			if(dom_urls.val() != '')
			{
				arr_attachments = dom_urls.val().split(",");
			}

			dom_raw.html(html);

			self.siblings(".error").remove();

			if(dom_raw.find("a").length > 0)
			{
				dom_raw.find("a").each(function()
				{
					var dom_a = $(this),
						file_name = '',
						file_url = '';

					if(dom_a.children("img").length > 0)
					{
						file_name = dom_a.children("img").attr('alt');
						file_url = dom_a.children("img").attr('src');

						var file_class = dom_a.children("img").attr('class'),
							file_id = file_class.match(/wp-image-(\d*)/)[1];

						file_url += "|" + file_id;
					}

					else
					{
						file_name = dom_a.text();
						file_url = dom_a.attr('href');
					}

					arr_attachments.push(file_name + "|" + file_url);
				});
			}

			else if(dom_raw.find("img").length > 0)
			{
				dom_raw.find("img").each(function()
				{
					var dom_img = $(this);

					arr_attachments.push(dom_img.attr('alt') + "|" + dom_img.attr('src'));
				});
			}

			else
			{
				self.after("<div class='error'><p>" + script_media_button.no_attachment_link + "</p></div>");
			}

			render_attachment_list();

			dom_raw.empty();

			tb_remove();

			window.send_to_editor = restore_send_to_editor;
		}
	});

	init_media_button();

	/* Single */
	$(document).on('click', ".mf_image_button .button", function()
	{
		var restore_send_to_editor = window.send_to_editor,
			self = $(this).parents(".mf_image_button");

		window.send_to_editor = function(html)
		{
			var dom_raw = self.children(".mf_file_raw");

			dom_raw.html(html);

			if(dom_raw.find("img").length > 0)
			{
				dom_raw.find("img").each(function()
				{
					var dom_img = $(this),
						img_url = dom_img.attr('src');

					self.find("input[type='hidden']").val(img_url);
					self.find("img").attr('src', img_url).show().siblings("span").hide();

					self.children("div:first-of-type").removeClass('hide');
					self.find("button").text(script_media_button.change_file_text);
				});
			}

			dom_raw.empty();

			tb_remove();

			window.send_to_editor = restore_send_to_editor;
		};

		tbframe_interval = setInterval(function()
		{
			$("#TB_iframeContent").contents().find(".savesend input[type='submit']").val(script_media_button.insert_file_text);
		}, 2000);

		tb_show('', script_media_button.adminurl + 'media-upload.php?type=image&amp&TB_iframe=1');

		return false;
	});

	$(document).on('click', ".mf_image_button a", function()
	{
		var dom_parent = $(this).parents(".mf_image_button");

		dom_parent.find("input[type='hidden']").val('');
		dom_parent.find("img").attr('src', '');

		dom_parent.find("button").text(script_media_button.add_file_text);
		dom_parent.children("div:first-of-type").addClass('hide');

		return false;
	});
});