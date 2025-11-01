<?php

if(!defined('ABSPATH'))
{
	header("Content-Type: text/css; charset=utf-8");

	$folder = str_replace("/wp-content/plugins/mf_base/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

$arr_breakpoints = apply_filters('get_layout_breakpoints', ['tablet' => 1200, 'mobile' => 930, 'suffix' => "px"]);

echo ".flex_flow
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
	}";

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