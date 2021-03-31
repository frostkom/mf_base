<?php

if(!defined('ABSPATH'))
{
	header("Content-Type: text/css; charset=utf-8");

	$plugin_fonts_url = "/wp-content/plugins/mf_base/fonts/";
}

else
{
	$plugin_fonts_url = str_replace("/include/", "/fonts/", plugin_dir_url(__FILE__));
}

 echo "@font-face {
	font-family: 'icomoon';
	src:url('".$plugin_fonts_url."icomoon.eot?p0ysti');
	src:url('".$plugin_fonts_url."icomoon.eot?p0ysti#iefix') format('embedded-opentype'),
		url('".$plugin_fonts_url."icomoon.ttf?p0ysti') format('truetype'),
		url('".$plugin_fonts_url."icomoon.woff?p0ysti') format('woff'),
		url('".$plugin_fonts_url."icomoon.svg?p0ysti#icomoon') format('svg');
	font-weight: normal;
	font-style: normal;
}";

?>

[class^="icon-"], [class*=" icon-"]
{
	font-family: 'icomoon';
	speak: none;
	font-style: normal;
	font-weight: normal;
	font-variant: normal;
	text-transform: none;
	line-height: 1.1;

	/* Better Font Rendering */
	-webkit-font-smoothing: antialiased;
	-moz-osx-font-smoothing: grayscale;
}

.icon-accessible:before {
	content: "\e900";
}
.icon-bed:before {
	content: "\e901";
}
.icon-chair:before {
	content: "\e902";
}
.icon-clock:before {
	content: "\e903";
}
.icon-close:before {
	content: "\e904";
}
.icon-drink:before {
	content: "\e905";
}
.icon-exclusive:before {
	content: "\e906";
}
.icon-food:before {
	content: "\e907";
}
.icon-food-n-drink:before {
	content: "\e908";
}
.icon-home:before {
	content: "\e909";
}
.icon-info:before {
	content: "\e90a";
}
.icon-music:before {
	content: "\e90b";
}
.icon-parking:before {
	content: "\e90c";
}
.icon-person:before {
	content: "\e90d";
}
.icon-price:before {
	content: "\e90e";
}
.icon-transportation:before {
	content: "\e90f";
}