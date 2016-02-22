<?php
	// Barebones CMS Layout Widget
	// (C) 2014 CubicleSoft.  All Rights Reserved.

	if (!defined("BB_FILE"))  exit();

	require_once ROOT_PATH . "/" . SUPPORT_PATH . "/bb_functions.php";

	$bb_widget->_s = "bb_layout";
	$bb_widget->_n = "Layout";
	$bb_widget->_key = "";
	$bb_widget->_ver = "";

	class bb_layout extends BB_WidgetBase
	{
		private $layoutpath, $info;

		// Extracts all the relevant information of a layout.
		private function ExtractFileInfo($file)
		{
			$data = file_get_contents($file);
			if ($data === false)  return false;

			$profile = "";
			$result = false;
			do
			{
				$pos = stripos($data, "#edit");
				$pos2 = stripos($data, "#start");
				$pos3 = stripos($data, "#end");
				if ($pos2 === false || $pos3 === false || $pos3 < $pos2)  return false;

				if ($result === false)  $result = array();
				$result[$profile] = array("edit" => "", "info" => array(), "widgets" => array(), "chunks" => array());

				// Process optional '#edit' section.
				if ($pos !== false && $pos < $pos2)  $result[$profile]["edit"] = substr($data, $pos + 5, $pos2 - $pos - 5);

				// Save extra data for later, duplicate comments.
				$dataleft = trim(substr($data, $pos3 + 4)) . "\n";
				$data = trim(substr($data, $pos2 + 6, $pos3 - $pos2 - 6)) . "\n";
				while (substr($data, 0, 2) == "//")
				{
					$pos = strpos($data, "\n");
					if ($pos === false)  break;
					$result[$profile]["info"][] = trim(substr($data, 2, $pos - 2));
					$data = trim(substr($data, $pos + 1));
				}
				$result[$profile]["data"] = $data;

				// Process main area for widget master placeholders.
				$chunkpos = 0;
				$pos = strpos($data, "@mw_");
				if ($pos !== false)
				{
					$pos2 = strpos($data, "@", $pos + 4);
					while ($pos !== false && $pos2 !== false)
					{
						$name = substr($data, $pos + 4, $pos2 - $pos - 4);
						if ($name != "")
						{
							if (strpos($name, "<") !== false)  return false;
							if (substr($name, 0, 1) != "$")  $prefix = "";
							else
							{
								$prefix = "$";
								$name = substr($name, 1);
							}
							$name = preg_replace('/[^A-Za-z0-9_\-]/', "_", $name);
							$result[$profile]["widgets"][] = $prefix . $name;
							$result[$profile]["chunks"][] = substr($data, $chunkpos, $pos - $chunkpos);
							$chunkpos = $pos2 + 1;
						}

						$pos = strpos($data, "@mw_", $pos2 + 1);
						if ($pos !== false)  $pos2 = strpos($data, "@", $pos + 4);
					}
				}
				$result[$profile]["chunks"][] = substr($data, $chunkpos, strlen($data) - $chunkpos);

				$result[$profile]["ts"] = time();

				// Locate next profile, if any.
				$pos = stripos($dataleft, "#profile");
				if ($pos === false)  break;
				$pos2 = strpos($dataleft, "\n", $pos);
				if ($pos2 === false)  break;
				$profile = substr($dataleft, $pos + 8, $pos2 - $pos - 8);
				$profile = preg_replace('/[^A-Za-z0-9_\-]/', "", substr($profile, 1));
				if ($profile == "")  break;
				$data = substr($dataleft, $pos2 + 1);
			} while (1);

			return $result;
		}

		public function Init()
		{
			global $bb_widget, $bb_widget_id, $bb_lastwidgetupdate, $bb_langpage, $bb_profile;

			$this->layoutpath = BB_GetRealPath(Str::ExtractPathname($bb_widget->_file) . "/layouts");

			if (!isset($bb_widget->layout))  $bb_widget->layout = "";
			if ($bb_widget->layout == "")
			{
				BB_DetachAllWidgets($bb_widget_id);
				return;
			}

			$this->info = false;
			$basefile = ROOT_PATH . "/" . WIDGET_PATH . "/" . $this->layoutpath . "/" . $bb_widget->layout;
			$datfile = $basefile . ".dat";
			if (file_exists($datfile))  $this->info = unserialize(file_get_contents($datfile));
			if ($this->info === false || !isset($this->info[$bb_profile]) || $this->info[$bb_profile]["ts"] < $bb_lastwidgetupdate || defined("BB_MODE_EDIT"))
			{
				$this->info = $this->ExtractFileInfo($basefile);
				if ($this->info !== false)  BB_WriteFile($datfile, serialize($this->info));
			}

			if ($this->info === false)
			{
				$bb_widget->layout = "";
				BB_DetachAllWidgets($bb_widget_id);
			}
			else
			{
				if (!isset($this->info[$bb_profile]))  $profile = "";
				else  $profile = $bb_profile;

				$ids = array();
				foreach ($this->info[$profile]["widgets"] as $name)
				{
					if (substr($name, 0, 1) != "$")  $multi = true;
					else
					{
						$multi = false;
						$name = substr($name, 1);
					}
					$id = $bb_widget_id . "_" . $name;
					$ids[$id] = true;
					if (!BB_IsMasterWidgetConnected($bb_widget_id, $name))  BB_AddMasterWidget($bb_widget_id, $name);
					if (!$multi && isset($bb_langpage["widgets"][$id]) && !count($bb_langpage["widgets"][$id]["_ids"]))  BB_AddWidget($name, ucfirst(str_replace("_", " ", $name)), $id, true);
				}

				if ($profile == "" && isset($bb_widget->_ids))
				{
					foreach ($bb_widget->_ids as $id)
					{
						if (!isset($ids[$id]))  BB_DetachWidget($id);
					}
				}
			}
		}

		public function Process()
		{
			global $bb_mode, $bb_widget, $bb_widget_id, $bb_css, $bb_js, $bb_extra, $bb_profile;

			if ($bb_widget->layout == "")  return;

			if (!isset($this->info[$bb_profile]))  $profile = "";
			else  $profile = $bb_profile;

			if (!isset($this->info[$profile]))  return;

			if ($bb_mode == "head")
			{
				$pos = strrpos($bb_widget->layout, ".");
				$name = substr($bb_widget->layout, 0, $pos) . ($bb_profile != "" ? "." . $bb_profile : "") . ".css";
				$bb_css[ROOT_URL . "/" . WIDGET_PATH . "/" . $this->layoutpath . "/" . $name] = ROOT_PATH . "/" . WIDGET_PATH . "/" . $this->layoutpath . "/" . $name;

				foreach ($this->info[$profile]["widgets"] as $num => $name)
				{
					if (substr($name, 0, 1) != "$")  $multi = true;
					else
					{
						$multi = false;
						$name = substr($name, 1);
					}
					BB_ProcessMasterWidget($bb_widget_id . "_" . $name, $multi);
				}
			}
			else if ($bb_mode == "body")
			{
				if (defined("BB_MODE_EDIT"))  echo $this->info[$profile]["edit"];

				foreach ($this->info[$profile]["widgets"] as $num => $name)
				{
					echo $this->info[$profile]["chunks"][$num];

					if (substr($name, 0, 1) != "$")  $multi = true;
					else
					{
						$multi = false;
						$name = substr($name, 1);
					}
					BB_ProcessMasterWidget($bb_widget_id . "_" . $name, $multi);
				}

				echo $this->info[$profile]["chunks"][count($this->info[$profile]["chunks"]) - 1];
			}
			else
			{
				foreach ($this->info[$profile]["widgets"] as $num => $name)
				{
					if (substr($name, 0, 1) != "$")  $multi = true;
					else
					{
						$multi = false;
						$name = substr($name, 1);
					}
					BB_ProcessMasterWidget($bb_widget_id . "_" . $name, $multi);
				}
			}
		}

		public function PreWidget()
		{
			global $bb_widget, $bb_account;

			if ($bb_account["type"] == "dev" || $bb_account["type"] == "design")
			{
				echo BB_CreateWidgetPropertiesLink("Configure", "bb_layout_configure_widget");

				if ($bb_widget->layout != "")
				{
					global $editmap, $extmap, $bb_profiles, $bb_profile;

					$editmap = array(
						"ea_html" => array("<a href=\"#\" onclick=\"return window.parent.EditFile('%%HTML_JS_DIR%%', '%%HTML_JS_FILE%%', '%%HTML_JS_syntax%%', '%%HTML_JS_LOADTOKEN%%', '%%HTML_JS_SAVETOKEN%%');\">" . htmlspecialchars(BB_Translate("Edit HTML")) . "</a>", "syntax"),
						"ea_css" => array("<a href=\"#\" onclick=\"return window.parent.EditFile('%%HTML_JS_DIR%%', '%%HTML_JS_FILE%%', '%%HTML_JS_syntax%%', '%%HTML_JS_LOADTOKEN%%', '%%HTML_JS_SAVETOKEN%%');\">" . htmlspecialchars(BB_Translate("Edit CSS@CACHEPROFILE@")) . "</a>", "syntax")
					);

					$extmap = array(
						".html" => array("edit" => "ea_html", "syntax" => "html"),
						".css" => array("edit" => "ea_css", "syntax" => "css")
					);

					BB_RunPluginAction("bb_layout_prewidget_exteditmaps");

					$pos = strrpos($bb_widget->layout, ".");
					if ($pos !== false && substr($bb_widget->layout, $pos) == ".html")
					{
						echo BB_FileExplorer_GetActionStr(WIDGET_PATH . "/" . $this->layoutpath, $bb_widget->layout);
						echo str_replace("@CACHEPROFILE@", "", BB_FileExplorer_GetActionStr(WIDGET_PATH . "/" . $this->layoutpath, substr($bb_widget->layout, 0, $pos) . ".css"));
						if ($bb_profile != "")
						{
							$filename = ROOT_PATH . "/" . WIDGET_PATH . "/" . $this->layoutpath . "/" . substr($bb_widget->layout, 0, $pos) . "." . $bb_profile . ".css";
							if (!file_exists($filename))  BB_WriteFile($filename, "");
							echo str_replace("@CACHEPROFILE@", htmlspecialchars(" (" . $bb_profiles[$bb_profile] . ")"), BB_FileExplorer_GetActionStr(WIDGET_PATH . "/" . $this->layoutpath, substr($bb_widget->layout, 0, $pos) . ($bb_profile != "" ? "." . $bb_profile : "") . ".css"));
						}
					}
				}
			}
		}

		public function ProcessBBAction()
		{
			global $bb_widget, $bb_account, $bb_revision_num;

			$basepath = BB_GetRealPath(Str::ExtractPathname($bb_widget->_file) . "/base");

			if (($bb_account["type"] == "dev" || $bb_account["type"] == "design") && $_REQUEST["bb_action"] == "bb_layout_configure_widget_new_layout_submit")
			{
				BB_RunPluginAction("pre_bb_layout_configure_widget_new_layout_submit");

				$found = false;
				$dirlist = BB_GetDirectoryList(ROOT_PATH . "/" . WIDGET_PATH . "/" . $basepath);
				foreach ($dirlist["files"] as $name)
				{
					$pos = strrpos($name, ".");
					if ($pos !== false && substr($name, $pos) == ".html" && substr($name, 0, $pos) == $_REQUEST["pattern"])
					{
						$found = true;
						break;
					}
				}
				if (!$found)  BB_PropertyFormError("Invalid pattern specified.");

				$name = $_REQUEST["name"];
				if ($name == "")  BB_PropertyFormError("Name field not filled out.");
				$dirfile = preg_replace('/[^A-Za-z0-9_\-]/', "_", $name);

				if (file_exists(ROOT_PATH . "/" . WIDGET_PATH . "/" . $this->layoutpath . "/" . $dirfile . ".html"))  BB_PropertyFormError("A layout with that name already exists.");
				if (file_exists(ROOT_PATH . "/" . WIDGET_PATH . "/" . $this->layoutpath . "/" . $dirfile . ".css"))  BB_PropertyFormError("A layout with that name already exists.");

				$data = file_get_contents(ROOT_PATH . "/" . WIDGET_PATH . "/" . $basepath . "/" . $_REQUEST["pattern"] . ".html");
				$data = str_replace(htmlspecialchars($_REQUEST["pattern"]), htmlspecialchars($dirfile), $data);
				if (BB_WriteFile(ROOT_PATH . "/" . WIDGET_PATH . "/" . $this->layoutpath . "/" . $dirfile . ".html", $data) === false)  BB_PropertyFormError("Unable to create layout HTML.");
				if (!copy(ROOT_PATH . "/" . WIDGET_PATH . "/" . $basepath . "/" . $_REQUEST["pattern"] . ".css", ROOT_PATH . "/" . WIDGET_PATH . "/" . $this->layoutpath . "/" . $dirfile . ".css"))  BB_PropertyFormError("Unable to create layout CSS.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Layout created.")); ?></div>
<script type="text/javascript">
window.parent.LoadProperties(<?php echo BB_CreateWidgetPropertiesJS("bb_layout_configure_widget"); ?>);
</script>
<?php

				BB_RunPluginAction("post_bb_layout_configure_widget_new_layout_submit");
			}
			else if (($bb_account["type"] == "dev" || $bb_account["type"] == "design") && $_REQUEST["bb_action"] == "bb_layout_configure_widget_new_layout")
			{
				BB_RunPluginAction("pre_bb_layout_configure_widget_new_layout");

				$desc = "<br />";
				$desc .= BB_CreateWidgetPropertiesLink(BB_Translate("Back"), "bb_layout_configure_widget");

				$patterns = array();
				$dirlist = BB_GetDirectoryList(ROOT_PATH . "/" . WIDGET_PATH . "/" . $basepath);
				foreach ($dirlist["files"] as $name)
				{
					$pos = strrpos($name, ".");
					if ($pos !== false && substr($name, $pos) == ".html")
					{
						$info = $this->ExtractFileInfo(ROOT_PATH . "/" . WIDGET_PATH . "/" . $basepath . "/" . $name);
						if ($info !== false)  $patterns[substr($name, 0, $pos)] = $info[""]["info"][0];
					}
				}

				$options = array(
					"title" => BB_Translate("Configure %s - New Layout", $bb_widget->_f),
					"desc" => "Create a new layout.",
					"htmldesc" => $desc,
					"fields" => array(
						array(
							"title" => "Pattern",
							"type" => "select",
							"name" => "pattern",
							"options" => $patterns,
							"desc" => "The pattern to use for the new layout."
						),
						array(
							"title" => "Name",
							"type" => "text",
							"name" => "name",
							"value" => "",
							"desc" => "The name of the new layout."
						)
					),
					"submit" => "Create",
					"focus" => true
				);

				BB_RunPluginActionInfo("bb_layout_configure_widget_new_layout_options", $options);

				BB_PropertyForm($options);

				BB_RunPluginAction("post_bb_layout_configure_widget_new_layout");
			}
			else if (($bb_account["type"] == "dev" || $bb_account["type"] == "design") && $_REQUEST["bb_action"] == "bb_layout_configure_widget_activate_layout" && BB_IsSecExtraOpt("file"))
			{
				BB_RunPluginAction("pre_bb_layout_configure_widget_activate_layout");

				$found = false;
				$dirlist = BB_GetDirectoryList(ROOT_PATH . "/" . WIDGET_PATH . "/" . $this->layoutpath);
				foreach ($dirlist["files"] as $name)
				{
					$pos = strrpos($name, ".");
					if ($pos !== false && substr($name, $pos) == ".html" && $name == $_REQUEST["file"])
					{
						$info = $this->ExtractFileInfo(ROOT_PATH . "/" . WIDGET_PATH . "/" . $this->layoutpath . "/" . $name);
						if ($info !== false)
						{
							$bb_widget->layout = $name;
							$found = true;

							break;
						}
					}
				}
				if (!$found)  BB_PropertyFormLoadError("Invalid layout specified.");

				if (!BB_SaveLangPage($bb_revision_num))  BB_PropertyFormError("Unable to save the layout activation.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Layout activated.")); ?></div>
<script type="text/javascript">
window.parent.LoadProperties(<?php echo BB_CreateWidgetPropertiesJS("bb_layout_configure_widget"); ?>);
window.parent.ReloadIFrame();
</script>
<?php

				BB_RunPluginAction("post_bb_layout_configure_widget_activate_layout");
			}
			else if (($bb_account["type"] == "dev" || $bb_account["type"] == "design") && $_REQUEST["bb_action"] == "bb_layout_configure_widget_deactivate_layout")
			{
				BB_RunPluginAction("pre_bb_layout_configure_widget_deactivate_layout");

				$bb_widget->layout = "";

				if (!BB_SaveLangPage($bb_revision_num))  BB_PropertyFormError("Unable to save the layout deactivation.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Layout deactivated.")); ?></div>
<script type="text/javascript">
window.parent.LoadProperties(<?php echo BB_CreateWidgetPropertiesJS("bb_layout_configure_widget"); ?>);
window.parent.ReloadIFrame();
</script>
<?php

				BB_RunPluginAction("post_bb_layout_configure_widget_deactivate_layout");
			}
			else if (($bb_account["type"] == "dev" || $bb_account["type"] == "design") && $_REQUEST["bb_action"] == "bb_layout_configure_widget_delete_layout" && BB_IsSecExtraOpt("file"))
			{
				BB_RunPluginAction("pre_bb_layout_configure_widget_delete_layout");

				$found = false;
				$dirlist = BB_GetDirectoryList(ROOT_PATH . "/" . WIDGET_PATH . "/" . $this->layoutpath);
				foreach ($dirlist["files"] as $name)
				{
					$pos = strrpos($name, ".");
					if ($pos !== false && substr($name, $pos) == ".html" && $name == $_REQUEST["file"])
					{
						$info = $this->ExtractFileInfo(ROOT_PATH . "/" . WIDGET_PATH . "/" . $this->layoutpath . "/" . $name);
						if ($info !== false)
						{
							if (!unlink(ROOT_PATH . "/" . WIDGET_PATH . "/" . $this->layoutpath . "/" . $name))  BB_PropertyFormLoadError("Unable to delete the layout HTML.");
							foreach ($info as $profile => $data)
							{
								$filename = ROOT_PATH . "/" . WIDGET_PATH . "/" . $this->layoutpath . "/" . substr($name, 0, $pos) . ($profile != "" ? "." . $profile : "") . ".css";
								if (file_exists($filename))  @unlink($filename);
							}
							if (file_exists(ROOT_PATH . "/" . WIDGET_PATH . "/" . $this->layoutpath . "/" . $name . ".dat"))  @unlink(ROOT_PATH . "/" . WIDGET_PATH . "/" . $this->layoutpath . "/" . $name . ".dat");
							if (isset($bb_widget->layout) && $bb_widget->layout == $name)  unset($bb_widget->layout);
							$found = true;

							break;
						}
					}
				}
				if (!$found)  BB_PropertyFormLoadError("Invalid layout specified.");

				if (!BB_SaveLangPage($bb_revision_num))  BB_PropertyFormLoadError("Unable to save the layout activation status.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Layout deleted.")); ?></div>
<script type="text/javascript">
window.parent.LoadProperties(<?php echo BB_CreateWidgetPropertiesJS("bb_layout_configure_widget"); ?>);
window.parent.ReloadIFrame();
</script>
<?php

				BB_RunPluginAction("post_bb_layout_configure_widget_delete_layout");
			}
			else if (($bb_account["type"] == "dev" || $bb_account["type"] == "design") && $_REQUEST["bb_action"] == "bb_layout_configure_widget")
			{
				global $editmap, $extmap;

				BB_RunPluginAction("pre_bb_layout_configure_widget");

				$editmap = array(
					"ea_html" => array("<a href=\"#\" onclick=\"return EditFile('%%HTML_JS_DIR%%', '%%HTML_JS_FILE%%', '%%HTML_JS_syntax%%', '%%HTML_JS_LOADTOKEN%%', '%%HTML_JS_SAVETOKEN%%');\">" . htmlspecialchars(BB_Translate("Edit HTML")) . "</a>", "syntax"),
					"ea_css" => array("<a href=\"#\" onclick=\"return EditFile('%%HTML_JS_DIR%%', '%%HTML_JS_FILE%%', '%%HTML_JS_syntax%%', '%%HTML_JS_LOADTOKEN%%', '%%HTML_JS_SAVETOKEN%%');\">" . htmlspecialchars(BB_Translate("Edit CSS")) . "</a>", "syntax")
				);

				$extmap = array(
					".html" => array("edit" => "ea_html", "syntax" => "html"),
					".css" => array("edit" => "ea_css", "syntax" => "css")
				);

				BB_RunPluginAction("bb_layout_configure_widget_exteditmaps");

				$desc = "<br />";
				$desc .= BB_CreateWidgetPropertiesLink(BB_Translate("New Layout"), "bb_layout_configure_widget_new_layout");
				if ($bb_widget->layout != "")  $desc .= " | " . BB_CreateWidgetPropertiesLink(BB_Translate("Deactivate Current Layout"), "bb_layout_configure_widget_deactivate_layout");

				$rows = array();
				$dirlist = BB_GetDirectoryList(ROOT_PATH . "/" . WIDGET_PATH . "/" . $this->layoutpath);
				foreach ($dirlist["files"] as $name)
				{
					$pos = strrpos($name, ".");
					if ($pos !== false && substr($name, $pos) == ".html")
					{
						$info = $this->ExtractFileInfo(ROOT_PATH . "/" . WIDGET_PATH . "/" . $this->layoutpath . "/" . $name);
						if ($info !== false)
						{
							$rows[] = array("<a href=\"" . htmlspecialchars(ROOT_URL . "/" . WIDGET_PATH . "/" . $this->layoutpath . "/" . $name) . "\" target=\"_blank\">" . htmlspecialchars($name) . "</a>", BB_FileExplorer_GetActionStr(WIDGET_PATH . "/" . $this->layoutpath, $name) . " | " . BB_FileExplorer_GetActionStr(WIDGET_PATH . "/" . $this->layoutpath, substr($name, 0, $pos) . ".css") . " | " . ($bb_widget->layout != $name ? BB_CreateWidgetPropertiesLink(BB_Translate("Activate"), "bb_layout_configure_widget_activate_layout", array("file" => $name)) : BB_CreateWidgetPropertiesLink(BB_Translate("Deactivate"), "bb_layout_configure_widget_deactivate_layout")) . " | " . BB_CreateWidgetPropertiesLink(BB_Translate("Delete"), "bb_layout_configure_widget_delete_layout", array("file" => $name), BB_Translate("Deleting the '%s' layout will immediately affect any pages that utilize the layout.  Continue?", $name)));
						}
						else if (file_exists(ROOT_PATH . "/" . WIDGET_PATH . "/" . $this->layoutpath . "/" . $name))
						{
							$rows[] = array(BB_Translate("%s (Broken layout)", "<a href=\"" . htmlspecialchars(ROOT_URL . "/" . WIDGET_PATH . "/" . $this->layoutpath . "/" . $name) . "\" target=\"_blank\">" . htmlspecialchars($name) . "</a>"), BB_FileExplorer_GetActionStr(WIDGET_PATH . "/" . $this->layoutpath, $name));
						}
					}
				}

				$options = array(
					"title" => BB_Translate("Configure %s", $bb_widget->_f),
					"desc" => "Select an existing layout or create a new layout.",
					"htmldesc" => $desc
				);

				if (count($rows))
				{
					$options["fields"] = array(
						array(
							"type" => "table",
							"cols" => array("Layout", "Options"),
							"rows" => $rows
						)
					);
				}

				BB_RunPluginActionInfo("bb_layout_configure_widget_options", $options);

				BB_PropertyForm($options);

				BB_RunPluginAction("post_bb_layout_configure_widget");
			}
		}
	}
?>