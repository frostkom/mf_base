jQuery(function($)
{
	$(document).on('click', ".mf_media_library button", function()
	{
		var dom_parent = $(this).parents(".mf_media_library"),
			type = dom_parent.attr('data-type'),
			multiple = dom_parent.attr('data-multiple'),
			return_to = dom_parent.attr('data-return_to'),
			return_type = dom_parent.attr('data-return_type');

		if(this.window === undefined)
		{
			this.window = wp.media(
			{
				title: script_media_library.insert_file_text,
				library: {type: type},
				multiple: multiple == 'true',
				button: {text: script_media_library.insert_text}
			});

			var self = this;

			this.window.on('select', function()
			{
				/*author: "[id]", authorName: "[name]"
				id: [id]
				filename: [filename.suffix], subtype: "png", name: [name], title: [name]
				type: "image", icon: [url (wp-includes\images\media\)], mime: [mime]
				url: [url]
				status: "inherit"
				menuOrder: [number]
				height: [number], width: [number], orientation: "landscape", sizes: [object]
				filesizeInBytes: [size], filesizeHumanReadable: [size]
				date: [datetime], modified: [datetime], dateFormatted: [datetime]
				link: [url]
				editLink: [url]
				description: "", alt: "", caption: ""
				uploadedTo: [id]
				uploadedToLink: [url]
				uploadedToTitle: [post_title]
				context: ""
				meta: false
				nonces: [object]
				compat: [object]*/

				if(multiple == 'true')
				{
					var files = self.window.state().get('selection').toArray();

					for(var i = 0; i < files.length; i++)
					{
						console.log("Multiple Files: " , files[i].toJSON());
					}
				}

				else
				{
					var first = self.window.state().get('selection').first().toJSON();

					if(return_to != '')
					{
						switch(return_type)
						{
							case 'input':
								$("#" + return_to).val("[mf_file id=" + first.id + " filetype=" + first.subtype + "]");
							break;

							default:
								console.log("Return Type: " + return_type);
							break;
						}
					}

					else
					{
						var dom_img = $(this),
						img_url = dom_img.attr('src');

						dom_parent.find("input[type='hidden']").val(first.url);

						if(first.type == 'image')
						{
							dom_parent.find("img").attr('src', first.url).removeClass('hide').siblings("span").addClass('hide');
						}

						else
						{
							dom_parent.find("span").removeClass('hide').children(".fa").attr({'title': first.url});
							dom_parent.find("span").siblings("img").addClass('hide');
						}

						dom_parent.find("div > div").removeClass('hide');
						dom_parent.find("button").text(script_media_library.change_file_text);

						/*wp.media.editor.insert('[myshortcode id="' + first.id + '"]');*/
					}
				}
			});
		}

		this.window.open();
		return false;
	});

	$(document).on('click', ".mf_media_library > div a", function()
	{
		var dom_parent = $(this).parents(".mf_media_library");

		dom_parent.find("input[type='hidden']").val('');
		dom_parent.find("img").attr('src', '');
		dom_parent.find("span").text('');

		dom_parent.find("button").text(script_media_library.add_file_text);
		dom_parent.find("div > div").addClass('hide');

		return false;
	});
});