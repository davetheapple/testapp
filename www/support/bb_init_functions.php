<?php
	// Barebones CMS initialization support functions.
	// (C) 2014 CubicleSoft.  All Rights Reserved.

	global $bb_writeperms;
	if (!defined("WRITE_PERMS") || WRITE_PERMS === "o")  $bb_writeperms = 0200;
	else if (WRITE_PERMS === "g")  $bb_writeperms = 0220;
	else if (WRITE_PERMS === "w")  $bb_writeperms = 0222;
	else  $bb_writeperms = 0200;

	$bb_doctypes = array(
		"XHTML 1.0 Strict" => "<" . "!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\">\n",
		"XHTML 1.0 Transitional" => "<" . "!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\">\n",
		"XHTML 1.0 Frameset" => "<" . "!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Frameset//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\">\n",
		"XHTML 1.1" => "<" . "!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\">\n",
		"XHTML Basic 1.0" => "<" . "!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML Basic 1.0//EN\" \"http://www.w3.org/TR/xhtml-basic/xhtml-basic10.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\">\n",
		"XHTML Basic 1.1" => "<" . "!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML Basic 1.1//EN\" \"http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\">\n",
		"HTML 4.01" => "<" . "!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\">\n<html>\n",
		"HTML 4.01 Transitional" => "<" . "!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n<html>\n",
		"HTML 4.01 Frameset" => "<" . "!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Frameset//EN\" \"http://www.w3.org/TR/html4/frameset.dtd\">\n<html>\n",
		"HTML 5" => "<" . "!DOCTYPE html>\n<html>\n"
	);

	function BB_CreatePHPStorageData($data)
	{
		if (!defined("USE_LESS_SAFE_STORAGE") || !USE_LESS_SAFE_STORAGE)  return "unserialize(base64_decode(\"" . base64_encode(serialize($data)) . "\"))";

		ob_start();
		var_export($data);
		$data = ob_get_contents();
		ob_end_clean();

		return $data;
	}

	function BB_WriteFile($filename, $data)
	{
		global $bb_writeperms;

		$result = file_put_contents($filename, $data);
		if ($result === false)  return false;
		@chmod($filename, 0444 | $bb_writeperms);

		return $result;
	}

	function BB_SavePage()
	{
		global $bb_dir, $bb_file, $bb_relroot, $bb_page, $bb_writeperms;

		// Calculate the number of languages - allow for faster cache selection.
		$bb_page["onelang"] = true;
		$numlang = 0;
		foreach ($bb_page["langs"] as $langmap)
		{
			if (is_array($langmap))
			{
				$numlang++;
				if ($numlang > 1)
				{
					$bb_page["onelang"] = false;

					break;
				}
			}
		}

		// Generate and write out the final page.
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

		if (function_exists("BB_RunPluginAction"))  BB_RunPluginAction("post_bb_savepage");

		return true;
	}

	class BB_WidgetBase
	{
		public function Init()
		{
		}

		public function Process()
		{
		}

		public function PreWidget()
		{
		}

		public function ProcessAction()
		{
		}

		public function ProcessBBAction()
		{
		}
	}

	// Needed because global &$bb_widget was causing memory corruption.
	global $bb_widget_id, $bb_widget, $bb_widget_instances, $bb_filetosname;
	$bb_widget_id = "";
	class bb_widget_internal
	{
		public function SetID($id)
		{
			global $bb_langpage, $bb_widget_id;

			if ($bb_widget_id != "")  $this->Save();

			foreach ($this as $name => $val)  unset($this->$name);

			if ($id != "")
			{
				foreach ($bb_langpage["widgets"][$id] as $name => $val)  $this->$name = $val;
			}

			$bb_widget_id = $id;
		}

		public function Save()
		{
			global $bb_langpage, $bb_widget_id;

			foreach ($this as $name => $val)  $bb_langpage["widgets"][$bb_widget_id][$name] = $val;
		}
	}
	$bb_widget = new bb_widget_internal();
	$bb_widget_instances = array();

	$bb_filetosname = array();
	function BB_InitWidget()
	{
		global $bb_filetosname, $bb_widget, $bb_widget_id, $bb_widget_instances;

		$file = $bb_widget->_file;
		if (!isset($bb_filetosname[$file]))  $bb_filetosname[$file] = array($bb_widget->_s, $bb_widget->_n, $bb_widget->_key, $bb_widget->_ver);
		else
		{
			$bb_widget->_s = $bb_filetosname[$file][0];
			$bb_widget->_n = $bb_filetosname[$file][1];
			$bb_widget->_key = $bb_filetosname[$file][2];
			$bb_widget->_ver = $bb_filetosname[$file][3];
		}

		if ($bb_widget_id != "" && class_exists($bb_widget->_s))
		{
			$bb_widget_instances[$bb_widget_id] = new $bb_widget->_s;
			$bb_widget_instances[$bb_widget_id]->Init();
		}
	}

	function BB_ProcessMasterWidget($name, $displaymwm = true)
	{
		global $bb_mode, $bb_widget, $bb_widget_id, $bb_widget_instances, $bb_langpage;

		$widget = $bb_langpage["widgets"][$name];
		if ($widget["_m"] === true && $widget["_a"] !== false)
		{
			$oldid = $bb_widget_id;
			foreach ($widget["_ids"] as $id)
			{
				$bb_widget->SetID($id);
				if ($bb_widget->_m === false && $bb_widget->_a !== false && isset($bb_widget_instances[$id]))
				{
					if ($bb_mode == "body" && defined("BB_MODE_EDIT"))  BB_MasterWidgetPreWidget($displaymwm);
					$bb_widget_instances[$id]->Process();
				}
			}
			$bb_widget->SetID($oldid);
			if ($displaymwm && defined("BB_MODE_EDIT"))  BB_MasterWidgetManager($name);
		}
	}

	function BB_ProcessPage($retcache, $dumpcache, $easyedit)
	{
		global $bb_page, $bb_langpage, $bb_doctypes, $bb_mode, $bb_css, $bb_js, $bb_use_premainjs, $bb_paths;

		$bb_mode = "prehtml";
		BB_ProcessMasterWidget("root");

		$result = "";
		if ($retcache)  ob_start();

		echo $bb_doctypes[$bb_page["doctype"]];
		echo "<head>\n";
		echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />\n";
		if ($bb_langpage["title"] != "")  echo "<title>" . htmlspecialchars($bb_langpage["title"]) . "</title>\n";
		if ($bb_langpage["metadesc"] != "")  echo "<meta name=\"description\" content=\"" . htmlspecialchars($bb_langpage["metadesc"]) . "\" />\n";
		if ($bb_page["metarobots"] != "")  echo "<meta name=\"robots\" content=\"" . htmlspecialchars($bb_page["metarobots"]) . "\" />\n";
		if (defined("BB_MODE_EDIT"))  echo "<base target=\"_top\" />\n";

		$bb_css = array();
		$bb_js = array();
		$bb_mode = "head";
		$bb_use_premainjs = false;
		BB_ProcessMasterWidget("root");

		foreach ($bb_css as $url => $path)  echo "<link rel=\"stylesheet\" href=\"" . htmlspecialchars($url) . "\" type=\"text/css\" media=\"" . (is_array($path) ? htmlspecialchars($path[1]) : "all") . "\" />\n";
		if ($bb_use_premainjs && function_exists("BB_PreMainJS"))  BB_PreMainJS();
		foreach ($bb_js as $url => $path)  echo "<script type=\"text/javascript\" src=\"" . htmlspecialchars($url) . "\"></script>\n";

		echo "</head>\n";
		echo "<body>\n";

		if ($bb_page["easyedit"] && $easyedit)  echo "<script type=\"text/javascript\" src=\"" . htmlspecialchars(isset($bb_paths) ? $bb_paths["ROOT_URL"] . "/" . $bb_paths["SUPPORT_PATH"] : ROOT_URL . "/" . SUPPORT_PATH) . "/js/easyedit.js\"></script>\n";

		if ($retcache)
		{
			$result .= ob_get_contents();
			if ($dumpcache)  ob_end_flush();
			else  ob_end_clean();
			ob_start();
		}

		$bb_mode = "body";
		BB_ProcessMasterWidget("root");

		if ($retcache)
		{
			$result .= ob_get_contents();
			if ($dumpcache)  ob_end_flush();
			else  ob_end_clean();
			ob_start();
		}

		$bb_mode = "foot";
		BB_ProcessMasterWidget("root");

		echo "</body>\n";
		echo "</html>\n";

		if ($retcache)
		{
			$result .= ob_get_contents();
			if ($dumpcache)  ob_end_flush();
			else  ob_end_clean();
		}

		return $result;
	}

	function BB_LoadWidgets()
	{
		global $bb_langpage, $bb_widget, $bb_paths;

		foreach ($bb_langpage["widgets"] as $id => $data)
		{
			$bb_widget->SetID($id);

			if ($bb_widget->_m === false && $bb_widget->_a !== false)
			{
				$filename = (isset($bb_paths) ? $bb_paths["ROOT_PATH"] . "/" . $bb_paths["WIDGET_PATH"] : ROOT_PATH . "/" . WIDGET_PATH) . "/" . $bb_widget->_file;

				if (file_exists($filename))
				{
					require_once $filename;
					BB_InitWidget();
				}
			}
		}
	}
?>