<?php
	// Barebones CMS
	// (C) 2011 CubicleSoft.  All Rights Reserved.

	if (!defined("BB_FILE"))  exit();

	function BB_GetInitClientLangMap($lang)
	{
		global $bb_page;

		$lang = preg_replace('/[^a-z]/', '_', strtolower(trim($lang)));
		if (isset($bb_page["langs"][$lang]))  return (is_string($bb_page["langs"][$lang]) ? $bb_page["langs"][$lang] : $lang);

		$pos = strpos($lang, "_");
		if ($pos !== false)
		{
			$lang = substr($lang, 0, $pos);

			if (isset($bb_page["langs"][$lang]))  return (is_string($bb_page["langs"][$lang]) ? $bb_page["langs"][$lang] : $lang);
		}

		return false;
	}

	function BB_GetInitClientLang()
	{
		global $bb_page;

		if (isset($bb_page["onelang"]) && $bb_page["onelang"])  return $bb_page["defaultlang"];
		else if (isset($_REQUEST["lang"]))
		{
			$lang = BB_GetInitClientLangMap($_REQUEST["lang"]);
			if ($lang !== false)  return $lang;
		}
		else if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]))
		{
			$langs = explode(",", $_SERVER["HTTP_ACCEPT_LANGUAGE"]);
			foreach ($langs as $lang)
			{
				$lang = trim($lang);
				$pos = strpos($lang, ";");
				if ($pos !== false)  $lang = substr($lang, 0, $pos);
				$lang = BB_GetInitClientLangMap($lang);
				if ($lang !== false)  return $lang;
			}
		}

		return $bb_page["defaultlang"];
	}

	$bb_profiles = array("" => "Default");
	$bb_profile = "";
	if (file_exists("main_hook.php"))  require_once "main_hook.php";

	if ((isset($_REQUEST["action"]) && $bb_page["redirect"] == "") || isset($_REQUEST["bb_action"]))  $bb_pref_lang = false;
	else
	{
		if ($bb_page["redirect"] != "")
		{
			header("Location: " . $bb_page["redirect"]);
			exit();
		}

		$lang = $bb_pref_lang = BB_GetInitClientLang();
		if (file_exists($bb_dir . "/" . $bb_file . "_" . $lang . ($bb_profile != "" ? "." . $bb_profile : "") . ".html"))
		{
			if (isset($bb_page["langs"][$lang][$bb_profile]))
			{
				require_once "lastupdated.php";
				$bb_lastcached = $bb_page["langs"][$lang][$bb_profile];
				if ($bb_lastwidgetupdate < $bb_lastcached[0] && ($bb_page["cachetime"] < 0 || $bb_lastcached[0] + $bb_page["cachetime"] > time()))
				{
					if (isset($_SERVER["HTTP_IF_MODIFIED_SINCE"]) && $_SERVER["HTTP_IF_MODIFIED_SINCE"] == $bb_lastcached[1])  header("HTTP/1.1 304 Not Modified");
					else
					{
						header("Last-Modified: " . $bb_lastcached[1]);
						header("Content-Type: text/html; charset=UTF-8");
						readfile($bb_dir . "/" . $bb_file . "_" . $lang . ($bb_profile != "" ? "." . $bb_profile : "") . ".html");
					}
					exit();
				}
			}
		}
	}

	require_once "proc.php";
?>