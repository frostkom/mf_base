<?php

if(!defined('ABSPATH'))
{
	header("Content-Type: text/css; charset=utf-8");

	$folder = str_replace("/wp-content/plugins/mf_base/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

$arr_breakpoints = apply_filters('get_layout_breakpoints', ['tablet' => 1200, 'mobile' => 930, 'suffix' => "px"]);

echo "@media all
{
	html
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

	/*.display_inline
	{
		display: inline !important;
	}*/

	.nowrap
	{
		white-space: nowrap;
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

	.color_sunday, .mf_form .asterisk
	{
		color: #f00 !important;
		font-weight: bold;
		margin-left: .2em;
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

	.strong, .bold
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

	/*.rwmb-text, .rwmb-email, .rwmb-date, .rwmb-url, .rwmb-date, .rwmb-address
	{
		width: 100%;
	}*/

	.image_fallback
	{
		background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0naHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmcnIHhtbG5zOnhsaW5rPSdodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rJyB2aWV3Qm94PScwIDAge3t3fX0ge3tofX0nPjxkZWZzPjxzeW1ib2wgaWQ9J2EnIHZpZXdCb3g9JzAgMCA5MCA2Nicgb3BhY2l0eT0nMC4zJz48cGF0aCBkPSdNODUgNXY1Nkg1VjVoODBtNS01SDB2NjZoOTBWMHonLz48Y2lyY2xlIGN4PScxOCcgY3k9JzIwJyByPSc2Jy8+PHBhdGggZD0nTTU2IDE0TDM3IDM5bC04LTYtMTcgMjNoNjd6Jy8+PC9zeW1ib2w+PC9kZWZzPjx1c2UgeGxpbms6aHJlZj0nI2EnIHdpZHRoPScyMCUnIHg9JzQwJScvPjwvc3ZnPg==');
		padding: 3em 0;
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

			/*.flex_flow > h3
			{
				line-height: 2.1;
			}*/

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
	/* ####################### */

	/* Forms */
	/* ####################### */
	.mf_form
	{
		overflow: hidden;
	}

		.mf_form > div
		{
			clear: both;
		}

		.mf_form label
		{
			color: inherit;
			cursor: pointer;
			display: block;
			line-height: 1.8;
		}

		.mf_form .mf_form_field/*, .mf_form div.input, .mf_form p.input, #comments #comment*/
		{
			background: #fff;
			background: rgba(255, 255, 255, .9);
			border: .1em solid #e1e1e1;
			box-sizing: border-box;
			display: inline-block;
			font: inherit;
			margin: 0 0 .8em;
			padding: .4em;
			width: 100%;
		}

			.mf_form .mf_form_field.green
			{
				border-color: #76e476;
				position: relative;
			}

			.mf_form .mf_form_field.red
			{
				border-color: #e47676;
			}

			/*.mf_form div.input, .mf_form p.input
			{
				border-radius: .4em;
			}*/

			.mf_form label .maxlength_counter
			{
				display: inline-block;
				font-size: .8em;
				font-style: italic;
				margin-left: .5em;
				opacity: .5;
			}

			.mf_form .has_suffix .mf_form_field
			{
				width: auto;
				max-width: 80%;
			}

				.mf_form .has_suffix span.description, .rwmb-field .has_suffix span.description
				{
					margin-left: 1em;
				}

			.mf_form .form_select select
			{
				appearance: none;
				background-repeat: no-repeat;
				cursor: pointer;

				/* CSS gradients */
				background-image: linear-gradient(45deg, transparent 50%, #999 50%), linear-gradient(135deg, #999 50%, transparent 50%), linear-gradient(to right, #ccc, #ccc);
				background-position: calc(100% - 1.1em) center, calc(100% - .7em) center, calc(100% - 2.2em) center;
				background-size: .4em .4em, .4em .4em, .1em 1.5em;
				padding-right: 2.6em;
			}

				.mf_form .form_select select:focus
				{
					background-image: linear-gradient(45deg, #999 50%, transparent 50%), linear-gradient(135deg, transparent 50%, #999 50%), linear-gradient(to right, #ccc, #ccc);
					background-position: calc(100% - .7em) center, calc(100% - 1.1em) center, calc(100% - 2.2em) center;
					outline: 0;
				}

				.mf_form .form_select select option.is_disabled
				{
					background: #eee;
					color: #ccc;
				}

				.mf_form .form_select_multiple > select
				{
					background-image: none !important;
					padding-right: 0;
				}

			.mf_form .mf_form_field[type='color']
			{
				height: 2em;
				width: 4em;
			}

			.mf_form .mf_form_field[type='date'], .mf_form .mf_form_field[type='time']
			{
				appearance: none;
			}

		.mf_form .wp-editor-wrap
		{
			margin: 0 0 .8em;
		}

			.mf_form .mf_form_field:focus, .mf_form select:focus
			{
				border-color: #999;
				outline: none;
			}

		.mf_form .form_textfield .description, .mf_form .form_select .description, .mf_form .form_textarea .description, .mf_form .form_password .description
		{
			font-size: .7em;
			margin: -.6em 0 1em;
		}

			.mf_form .form_textfield > i
			{
				position: absolute;
				margin: .7em -1.3em;
			}

			.mf_form .description .fa
			{
				line-height: 0;
			}

		.mf_form .form_checkbox
		{
			margin: 0 0 .4em;
		}

			.mf_form .form_checkbox_multiple > label, .mf_form .form_radio_multiple > label
			{
				font-weight: bold;
			}

			.mf_form .form_checkbox_multiple > ul, .mf_form .form_radio_multiple > ul
			{
				list-style: none;
				margin: 0 0 .5em;
				padding: 0;
			}

				.mf_form .form_checkbox_multiple .form_checkbox, .mf_form .form_radio_multiple .form_radio
				{
					white-space: nowrap;
				}

			.mf_form .form_checkbox label, .mf_form .form_radio label
			{
				display: inline;
			}

			.mf_form .form_checkbox input, .mf_form .form_radio input
			{
				display: inline-block;
				margin-right: .6em;
			}

				.mf_form .input-buttons.input-button-size-2
				{
					margin-left: -43px;
				}

		.mf_form .wp-block-button .wp-block-button__link
		{
			height: auto; /* Has to be here since WP sets height to 100% */
			width: auto;
		}

		.form_button button, .form_button .button, .wp-block-button button, .wp-block-button .button
		{
			cursor: pointer;
			display: inline-block;
			white-space: nowrap;
		}

			.form_button button, .form_button .button
			{
				margin: 0 .5em .5em 0;
				padding: .5em 1.5em;
			}

			button.is_disabled, .button.is_disabled
			{
				cursor: no-drop;
				opacity: .5;
			}

			button.loading, .button.loading
			{
				cursor: wait;
				opacity: .5;
			}

			/*.mf_form .button-primary, .mf_form .button-secondary, .mf_form .button
			{
				margin-right: .5em;
				margin-bottom: .5em;
			}

				.mf_form .button-primary:last-of-type, .mf_form .button-secondary:last-of-type, .mf_form .button:last-of-type
				{
					margin-right: 0 !important;
				}*/

				.mf_form .button-primary:disabled, .mf_form .button-secondary:disabled, .mf_form .button:disabled
				{
					filter: grayscale(1);
					opacity: .3;
				}

				.form_button button .fa, .form_button .button .fa, .form_button button .fab, .form_button .button .fab, .form_button button .far, .form_button .button .far, .form_button button .fas, .form_button .button .fas, .wp-block-button button .fa, .wp-block-button .button .fa, .wp-block-button button .fab, .wp-block-button .button .fab, .wp-block-button button .far, .wp-block-button .button .far, .wp-block-button button .fas, .wp-block-button .button .fas
				{
					margin-right: .3em;
				}

			.mf_form .button.delete
			{
				background: #ba0000;
				border-color: #a00 #900 #900;
				box-shadow: 0 .1em 0 #900;
				color: #fff;
				text-shadow: 0 -.1em .1em #900, .1em 0 .1em #900, 0 .1em .1em #900, -.1em 0 .1em #900;
			}

				.mf_form .button.delete:hover
				{
					background: #c20000;
					border-color: #900;
				}

			.mf_settings #postbox-container-1 button
			{
				margin: .6em .6em 0 0;
			}

			:root :where(.wp-block-button.is-style-outline--1 .wp-block-button__link)
			{
				background: transparent none !important;
				border-color: var(--wp--preset--color--contrast);
				border-width: .1em !important;
				border-style: solid;
				color: var(--wp--preset--color--contrast) !important;
			}
	/* ####################### */

	/* Tables */
	/* ####################### */
	body:not(.wp-admin) .widefat
	{
		border: .1em solid #e5e5e5;
		border-spacing: 0;
		/*table-layout: fixed;*/
		width: 100%;
	}

		.widefat.layout_fixed
		{
			table-layout: fixed;
		}

		/*body:not(.wp-admin) .widefat *
		{
			word-wrap: break-word;
		}*/

			body:not(.wp-admin) .widefat thead tr
			{
				background: #fff;
			}

			body:not(.wp-admin) .widefat td, body:not(.wp-admin) .widefat th
			{
				/*color: #555;*/
				padding: .8em 1em;
				vertical-align: top;
			}

			body:not(.wp-admin) .widefat th
			{
				border-bottom: .1em solid #e1e1e1;
				/*color: #32373c;
				cursor: pointer;
				font-size: .9em;*/
				overflow: hidden;
				text-align: left;
				text-overflow: ellipsis;
				white-space: nowrap;
			}

				/*body:not(.wp-admin) .widefat th:hover
				{
					white-space: normal;
				}*/

			body:not(.wp-admin) .widefat.striped > tbody > *:nth-child(2n+1)
			{
				background-color: #f9f9f9;
			}

			body:not(.wp-admin) .widefat.striped > tbody > *:nth-child(2n)
			{
				background-color: #fff;
			}

				.widefat tr.active
				{
					background: #fff8df !important;
				}

				.widefat td
				{
					/*font-family: inherit;
					font-size: inherit;
					font-weight: inherit;
					line-height: inherit;*/
					overflow: hidden;
					/*text-overflow: ellipsis;*/
				}

					/*body:not(.wp-admin) .widefat td, body:not(.wp-admin) .widefat td ol, body:not(.wp-admin) .widefat td p, body:not(.wp-admin) .widefat td ul
					{
						font-size: .8em;
					}*/

					body:not(.wp-admin) .widefat tr .row-actions
					{
						/*color: #999;
						left: -9999em;*/
						opacity: 0;
						padding: .1em 0 0;
						pointer-events: none;
						position: relative;
						white-space: nowrap;
					}

						body:not(.wp-admin) .widefat tr:hover .row-actions
						{
							opacity: 1;
							pointer-events: all;
							/*position: static;*/
						}

						body:not(.wp-admin) .widefat tr .row-actions > * + *:before
						{
							content: ' | ';
						}

						.widefat tr .row-actions a
						{
							text-decoration: none;
						}

							.widefat tr .row-actions .trash, .widefat tr .row-actions .delete
							{
								color: #b32d2e;
							}

			body:not(.wp-admin) .widefat tfoot th, body:not(.wp-admin) .widefat tbody + thead th
			{
				border-top: .1em solid #e1e1e1;
			}
	/* ####################### */
}";

if($arr_breakpoints['mobile'] > 0)
{
	echo "@media screen and (max-width: ".($arr_breakpoints['mobile'] - 1).$arr_breakpoints['suffix'].")
	{
		.flex_flow
		{
			display: block;
		}

		/*.flex_flow > *
		{
			margin-bottom: 1em;
		}*/
	}";
}

echo "@media print
{
	body
	{
		background: none !important;
	}

		#wpadminbar, header, .hide_on_print, .mf_search, footer
		{
			display: none;
		}

		.wp-block-group, .wp-block-column
		{
			background: none !important;
			padding: 0 !important;
		}
}";