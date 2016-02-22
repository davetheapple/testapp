<?php
	// Barebones CMS
	// (C) 2015 CubicleSoft.  All Rights Reserved.

	if (!defined("LANG_PATH"))  define("LANG_PATH", "lang");

	// Only for use in scripts that automate tasks outside of Barebones CMS core.
	global $bb_paths;
	$bb_paths = array(
		"ROOT_PATH" => ROOT_PATH,
		"ROOT_URL" => ROOT_URL,
		"SUPPORT_PATH" => SUPPORT_PATH,
		"WIDGET_PATH" => WIDGET_PATH,
		"PLUGIN_PATH" => PLUGIN_PATH,
		"LANG_PATH" => LANG_PATH
	);
?>