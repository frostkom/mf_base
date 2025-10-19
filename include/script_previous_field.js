jQuery(function($)
{
	$(".mf_form").on('keyup', "input, textarea", function(e)
	{
		if(e.which === 8)
		{
			if($(this).val() === '')
			{
				var $inputs = $(".mf_form").find("input, textarea").filter(":visible"),
					idx = $inputs.index(this);

				if(idx > 0)
				{
					$inputs.eq(idx - 1).focus();
				}
			}
		}
	});
});