<?php
	// Barebones CMS support functions.  These functions drive the core functionality.
	// (C) 2015 CubicleSoft.  All Rights Reserved.

	// Handles the cases of this file being loaded inside and outside of the editor.
	if (!function_exists("BB_RunPluginAction"))
	{
		if (defined("BB_MODE_EDIT"))
		{
			// Plugin support functions.
			$bb_plugin_actions = array();
			function BB_AddPluginAction($name, $callback, $priority = 10)
			{
				global $bb_plugin_actions;

				if (!function_exists($callback))  return false;

				if (!isset($bb_plugin_actions[$name]))  $bb_plugin_actions[$name] = array();
				if (!isset($bb_plugin_actions[$name][$priority]))
				{
					$bb_plugin_actions[$name][$priority] = array();
					ksort($bb_plugin_actions[$name]);
				}
				$bb_plugin_actions[$name][$priority][] = $callback;

				return true;
			}

			function BB_RunPluginAction($name)
			{
				global $bb_plugin_actions;

				if (isset($bb_plugin_actions[$name]))
				{
					foreach ($bb_plugin_actions[$name] as &$callbacks)
					{
						foreach ($callbacks as &$callback)  $callback();
					}
				}

				if (isset($bb_plugin_actions["global_action"]))
				{
					foreach ($bb_plugin_actions["global_action"] as &$callbacks)
					{
						foreach ($callbacks as &$callback)  $callback($name);
					}
				}
			}

			function BB_RunPluginActionInfo($name, &$info)
			{
				global $bb_plugin_actions;

				if (isset($bb_plugin_actions[$name]))
				{
					foreach ($bb_plugin_actions[$name] as &$callbacks)
					{
						foreach ($callbacks as &$callback)  $callback($info);
					}
				}

				if (isset($bb_plugin_actions["global_action_info"]))
				{
					foreach ($bb_plugin_actions["global_action_info"] as &$callbacks)
					{
						foreach ($callbacks as &$callback)  $callback($name, $info);
					}
				}
			}
		}
		else
		{
			function BB_RunPluginAction($name)
			{
			}

			function BB_RunPluginActionInfo($name, &$info)
			{
			}
		}
	}

	if (!function_exists("BB_CreatePHPStorageData"))
	{
		function BB_CreatePHPStorageData($data)
		{
			if (!defined("USE_LESS_SAFE_STORAGE") || !USE_LESS_SAFE_STORAGE)  return "unserialize(base64_decode(\"" . base64_encode(serialize($data)) . "\"))";

			ob_start();
			var_export($data);
			$data = ob_get_contents();
			ob_end_clean();

			return $data;
		}
	}

	global $bb_writeperms;
	if (!defined("WRITE_PERMS") || WRITE_PERMS === "o")  $bb_writeperms = 0200;
	else if (WRITE_PERMS === "g")  $bb_writeperms = 0220;
	else if (WRITE_PERMS === "w")  $bb_writeperms = 0222;
	else  $bb_writeperms = 0200;

	if (!function_exists("BB_WriteFile"))
	{
		function BB_WriteFile($filename, $data)
		{
			global $bb_writeperms;

			$result = file_put_contents($filename, $data);
			if ($result === false)  return false;
			@chmod($filename, 0444 | $bb_writeperms);

			return $result;
		}
	}

	// Drop-in replacement for hash_hmac() on hosts where Hash is not available.
	// Only supports HMAC-MD5 and HMAC-SHA1.
	if (!function_exists("hash_hmac"))
	{
		function hash_hmac($algo, $data, $key, $raw_output = false)
		{
			$algo = strtolower($algo);
			$size = 64;
			$opad = str_repeat("\x5C", $size);
			$ipad = str_repeat("\x36", $size);

			if (strlen($key) > $size)  $key = $algo($key, true);
			$key = str_pad($key, $size, "\x00");

			$y = strlen($key) - 1;
			for ($x = 0; $x < $y; $x++)
			{
				$opad[$x] = $opad[$x] ^ $key[$x];
				$ipad[$x] = $ipad[$x] ^ $key[$x];
			}

			$result = $algo($opad . $algo($ipad . $data, true), $raw_output);

			return $result;
		}
	}

	// Multilingual admin functions.
	function BB_Translate()
	{
		global $bb_admin_lang, $bb_admin_def_lang, $bb_langmap;

		$args = func_get_args();
		if (!count($args) || $args[0] == "")  return "";
		if (isset($bb_admin_lang) && isset($bb_admin_def_lang) && isset($bb_langmap))
		{
			$arg = $args[0];
			if (isset($bb_langmap[$bb_admin_lang]) && isset($bb_langmap[$bb_admin_lang][$arg]))  $args[0] = $bb_langmap[$bb_admin_lang][$arg];
			else if (isset($bb_langmap[$bb_admin_def_lang]) && isset($bb_langmap[$bb_admin_def_lang][$arg]))  $args[0] = $bb_langmap[$bb_admin_def_lang][$arg];
			else if (isset($bb_langmap[""][$arg]))  $args[0] = $bb_langmap[""][$arg];
			else if (function_exists("BB_Untranslated"))  BB_Untranslated($args);
		}
		if (count($args) == 1 && strpos($args[0], "%") !== false)  $args[0] = str_replace("%", "%%", $args[0]);

		return call_user_func_array("sprintf", $args);
	}

	function BB_PostTranslate($str)
	{
		global $bb_admin_lang, $bb_admin_def_lang, $bb_langmap;

		if (isset($bb_admin_lang) && isset($bb_admin_def_lang) && isset($bb_langmap))
		{
			if (isset($bb_langmap[$bb_admin_lang]) && isset($bb_langmap[$bb_admin_lang][""]) && is_array($bb_langmap[$bb_admin_lang][""]))  $str = str_replace($bb_langmap[$bb_admin_lang][""][0], $bb_langmap[$bb_admin_lang][""][1], $str);
			else if (isset($bb_langmap[$bb_admin_def_lang]) && isset($bb_langmap[$bb_admin_def_lang][""]) && is_array($bb_langmap[$bb_admin_def_lang][""]))  $str = str_replace($bb_langmap[$bb_admin_def_lang][""][0], $bb_langmap[$bb_admin_def_lang][""][1], $str);
			else if (isset($bb_langmap[""][""]) && is_array($bb_langmap[""][""]))  $str = str_replace($bb_langmap[""][""][0], $bb_langmap[""][""][1], $str);
		}

		return $str;
	}

	function BB_FormatDate($ts)
	{
		return BB_PostTranslate(date(BB_Translate("M j, Y"), $ts));
	}

	function BB_FormatTimestamp($ts)
	{
		return BB_PostTranslate(date(BB_Translate("M j, Y, g:i A"), $ts));
	}

	function BB_SetLanguage($path, $lang)
	{
		global $bb_langmap, $bb_admin_lang;

		$lang = preg_replace('/\s+/', "_", trim(preg_replace('/[^a-z]/', " ", strtolower($lang))));
		if ($lang == "")
		{
			$path .= "default/";
		}
		else
		{
			if ($lang == "default")  return array("success" => false, "error" => "Invalid language.");
			$path .= $lang . "/";
		}

		if (isset($bb_langmap[$lang]))
		{
			if ($lang != "")  $bb_admin_lang = $lang;

			return array("success" => true);
		}
		$bb_langmap[$lang] = array();

		$dir = @opendir($path);
		if ($dir === false)  return array("success" => false, "error" => "Directory does not exist.", "info" => $path);

		while (($file = readdir($dir)) !== false)
		{
			if (strtolower(substr($file, -4)) == ".php")  require_once $path . $file;
		}

		closedir($dir);

		if (isset($bb_langmap[$lang][""]) && is_array($bb_langmap[$lang][""]))  $bb_langmap[$lang][""] = array(array_keys($bb_langmap[$lang][""]), array_values($bb_langmap[$lang][""]));

		$bb_admin_lang = $lang;

		return array("success" => true);
	}

	function BB_InitLangmap($path, $default = "")
	{
		global $bb_admin_lang, $bb_admin_def_lang, $bb_langmap;

		$bb_langmap = array();
		BB_SetLanguage($path, "");
		if ($default != "")  BB_SetLanguage($path, $default);
		$bb_admin_def_lang = $bb_admin_lang;
		if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]))
		{
			$langs = explode(",", $_SERVER["HTTP_ACCEPT_LANGUAGE"]);
			foreach ($langs as $lang)
			{
				$lang = trim($lang);
				$pos = strpos($lang, ";");
				if ($pos !== false)  $lang = substr($lang, 0, $pos);
				if ($lang != "")
				{
					$result = BB_SetLanguage($path, $lang);
					if ($result["success"])  break;
				}
			}
		}
	}

	// Account functions.
	function BB_CreateUser($type, $username, $password, $group = "")
	{
		global $bb_accounts;

		require_once ROOT_PATH . "/" . SUPPORT_PATH . "/random.php";

		// Don't overwrite existing users and users must have a username and password.
		if ($type != "dev" && $type != "design" && $type != "content")  return false;
		$username = trim($username);
		$password = trim($password);
		$group = trim($group);
		if (isset($bb_accounts["users"][$username]) || $username == "" || $password == "")  return false;

		$rng = new CSPRNG(false);

		$bb_accounts["users"][$username] = array("type" => $type, "user" => $username, "pass" => sha1($username . ":" . $password), "lang" => "", "group" => $group, "session" => $rng->GenerateToken());

		BB_RunPluginActionInfo("post_bb_createuser", $bb_accounts["users"][$username]);

		return BB_SaveUserAccounts();
	}

	function BB_SetUserPassword($username, $password)
	{
		global $bb_accounts;

		require_once ROOT_PATH . "/" . SUPPORT_PATH . "/random.php";

		$username = trim($username);
		$password = trim($password);
		if (!isset($bb_accounts["users"][$username]) || $password == "")  return false;

		BB_RunPluginActionInfo("pre_bb_setuserpassword", $bb_accounts["users"][$username]);

		$rng = new CSPRNG(false);

		$bb_accounts["users"][$username]["pass"] = sha1($username . ":" . $password);
		$bb_accounts["users"][$username]["session"] = $rng->GenerateToken();
		foreach ($bb_accounts["sessions"] as $id => $info)
		{
			if ($info["username"] == $username)  unset($bb_accounts["sessions"][$id]);
		}

		BB_RunPluginActionInfo("post_bb_setuserpassword", $bb_accounts["users"][$username]);

		return BB_SaveUserAccounts();
	}

	function BB_SetUserLanguage($username, $lang)
	{
		global $bb_accounts, $bb_admin_pref_lang;

		if (!isset($bb_accounts["users"][$username]))  return false;

		$result = BB_SetLanguage($lang);
		if (!$result["success"])  return false;

		BB_RunPluginActionInfo("pre_bb_setuserlanguage", $bb_accounts["users"][$username]);

		$bb_accounts["users"][$username]["lang"] = $bb_admin_pref_lang;

		BB_RunPluginActionInfo("post_bb_setuserlanguage", $bb_accounts["users"][$username]);

		return BB_SaveUserAccounts();
	}

	function BB_NewUserSession($username, $oldid)
	{
		global $bb_accounts;

		$oldid = trim($oldid);
		if (!isset($bb_accounts["users"][$username]))  return false;

		require_once ROOT_PATH . "/" . SUPPORT_PATH . "/random.php";

		$tempinfo = array($username, $oldid);
		BB_RunPluginActionInfo("pre_bb_newusersession", $tempinfo);

		// Delete the old session.
		if ($oldid != "")
		{
			if (isset($bb_accounts["sessions"][$oldid]) && $bb_accounts["sessions"][$oldid]["username"] == $username)  unset($bb_accounts["sessions"][$oldid]);
			else  return false;
		}

		$rng = new CSPRNG(false);

		do
		{
			$newid = $rng->GenerateToken();
			do
			{
				$newid2 = $rng->GenerateToken();
			} while ($newid == $newid2);
		} while (isset($bb_accounts["sessions"][$newid]));

		switch ($bb_accounts["users"][$username]["type"])
		{
			case "dev":  $expire = time() + DEV_SESSION_TIMEOUT;  break;
			case "design":  $expire = time() + DESIGN_SESSION_TIMEOUT;  break;
			case "content":  $expire = time() + CONTENT_SESSION_TIMEOUT;  break;
			default:  return false;
		}
		$bb_accounts["sessions"][$newid] = array("username" => $username, "session2" => $newid2, "expire" => $expire);

		BB_RunPluginActionInfo("post_bb_newusersession", $newid);

		if (!BB_SaveUserAccounts())  return false;

		return $newid;
	}

	function BB_LogoutUserSession($username, $oldid)
	{
		global $bb_accounts;

		$oldid = trim($oldid);
		if (!isset($bb_accounts["users"][$username]) || $oldid == "" || !isset($bb_accounts["sessions"][$oldid]) || $bb_accounts["sessions"][$oldid]["username"] != $username)  return false;

		$tempinfo = array($username, $oldid);
		BB_RunPluginActionInfo("pre_bb_logoutusersession", $tempinfo);

		unset($bb_accounts["sessions"][$oldid]);

		BB_RunPluginActionInfo("post_bb_logoutusersession", $tempinfo);

		return BB_SaveUserAccounts();
	}

	function BB_DeleteExpiredUserSessions()
	{
		global $bb_accounts;

		BB_RunPluginAction("pre_bb_deleteexpiredusersessions");

		$found = false;
		foreach ($bb_accounts["sessions"] as $id => $info)
		{
			if ($info["expire"] < time())
			{
				unset($bb_accounts["sessions"][$id]);
				$found = true;
			}
		}

		BB_RunPluginAction("post_bb_deleteexpiredusersessions");

		return (!$found || BB_SaveUserAccounts());
	}

	function BB_DeleteAllUserSessions()
	{
		global $bb_accounts;

		BB_RunPluginAction("pre_bb_deleteallusersessions");

		$bb_accounts["sessions"] = array();

		BB_RunPluginAction("post_bb_deleteallusersessions");

		return BB_SaveUserAccounts();
	}

	function BB_DeleteUser($username)
	{
		global $bb_accounts;

		BB_RunPluginActionInfo("pre_bb_deleteuser", $username);

		$username = trim($username);
		unset($bb_accounts["users"][$username]);
		foreach ($bb_accounts["sessions"] as $id => $info)
		{
			if ($info["username"] == $username)  unset($bb_accounts["sessions"][$id]);
		}

		BB_RunPluginActionInfo("post_bb_deleteuser", $username);

		return BB_SaveUserAccounts();
	}

	function BB_SaveUserAccounts()
	{
		global $bb_accounts, $bb_paths;

		BB_RunPluginAction("pre_bb_saveuseraccounts");

		$data = "<" . "?php\n\t\$bb_accounts = " . BB_CreatePHPStorageData($bb_accounts) . ";\n?" . ">";
		if (BB_WriteFile((isset($bb_paths) ? $bb_paths["ROOT_PATH"] : ROOT_PATH) . "/accounts.php", $data) === false)  return false;

		return true;
	}

	function BB_GetAccountGroups()
	{
		global $bb_accounts;

		$groups = array();
		foreach ($bb_accounts["users"] as $user => $account)
		{
			if ($account["type"] == "content")  $groups[$account["group"]] = $account["group"];
		}
		ksort($groups);

		return $groups;
	}

	// Gets the client's (web browser) preferred languages.
	function BB_ExtractClientLanguages()
	{
		if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]))
		{
			$langs = explode(",", $_SERVER["HTTP_ACCEPT_LANGUAGE"]);
			$y = count($langs);
			for ($x = 0; $x < $y; $x++)
			{
				$lang = trim($langs[$x]);
				$pos = strpos($lang, ";");
				if ($pos !== false)  $lang = substr($lang, 0, $pos);
				$langs[$x] = preg_replace('/[^a-z]/', '_', strtolower($lang));
			}
		}
		else  $langs = array();

		return $langs;
	}

	function BB_MakePageDirs(&$bb_dir)
	{
		global $bb_writeperms, $bb_paths;

		$bb_dir = str_replace("\\", "/", trim($bb_dir));
		if (substr($bb_dir, 0, 1) == "/")  $bb_dir = substr($bb_dir, 1);
		if (substr($bb_dir, -1) == "/")  $bb_dir = substr($bb_dir, 0, -1);
		if ($bb_dir == "")  $bb_dir = ".";
		if ($bb_dir == ".")  $bb_relroot = "";
		else
		{
			$bb_relroot = implode("/", array_fill(0, count(explode("/", $bb_dir)), ".."));
			$dirname = (isset($bb_paths) ? $bb_paths["ROOT_PATH"] : ROOT_PATH) . "/" . $bb_dir;
			if (!is_dir($dirname))
			{
				mkdir($dirname, 0777, true);
				@chmod($dirname, 0555 | $bb_writeperms);
			}
		}

		return $bb_relroot;
	}

	function BB_CreatePage($bb_dir, $bb_file)
	{
		if (defined("DEFAULT_PAGE_LANG") && DEFAULT_PAGE_LANG != "")  $bb_pref_lang = DEFAULT_PAGE_LANG;
		else
		{
			$clientlangs = BB_ExtractClientLanguages();
			$bb_pref_lang = (count($clientlangs) ? $clientlangs[0] : "en");
		}
		BB_RunPluginActionInfo("bb_createpage_pref_lang", $bb_pref_lang);
		$bb_page = array(
			"ver" => 1.0,
			"redirect" => "",
			"cachetime" => -1,
			"easyedit" => true,
			"sitemap" => false,
			"sitemappriority" => "normal",
			"doctype" => "HTML 5",
			"metarobots" => "",
			"perms" => array(),
			"langs" => array($bb_pref_lang => array()),
			"onelang" => true,
			"defaultlang" => $bb_pref_lang
		);

		// Map 'en' to 'en_us'.
		if (strpos($bb_pref_lang, "_"))  $bb_page["langs"][substr($bb_pref_lang, 0, strpos($bb_pref_lang, "_"))] = $bb_pref_lang;

		BB_RunPluginActionInfo("bb_createpage_bb_page", $bb_page);

		require_once ROOT_PATH . "/" . SUPPORT_PATH . "/random.php";

		$rng = new CSPRNG(false);

		$bb_langpage = array(
			"title" => "",
			"metadesc" => "",
			"widgets" => array("root" => array("_f" => "Root/Page", "_m" => true, "_a" => "root", "_id" => "root", "_ids" => array())),
			"pagerand" => $rng->GenerateToken()
		);

		BB_RunPluginActionInfo("bb_createpage_bb_langpage", $bb_langpage);

		$bb_langpagerevisions = array(
			"rootrev" => 0,
			"branches" => array(),
			"revisions" => array(array("", serialize($bb_langpage), time(), time(), "Initial Page"))
		);

		BB_RunPluginActionInfo("bb_createpage_bb_langpagerevisions", $bb_langpagerevisions);

		$bb_relroot = BB_MakePageDirs($bb_dir);

		$data = "<" . "?php\n";
		$data .= "\tdefine(\"BB_FILE\", 1);\n";
		$data .= "\trequire_once \"" . $bb_file . "_page.php\";\n";
		if ($bb_relroot != "")  $data .= "\tchdir(\$bb_relroot);\n";
		$data .= "\trequire_once \"main.php\";\n";
		$data .= "?" . ">";
		if (BB_WriteFile($bb_dir . "/" . $bb_file . ".php", $data) === false)  return false;

		$data = "<" . "?php\n";
		$data .= "\t\$bb_dir = \"" . $bb_dir . "\";\n";
		$data .= "\t\$bb_file = \"" . $bb_file . "\";\n";
		$data .= "\t\$bb_relroot = \"" . $bb_relroot . "\";\n";
		$data .= "\t\$bb_page = " . BB_CreatePHPStorageData($bb_page) . ";\n";
		$data .= "?" . ">";
		if (BB_WriteFile($bb_dir . "/" . $bb_file . "_page.php", $data) === false)  return false;

		$data = "<" . "?php\n\t\$bb_langpage = " . BB_CreatePHPStorageData($bb_langpage) . ";\n?" . ">";
		if (BB_WriteFile($bb_dir . "/" . $bb_file . "_" . $bb_pref_lang . "_page.php", $data) === false)  return false;

		$data = "<" . "?php\n\t\$bb_langpagerevisions = " . BB_CreatePHPStorageData($bb_langpagerevisions) . ";\n?" . ">";
		if (BB_WriteFile($bb_dir . "/" . $bb_file . "_" . $bb_pref_lang . "_rev.php", $data) === false)  return false;

		BB_RunPluginAction("post_bb_createpage");

		return true;
	}

	function BB_SaveLangPage($revnum)
	{
		global $bb_dir, $bb_file, $bb_pref_lang, $bb_langpage, $bb_revision_num, $bb_revision, $bb_langpagerevisions, $bb_widget, $bb_widget_id;

		if (!BB_IsRevisionWriteable($revnum))  return false;

		if (isset($bb_widget) && isset($bb_widget_id) && $bb_widget_id != "")  $bb_widget->Save();

		if ($revnum < 0)
		{
			$data = "<" . "?php\n\t\$bb_langpage = " . BB_CreatePHPStorageData($bb_langpage) . ";\n?" . ">";
			if (BB_WriteFile($bb_dir . "/" . $bb_file . "_" . $bb_pref_lang . "_page.php", $data) === false)  return false;

			BB_DeletePageCache();

			BB_RunPluginAction("post_bb_savelangpage");
		}
		else
		{
			$bb_langpagerevisions["revisions"][$revnum][1] = serialize($bb_langpage);
			$bb_langpagerevisions["revisions"][$revnum][3] = time();

			if ($revnum == $bb_revision_num)  $bb_revision = $bb_langpagerevisions["revisions"][$revnum];

			BB_RunPluginActionInfo("post_bb_savelangpage_revision", $revnum);

			return BB_SaveRevisions();
		}

		return true;
	}

	function BB_IsRevisionWriteable($revnum)
	{
		global $bb_langpagerevisions;

		if ($revnum < 0)  return true;
		if (!isset($bb_langpagerevisions["revisions"][$revnum]))  return false;

		$revision = $bb_langpagerevisions["revisions"][$revnum];
		if ($revision[0] == "" && $bb_langpagerevisions["rootrev"] != $revnum)  return false;
		if ($revision[0] != "" && (!isset($bb_langpagerevisions["branches"][$revision[0]]) || $bb_langpagerevisions["branches"][$revision[0]][0] != $revnum))  return false;

		return true;
	}

	function BB_CreateRevision($reason, $name = false)
	{
		global $bb_langpage, $bb_revision, $bb_langpagerevisions;

		// Live site, Root, and active branches can have revisions.
		if ($name !== false && ($name == "" || isset($bb_langpagerevisions["branches"][$name])))
		{
			if ($name != "")  $bb_langpagerevisions["branches"][$name][0] = count($bb_langpagerevisions["revisions"]);
		}
		else if ($bb_revision === false || $bb_revision[0] == "")  $name = "";
		else if (!isset($bb_langpagerevisions["branches"][$bb_revision[0]]))  return false;
		else
		{
			$bb_langpagerevisions["branches"][$bb_revision[0]][0] = count($bb_langpagerevisions["revisions"]);
			$name = $bb_revision[0];
		}

		if ($name == "")  $bb_langpagerevisions["rootrev"] = count($bb_langpagerevisions["revisions"]);
		$bb_langpagerevisions["revisions"][] = array($name, serialize($bb_langpage), time(), time(), $reason);

		BB_RunPluginAction("post_bb_createrevision");

		return BB_SaveRevisions();
	}

	function BB_CreateRevisionBranch($name)
	{
		global $bb_langpage, $bb_revision, $bb_langpagerevisions;

		if (isset($bb_langpagerevisions["branches"][$name]))  return false;

		$bb_langpagerevisions["branches"][$name] = array(count($bb_langpagerevisions["revisions"]), ($bb_revision === false ? "" : $bb_revision[0]));
		$bb_langpagerevisions["revisions"][] = array($name, serialize($bb_langpage), time(), time(), "Created Branch");

		BB_RunPluginAction("post_bb_createrevisionbranch");

		return BB_SaveRevisions();
	}

	function BB_CloseRevisionBranch($name)
	{
		global $bb_langpagerevisions;

		if (!isset($bb_langpagerevisions["branches"][$name]))  return false;

		BB_RunPluginActionInfo("pre_bb_closerevisionbranch", $name);

		unset($bb_langpagerevisions["branches"][$name]);

		BB_RunPluginActionInfo("post_bb_closerevisionbranch", $name);

		return BB_SaveRevisions();
	}

	function BB_SaveRevisions()
	{
		global $bb_dir, $bb_file, $bb_pref_lang, $bb_langpagerevisions;

		$data = "<" . "?php\n\t\$bb_langpagerevisions = " . BB_CreatePHPStorageData($bb_langpagerevisions) . ";\n?" . ">";
		if (BB_WriteFile($bb_dir . "/" . $bb_file . "_" . $bb_pref_lang . "_rev.php", $data) === false)  return false;

		BB_RunPluginAction("post_bb_saverevisions");

		return true;
	}

	function BB_AddTranslationNotification($lang, $reason)
	{
		global $bb_dir, $bb_file, $bb_pref_lang, $bb_account, $bb_translate_notify;

		$bb_translate_notify[] = array((isset($bb_account) ? $bb_account["user"] : ""), time(), $bb_dir, $bb_file, $bb_pref_lang, $lang, $reason);

		BB_RunPluginAction("post_bb_addtranslationnotification");

		return BB_SaveTranslationNotifications();
	}

	function BB_DeleteTranslationNotification($num)
	{
		global $bb_translate_notify;

		BB_RunPluginActionInfo("pre_bb_deletetranslationnotification", $num);

		unset($bb_translate_notify[$num]);

		BB_RunPluginActionInfo("post_bb_deletetranslationnotification", $num);

		return BB_SaveTranslationNotifications();
	}

	function BB_SaveTranslationNotifications()
	{
		global $bb_translate_notify, $bb_paths;

		$data = "<" . "?php\n\t\$bb_translate_notify = " . BB_CreatePHPStorageData($bb_translate_notify) . ";\n?" . ">";
		if (BB_WriteFile((isset($bb_paths) ? $bb_paths["ROOT_PATH"] : ROOT_PATH) . "/translate.php", $data) === false)  return false;

		BB_RunPluginAction("post_bb_savetranslationnotifications");

		return true;
	}

	function BB_GetCleanLang($lang)
	{
		$lang = trim($lang);
		$lang = strtolower(str_replace("_", "-", $lang));
		$lang = preg_replace('/[^a-z\-]/', '', $lang);
		$lang = preg_replace('/\-+/', '-', $lang);
		if (substr($lang, 0, 1) == "-")  $lang = substr($lang, 1);
		if (substr($lang, -1) == "-")  $lang = substr($lang, 0, -1);

		BB_RunPluginActionInfo("post_bb_getcleanlang", $lang);

		return $lang;
	}

	function BB_CreateLangPage($lang, $langmap = "")
	{
		global $bb_dir, $bb_file, $bb_page, $bb_langpage;

		$lang = preg_replace('/[^a-z]/', '_', strtolower(trim($lang)));
		$langmap = preg_replace('/[^a-z]/', '_', strtolower(trim($langmap)));

		if (isset($bb_page["langs"][$lang]))  return false;
		if ($langmap != "" && (!isset($bb_page["langs"][$langmap]) || is_string($bb_page["langs"][$langmap])))  return false;
		$bb_page["langs"][$lang] = ($langmap == "" ? array(0, "") : $langmap);

		BB_RunPluginActionInfo("bb_createlangpage_pre_bb_savepage", $lang);

		if (!BB_SavePage())  return false;

		if ($langmap == "")
		{
			$langpage2 = $bb_langpage;

			BB_RunPluginActionInfo("bb_createlangpage_bb_langpage", $langpage2);

			$data = "<" . "?php\n\t\$bb_langpage = " . BB_CreatePHPStorageData($langpage2) . ";\n?" . ">";
			if (BB_WriteFile($bb_dir . "/" . $bb_file . "_" . $lang . "_page.php", $data) === false)  return false;

			$bb_langpagerevisions = array(
				"rootrev" => 0,
				"branches" => array(),
				"revisions" => array(array("", serialize($bb_langpage), time(), time(), "Initial Page"))
			);

			BB_RunPluginActionInfo("bb_createlangpage_bb_langpagerevisions", $bb_langpagerevisions);

			$data = "<" . "?php\n\t\$bb_langpagerevisions = " . BB_CreatePHPStorageData($bb_langpagerevisions) . ";\n?" . ">";
			if (BB_WriteFile($bb_dir . "/" . $bb_file . "_" . $lang . "_rev.php", $data) === false)  return false;

			BB_RunPluginActionInfo("post_bb_createlangpage_nomap", $lang);
		}

		BB_RunPluginActionInfo("post_bb_createlangpage", $lang);

		return true;
	}

	function BB_DeleteLangPage($lang)
	{
		global $bb_dir, $bb_file, $bb_page, $bb_pref_lang, $bb_langpage, $bb_profiles;

		$lang = preg_replace('/[^a-z]/', '_', strtolower(trim($lang)));

		if (!isset($bb_page["langs"][$lang]) || $lang == $bb_pref_lang || $lang == $bb_page["defaultlang"])  return false;

		BB_RunPluginActionInfo("pre_bb_deletelangpage", $lang);

		if (is_array($bb_page["langs"][$lang]))
		{
			if (file_exists($bb_dir . "/" . $bb_file . "_" . $lang . "_rev.php"))  unlink($bb_dir . "/" . $bb_file . "_" . $lang . "_rev.php");
			if (file_exists($bb_dir . "/" . $bb_file . "_" . $lang . "_page.php"))  unlink($bb_dir . "/" . $bb_file . "_" . $lang . "_page.php");
			foreach ($bb_profiles as $profile => $disp)
			{
				$filename = $bb_dir . "/" . $bb_file . "_" . $lang . ($profile != "" ? "." . $profile : "") . ".html";
				if (file_exists($filename))  unlink($filename);
			}

			foreach ($bb_page["langs"] as $lang2 => $map)
			{
				if (is_string($map) && $map == $lang)  unset($bb_page["langs"][$lang2]);
			}
		}

		unset($bb_page["langs"][$lang]);

		BB_RunPluginActionInfo("post_bb_deletelangpage", $lang);

		return BB_SavePage();
	}

	function BB_SetDefaultLangPage($lang)
	{
		global $bb_page;

		if (!isset($bb_page["langs"][$lang]) || is_string($bb_page["langs"][$lang]))  return false;

		BB_RunPluginActionInfo("pre_bb_setdefaultlangpage", $lang);

		$bb_page["defaultlang"] = $lang;

		BB_RunPluginAction("post_bb_setdefaultlangpage");

		return BB_SavePage();
	}

	function BB_UpdateSitemaps()
	{
		global $bb_dir, $bb_file, $bb_page;

		if ($bb_page["cachetime"] < 0)  $changefreq = "monthly";
		else if ($bb_page["cachetime"] < 2 * 60 * 60)  $changefreq = "hourly";
		else if ($bb_page["cachetime"] < 7 * 24 * 60 * 60)  $changefreq = "daily";
		else if ($bb_page["cachetime"] < 31 * 24 * 60 * 60)  $changefreq = "weekly";
		else if ($bb_page["cachetime"] < 365 * 24 * 60 * 60)  $changefreq = "monthly";
		else  $changefreq = "yearly";

		BB_RunPluginActionInfo("bb_updatesitemaps_changefreq", $changefreq);

		foreach ($bb_page["langs"] as $lang => $map)
		{
			if (is_array($map))
			{
				$bb_sitemap = array();
				if (file_exists("sitemap_" . $lang . ".php"))  require "sitemap_" . $lang . ".php";

				$page = ($bb_dir != "." ? $bb_dir . "/" : "") . ($bb_file != "index" ? $bb_file . ".php" : "");
				unset($bb_sitemap[$page]);
				if ($lang != $bb_page["defaultlang"])  $page .= "?lang=" . $lang;
				unset($bb_sitemap[$page]);

				if ($bb_page["redirect"] == "" && $bb_page["sitemap"])
				{
					$bb_sitemap[$page] = array(
						$changefreq,
						$bb_page["sitemappriority"]
					);

					BB_RunPluginActionInfo("bb_updatesitemaps_bb_sitemap_page", $bb_sitemap[$page]);
				}

				$data = "<" . "?php\n\t\$bb_sitemap = " . BB_CreatePHPStorageData($bb_sitemap) . ";\n?" . ">";
				if (BB_WriteFile("sitemap_" . $lang . ".php", $data) === false)  return false;

				BB_RunPluginActionInfo("post_bb_updatesitemaps_save", $bb_sitemap);
			}
		}

		return true;
	}

	function BB_DeletePageCache()
	{
		global $bb_page, $bb_dir, $bb_file, $bb_profiles;

		foreach ($bb_page["langs"] as $lang => $map)
		{
			if (is_array($map))
			{
				foreach ($bb_profiles as $profile => $disp)
				{
					$filename = $bb_dir . "/" . $bb_file . "_" . $lang . ($profile != "" ? "." . $profile : "") . ".html";
					if (file_exists($filename))  @unlink($filename);
				}
			}
		}

		BB_RunPluginAction("post_bb_deletepagecache");
	}

	function BB_GetPageGroupPermissions($perm)
	{
		global $bb_page;

		return (isset($bb_page["perms"][$perm]) ? $bb_page["perms"][$perm] : array());
	}

	function BB_IsMemberOfPageGroup($perm, $account = "")
	{
		global $bb_account, $bb_page;

		if (!is_array($account))  $account = $bb_account;

		return ($account["type"] == "dev" || $account["type"] == "design" || ($account["type"] == "content" && isset($bb_page["perms"][$perm]) && isset($bb_page["perms"][$perm][$account["group"]])));
	}

	function BB_CreateWidget($sname, $name)
	{
		global $bb_paths;

		$sname = preg_replace('/[^A-Za-z0-9]/', "_", trim($sname));
		if ($sname == "")  return false;

		$name = preg_replace('/[^A-Za-z0-9\']/', " ", trim($name));
		if ($name == "")  return false;

		$dir = (isset($bb_paths) ? $bb_paths["WIDGET_PATH"] : WIDGET_PATH) . "/" . strtolower($sname);
		BB_MakePageDirs($dir);
		if (file_exists((isset($bb_paths) ? $bb_paths["ROOT_PATH"] : ROOT_PATH) . "/" . $dir . "/index.php"))  return false;

		$data = "<" . "?php\n";
		$data .= "\tif (!defined(\"BB_FILE\"))  exit();\n\n";
		$data .= "\t\$bb_widget->_s = \"" . $sname . "\";\n";
		$data .= "\t\$bb_widget->_n = \"" . $name . "\";\n";
		$data .= "\t\$bb_widget->_key = \"\";\n";
		$data .= "\t\$bb_widget->_ver = \"\";\n\n";
		$data .= "\tclass " . $sname . " extends BB_WidgetBase\n";
		$data .= "\t{\n";
		$data .= "\t\tpublic function Init()\n";
		$data .= "\t\t{\n";
		$data .= "\t\t\tglobal \$bb_widget;\n";
		$data .= "\t\t}\n\n";
		$data .= "\t\tpublic function PreWidget()\n";
		$data .= "\t\t{\n";
		$data .= "\t\t\tglobal \$bb_widget, \$bb_account;\n\n";
		$data .= "\t\t\tif (\$bb_account[\"type\"] == \"dev\" || \$bb_account[\"type\"] == \"design\")\n";
		$data .= "\t\t\t{\n";
		$data .= "\t\t\t\techo BB_CreateWidgetPropertiesLink(BB_Translate(\"Configure\"), \"" . $sname . "_configure_widget\");\n";
		$data .= "\t\t\t}\n";
		$data .= "\t\t}\n\n";
		$data .= "\t\tpublic function Process()\n";
		$data .= "\t\t{\n";
		$data .= "\t\t\tglobal \$bb_mode, \$bb_widget, \$bb_css, \$bb_js;\n\n";
		$data .= "\t\t\tif (\$bb_mode == \"head\")\n";
		$data .= "\t\t\t{\n";
		$data .= "\t\t\t}\n";
		$data .= "\t\t\telse if (\$bb_mode == \"body\")\n";
		$data .= "\t\t\t{\n";
		$data .= "\t\t\t}\n";
		$data .= "\t\t}\n\n";
		$data .= "\t\tpublic function ProcessAction()\n";
		$data .= "\t\t{\n";
		$data .= "\t\t\tglobal \$bb_widget;\n";
		$data .= "\t\t}\n\n";
		$data .= "\t\tpublic function ProcessBBAction()\n";
		$data .= "\t\t{\n";
		$data .= "\t\t\tglobal \$bb_widget, \$bb_account, \$bb_revision_num;\n\n";
		$data .= "\t\t\tif ((\$bb_account[\"type\"] == \"dev\" || \$bb_account[\"type\"] == \"design\") && \$_REQUEST[\"bb_action\"] == \"" . $sname . "_configure_widget\")\n";
		$data .= "\t\t\t{\n";
		$data .= "\t\t\t\tBB_RunPluginAction(\"pre_" . $sname . "_configure_widget\");\n\n";
		$data .= "\t\t\t\t\$options = array(\n";
		$data .= "\t\t\t\t\t\"title\" => BB_Translate(\"Configure %s\", \$bb_widget->_f),\n";
		$data .= "\t\t\t\t\t\"desc\" => \"Configuration options.\",\n";
		$data .= "\t\t\t\t\t\"fields\" => array(\n";
		$data .= "\t\t\t\t\t),\n";
		$data .= "\t\t\t\t\t\"submit\" => \"Save\",\n";
		$data .= "\t\t\t\t\t\"focus\" => true\n";
		$data .= "\t\t\t\t);\n\n";
		$data .= "\t\t\t\tBB_RunPluginActionInfo(\"" . $sname . "_configure_widget_options\", \$options);\n\n";
		$data .= "\t\t\t\tBB_PropertyForm(\$options);\n\n";
		$data .= "\t\t\t\tBB_RunPluginAction(\"post_" . $sname . "_configure_widget\");\n";
		$data .= "\t\t\t}\n";
		$data .= "\t\t}\n";
		$tempinfo = array($sname, $name, &$data);
		BB_RunPluginActionInfo("pre_bb_createwidget_class", $tempinfo);
		$data .= "\t}\n";
		BB_RunPluginActionInfo("pre_bb_createwidget", $tempinfo);
		$data .= "?" . ">";
		if (BB_WriteFile((isset($bb_paths) ? $bb_paths["ROOT_PATH"] : ROOT_PATH) . "/" . $dir . "/index.php", $data) === false)  return false;

		BB_RunPluginActionInfo("post_bb_createwidget", $name);

		return BB_WidgetStatusUpdate();
	}

	function BB_GetWidgetList()
	{
		global $bb_widget, $bb_widget_id, $bb_paths;

		$widgets = array();
		$id = $bb_widget_id;
		$bb_widget->SetID("");
		$pathname = (isset($bb_paths) ? $bb_paths["ROOT_PATH"] . "/" . $bb_paths["WIDGET_PATH"] : ROOT_PATH . "/" . WIDGET_PATH);
		$dir = opendir($pathname);
		if ($dir)
		{
			while (($file = readdir($dir)) !== false)
			{
				if (substr($file, 0, 1) != "." && file_exists($pathname . "/" . $file . "/index.php"))
				{
					$bb_widget->SetID("");
					$bb_widget->_dir = $file;
					$bb_widget->_file = $file . "/index.php";
					require_once $pathname . "/" . $bb_widget->_file;
					BB_InitWidget();

					$data = array();
					foreach ($bb_widget as $name => $val)  $data[$name] = $val;
					$widgets[] = $data;
				}
			}

			closedir($dir);
		}
		$bb_widget->SetID($id);

		return $widgets;
	}

	function BB_CreateWidgetID($base)
	{
		global $bb_langpage;

		$base = preg_replace('/[^a-z0-9]/', '_', strtolower(trim($base)));
		$name = $base;
		$x = 2;
		$found = false;
		foreach ($bb_langpage["widgets"] as $widget)
		{
			if ($widget["_a"] !== false && $name == $widget["_a"])  $found = true;
		}
		while (isset($bb_langpage["widgets"][$name]) || $found)
		{
			$name = $base . "_" . $x;
			$x++;
			$found = false;
			foreach ($bb_langpage["widgets"] as $widget)
			{
				if ($widget["_a"] !== false && $name == $widget["_a"])  $found = true;
			}
		}

		return $name;
	}

	function BB_AddWidget($widgetdir, $name, $parent, $init = false)
	{
		global $bb_langpage, $bb_revision_num, $bb_widget, $bb_widget_id;

		$tempinfo = array($parent, $widgetdir, $name);
		BB_RunPluginActionInfo("pre_bb_addwidget", $tempinfo);

		if (!isset($bb_langpage["widgets"][$parent]) || $bb_langpage["widgets"][$parent]["_m"] === false)  return false;

		$data = BB_GetWidgetList();
		if (!count($data))  return false;
		for ($x = 0; $x < count($data) && $data[$x]["_dir"] != $widgetdir; $x++);
		if ($x == count($data))  return false;

		$name = trim($name);
		if ($name == "")  return false;

		$id = BB_CreateWidgetID($data[$x]["_s"] . " " . $name);
		$bb_langpage["widgets"][$id] = array("_f" => $name, "_m" => false, "_a" => $parent, "_id" => $id, "_file" => $widgetdir . "/index.php");
		if (isset($bb_widget) && isset($bb_widget_id) && $bb_widget_id == $parent)  $bb_widget->_ids[] = $id;
		else  $bb_langpage["widgets"][$parent]["_ids"][] = $id;

		if ($init)
		{
			$oldid = $bb_widget_id;
			$bb_widget->SetID($id);
			BB_InitWidget();
			$bb_widget->SetID($oldid);
		}

		BB_RunPluginAction("post_bb_addwidget");

		return BB_SaveLangPage($bb_revision_num);
	}

	function BB_AttachWidget($name, $parent)
	{
		global $bb_langpage, $bb_revision_num, $bb_widget, $bb_widget_id;

		BB_RunPluginActionInfo("pre_bb_attachwidget", $name);

		if ($name == "root" || !isset($bb_langpage["widgets"][$parent]) || !isset($bb_langpage["widgets"][$name]) || $bb_langpage["widgets"][$parent]["_m"] === $bb_langpage["widgets"][$name]["_m"])  return false;

		$bb_langpage["widgets"][$name]["_a"] = $parent;
		if (!isset($bb_langpage["widgets"][$parent]["_ids"]))  $bb_langpage["widgets"][$parent]["_ids"] = array();
		if (isset($bb_widget) && isset($bb_widget_id) && $bb_widget_id == $parent)  $bb_widget->_ids[] = $name;
		else  $bb_langpage["widgets"][$parent]["_ids"][] = $name;

		BB_RunPluginAction("post_bb_attachwidget");

		return BB_SaveLangPage($bb_revision_num);
	}

	function BB_DetachWidget($name)
	{
		global $bb_langpage, $bb_revision_num, $bb_widget, $bb_widget_id;

		BB_RunPluginActionInfo("pre_bb_detachwidget", $name);

		if ($name == "root" || !isset($bb_langpage["widgets"][$name]))  return false;
		if (isset($bb_widget) && isset($bb_widget_id) && $bb_widget_id == $name)  $bb_widget->SetID("");
		$found = false;
		$parent = $bb_langpage["widgets"][$name]["_a"];
		if ($parent !== false && isset($bb_langpage["widgets"][$parent]) && isset($bb_langpage["widgets"][$parent]["_ids"]))
		{
			if (isset($bb_widget) && isset($bb_widget_id) && $bb_widget_id == $parent)
			{
				foreach ($bb_widget->_ids as $num => $id)
				{
					if ($id == $name)
					{
						unset($bb_widget->_ids[$num]);
						$found = true;
						break;
					}
				}
			}
			else
			{
				foreach ($bb_langpage["widgets"][$parent]["_ids"] as $num => $id)
				{
					if ($id == $name)
					{
						unset($bb_langpage["widgets"][$parent]["_ids"][$num]);
						$found = true;
						break;
					}
				}
			}
		}
		$bb_langpage["widgets"][$name]["_a"] = false;

		BB_RunPluginAction("post_bb_detachwidget");

		return (!$found || BB_SaveLangPage($bb_revision_num));
	}

	function BB_DetachAllWidgets($name)
	{
		global $bb_langpage, $bb_revision_num, $bb_widget, $bb_widget_id;

		BB_RunPluginActionInfo("pre_bb_detachallwidgets", $name);

		if (!isset($bb_langpage["widgets"][$name]))  return false;
		$found = false;
		if (isset($bb_widget) && isset($bb_widget_id) && $bb_widget_id == $name)
		{
			if (isset($bb_widget->_ids))
			{
				foreach ($bb_widget->_ids as $id)
				{
					if (isset($bb_langpage["widgets"][$id]) && $bb_langpage["widgets"][$id]["_a"] !== false && $bb_langpage["widgets"][$id]["_a"] == $name)
					{
						$bb_langpage["widgets"][$id]["_a"] = false;
						$found = true;
					}
				}
				$bb_widget->_ids = array();
			}
		}
		else
		{
			if (isset($bb_langpage["widgets"][$name]["_ids"]))
			{
				foreach ($bb_langpage["widgets"][$name]["_ids"] as $id)
				{
					if (isset($bb_langpage["widgets"][$id]) && $bb_langpage["widgets"][$id]["_a"] !== false && $bb_langpage["widgets"][$id]["_a"] == $name)
					{
						if (isset($bb_widget) && isset($bb_widget_id) && $bb_widget_id == $id)  $bb_widget->_a = false;
						else  $bb_langpage["widgets"][$id]["_a"] = false;

						$found = true;
					}
				}
				$bb_langpage["widgets"][$name]["_ids"] = array();
			}
		}

		BB_RunPluginAction("post_bb_detachallwidgets");

		return (!$found || BB_SaveLangPage($bb_revision_num));
	}

	function BB_DeleteWidget($name)
	{
		global $bb_langpage, $bb_revision_num, $bb_widget, $bb_widget_id;

		BB_RunPluginActionInfo("pre_bb_deletewidget", $name);

		if ($name == "root")  return false;
		if (isset($bb_widget) && isset($bb_widget_id) && $bb_widget_id == $name)  $bb_widget->SetID("");
		if (isset($bb_langpage["widgets"][$name]))
		{
			if (isset($bb_langpage["widgets"][$name]["_ids"]))
			{
				foreach ($bb_langpage["widgets"][$name]["_ids"] as $num => $id)
				{
					if (isset($bb_langpage["widgets"][$id]))
					{
						if (isset($bb_widget) && isset($bb_widget_id) && $bb_widget_id == $id)  $bb_widget->_a = false;
						else  $bb_langpage["widgets"][$id]["_a"] = false;
					}
				}
			}

			$parent = $bb_langpage["widgets"][$name]["_a"];
			if ($parent !== false && isset($bb_langpage["widgets"][$parent]) && isset($bb_langpage["widgets"][$parent]["_ids"]))
			{
				if (isset($bb_widget) && isset($bb_widget_id) && $bb_widget_id == $parent)
				{
					foreach ($bb_widget->_ids as $num => $id)
					{
						if ($id == $name)  unset($bb_widget->_ids[$num]);
					}
				}
				else
				{
					foreach ($bb_langpage["widgets"][$parent]["_ids"] as $num => $id)
					{
						if ($id == $name)  unset($bb_langpage["widgets"][$parent]["_ids"][$num]);
					}
				}
			}

			unset($bb_langpage["widgets"][$name]);
		}

		BB_RunPluginAction("post_bb_deletewidget");

		return BB_SaveLangPage($bb_revision_num);
	}

	function BB_IsMasterWidgetConnected($parent, $name)
	{
		global $bb_langpage;

		if (!isset($bb_langpage["widgets"][$parent]) || $bb_langpage["widgets"][$parent]["_m"] === true)  return false;

		$name = trim($name);
		if ($name == "")  return false;

		$id = $parent . "_" . $name;

		return (isset($bb_langpage["widgets"][$id]) && $bb_langpage["widgets"][$id]["_m"] === true && $bb_langpage["widgets"][$id]["_a"] !== false && $bb_langpage["widgets"][$id]["_a"] == $parent);
	}

	function BB_AddMasterWidget($parent, $name)
	{
		global $bb_langpage, $bb_revision_num, $bb_widget, $bb_widget_id;

		$tempinfo = array($parent, $name);
		BB_RunPluginActionInfo("pre_bb_addmasterwidget", $tempinfo);

		if (!isset($bb_langpage["widgets"][$parent]) || $bb_langpage["widgets"][$parent]["_m"] === true)  return false;

		$name = trim($name);
		if ($name == "")  return false;

		$id = $parent . "_" . $name;
		if (!isset($bb_langpage["widgets"][$id]))  $bb_langpage["widgets"][$id] = array("_f" => $bb_langpage["widgets"][$parent]["_f"] . " - " . ucfirst(str_replace("_", " ", $name)), "_m" => true, "_a" => $parent, "_id" => $id, "_ids" => array());
		else
		{
			if ($bb_langpage["widgets"][$id]["_m"] === false)  return false;
			if (isset($bb_widget) && isset($bb_widget_id) && $bb_widget_id == $id)  $bb_widget->_a = $parent;
			else  $bb_langpage["widgets"][$id]["_a"] = $parent;
		}
		if (isset($bb_widget) && isset($bb_widget_id) && $bb_widget_id == $parent)
		{
			if (!isset($bb_widget->_ids))  $bb_widget->_ids = array();
			$bb_widget->_ids[] = $id;
		}
		else
		{
			if (!isset($bb_langpage["widgets"][$parent]["_ids"]))  $bb_langpage["widgets"][$parent]["_ids"] = array();
			$bb_langpage["widgets"][$parent]["_ids"][] = $id;
		}

		BB_RunPluginAction("post_bb_addmasterwidget");

		return BB_SaveLangPage($bb_revision_num);
	}

	function BB_GetPluginList()
	{
		global $bb_paths;

		$plugins = array();
		$pathname = (isset($bb_paths) ? $bb_paths["ROOT_PATH"] . "/" . $bb_paths["PLUGIN_PATH"] : ROOT_PATH . "/" . PLUGIN_PATH);
		$dir = opendir($pathname);
		if ($dir)
		{
			while (($file = readdir($dir)) !== false)
			{
				if (substr($file, 0, 1) != "." && file_exists($pathname . "/" . $file . "/index.php"))  $plugins[] = $file;
			}

			closedir($dir);
		}

		return $plugins;
	}

	function BB_ProcessRelativePath($path)
	{
		$parts = explode("/", str_replace("\\", "/", $path));
		$path = array();
		foreach ($parts as $part)
		{
			if ($part == "..")  array_pop($path);
			else if ($part != "" && $part != ".")  $path[] = $part;
		}

		return implode("/", $path);
	}

	function BB_GetRealPath($path, $relroot = true)
	{
		global $bb_paths;

		$path = str_replace("\\", "/", $path);
		if (substr($path, 0, 1) == "/")  $path = substr($path, 1);
		if (!$relroot)  $path = (isset($bb_paths) ? $bb_paths["ROOT_PATH"] : ROOT_PATH) . "/" . $path;
		$path = BB_ProcessRelativePath($path);

		return $path;
	}

	function BB_GetDirectoryList($path)
	{
		if (substr($path, -1) == "/")  $path = substr($path, 0, -1);

		$result = array("dirs" => array(), "files" => array());

		if ($path == "")  $path = ".";
		if (!file_exists($path))  return false;
		if (is_file($path))
		{
			$result["files"][] = $path;

			return $result;
		}

		$dir = opendir($path);
		if ($dir)
		{
			while (($file = readdir($dir)) !== false)
			{
				if ($file != "." && $file != "..")
				{
					$result[(is_file($path . "/" . $file) ? "files" : "dirs")][] = $file;
				}
			}

			closedir($dir);
		}

		natcasesort($result["dirs"]);
		natcasesort($result["files"]);
		$result["dirs"] = array_values($result["dirs"]);
		$result["files"] = array_values($result["files"]);

		return $result;
	}

	function BB_RemoveDirectory($path, $recursive = true)
	{
		if (substr($path, -1) == "/")  $path = substr($path, 0, -1);

		if (!file_exists($path))  return false;
		if (is_file($path) || is_link($path))
		{
			BB_RunPluginActionInfo("post_bb_removedirectory_fileorlink", $path);

			return unlink($path);
		}
		if (!$recursive)
		{
			BB_RunPluginActionInfo("post_bb_removedirectory_path", $path);

			return rmdir($path);
		}

		$dir = opendir($path);
		if ($dir)
		{
			while (($file = readdir($dir)) !== false)
			{
				if ($file != "." && $file != "..")
				{
					BB_RemoveDirectory($path . "/" . $file);
				}
			}

			closedir($dir);
		}

		BB_RunPluginActionInfo("post_bb_removedirectory_path", $path);

		return rmdir($path);
	}

	function BB_DeleteWidgetFiles($dir)
	{
		global $bb_paths;

		$widgets = BB_GetWidgetList();
		foreach ($widgets as $widget)
		{
			if ($widget["_dir"] == $dir)
			{
				BB_RunPluginActionInfo("pre_bb_deletewidgetfiles", $dir);

				if (!BB_RemoveDirectory((isset($bb_paths) ? $bb_paths["ROOT_PATH"] . "/" . $bb_paths["WIDGET_PATH"] : ROOT_PATH . "/" . WIDGET_PATH) . "/" . $dir))  return false;

				BB_RunPluginAction("post_bb_deletewidgetfiles");

				return BB_WidgetStatusUpdate();
			}
		}

		return false;
	}

	function BB_WidgetStatusUpdate()
	{
		global $bb_paths;

		$data = "<" . "?php \$bb_lastwidgetupdate = " . time() . "; ?" . ">";
		if (BB_WriteFile((isset($bb_paths) ? $bb_paths["ROOT_PATH"] : ROOT_PATH) . "/lastupdated.php", $data) === false)  return false;

		BB_RunPluginAction("post_bb_widgetstatusupdate");

		return true;
	}

	function BB_IsSSLRequest()
	{
		return ((isset($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] == "on" || $_SERVER["HTTPS"] == "1")) || (isset($_SERVER["SERVER_PORT"]) && $_SERVER["SERVER_PORT"] == "443") || (str_replace("\\", "/", strtolower(substr($_SERVER["REQUEST_URI"], 0, 8))) == "https://"));
	}

	// Returns 'http[s]://www.something.com[:port]' based on the current page request.
	function BB_GetRequestHost($protocol = "")
	{
		global $bb_getrequesthost_cache;

		$protocol = strtolower($protocol);
		$ssl = ($protocol == "https" || ($protocol == "" && BB_IsSSLRequest()));
		if ($protocol == "")  $type = "def";
		else if ($ssl)  $type = "https";
		else  $type = "http";

		if (!isset($bb_getrequesthost_cache))  $bb_getrequesthost_cache = array();
		if (isset($bb_getrequesthost_cache[$type]))  return $bb_getrequesthost_cache[$type];

		$url = "http" . ($ssl ? "s" : "") . "://";
		if ($ssl && defined("HTTPS_SERVER") && HTTPS_SERVER != "")  $url .= HTTPS_SERVER;
		else if (!$ssl && defined("HTTP_SERVER") && HTTP_SERVER != "")  $url .= HTTP_SERVER;
		else
		{
			$str = str_replace("\\", "/", $_SERVER["REQUEST_URI"]);
			$pos = strpos($str, "?");
			if ($pos !== false)  $str = substr($str, 0, $pos);
			$str2 = strtolower($str);
			if (substr($str2, 0, 7) == "http://")
			{
				$pos = strpos($str, "/", 7);
				if ($pos === false)  $str = "";
				else  $str = substr($str, 7, $pos);
			}
			else if (substr($str2, 0, 8) == "https://")
			{
				$pos = strpos($str, "/", 8);
				if ($pos === false)  $str = "";
				else  $str = substr($str, 8, $pos);
			}
			else  $str = "";

			if ($str != "")  $host = $str;
			else if (isset($_SERVER["HTTP_HOST"]))  $host = $_SERVER["HTTP_HOST"];
			else  $host = $_SERVER["SERVER_NAME"] . ":" . (int)$_SERVER["SERVER_PORT"];

			$pos = strpos($host, ":");
			if ($pos === false)  $port = 0;
			else
			{
				$port = (int)substr($host, $pos + 1);
				$host = substr($host, 0, $pos);
			}
			if ($port < 1 || $port > 65535)  $port = ($ssl ? 443 : 80);
			$url .= preg_replace('/[^a-z0-9.\-]/', "", strtolower($host));
			if ($protocol == "" && ((!$ssl && $port != 80) || ($ssl && $port != 443)))  $url .= ":" . $port;
			else if ($protocol == "http" && !$ssl && $port != 80)  $url .= ":" . $port;
			else if ($protocol == "https" && $ssl && $port != 443)  $url .= ":" . $port;
		}

		$bb_getrequesthost_cache[$type] = $url;

		return $url;
	}

	function BB_GetRequestURLBase()
	{
		global $bb_dir, $bb_file, $bb_paths;

		if (defined("ROOT_URL") && isset($bb_dir) && isset($bb_file))  return (isset($bb_paths) ? $bb_paths["ROOT_URL"] : ROOT_URL) . "/" . BB_GetRealPath($bb_dir . "/" . $bb_file . ".php");

		$str = str_replace("\\", "/", $_SERVER["REQUEST_URI"]);
		$pos = strpos($str, "?");
		if ($pos !== false)  $str = substr($str, 0, $pos);
		$str2 = strtolower($str);
		if (substr($str2, 0, 7) == "http://" || substr($str2, 0, 8) == "https://")
		{
			$pos = strpos($str, "/", 8);
			if ($pos === false)  $str = "/";
			else  $str = substr($str, $pos);
		}

		return $str;
	}

	function BB_GetFullRequestURLBase($protocol = "")
	{
		return BB_GetRequestHost($protocol) . BB_GetRequestURLBase();
	}

	function BB_GetFullRootURLBase($protocol = "")
	{
		global $bb_paths;

		return BB_GetRequestHost($protocol) . (isset($bb_paths) ? $bb_paths["ROOT_URL"] : ROOT_URL);
	}

	function BB_Redirect($url)
	{
		$y = ob_get_level();
		for ($x = 0; $x < $y; $x++)  ob_end_clean();

		header("Location: " . $url);

		exit();
	}

	function BB_GetIANAInfo($lang, $usecache = true)
	{
		global $bb_iana_lang_desc_cache, $bb_common_lang, $bb_iana_lang, $bb_paths;

		$lang = BB_GetCleanLang($lang);

		if ($usecache)
		{
			if (!file_exists((isset($bb_paths) ? $bb_paths["ROOT_PATH"] : ROOT_PATH) . "/iana_lang_desc_cache.php"))  $bb_iana_lang_desc_cache = array();
			else
			{
				require_once (isset($bb_paths) ? $bb_paths["ROOT_PATH"] : ROOT_PATH) . "/iana_lang_desc_cache.php";

				if (isset($bb_iana_lang_desc_cache[$lang]))  return $bb_iana_lang_desc_cache[$lang];
			}
		}

		$supportpath = (isset($bb_paths) ? $bb_paths["ROOT_PATH"] . "/" . $bb_paths["SUPPORT_PATH"] : ROOT_PATH . "/" . SUPPORT_PATH);
		if (file_exists($supportpath . "/php/common_lang.php"))
		{
			require_once $supportpath . "/php/common_lang.php";

			if (isset($bb_common_lang[$lang]))  return BB_AddIANACache($lang, $bb_common_lang[$lang], $usecache);
		}

		require_once $supportpath . "/php/iana_lang.php";

		$mappath = array($lang);
		while (isset($bb_iana_lang["grandfathered"][$lang]))
		{
			if (!isset($bb_iana_lang["grandfathered"][$lang]["pv"]))  return BB_AddIANACache($lang, array(array($bb_iana_lang["grandfathered"][$lang]["ds"]), $mappath), $usecache);
			$lang2 = $bb_iana_lang["grandfathered"][$lang]["pv"];
			if ($lang == $lang2)  return "[Infinite loop detected]";
			$lang = $lang2;
			$mappath[] = $lang;
		}

		$scanpath = array("language", "extlang", "script", "region", "variant", "extension", "privateuse");

		do
		{
			$reset = false;
			$lang = explode("-", $lang);
			$descpath = array();
			$y = 0;
			for ($x = 0; $x < count($scanpath) && $y < count($lang); $x++)
			{
				if (isset($bb_iana_lang[$scanpath[$x]]) && isset($bb_iana_lang[$scanpath[$x]][strtolower($lang[$y])]))
				{
					$info = $bb_iana_lang[$scanpath[$x]][strtolower($lang[$y])];
					if (isset($info["pv"]))
					{
						$lang2 = $info["pv"];
						if ($lang == $lang2)  return "[Infinite loop detected]";
						$lang = $lang2;
						$mappath[] = $lang;
						$reset = true;

						break;
					}

					$descpath[] = $info["ds"];

					$y++;
				}
			}
		} while ($reset);

		return BB_AddIANACache($mappath[0], array($descpath, $mappath), $usecache);
	}

	function BB_AddIANACache($src, $dest, $usecache = true)
	{
		global $bb_iana_lang_desc_cache, $bb_paths;

		$tempinfo = array($src, $dest, &$usecache);
		BB_RunPluginActionInfo("pre_bb_addianacache", $tempinfo);

		if ($usecache)
		{
			$bb_iana_lang_desc_cache[$src] = $dest;

			$data = "<" . "?php\n\t\$bb_iana_lang_desc_cache = " . BB_CreatePHPStorageData($bb_iana_lang_desc_cache) . ";\n?" . ">";
			BB_WriteFile((isset($bb_paths) ? $bb_paths["ROOT_PATH"] : ROOT_PATH) . "/iana_lang_desc_cache.php", $data);
		}

		return $dest;
	}

	function BB_GetIANADesc($lang, $usecache = true, $simple = false)
	{
		$data = BB_GetIANAInfo($lang, $usecache);
		if (!count($data[0]))  return false;

		return ($simple ? $data[0][0] . " (" . $data[1][0] . ")" : implode(", ", $data[0]) . " (" . implode(" => ", $data[1]) . ")");
	}

	function BB_ValidateAJAXUpload()
	{
		$result = "";

		if (!isset($_FILES["Filedata"]))  $result = "File not uploaded.  Possible server configuration issue [1].";
		else if ($_FILES["Filedata"]["error"])
		{
			switch ($_FILES["Filedata"]["error"])
			{
				case 1:  $result = "File too big.  File size exceeds server configuration limit.";  break;
				case 2:  $result = "File too big.  File size exceeds HTML form configuration limit.";  break;
				case 3:  $result = "Partial upload.  File failed to completely upload.";  break;
				case 4:  $result = "No file uploaded.";  break;
				case 6:  $result = "Temporary directory missing.  Contact ISP.";  break;
				case 7:  $result = "Write failure.  Unable to write the file to disk.  Disk full?";  break;
				case 8:  $result = "Blocked file extension.  Server configuration blocked upload.";  break;
				default:  $result = "Unknown error.  Error code:  " . $_FILES["Filedata"]["error"];
			}
		}
		else if (!is_uploaded_file($_FILES["Filedata"]["tmp_name"]))  $result = "File not uploaded.  Possible server configuration issue [2].\n";

		return $result;
	}

	function BB_LoadExtensionsCache()
	{
		global $bb_extensions_info, $bb_paths;

		$rootpath = (isset($bb_paths) ? $bb_paths["ROOT_PATH"] : ROOT_PATH);
		if (file_exists($rootpath . "/extensions_cache.php"))  require_once $rootpath . "/extensions_cache.php";
		else  $bb_extensions_info = array("exts" => array(), "updates" => array(), "vulnerabilities" => array(), "nextcheck" => 0);

		return true;
	}

	function BB_UpdateExtensionsCache($force = false)
	{
		global $bb_extensions_info, $bb_paths;

		if ($force || $bb_extensions_info["nextcheck"] < time())
		{
			$data = array();
			foreach ($bb_extensions_info["exts"] as $extinfo)
			{
				$data[] = (int)$extinfo["id"];
				$data[] = urlencode($extinfo["ver"]);
			}

			$url = "https://barebonescms.com/extend/api/?action=update_check&id_vers=" . implode(",", $data);
			BB_RunPluginActionInfo("pre_bb_updateextensionscache_url", $url);

			$info = BB_IsValidURL($url);
			if ($info["success"])
			{
				$data = @json_decode(trim($info["data"]), true);
				if ($data["success"])
				{
					if (defined("BB_CORE_VER") && $data["corever"] !== BB_CORE_VER)  array_unshift($data["updates"], BB_Translate("<strong>New version of Barebones CMS available!</strong><br />Currently installed:  %s<br />New:  %s", BB_CORE_VER, htmlspecialchars($data["corever"])));
					$bb_extensions_info["updates"] = $data["updates"];
					$bb_extensions_info["vulnerabilities"] = $data["vulnerabilities"];
				}
			}

			$bb_extensions_info["nextcheck"] = time() + (24 * 60 * 60);
			$data = "<" . "?php\n\t\$bb_extensions_info = " . BB_CreatePHPStorageData($bb_extensions_info) . ";\n?" . ">";
			BB_WriteFile((isset($bb_paths) ? $bb_paths["ROOT_PATH"] : ROOT_PATH) . "/extensions_cache.php", $data);
		}
	}

	function BB_GetExtensionInfo($id)
	{
		if (defined("BB_CORE_VER"))
		{
			$url = "https://barebonescms.com/extend/api/?action=get_info&core_ver=" . urlencode(BB_CORE_VER) . "&id=" . urlencode($id);
			BB_RunPluginActionInfo("pre_bb_getextensioninfo_url", $url);

			$info = BB_IsValidURL($url);
			if ($info["success"])
			{
				$data = @json_decode(trim($info["data"]), true);

				return $data;
			}
		}

		return false;
	}

	function BB_UninstallExtension($id)
	{
		global $bb_extensions_info, $bb_paths;

		if (!isset($bb_extensions_info["exts"][$id]))  return true;

		BB_RunPluginAction("pre_bb_uninstallextension");

		$rootpath = (isset($bb_paths) ? $bb_paths["ROOT_PATH"] : ROOT_PATH);
		foreach ($bb_extensions_info["exts"][$id]["files"] as $file)
		{
			@unlink($rootpath . "/" . $file);

			$file = dirname($file);
			while ($file != "" && $file != ".")
			{
				@rmdir($rootpath . "/" . $file);

				$file = dirname($file);
			}
		}

		unset($bb_extensions_info["exts"][$id]);

		if (!BB_SaveExtensionsCache())  return false;

		BB_RunPluginAction("post_bb_uninstallextension");

		return true;
	}

	function BB_InstallExtension($id)
	{
		global $bb_extensions_info, $bb_writeperms, $bb_paths;

		BB_RunPluginAction("pre_bb_installextension");

		if (!defined("BB_CORE_VER"))  return array("success" => false, "error" => "Core version not defined.");
		if (!function_exists("zip_open"))  return array("success" => false, "error" => "Required function 'zip_open' does not exist.  Ask your hosting provider to enable ZIP file functionality for PHP.");

		// Remove any existing installation of this extension.
		if (!BB_UninstallExtension($id))  return array("success" => false, "error" => "Unable to remove the existing extension.");

		if (isset($bb_paths))
		{
			$rootpath = $bb_paths["ROOT_PATH"];
			$supportpath = $bb_paths["SUPPORT_PATH"];
			$widgetpath = $bb_paths["WIDGET_PATH"];
			$pluginpath = $bb_paths["PLUGIN_PATH"];
			$langpath = $bb_paths["LANG_PATH"];
		}
		else
		{
			$rootpath = ROOT_PATH;
			$supportpath = SUPPORT_PATH;
			$widgetpath = WIDGET_PATH;
			$pluginpath = PLUGIN_PATH;
			$langpath = LANG_PATH;
		}

		@unlink($rootpath . "/install_extension.zip");
		if (file_exists($rootpath . "/install_extension.zip"))  return array("success" => false, "error" => "Unable to remove ZIP file from another extension installation attempt.");

		$url = "https://barebonescms.com/extend/api/?action=install&core_ver=" . urlencode(BB_CORE_VER) . "&id=" . urlencode($id);
		BB_RunPluginActionInfo("pre_bb_installextension_url", $url);

		$info = BB_IsValidURL($url);
		if (!$info["success"])  return $info;

		$data = @json_decode(trim($info["data"]), true);
		if ($data === false)  return array("success" => false, "error" => "Invalid data returned from the Barebones CMS distribution server.");
		if (!$data["success"])  return $data;
		if (!isset($data["data"]))  return array("success" => false, "error" => "ZIP file data is missing.");

		$data2 = @base64_decode(str_replace(array("-", "_"), array("+", "/"), $data["data"]));
		if ($data2 === false)  return array("success" => false, "error" => "Invalid ZIP file data returned from the Barebones CMS distribution server.");

		if (BB_WriteFile($rootpath . "/install_extension.zip", $data2) === false)  return array("success" => false, "error" => "Unable to write ZIP file data to a temporary file.");

		// Find the 'package.json' file.
		$zip = @zip_open($rootpath . "/install_extension.zip");
		if (!is_resource($zip))  return array("success" => false, "error" => "Unable to open ZIP file.  Possibly a temporary issue.  Try again later.  If the problem persists, notify CubicleSoft.");

		$basepath = false;
		$packageinfo = false;
		$entry = @zip_read($zip);
		while ($packageinfo === false && is_resource($entry))
		{
			$name = @zip_entry_name($entry);
			$name = str_replace("\\", "/", $name);
			$origname = $name;
			$pos = strrpos($name, "/");
			if ($pos !== false)  $name = substr($name, $pos + 1);
			if ($name === "package.json")
			{
				if (@zip_entry_open($zip, $entry))
				{
					$packageinfo = @zip_entry_read($entry, @zip_entry_filesize($entry));
					@zip_entry_close($entry);

					if ($packageinfo !== false)  $packageinfo = @json_decode($packageinfo, true);
					if ($packageinfo === false)  return array("success" => false, "error" => "ZIP file contains a 'package.json' file but is invalid or corrupted.");

					if (!isset($packageinfo["id"]))  return array("success" => false, "error" => "ZIP file contains a 'package.json' file but is missing 'id'.");
					else if (!is_string($packageinfo["id"]))  return array("success" => false, "error" => "ZIP file contains a 'package.json' file but 'id' is not a string.");
					else if ($packageinfo["id"] === "")  return array("success" => false, "error" => "ZIP file contains a 'package.json' file but 'id' is an empty string.");
					else if ($packageinfo["id"] !== $id)  return array("success" => false, "error" => "ZIP file contains a 'package.json' file but 'id' in the package does not match the expected extension ID.");
					else if (!isset($packageinfo["version"]))  return array("success" => false, "error" => "ZIP file contains a 'package.json' file but is missing 'version'.");
					else if (!is_string($packageinfo["version"]))  return array("success" => false, "error" => "ZIP file contains a 'package.json' file but 'version' is not a string.");
					else if ($packageinfo["version"] === "")  return array("success" => false, "error" => "ZIP file contains a 'package.json' file but 'version' is an empty string.");
					else if ($packageinfo["version"] !== $data["ver"])  return array("success" => false, "error" => "ZIP file contains a 'package.json' file but the 'version' in the package does not match the expected version.");
					else if (!isset($packageinfo["files"]))  return array("success" => false, "error" => "ZIP file contains a 'package.json' file but is missing 'files'.");
					else if (!is_array($packageinfo["files"]))  return array("success" => false, "error" => "ZIP file contains a 'package.json' file but 'files' is not an array.");
					else if (!count($packageinfo["files"]))  return array("success" => false, "error" => "ZIP file contains a 'package.json' file but 'files' is an empty array.");

					$basepath = (string)substr($origname, 0, -12);
				}
			}

			$entry = @zip_read($zip);
		}

		@zip_close($zip);

		if ($packageinfo === false)  return array("success" => false, "error" => "ZIP file is missing a 'package.json' file.");

		// If the extension has external file prerequisites, then verify the files.
		if (isset($packageinfo["dependency_check"]) && is_array($packageinfo["dependency_check"]))
		{
			foreach ($packageinfo["dependency_check"] as $name => $error)
			{
				$name = str_replace("\\", "/", $name);
				$error = htmlspecialchars($error);

				$checkdir = (substr($name, -1) === "/");

				$name = BB_GetRealPath($name);

				$pos = strpos($name, "/");
				if ($pos !== false)
				{
					$basedir = substr($name, 0, $pos);
					if ($basedir === "support")  $name = BB_GetRealPath($supportpath . substr($name, $pos));
					else if ($basedir === "widgets")  $name = BB_GetRealPath($widgetpath . substr($name, $pos));
					else if ($basedir === "plugins")  $name = BB_GetRealPath($pluginpath . substr($name, $pos));
					else if ($basedir === "lang")  $name = BB_GetRealPath($langpath . substr($name, $pos));
				}

				if ($checkdir && !is_dir($rootpath . "/" . $name))  return array("success" => false, "error" => $error);
				else if (!$checkdir && !is_file($rootpath . "/" . $name))  return array("success" => false, "error" => $error);
			}
		}

		// Extract files to the correct locations.
		$zip = @zip_open($rootpath . "/install_extension.zip");
		if (!is_resource($zip))  return array("success" => false, "error" => "Unable to open ZIP file.  Possibly a temporary issue.  Try again later.  If the problem persists, notify CubicleSoft.");

		$bb_extensions_info["exts"][$id] = array(
			"id" => $id,
			"name" => $data["name"],
			"type" => $data["type"],
			"type_disp" => $data["type_disp"],
			"author" => $data["author"],
			"ver" => $data["ver"],
			"files" => array()
		);

		$entry = @zip_read($zip);
		while (is_resource($entry))
		{
			$name = @zip_entry_name($entry);
			$name = str_replace("\\", "/", $name);
			if ($basepath === substr($name, 0, strlen($basepath)))
			{
				$name = substr($name, strlen($basepath));

				// Attempt to match an option in 'files'.
				$match = false;
				foreach ($packageinfo["files"] as $src => $dest)
				{
					if ($src === $name)
					{
						$match = $dest;
						$name = "";
					}
					else if (substr($src, -1) === "*" && substr($src, 0, -1) === substr($name, 0, strlen($src) - 1))
					{
						$match = $dest;
						$name = substr($name, strlen($src) - 1);
					}
				}

				if ($match !== false)
				{
					if ($match === "support")  $match = $supportpath . "/";
					else if ($match === "widgets")  $match = $widgetpath . "/";
					else if ($match === "plugins")  $match = $pluginpath . "/";
					else if ($match === "lang")  $match = $langpath . "/";

					$match .= $name;

					$makedir = (substr($match, -1) === "/");
					$dest = BB_GetRealPath($match);

					if ($makedir)
					{
						@mkdir($rootpath . "/" . $dest, 0777, true);
						@chmod($rootpath . "/" . $dest, 0555 | $bb_writeperms);
					}
					else if (@zip_entry_open($zip, $entry))
					{
						$filedata = @zip_entry_read($entry, @zip_entry_filesize($entry));
						@zip_entry_close($entry);

						if ($filedata === false)  return array("success" => false, "error" => "An error occurred while reading '" . htmlspecialchars($name) . "'.");
						else
						{
							$dirpath = dirname($dest);
							if ($dirpath != "" && $dirpath != ".")
							{
								@mkdir($rootpath . "/" . $dirpath, 0777, true);
								@chmod($rootpath . "/" . $dirpath, 0555 | $bb_writeperms);
							}

							if (BB_WriteFile($rootpath . "/" . $dest, $filedata) === false)  return array("success" => false, "error" => "An error occurred while writing '" . htmlspecialchars($dest) . "'.");

							$bb_extensions_info["exts"][$id]["files"][] = $dest;
						}
					}
				}
			}

			$entry = @zip_read($zip);
		}

		@zip_close($zip);

		@unlink($rootpath . "/install_extension.zip");

		if (!BB_SaveExtensionsCache())  return array("success" => false, "error" => "Unable to save extension information to the cache.");

		BB_RunPluginAction("post_bb_installextension");

		return array("success" => true);
	}

	function BB_SaveExtensionsCache()
	{
		global $bb_extensions_info, $bb_paths;

		$data = "<" . "?php\n\t\$bb_extensions_info = " . BB_CreatePHPStorageData($bb_extensions_info) . ";\n?" . ">";
		if (BB_WriteFile((isset($bb_paths) ? $bb_paths["ROOT_PATH"] : ROOT_PATH) . "/extensions_cache.php", $data) === false)  return false;

		BB_RunPluginAction("post_bb_saveextensionscache");

		return true;
	}

	// Runs data through HTML Purifier.  Be sure to check out BB_HTMLPurifyForWYMEditor().
	function BB_HTMLPurify($data, $options = array())
	{
		require_once ROOT_PATH . "/" . SUPPORT_PATH . "/htmlpurifier/HTMLPurifier.standalone.php";

		$data = UTF8::MakeValid($data);

		$config = HTMLPurifier_Config::createDefault();
		foreach ($options as $key => $val)  $config->set($key, $val);
		$purifier = new HTMLPurifier($config);

		$data = $purifier->purify($data);

		unset($purifier);
		unset($config);

		$data = UTF8::MakeValid($data);

		return $data;
	}

	function BB_CSSPurify($data, $cleanall = true)
	{
		require_once ROOT_PATH . "/" . SUPPORT_PATH . "/csstidy/class.csstidy.php";

		$csspurify = new csstidy();
		$csspurify->set_cfg(array(
			"remove_last_;" => false,
			"sort_properties" => false,
			"template" => "barebones"
		));

		$csspurify->parse($data);
		$data = $csspurify->print->plain();

		unset($csstidy);

		// Remove Javascript, IE behaviors and expressions, and @import (which could contain Javascript and IE behaviors).
		if ($cleanall)
		{
			$data = str_replace("javascript:", "", $data);
			$lines = explode("\n", $data);
			$lines2 = array();
			foreach ($lines as $line)
			{
				if (strtolower(substr($line, 0, strlen("\tbehavior:"))) != "\tbehavior:" && stripos($line, "expression(") === false && strtolower(substr($line, 0, strlen("@import "))) != "@import ")  $lines2[] = $line;
			}
			$data = implode("\n", $lines2);
		}

		return $data;
	}

	// Easily manipulate classes in a tag.
	function BB_HTMLClassExplode($classes)
	{
		$result = array();
		$pos = 0;
		$y = strlen($classes);
		while ($pos < $y)
		{
			$pos2 = strpos($classes, " ", $pos + 1);
			if ($pos2 === false)  $pos2 = $y;
			$str = trim(substr($classes, $pos, $pos2 - $pos));
			if ($str != "")  $result[$str] = $str;

			$pos = $pos2 + 1;
		}

		return $result;
	}

	function BB_HTMLHasClass($classes, $class)
	{
		$classes = BB_HTMLClassExplode($classes);

		return isset($classes[$class]);
	}

	function BB_HTMLAddClass($classes, $class)
	{
		$class = preg_replace('/[^a-z0-9_\-]/', "_", $class);

		$classes = BB_HTMLClassExplode($classes);
		$classes[$class] = $class;

		return implode(" ", $classes);
	}

	function BB_HTMLRemoveClass($classes, $class)
	{
		$classes = BB_HTMLClassExplode($classes);
		unset($classes[$class]);

		return implode(" ", $classes);
	}

	// Very lightweight tag parser.  Assumes valid XHTML output from BB_HTMLPurify.
	function BB_HTMLParseTag($tag)
	{
		$pos = strpos($tag, "<");
		if ($pos === false)  return false;
		$pos2 = strpos($tag, ">", $pos + 1);
		if ($pos2 === false)  return false;
		$tag = trim(substr($tag, $pos + 1, $pos2 - $pos - 1));
		if (substr($tag, -1) != "/")  $selfclose = false;
		else
		{
			$selfclose = true;
			$tag = trim(substr($tag, 0, -1));
		}

		// Extract tag name.
		$pos = strpos($tag, " ");
		if ($pos === false)  $pos = strlen($tag);
		$result = array("" => array("tag" => substr($tag, 0, $pos), "close" => $selfclose));
		$tag = trim(substr($tag, $pos));

		// Extract attributes.
		while ($tag != "")
		{
			$pos = strpos($tag, "=");
			if ($pos === false)  $tag = "";
			else
			{
				$attr = trim(substr($tag, 0, $pos));
				$tag = trim(substr($tag, $pos + 1));
				if (substr($tag, 0, 1) != "\"")  $pos = strpos($tag, " ");
				else
				{
					$tag = substr($tag, 1);
					$pos = strpos($tag, "\"");
				}
				if ($pos === false)  $pos = strlen($tag);
				$result[$attr] = substr($tag, 0, $pos);
				$tag = trim(substr($tag, $pos + 1));
			}
		}

		return $result;
	}

	// Recreates tags from BB_HTMLParseTag.  No input checking.
	function BB_HTMLMakeTag($data)
	{
		$result = "<" . $data[""]["tag"];
		foreach ($data as $key => $val)
		{
			if ($key != "")  $result .= " " . $key . "=\"" . $val . "\"";
		}
		$result .= ($data[""]["close"] ? " />" : ">");

		return $result;
	}

	// Determines if a HTTP/HTTPS URL is the local server.
	function BB_IsLocalURL($url)
	{
		$url = trim($url);
		return ((stripos($url, "http://") !== 0 && stripos($url, "https://") !== 0) || stripos($url, BB_GetRequestHost("http")) === 0 || stripos($url, BB_GetRequestHost("https")) === 0);
	}

	// Determines if the HTTP/HTTPS URL is valid by downloading the content.
	function BB_IsValidURL($url, $options = array())
	{
		if (!function_exists("fsockopen"))  return array("success" => false, "error" => "Unable to retrieve the URL content since the PHP function 'fsockopen' does not exist.");

		require_once ROOT_PATH . "/" . SUPPORT_PATH . "/http.php";
		require_once ROOT_PATH . "/" . SUPPORT_PATH . "/web_browser.php";

		// Map relative URLs to the local server.
		$url = trim($url);
		if (stripos($url, "http://") !== 0 && stripos($url, "https://") !== 0)
		{
			if (substr($url, 0, 1) == "/" || substr($url, 0, 1) == "\\")  $url = BB_GetRequestHost(isset($options["protocol"]) ? $options["protocol"] : "") . $url;
			else
			{
				$base = BB_GetFullRequestURLBase(isset($options["protocol"]) ? $options["protocol"] : "");
				$pos = strrpos($base, "/");
				if ($pos !== false)  $base = substr($base, 0, $pos + 1);
				$url = $base . $url;
			}
		}

		$web = new WebBrowser();
		$result = $web->Process($url);
		if (!$result["success"])  return array("success" => false, "error" => "Unable to retrieve the URL content.  " . $result["error"]);
		if ($result["response"]["code"] != 200)  return array("success" => false, "error" => "Unable to retrieve the URL content.  Server returned:  " . $result["response"]["code"] . " " . $result["response"]["meaning"]);

		return array("success" => true, "data" => $result["body"], "headers" => $result["headers"], "url" => $url);
	}

	// Uses official magic numbers for each format to determine the real content type.
	function BB_GetImageType(&$data)
	{
		if (substr($data, 0, 6) == "GIF87a" || substr($data, 0, 6) == "GIF89a")  return "gif";
		else if (substr($data, 0, 2) == "\xFF\xD8")  return "jpg";
		else if (substr($data, 0, 8) == "\x89PNG\x0D\x0A\x1A\x0A")  return "png";

		return "";
	}

	// Determines if the HTTP/HTTPS URL is a valid image that browsers can display by downloading the content and analyzing it.
	function BB_IsValidHTMLImage($url, $options = array())
	{
		if (!function_exists("fsockopen"))  return array("success" => false, "error" => "Unable to retrieve the image since the PHP function 'fsockopen' does not exist.");

		require_once ROOT_PATH . "/" . SUPPORT_PATH . "/http.php";
		require_once ROOT_PATH . "/" . SUPPORT_PATH . "/web_browser.php";

		// Map relative URLs to the local server.
		$url = trim($url);
		if (stripos($url, "http://") !== 0 && stripos($url, "https://") !== 0)
		{
			if (substr($url, 0, 1) == "/" || substr($url, 0, 1) == "\\")  $url = BB_GetRequestHost(isset($options["protocol"]) ? $options["protocol"] : "") . $url;
			else
			{
				$base = BB_GetFullRequestURLBase(isset($options["protocol"]) ? $options["protocol"] : "");
				$pos = strrpos($base, "/");
				if ($pos !== false)  $base = substr($base, 0, $pos + 1);
				$url = $base . $url;
			}
		}

		$web = new WebBrowser();
		$result = $web->Process($url);
		if (!$result["success"])  return array("success" => false, "error" => "Unable to retrieve the image.  " . $result["error"]);
		if ($result["response"]["code"] != 200)  return array("success" => false, "error" => "Unable to retrieve the image.  Server returned:  " . $result["response"]["code"] . " " . $result["response"]["meaning"]);

		$type = BB_GetImageType($result["body"]);
		if ((!isset($options["allow_gif"]) || $options["allow_gif"]) && $type == "gif")  return array("success" => true, "type" => "gif", "data" => $result["body"], "url" => $url);
		else if ((!isset($options["allow_jpg"]) || $options["allow_jpg"]) && $type == "jpg")  return array("success" => true, "type" => "jpg", "data" => $result["body"], "url" => $url);
		else if ((!isset($options["allow_png"]) || $options["allow_png"]) && $type == "png")  return array("success" => true, "type" => "png", "data" => $result["body"], "url" => $url);

		return array("success" => false, "error" => "Invalid image.");
	}

	// Creates a filename from a URL object output from ExtractURL().
	function BB_MakeFilenameFromURL($url, $forceext = "", $unique = false, $dir = "")
	{
		$path = array_reverse(explode("/", $url["path"]));
		for ($x = 0; $x < count($path) && trim($path[$x]) == ""; $x++);
		$dirfile = preg_replace('/[^A-Za-z0-9_.\-]/', "_", trim($x == count($path) ? str_replace(".", "_", $url["host"]) : $path[$x]));

		$pos = strrpos($dirfile, ".");
		if ($pos === false)  $ext = ".html";
		else
		{
			$ext = substr($dirfile, $pos);
			$dirfile = substr($dirfile, 0, $pos);
		}

		if ($forceext != "")  $ext = (substr($forceext, 0, 1) == "." ? "" : ".") . $forceext;

		if ($unique)
		{
			$dir = BB_GetRealPath($dir);
			if ($dir == "")  $dir = ".";

			if (file_exists($dir . "/" . $dirfile . $ext))
			{
				$num = 1;
				while (file_exists($dir . "/" . $dirfile . "-" . $num . $ext))  $num++;
				$dirfile .= "-" . $num;
			}
		}

		return $dirfile . $ext;
	}

	// Scrubs user-submitted HTML.  Outputs a single-line string of valid XHTML content compatible with WYMEditor.
	// NOTE:  Processes approximately 6KB/sec.
	function BB_HTMLPurifyForWYMEditor($data, $options)
	{
		if (isset($options["shortcodes"]) && (!$options["shortcodes"] || !isset($options["shortcode_placeholder"]) || !isset($options["shortcode_ids"])))  unset($options["shortcodes"]);
		if (isset($options["validate_img"]) && !$options["validate_img"])  unset($options["validate_img"]);

		// Let HTML Purifier do the heavy-lifting (removes XSS, etc).
		// If the 'p' tag ever accepts more than 'class', the 'class' extraction code while generating pretty HTML will need rewriting.
		$config = array(
			"Attr.EnableID" => isset($options["shortcodes"]),
			"HTML.Allowed" => "p[class],strong,em,sup,sub,a[title|href],ul[class],ol[class],li[class],h1[class],h2[class],h3[class],h4[class],h5[class],h6[class],pre[class],blockquote[class],img[" . (isset($options["shortcodes"]) ? "id|class|" : "") . "src|alt]"
		);
		if (isset($options["allowed_classes"]) && is_array($options["allowed_classes"]))  $config["Attr.AllowedClasses"] = $options["allowed_classes"];
		$data = BB_HTMLPurify($data, $config);

		// Replace newlines outside of 'pre' tags with spaces.
		$data2 = "";
		$lastpos = 0;
		$pos = strpos($data, "<pre");
		$pos2 = strpos($data, "</pre>");
		$pos3 = strpos($data, ">", $pos);
		while ($pos !== false && $pos2 !== false && $pos3 !== false && $pos3 < $pos2)
		{
			$data2 .= Str::ReplaceNewlines(" ", substr($data, $lastpos, $pos3 + 1 - $lastpos));
			$data2 .= Str::ReplaceNewlines("\n", substr($data, $pos3 + 1, $pos2 - $pos3 - 1));
			$data2 .= "</pre>";

			$lastpos = $pos2 + 6;
			$pos = strpos($data, "<pre", $lastpos);
			$pos2 = strpos($data, "</pre>", $lastpos);
			$pos3 = strpos($data, ">", $pos);
		}
		$data = $data2 . Str::ReplaceNewlines(" ", substr($data, $lastpos));

		// Process the DOM to create consistent input and output.
		require_once ROOT_PATH . "/" . SUPPORT_PATH . "/simple_html_dom.php";

		$html = new simple_html_dom();
		$html2 = new simple_html_dom();

		// Make sure all elements and text are inside a top-level tag.
		$html->load("<body>" . $data . "</body>");
		$bodytags = array(
			"p" => true,
			"ul" => true,
			"ol" => true,
			"h1" => true,
			"h2" => true,
			"h3" => true,
			"h4" => true,
			"h5" => true,
			"h6" => true,
			"pre" => true,
			"blockquote" => true
		);
		$rows = $html->find("body text");
		foreach ($rows as $row)
		{
			$row2 = $row;
			while ($row2->parent()->tag != "body")  $row2 = $row2->parent();
			if (!isset($bodytags[$row2->tag]))  $row2->outertext = "<p>" . $row2->outertext . "</p>";
		}
		$html->load($html->save());
		$body = $html->find("body", 0);
		$rows = $body->children();
		foreach ($rows as $row)
		{
			if (!isset($bodytags[$row->tag]))  $row->outertext = "<p>" . $row->outertext . "</p>";
		}
		$html->load($html->save());
		$rows = $html->find("blockquote text");
		foreach ($rows as $row)
		{
			$row2 = $row;
			while ($row2->parent()->tag != "blockquote")  $row2 = $row2->parent();
			if (!isset($bodytags[$row2->tag]))  $row2->outertext = "<p>" . $row2->outertext . "</p>";
		}
		$html->load($html->save());

		// Clean up 'li' elements.  WYMEditor only allows a limited number of tags (a good thing).
		$rows = $html->find("li");
		foreach ($rows as $row)
		{
			$row->innertext = strip_tags($row->innertext, "<strong><em><sup><sub><a><img><ul><ol><li>");
		}

		// Replace &nbsp; with spaces.
		$data = $html->save();
		$data = str_replace(array("&nbsp;", "&#160;", "\xC2\xA0"), array(" ", " ", " "), $data);
		$html->load($data);

		// Process shortcodes or images.
		if (isset($options["shortcodes"]))
		{
			// Remove invalid 'img' tags.
			$rows = $html->find("img");
			foreach ($rows as $row)
			{
				if (!isset($row->class) || $row->class != $options["shortcode_placeholder"] || !isset($row->id) || !isset($options["shortcode_ids"][$row->id]))  $row->outertext = "";
				else  $row->src = $options["shortcode_ids"][$row->id];
			}
			$html->load($html->save());

			// Move text inside the special 'p.wrap-shortcode' class to separate 'p' tags.
			$rows = $html->find("p.wrap-shortcode img");
			foreach ($rows as $row)
			{
				$str = $row->parent()->innertext;
				$pos = strpos($str, "<img ");
				$pos2 = strpos($str, "/>", $pos);
				$str2 = substr($str, 0, $pos);
				$str3 = substr($str, $pos2 + 2);
				$str = substr($str, $pos, $pos2 + 2 - $pos);
				if ($str2 != "" || $str3 != "")  $row->parent()->outertext = ($str2 == "" ? "" : "<p>" . $str2 . "</p>") . "<p class=\"" . $row->parent()->class . "\">" . $str . "</p>" . ($str3 == "" ? "" : "<p>" . $str3 . "</p>");
			}
			$html->load($html->save());
		}
		else if (isset($options["validate_img"]))
		{
			// Download each 'img' 'src' and check them for valid web output (only allow JPEG, PNG, and GIF).
			$imgopts = array(
				"protocol" => (isset($options["validate_img_protocol"]) ? $options["validate_img_protocol"] : ""),
				"allow_gif" => (isset($options["validate_img_allow_gif"]) ? $options["validate_img_allow_gif"] : true),
				"allow_jpg" => (isset($options["validate_img_allow_jpg"]) ? $options["validate_img_allow_jpg"] : true),
				"allow_png" => (isset($options["validate_img_allow_png"]) ? $options["validate_img_allow_png"] : true)
			);
			$rows = $html->find("img");
			foreach ($rows as $row)
			{
				if (!isset($row->src))  $row->outertext = "";
				else
				{
					$imginfo = BB_IsValidHTMLImage($row->src, $imgopts);
					if (!$imginfo["success"])  $row->outertext = "";
				}
			}
			$html->load($html->save());
		}

		// Remove special classes that are improperly used.
		$specials = array(
			"wrap-start" => "p",
			"wrap-end" => "p",
			"table-row" => "p",
			"table-cell" => "p",
			"table-end" => "p"
		);
		if (isset($options["shortcodes"]))  $specials["wrap-shortcode"] = array("p", "img");
		if (isset($options["additional_specials"]))  $specials = array_merge($specials, $options["additional_specials"]);
		foreach ($specials as $class => $tags)
		{
			$rows = $html->find("." . $class);
			foreach ($rows as $row)
			{
				if (is_string($tags))  $valid = true;
				else
				{
					$html2->load($row->innertext);
					$row2 = $html2->find($tags[1], 0);
					$valid = ($row2 ? true : false);
				}

				$valid = ($valid && ((is_string($tags) && $row->tag == $tags) || (is_array($tags) && $row->tag == $tags[0])));

				if (!$valid)  $row->class = BB_HTMLRemoveClass($row->class, $class);

				if ($row->class == "")  unset($row->class);
			}
			$html->load($html->save());
		}

		// Remove empty elements without a class attribute.
		do
		{
			$found = false;
			$stack = array();
			$body = $html->find("body", 0);
			$stack[] = array("rows" => $body->children(), "pos" => 0);
			while (count($stack))
			{
				$pos = count($stack) - 1;
				if ($stack[$pos]["pos"] >= count($stack[$pos]["rows"]))
				{
					$stack = array_slice($stack, 0, -1);
					if (count($stack))
					{
						$pos = count($stack) - 1;
						$row = $stack[$pos]["rows"][$stack[$pos]["pos"]];
						if (!$found && trim($row->innertext) !== $row->innertext)
						{
							$row->innertext = trim($row->innertext);
							$found = true;
						}
						$stack[$pos]["pos"]++;
					}
				}
				else
				{
					$row = $stack[$pos]["rows"][$stack[$pos]["pos"]];
					$rows = $row->children();
					if (count($rows))
					{
						$stack[] = array("rows" => $rows, "pos" => 0);
					}
					else
					{
						if (!isset($row->class) && trim($row->innertext) == "")
						{
							$row->outertext = "";
							$found = true;
						}
						else if (trim($row->innertext) !== $row->innertext)
						{
							$row->innertext = trim($row->innertext);
							$found = true;
						}
						$stack[$pos]["pos"]++;
					}
				}
			}
			$html->load($html->save());
		} while ($found);

		$body = $html->find("body", 0);
		$data = $body->innertext;

		// Finalize 'li' tag cleanup.
		$data = preg_replace('/<\/li>\s+/', "</li>", $data);

		return $data;
	}

	// Support function for BB_HTMLTransformForWYMEditor() to automatically close open tags created by special classes.
	function BB_HTMLTransformForWYMEditor_BacktrackStack($type, &$stack, &$depth, &$data, $options)
	{
		if ($type != "")
		{
			$found = false;
			foreach ($stack as $val)
			{
				if ($val == $type)
				{
					$found = true;
					break;
				}
			}

			if (!$found)  return false;
		}

		while(count($stack) && $stack[count($stack) - 1] != $type)
		{
			switch ($stack[count($stack) - 1])
			{
				case "wrap":
				{
					$depth = substr($depth, 0, -1);
					$data .= $depth . "</div>";

					break;
				}
				case "table":
				{
					$depth = substr($depth, 0, -1);
					$data .= $depth . "</td>";
					$depth = substr($depth, 0, -1);
					$data .= $depth . "</tr>";
					$depth = substr($depth, 0, -1);
					$data .= $depth . "</table>";

					break;
				}
				default:
				{
					if (isset($options["backtrack_stack_callback"]))  $options["backtrack_stack_callback"]($type, $stack, $depth, $data, $options);

					break;
				}
			}

			$stack = array_slice($stack, 0, -1);
		}

		return true;
	}

	// Takes the output from BB_HTMLPurifyForWYMEditor(), transforms tags with special classes,
	// and applies a liberal amount of whitespace to make the output readable in View Source.
	function BB_HTMLTransformForWYMEditor($data, $options)
	{
		if (isset($options["backtrack_stack_callback"]) && !function_exists($options["backtrack_stack_callback"]))  unset($options["backtrack_stack_callback"]);
		if (isset($options["inline_tag_callback"]) && !function_exists($options["inline_tag_callback"]))  unset($options["inline_tag_callback"]);
		if (isset($options["p_tag_callback"]) && !function_exists($options["p_tag_callback"]))  unset($options["p_tag_callback"]);
		if (isset($options["shortcode_callback"]) && !function_exists($options["shortcode_callback"]))  unset($options["shortcode_callback"]);

		// Process special classes and make the HTML look pretty.
		$bodytags = array(
			"p" => false,
			"ul" => true,
			"ol" => true,
			"li" => false,
			"h1" => false,
			"h2" => false,
			"h3" => false,
			"h4" => false,
			"h5" => false,
			"h6" => false,
			"pre" => false,
			"blockquote" => true
		);
		$depth = "\n";
		$tags = explode("<", $data);
		$data = "";
		$stack = array();
		$skiptag = "";
		$replacetag = array("", "");
		$floatused = false;
		foreach ($tags as $tagnum => $tag)
		{
			$pos = strpos($tag, ">");
			if ($pos === false)  $data .= $tag;
			else
			{
				$pos2 = strpos($tag, " ");
				if ($pos2 === false)  $pos2 = strlen($tag);
				$name = substr($tag, 0, ($pos < $pos2 ? $pos : $pos2));
				if (substr($name, 0, 1) != "/")  $closetag = false;
				else
				{
					$name = substr($name, 1);
					$closetag = true;
				}

				if ($name == "img" && isset($options["shortcode_callback"]))  $options["shortcode_callback"]($tagnum, $tag, $pos, $pos2, $depth, $data, $options);
				else if (!isset($bodytags[$name]))
				{
					if (!$closetag)
					{
						if (isset($options["link_target"]) && $name == "a")
						{
							$taginfo = BB_HTMLParseTag("<" . $tag);
							$taginfo["target"] = $options["link_target"];
							$tag = substr(BB_HTMLMakeTag($taginfo), 1) . substr($tag, $pos + 1);
						}

						if (isset($options["inline_tag_callback"]))  $options["inline_tag_callback"]($tagnum, $tag, $pos, $pos2, $name, $closetag, $stack, $depth, $skiptag, $replacetag, $data, $options);
					}

					$data .= ($skiptag == $name ? "" : "<" . ($replacetag[0] == $name ? $replacetag[1] : $tag));

					if ($skiptag == $name)  $skiptag = "";
					if ($replacetag[0] == $name)  $replacetag = array("", "");
				}
				else if ($closetag)
				{
					if (!$bodytags[$name])  $data .= ($skiptag == $name ? "" : "<" . ($replacetag[0] == $name ? $replacetag[1] : $tag));
					else
					{
						if ($name == "blockquote" && BB_HTMLTransformForWYMEditor_BacktrackStack("blockquote", $stack, $depth, $data, $options) && count($stack))
						{
							$stack = array_slice($stack, 0, -1);
						}

						$depth = substr($depth, 0, -1);
						$data .= ($skiptag == $name ? "" : ($data != "" ? $depth : "") . "<" . ($replacetag[0] == $name ? $replacetag[1] : $tag));
					}

					if ($skiptag == $name)  $skiptag = "";
					if ($replacetag[0] == $name)  $replacetag = array("", "");
				}
				else
				{
					$newdepth = $depth;
					if ($name == "blockquote")  $stack[] = "blockquote";
					else if ($name == "p" && $pos2 < $pos)
					{
						// Process special classes.  If the 'p' tag ever accepts more than 'class', the extraction code here will need rewriting (replace with BB_HTMLParseTag).
						$pos3 = strpos($tag, "\"", $pos2 + 1);
						if ($pos3 !== false && $pos3 < $pos)
						{
							$pos4 = strpos($tag, "\"", $pos3 + 1);
							if ($pos4 !== false && $pos4 < $pos)
							{
								$classes = substr($tag, $pos3 + 1, $pos4 - $pos3 - 1);
								$classes = BB_HTMLClassExplode($classes);
								if (!$floatused && (isset($classes["float-left"]) || isset($classes["float-right"])))  $floatused = true;

								if (isset($classes["wrap-shortcode"]))
								{
									unset($classes["wrap-shortcode"]);
									$classes["shortcode"] = "shortcode";
									$tag = "div" . (count($classes) ? " class=\"" . implode(" ", $classes) . "\"" : "") . substr($tag, $pos);
									$replacetag = array("p", "/div>");
								}
								else if (isset($classes["wrap-start"]))
								{
									$stack[] = "wrap";
									unset($classes["wrap-start"]);
									$classes["wrap"] = "wrap";
									$str = substr($tag, $pos + 1);
									$tag = "div" . (count($classes) ? " class=\"" . implode(" ", $classes) . "\">" : ">");
									$newdepth .= "\t";
									if ($str == "" && $tags[$tagnum + 1] == "/p>")  $skiptag = "p";
									else  $tag .= $newdepth . "<p>" . $str;
								}
								else if (isset($classes["wrap-end"]))
								{
									if (BB_HTMLTransformForWYMEditor_BacktrackStack("wrap", $stack, $depth, $data, $options) && count($stack))
									{
										$stack = array_slice($stack, 0, -1);
										unset($classes["wrap-end"]);
										$str = substr($tag, $pos + 1);
										$tag = "/div>";
										$closetag = true;
										$newdepth = $depth = substr($depth, 0, -1);
										if ($str == "" && $tags[$tagnum + 1] == "/p>")  $skiptag = "p";
										else  $tag .= (strlen($depth) == 1 ? "\n" : "") . $depth . "<p" . (count($classes) ? " class=\"" . implode(" ", $classes) . "\">" : ">") . $str;
									}
								}
								else if (isset($classes["table-row"]))
								{
									unset($classes["table-row"]);
									$rowspan = $colspan = 1;
									foreach ($classes as $class)
									{
										if (substr($class, 0, 8) == "rowspan-")
										{
											$rowspan = (int)substr($class, 8);
											unset($classes[$class]);
										}
										else if (substr($class, 0, 8) == "colspan-")
										{
											$colspan = (int)substr($class, 8);
											unset($classes[$class]);
										}
									}
									if ($rowspan < 1)  $rowspan = 1;
									if ($colspan < 1)  $colspan = 1;
									$td = ($rowspan > 1 ? " rowspan=\"" . $rowspan . "\"" : "") . ($colspan > 1 ? " rowspan=\"" . $colspan . "\"" : "");
									$str = substr($tag, $pos + 1);
									if (count($stack) && $stack[count($stack) - 1] == "table")
									{
										$tag = "/td>" . substr($depth, 0, -2) . "</tr>" . substr($depth, 0, -2) . "<tr>" . substr($depth, 0, -1) . "<td" . $td . (count($classes) ? " class=\"" . implode(" ", $classes) . "\">" : ">");
										$newdepth = $depth;
										$depth = substr($depth, 0, -1);
									}
									else
									{
										$stack[] = "table";
										$tag = "table>" . $depth . "\t<tr>" . $depth . "\t\t<td" . $td . (count($classes) ? " class=\"" . implode(" ", $classes) . "\">" : ">");
										$newdepth .= "\t\t\t";
									}

									if ($str == "" && $tags[$tagnum + 1] == "/p>")  $skiptag = "p";
									else  $tag .= $newdepth . "<p>" . $str;
								}
								else if (isset($classes["table-cell"]))
								{
									unset($classes["table-cell"]);
									$rowspan = $colspan = 1;
									foreach ($classes as $class)
									{
										if (substr($class, 0, 8) == "rowspan-")
										{
											$rowspan = (int)substr($class, 8);
											unset($classes[$class]);
										}
										else if (substr($class, 0, 8) == "colspan-")
										{
											$colspan = (int)substr($class, 8);
											unset($classes[$class]);
										}
									}
									if ($rowspan < 1)  $rowspan = 1;
									if ($colspan < 1)  $colspan = 1;
									$td = ($rowspan > 1 ? " rowspan=\"" . $rowspan . "\"" : "") . ($colspan > 1 ? " rowspan=\"" . $colspan . "\"" : "");
									$str = substr($tag, $pos + 1);
									if (count($stack) && $stack[count($stack) - 1] == "table")
									{
										$tag = "/td>" . substr($depth, 0, -1) . "<td" . $td . (count($classes) ? " class=\"" . implode(" ", $classes) . "\">" : ">");
										$newdepth = $depth;
										$depth = substr($depth, 0, -1);
									}
									else
									{
										$stack[] = "table";
										$tag = "table>" . $depth . "\t<tr>" . $depth . "\t\t<td" . $td . (count($classes) ? " class=\"" . implode(" ", $classes) . "\">" : ">");
										$newdepth .= "\t\t\t";
									}

									if ($str == "" && $tags[$tagnum + 1] == "/p>")  $skiptag = "p";
									else  $tag .= $newdepth . "<p>" . $str;
								}
								else if (isset($classes["table-end"]))
								{
									if (BB_HTMLTransformForWYMEditor_BacktrackStack("table", $stack, $depth, $data, $options) && count($stack))
									{
										$stack = array_slice($stack, 0, -1);
										unset($classes["table-end"]);
										$str = substr($tag, $pos + 1);
										$depth = substr($depth, 0, -1);
										$newdepth = substr($depth, 0, -2);
										$tag = "/td>" . substr($depth, 0, -1) . "</tr>" . $newdepth . "</table>";
										if ($str == "" && $tags[$tagnum + 1] == "/p>")  $skiptag = "p";
										else  $tag .= (strlen($newdepth) == 1 ? "\n" : "") . $newdepth . "<p" . (count($classes) ? " class=\"" . implode(" ", $classes) . "\">" : ">") . $str;
									}
								}
								else if (isset($options["p_tag_callback"]))
								{
									$options["p_tag_callback"]($tagnum, $tag, $pos, $pos2, $name, $closetag, $stack, $depth, $skiptag, $replacetag, $data, $options);
								}
							}
						}
					}

					$data .= ($data != "" ? (!$closetag && strlen($depth) == 1 ? "\n" : "") . $depth : "") . "<" . $tag;
					if ($bodytags[$name])  $depth .= "\t";
					else  $depth = $newdepth;
				}
			}
		}
		BB_HTMLTransformForWYMEditor_BacktrackStack("", $stack, $depth, $data, $options);
		if ($floatused)  $data .= "\n<p class=\"float-clear\"></p>";

		// Last second transformation of single tags from XHTML to HTML (e.g. <img /> to <img>).
		if (isset($options["doctype"]) && strtolower(substr($options["doctype"], 0, 6)) == "html 4")  $data = str_replace("/>", ">", str_replace(" />", ">", $data));

		return "\n" . $data . "\n";
	}

	function BB_JSSafe($data)
	{
		return str_replace(array("'", "\r", "\n"), array("\\'", "\\r", "\\n"), $data);
	}

	function BB_HTMLToJS($data)
	{
		$replacemap = array(
			"'" => "' + \"'\" + '",
			"<!" => "' + '<' + '!",
			"-->" => "' + '--' + '>",
		);

		$result = "";
		$lines = explode("\n", str_replace(array_keys($replacemap), array_values($replacemap), trim($data)));
		foreach ($lines as $line)  $result .= "document.write('" . rtrim($line) . "' + '\\n');\n";

		return $result;
	}

	function BB_PHPShorthandToBytes($val)
	{
		$val = trim($val);
		$last = strtoupper(substr($val, -1));
		$val = (int)$val;
		switch ($last)
		{
			case "G":  $val *= 1024;
			case "M":  $val *= 1024;
			case "K":  $val *= 1024;
		}

		return $val;
	}

	function BB_PreMainJS()
	{
		global $bb_pref_lang, $bb_revision_num, $bb_paths;

?>
<script type="text/javascript">
var Gx__RootURL = '<?php echo BB_JSSafe(isset($bb_paths) ? $bb_paths["ROOT_URL"] : ROOT_URL); ?>';
var Gx__SupportDebug = <?php echo (defined("DEBUG_JS") ? "true" : "false"); ?>;
var Gx__SupportPath = '<?php echo BB_JSSafe(isset($bb_paths) ? $bb_paths["SUPPORT_PATH"] : SUPPORT_PATH); ?>';
var Gx__URLBase = '<?php echo BB_JSSafe(BB_GetRequestURLBase()); ?>';
var Gx__PrefLang = '<?php echo BB_JSSafe($bb_pref_lang); ?>';
var Gx__FullRootURL = '<?php echo BB_JSSafe(BB_GetFullRootURLBase()); ?>';
var Gx__FullRootURLHTTP = '<?php echo BB_JSSafe(BB_GetFullRootURLBase("http")); ?>';
var Gx__FullRootURLHTTPS = '<?php echo BB_JSSafe(BB_GetFullRootURLBase("https")); ?>';
var Gx__FullURLBase = '<?php echo BB_JSSafe(BB_GetFullRequestURLBase()); ?>';
var Gx__FullURLBaseHTTP = '<?php echo BB_JSSafe(BB_GetFullRequestURLBase("http")); ?>';
var Gx__FullURLBaseHTTPS = '<?php echo BB_JSSafe(BB_GetFullRequestURLBase("https")); ?>';
<?php
		if (isset($bb_revision_num))  echo "var Gx__RevisionNum = " . $bb_revision_num . ";\n";
?>
var Gx__MaxSendSize = <?php echo min(BB_PHPShorthandToBytes(ini_get("post_max_size")), BB_PHPShorthandToBytes(ini_get("upload_max_filesize"))); ?>;
<?php
		BB_RunPluginAction("bb_premainjs");
?>
</script>
<?php
	}
?>