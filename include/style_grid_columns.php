<?php

if(!defined('ABSPATH'))
{
	header("Content-Type: text/css; charset=utf-8");

	$folder = str_replace("/wp-content/plugins/mf_base/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

$arr_breakpoints = apply_filters('get_layout_breakpoints', ['tablet' => 1200, 'mobile' => 930, 'suffix' => "px"]);

$setting_desktop_columns = 3;
$setting_tablet_columns = 2;
$setting_mobile_columns = 1;

if(!function_exists('calc_width'))
{
	function calc_width($columns)
	{
		return (100 / $columns) - ($columns > 1 ? 1 : 0);
	}
}

$column_width_desktop = calc_width($setting_desktop_columns);
$column_width_tablet = calc_width($setting_tablet_columns);
$column_width_mobile = calc_width($setting_mobile_columns);

echo "@media all
{
	.widget ul.grid_columns
	{
		gap: 1%;
		list-style: none;
		padding: 0;
	}

		.widget.masonry ul.grid_columns
		{
			column-count: ".$setting_desktop_columns.";
		}
			
		.widget.square ul.grid_columns
		{
			display: flex;
			flex-wrap: wrap;
		}

		.widget ul.grid_columns li
		{
			background: #fff;
			box-shadow: 0 .2em .4em rgba(0, 0, 0, .15);
			flex: 0 1 auto;
			margin: 0 0 .6em;
			overflow: hidden;
			position: relative;
		}

			.widget.masonry ul.grid_columns li
			{
				page-break-inside: avoid;
				break-inside: avoid;
			}
			
			.widget.square ul.grid_columns li
			{
				flex: 0 1 auto;
				width: ".$column_width_desktop."%;
			}

			.widget ul.grid_columns li .image
			{
				background: rgba(0, 0, 0, .03);
				overflow: hidden;
			}

				.widget ul.grid_columns li .image img
				{
					display: block;
					object-fit: cover;
					transition: all 1s ease;
					width: 100%;
				}

					.widget ul.grid_columns li:hover .image img
					{
						transform: scale(1.1);
					}

			.widget ul.grid_columns .content
			{
				padding: 1em;
			}

				.widget ul.grid_columns .meta
				{
					font-size: .7em;
					margin: .5em 0;
				}

					.widget ul.grid_columns .date
					{
						color: #ccc;
						font-size: .9em;
					}

				.widget ul.grid_columns .content a
				{
					text-decoration: none;
				}

				.widget ul.grid_columns .content p
				{
					margin: 0;
				}

				.widget ul.grid_columns .content .wp-block-button
				{
					margin-top: .5em;
					text-align: right;
				}

					.widget ul.grid_columns .content .wp-block-button__link
					{
						font-size: .9em;
						padding: .5em 1em;
					}
}";

if($arr_breakpoints['mobile'] > 0 && $arr_breakpoints['tablet'] > $arr_breakpoints['mobile'])
{
	echo "@media screen and (min-width: ".$arr_breakpoints['mobile'].$arr_breakpoints['suffix'].") and (max-width: ".($arr_breakpoints['tablet'] - 1).$arr_breakpoints['suffix'].")
	{
		.widget.masonry ul.grid_columns
		{
			column-count: ".$setting_tablet_columns.";
		}

		.widget.square ul.grid_columns li
		{
			width: ".$column_width_tablet."%;
		}
	}";
}

if($arr_breakpoints['mobile'] > 0)
{
	echo "@media screen and (max-width: ".($arr_breakpoints['mobile'] - 1).$arr_breakpoints['suffix'].")
	{
		.widget.masonry ul.grid_columns
		{
			column-count: ".$setting_mobile_columns.";
		}

		.widget.square ul.grid_columns li
		{
			width: ".$column_width_mobile."%;
		}
	}";
}