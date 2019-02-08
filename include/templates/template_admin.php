<?php
/*
Template Name: Front-End Admin
*/

//Only effective if cache is off, so we need an extra check in the API + possibly to invalidate cache on this page
if(!is_user_logged_in())
{
	mf_redirect(get_site_url()."/wp-login.php?redirect_to=".$_SERVER['REQUEST_URI']);
}

$arr_views = apply_filters('init_base_admin', array());

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

				if(count($arr_views) > 0)
				{
					echo "<nav>
						<ul>";

							foreach($arr_views as $key => $view)
							{
								echo "<li>";

									$i = 0;

									$count_temp = count($view['items']);

									foreach($view['items'] as $item)
									{
										$item_url = "#admin/".str_replace("_", "/", $key)."/".$item['id'];

										if($i == 0)
										{
											echo "<a href='".$item_url."'>";

												if(isset($view['icon']) && $view['icon'] != '')
												{
													echo "<i class='".$view['icon']."'></i>";
												}

												echo "<span>".$view['name']."</span>
											</a>";
										}

										else
										{
											if($i == 1)
											{
												echo "<ul>";
											}

												echo "<li>
													<a href='".$item_url."'>
														<span>".$item['name']."</span>
													</a>
												</li>";

											if($i == ($count_temp - 1))
											{
												echo "</ul>";
											}
										}

										$i++;
									}

								echo "</li>";
							}

						echo "</ul>
					</nav>";
				}

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

				echo "<section>";

					if(count($arr_views) > 0)
					{
						echo "<div class='error hide'><p></p></div>
						<div class='updated hide'><p></p></div>";

						//loading
						echo "<div class='admin_container'>
							<div class='default'>".$post_content."</div>
							<div class='loading hide'><i class='fa fa-spinner fa-spin fa-3x'></i></div>";

							foreach($arr_views as $key => $view)
							{
								foreach($view['items'] as $item)
								{
									echo "<div id='admin_".$key."_".$item['id']."' class='hide'>
										<h2>".$view['name']." - ".$item['name']."</h2>
										<div>...</div>
									</div>";
								}
							}

						echo "</div>";
					}

					else
					{
						echo $post_content;
					}

				echo "</section>";
			}

		echo "</article>";

		if(count($arr_views) > 0)
		{
			$arr_templates_id = array();

			foreach($arr_views as $key => $view)
			{
				if(!isset($view['templates_id']) || !in_array($view['templates_id'], $arr_templates_id))
				{
					echo $view['templates'];
				}

				if(isset($view['templates_id']))
				{
					$arr_templates_id[] = $view['templates_id'];
				}
			}
		}
	}

get_footer();