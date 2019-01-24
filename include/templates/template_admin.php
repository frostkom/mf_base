<?php
/*
Template Name: Front-End Admin
*/

$plugin_include_url = plugin_dir_url(__FILE__);
$plugin_version = get_plugin_version(__FILE__);

mf_enqueue_script('underscore');
mf_enqueue_script('backbone');
mf_enqueue_script('script_base_plugins', $plugin_include_url."backbone/bb.plugins.js", $plugin_version);

$arr_views = apply_filters('init_base_admin', array());

mf_enqueue_script('script_base_init', $plugin_include_url."backbone/bb.init.js", $plugin_version);

get_header();

	if(have_posts())
	{
		echo "<article>";

			while(have_posts())
			{
				the_post();

				$post_title = $post->post_title;
				$post_content = apply_filters('the_content', $post->post_content);

				echo "<h1>".$post_title."</h1>";

				if(is_active_sidebar('widget_after_heading') && !post_password_required())
				{
					ob_start();

					dynamic_sidebar('widget_after_heading');

					$widget_content = ob_get_clean();

					if($widget_content != '')
					{
						echo "<div class='aside after_heading'>"
							.$widget_content
						."</div>";
					}
				}

				echo "<section>"
					.$post_content;

					echo var_export($arr_views, true);

				echo "</section>";
			}

		echo "</article>";
	}

get_footer();