<?php

if(!defined('ABSPATH'))
{
	header("Content-Type: text/css; charset=utf-8");

	$folder = str_replace("/wp-content/plugins/mf_base/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

global $wpdb;

$arr_breakpoints = apply_filters('get_layout_breakpoints', ['tablet' => 1200, 'mobile' => 930, 'suffix' => "px"]);

/*$theme_slug = get_stylesheet();

$styles_modified = $wpdb->get_var($wpdb->prepare("SELECT post_modified FROM ".$wpdb->posts." WHERE post_type = %s AND post_name = %s AND post_status = %s", 'wp_global_styles', 'wp-global-styles-'.$theme_slug, 'publish'));*/

echo "html
{
	overflow-y: scroll;
}

:focus-visible
{
	outline-width: .1em;
	outline-offset: 0 !important;
}

:focus:not(:focus-visible)
{
	outline: none;
}

.hide, [hidden]
{
	display: none !important;
}

.nowrap
{
	white-space: nowrap;
}

.overflow
{
	overflow: hidden;
}

.row-actions
{
	color: #999;
	white-space: nowrap;
}

.fa, .fab, .far, .fas, .pointer
{
	cursor: pointer;
}

	.fa.green, .fab.green, .far.green, .fas.green, .color_green
	{
		color: #76e476;
	}

	.fa.blue, .fab.blue, .far.blue, .fas.blue, .color_blue
	{
		color: #4887bf;
	}

	.fa.yellow, .fab.yellow, .far.yellow, .fas.yellow, .color_yellow
	{
		color: #e4d176;
	}

	.fa.red, .fab.red, .far.red, .fas.red, .color_red
	{
		color: #e47676;
	}

	.fa.white, .fab.white, .far.white, .fas.white, .color_white
	{
		color: #fff;
	}

.grey, .grey > a
{
	color: #999;
}

.light_grey, .light_grey > a
{
	color: rgba(0, 0, 0, .2);
}

.green > th, .green > td, .bg_green
{
	background: rgba(211, 255, 204, .3);
}

.blue > th, .blue > td, .bg_blue
{
	background: rgba(72, 135, 191, .3);
}

.red > th, .red > td, .bg_red
{
	background: rgba(255, 204, 204, .3);
}

.yellow > th, .yellow > td, .bg_yellow
{
	background: rgba(255, 254, 204, .3);
}

tr.inactive
{
	cursor: no-drop;
	opacity: .3;
}

	tr.inactive:hover
	{
		opacity: 1;
	}

.strong
{
	font-weight: bold !important;
}

.italic
{
	font-style: italic;
}

.aligncenter
{
	text-align: center;
}

.alignleft
{
	text-align: left;
}

.alignright
{
	text-align: right;
}

/* Flexbox */
/* ####################### */
.flex_flow
{
	display: flex;
	flex-wrap: wrap;
	gap: 1em;
}

	.flex_flow > *
	{
		display: block;
		flex: 1 1 0;
	}

		.flex_flow > *:last-child
		{
			margin-right: 0;
		}

		.flex_flow > button, .flex_flow > .button
		{
			align-self: flex-end;
			height: auto !important;
			margin-bottom: .9em !important;
		}

	.flex_flow.tight > *
	{
		flex: initial;
		margin-right: .5em;
	}
/* ####################### */";

/*if($styles_modified > "2025-09-19 09:00:00")
{
	echo ".wp-block-button .wp-block-button__link
	{
		border-width: .1em;
		border-style: solid;
		border-color: var(--wp--preset--color--accent, var(--wp--preset--color--accent-1));
		background-color: var(--wp--preset--color--accent, var(--wp--preset--color--accent-1));
		color: var(--wp--preset--color--accent-2);
	}

		.wp-block-button .wp-block-button__link:hover
		{
			background-color: var(--wp--preset--color--accent-2);
			color: var(--wp--preset--color--accent, var(--wp--preset--color--accent-1));
		}";
}*/

if($arr_breakpoints['mobile'] > 0)
{
	echo "@media screen and (max-width: ".($arr_breakpoints['mobile'] - 1).$arr_breakpoints['suffix'].")
	{
		.flex_flow
		{
			display: block;
		}
	}";
}

echo "@media print
{
	body
	{
		background: none !important;
	}

		#wpadminbar, header, footer
		{
			display: none;
		}

		.wp-block-group, .wp-block-column
		{
			background: none !important;
			padding: 0 !important;
		}
}";