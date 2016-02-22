<?php
	// Barebones CMS
	// (C) 2014 CubicleSoft.  All Rights Reserved.

	if (!defined("BB_FILE"))  exit();

	// Used when some sort of dynamic action is needed or no cached page was found.
	require_once "lastupdated.php";
	require_once "config.php";
	require_once ROOT_PATH . "/" . SUPPORT_PATH . "/str_basics.php";
	require_once ROOT_PATH . "/" . SUPPORT_PATH . "/utf8.php";

	Str::ProcessAllInput();

	// Preferred language.
	if ($bb_pref_lang === false)  $bb_pref_lang = BB_GetInitClientLang();

	// Make sure the main page data is loaded.
	require_once $bb_dir . "/" . $bb_file . "_" . $bb_pref_lang . "_page.php";

	// Load initialization functions.
	require_once ROOT_PATH . "/" . SUPPORT_PATH . "/bb_init_functions.php";

	// Manage the page.
	if (isset($_REQUEST["bb_action"]))  require_once "edit.php";
	else if (isset($_REQUEST["action"]))
	{
		// Find the widget.  Can't be a widget master and must exist.
		if (isset($_REQUEST["wid"]) && isset($bb_langpage["widgets"][$_REQUEST["wid"]]))
		{
			$bb_widget->SetID($_REQUEST["wid"]);
			if ($bb_widget->_m === false && $bb_widget->_a !== false && file_exists(ROOT_PATH . "/" . WIDGET_PATH . "/" . $bb_widget->_file))
			{
				// Load widgets.
				BB_LoadWidgets();

				$bb_widget->SetID($_REQUEST["wid"]);

				$bb_widget_instances[$_REQUEST["wid"]]->ProcessAction();
			}
		}
	}
	else
	{
		// Load widgets.
		BB_LoadWidgets();

		$bb_widget->SetID("");

		$data = BB_ProcessPage($bb_page["cachetime"] != 0, true, true);

		if ($bb_page["cachetime"] != 0)
		{
			$bb_page["langs"][$bb_pref_lang][$bb_profile] = array(time(), gmdate("D, d M Y H:i:s") . " GMT");
			BB_WriteFile($bb_dir . "/" . $bb_file . "_" . $bb_pref_lang . ($bb_profile != "" ? "." . $bb_profile : "") . ".html", $data);
			BB_SavePage();
		}
	}
?>