<?php
	// Barebones CMS Content Widget
	// (C) 2014 CubicleSoft.  All Rights Reserved.

	if (!defined("BB_FILE"))  exit();

	require_once ROOT_PATH . "/" . SUPPORT_PATH . "/bb_functions.php";

	$bb_widget->_s = "bb_content";
	$bb_widget->_n = "Content";
	$bb_widget->_key = "";
	$bb_widget->_ver = "";

	// Define the base class for shortcodes.
	class BB_ContentShortcodeBase
	{
		public function GenerateShortcode($parent, $sid, $depth)
		{
			return "";
		}

		public function ProcessShortcodeAction($parent)
		{
		}

		public function ProcessShortcodeBBAction($parent)
		{
		}
	}

	// Load all shortcode handlers.
	global $g_bb_content_shortcodes;

	$g_bb_content_shortcodes = array();
	$g_path = "/" . WIDGET_PATH . "/" . BB_GetRealPath(Str::ExtractPathname($bb_widget->_file) . "/shortcodes");
	$g_url = ROOT_URL . $g_path;
	$g_path = ROOT_PATH . $g_path;
	$g_dirlist = BB_GetDirectoryList($g_path);
	foreach ($g_dirlist["dirs"] as $g_name)
	{
		if (file_exists($g_path . "/" . $g_name . "/index.php"))
		{
			$g_fullurl = $g_url . "/" . $g_name;
			require_once $g_path . "/" . $g_name . "/index.php";
		}
	}
	$g_fullurl = $g_url;
	foreach ($g_dirlist["files"] as $g_name)
	{
		if (substr($g_name, -4) == ".php")  require_once $g_path . "/" . $g_name;
	}

	// Load the shortcode handler security contexts.
	global $g_bb_content_security, $g_bb_content_security_path;

	$g_bb_content_security = array();
	if (defined("BB_MODE_EDIT"))
	{
		$g_bb_content_security_path = ROOT_PATH . "/" . WIDGET_PATH . "/" . BB_GetRealPath(Str::ExtractPathname($bb_widget->_file)) . "/security.php";
		if (file_exists($g_bb_content_security_path))  require_once $g_bb_content_security_path;
	}

	// Allow cache profile hooks to manipulate shortcode output.
	if (!function_exists("bb_content_shortcode_cache_profile_pre"))
	{
		function bb_content_shortcode_cache_profile_pre($sname, $parent, $sid, $depth)
		{
		}
	}

	if (!function_exists("bb_content_shortcode_cache_profile_post"))
	{
		function bb_content_shortcode_cache_profile_post($sname, $parent, $sid, $depth, $data)
		{
			return $data;
		}
	}

	if (!function_exists("bb_content_cache_profile"))
	{
		function bb_content_cache_profile($data)
		{
			return $data;
		}
	}

	function bb_content__GenerateShortcode($tagnum, $tag, $pos, $pos2, $depth, &$data, $options)
	{
		global $bb_widget, $g_bb_content_shortcodes;

		$str = substr($tag, $pos + 1);
		$taginfo = BB_HTMLParseTag("<" . $tag);
		if (isset($taginfo["id"]))
		{
			$sid = $taginfo["id"];
			$pos = strrpos($sid, "_");
			if ($pos !== false)  $sid = substr($sid, $pos + 1);
			$sid = (int)$sid;
			if (isset($bb_widget->shortcodes[$sid]) && isset($bb_widget->shortcodes[$sid]["_sn"]))
			{
				$sname = $bb_widget->shortcodes[$sid]["_sn"];
				if (isset($g_bb_content_shortcodes[$sname]))
				{
					if (!isset($g_bb_content_shortcodes[$sname]["instance"]))
					{
						if (!$g_bb_content_shortcodes[$sname]["cache"])  $bb_widget->body_cached = false;
						$shortcode = "bb_content_shortcode_" . $sname;
						$g_bb_content_shortcodes[$sname]["instance"] = new $shortcode;
					}

					$info = $bb_widget->shortcodes[$sid];
					bb_content_shortcode_cache_profile_pre($sname, $options["__parent"], $sid, $depth);
					$data2 = $g_bb_content_shortcodes[$sname]["instance"]->GenerateShortcode($options["__parent"], $sid, $depth);
					$data .= bb_content_shortcode_cache_profile_post($sname, $options["__parent"], $sid, $depth, $data2);
					$bb_widget->shortcodes[$sid] = $info;
				}
			}
		}

		$data .= $str;
	}

	class bb_content extends BB_WidgetBase
	{
		// Returns the ID for the current shortcode.
		public function GetSID()
		{
			return $this->currsid;
		}

		// Generates a generic upload form for shortcodes.
		public function CreateShortcodeUploader($back_sc_action, $back_extra, $title, $utype, $ltype, $uploadtypes, $uploadtypesdesc, $maxfiles = 1)
		{
			global $bb_widget, $bb_revision_num;

			BB_RunPluginAction("pre_bb_content_shortcode_" . $_REQUEST["sc_action"]);

			if ($maxfiles < 0)  $maxfiles = 0;

			$desc = "<br />";
			$desc .= $this->CreateShortcodePropertiesLink(BB_Translate("Back"), $back_sc_action, $back_extra);

			$options = array(
				"title" => BB_Translate("%s - Upload/Transfer %s", BB_Translate($title), BB_Translate($utype . ($maxfiles != 1 ? "s" : ""))),
				"desc" => BB_Translate("Upload/Transfer " . ($maxfiles == 1 ? "a single " : "one or more ") . "%s.", BB_Translate($ltype . ($maxfiles != 1 ? "s" : ""))),
				"htmldesc" => $desc,
				"bb_action" => $_REQUEST["bb_action"],
				"hidden" => array(
					"sid" => $this->currsid,
					"sc_action" => $_REQUEST["sc_action"] . "_submit"
				),
				"fields" => array(
					array(
						"title" => BB_Translate("Select %s", BB_Translate($utype . ($maxfiles != 1 ? "s" : ""))),
						"type" => "custom",
						"value" => '<div id="upload_inject" class="uploadinject"></div>',
						"desc" => "Click the button to select the file" . ($maxfiles != 1 ? "s" : "") . " to upload.  Selected file" . ($maxfiles != 1 ? "s" : "") . " will automatically begin uploading."
					)
				),
				"focus" => false
			);

			if (function_exists("fsockopen"))
			{
				$options["fields"][] = array(
					"title" => "URL",
					"type" => "text",
					"name" => "url",
					"value" => "",
					"desc" => "URL of a resource to transfer/copy to the server."
				);
				$options["fields"][] = array(
					"title" => "Filename",
					"type" => "text",
					"name" => "destfile",
					"value" => "",
					"desc" => "Filename can only contain alphanumeric (A-Z, 0-9), '_', '.', and '-' characters.  Leave blank to create a file based on the URL."
				);

				$options["submit"] = "Transfer";
			}

			BB_RunPluginActionInfo("bb_content_shortcode_" . $_REQUEST["sc_action"] . "_options", $options);

			BB_PropertyForm($options);

			// AJAX upload is delay-loaded and loaded exactly one time.
?>
<script type="text/javascript">
LoadConditionalScript(Gx__RootURL + '/' + Gx__SupportPath + '/upload.js?_=20090725', true, function(loaded) {
		return ((!loaded && typeof(window.CreateUploadInterface) == 'function') || (loaded && !IsConditionalScriptLoading()));
	}, function() {
		AddPropertyChange(ManageFileUploadDestroy, CreateUploadInterface('upload_inject', <?php echo $this->CreateShortcodePropertiesJS($_REQUEST["sc_action"] . "_ajaxupload", array()); ?>, ManageFileUploadResults, <?php echo $this->CreateShortcodePropertiesJS($back_sc_action, $back_extra); ?>, '<?php echo BB_JSSafe($uploadtypes); ?>', '<?php echo BB_JSSafe($uploadtypesdesc); ?>', '<?php echo BB_JSSafe($maxfiles); ?>'));
});
</script>
<?php

			BB_RunPluginAction("post_bb_content_shortcode_" . $_REQUEST["sc_action"]);
		}

		// Shortcode-specific link and JS functions.
		public function CreateShortcodePropertiesLink($title, $sc_action, $extra = array(), $confirm = "", $useprops2 = false)
		{
			$extra["sid"] = $this->currsid;
			if ($sc_action != "")  $extra["sc_action"] = $sc_action;

			return BB_CreateWidgetPropertiesLink($title, $_REQUEST["bb_action"], $extra, $confirm, $useprops2);
		}

		public function CreateShortcodePropertiesJS($sc_action, $extra = array(), $full = false, $usebbl = false)
		{
			$extra["sid"] = $this->currsid;
			if ($sc_action != "")  $extra["sc_action"] = $sc_action;

			return BB_CreateWidgetPropertiesJS($_REQUEST["bb_action"], $extra, $full, $usebbl);
		}

		// Checks to see if the shortcode can be displayed in the current user context.
		public function IsShortcodeAllowed($sname, $key)
		{
			global $g_bb_content_shortcodes, $g_bb_content_security, $bb_account;

			if ($key == "")
			{
				if (!isset($g_bb_content_shortcodes[$sname]) || !isset($g_bb_content_shortcodes[$sname]["security"][$key]))  return ($bb_account["type"] == "dev");
				if (!isset($g_bb_content_security[$sname]) || !isset($g_bb_content_security[$sname][$key]))  return ($bb_account["type"] == "dev");

				switch ($bb_account["type"])
				{
					case "dev":  $level = 1;  break;
					case "design":  $level = 2;  break;
					case "content":  $level = 3;  break;
					default:  $level = 4;  break;
				}

				switch ($g_bb_content_security[$sname][$key])
				{
					case "dev":  $level2 = 1;  break;
					case "design":  $level2 = 2;  break;
					case "content":  $level2 = 3;  break;
					default:  $level2 = 1;  break;
				}
			}
			else
			{
				if (!isset($g_bb_content_shortcodes[$sname]) || !isset($g_bb_content_shortcodes[$sname]["security"][$key]))  return false;
				if (!isset($g_bb_content_security[$sname]) || !isset($g_bb_content_security[$sname][$key]))  return false;

				switch ($bb_account["type"])
				{
					case "content":  $level = 1;  break;
					case "design":  $level = 2;  break;
					case "dev":  $level = 3;  break;
					default:  $level = 4;  break;
				}

				switch ($g_bb_content_security[$sname][$key])
				{
					case "content":  $level2 = 1;  break;
					case "design":  $level2 = 2;  break;
					case "dev":  $level2 = 3;  break;
					default:  $level2 = 0;  break;
				}
			}

			return ($level <= $level2);
		}

		// Regenerates the content sent to the browser.  If possible, the output is cached.
		public function RegenerateContent($forcesave)
		{
			global $bb_widget, $bb_page, $bb_revision_num, $bb_profile;

			$bb_widget->css = array();
			$bb_widget->js = array();
			$bb_widget->use_premainjs = false;
			$bb_widget->body_cached = true;
			$options = array(
				"doctype" => $bb_page["doctype"],
				"shortcode_callback" => "bb_content__GenerateShortcode",
				"__parent" => false
			);
			$body_cache = bb_content_cache_profile(BB_HTMLTransformForWYMEditor((isset($bb_widget->body) ? $bb_widget->body : ""), $options));

			$bb_widget->info[$bb_profile] = array(
				"ts" => time(),
				"css" => $bb_widget->css,
				"js" => $bb_widget->js,
				"use_premainjs" => $bb_widget->use_premainjs,
				"body_cached" => $bb_widget->body_cached,
				"body_cache" => $body_cache,
				"body_doctype" => $bb_page["doctype"]
			);

			return ($forcesave || $bb_widget->body_cached ? BB_SaveLangPage($bb_revision_num) : true);
		}

		// Saves the shortcode configuration and regenerates the content.
		public function SaveShortcode($info)
		{
			global $bb_widget, $bb_revision_num;

			$bb_widget->shortcodes[$this->currsid] = $info;

			return $this->RegenerateContent(true);
		}

		public function Init()
		{
			global $bb_widget;

			if (!isset($bb_widget->shortcodes))  $bb_widget->shortcodes = array();
			if (!isset($bb_widget->info))  $bb_widget->info = array();
		}

		public function PreWidget()
		{
			global $bb_widget, $bb_account;

			if (BB_IsMemberOfPageGroup("_p"))
			{
				if ($bb_account["type"] == "dev")  echo BB_CreateWidgetPropertiesLink(BB_Translate("Configure Security"), "bb_content_configure_security");
				echo BB_CreateWidgetPropertiesLink(BB_Translate("Edit"), "bb_content_edit", array(), "", true);
			}
		}

		public function Process()
		{
			global $bb_mode, $bb_widget, $bb_page, $bb_css, $bb_use_premainjs, $bb_js, $bb_extra, $bb_profile, $bb_lastwidgetupdate;

			if ($bb_mode == "head")
			{
				if (defined("BB_MODE_EDIT"))
				{
					if (isset($bb_widget->body))
					{
						$bb_widget->css = array();
						$bb_widget->js = array();
						$bb_widget->use_premainjs = false;
						$options = array(
							"link_target" => "_top",
							"doctype" => $bb_page["doctype"],
							"shortcode_callback" => "bb_content__GenerateShortcode",
							"__parent" => $this
						);
						$this->temp_body_cache = bb_content_cache_profile(BB_HTMLTransformForWYMEditor($bb_widget->body, $options));

						$bb_css = array_merge($bb_css, $bb_widget->css);
						if ($bb_widget->use_premainjs)  $bb_use_premainjs = true;
						$bb_js = array_merge($bb_js, $bb_widget->js);
					}
				}
				else if (isset($bb_widget->body))
				{
					if (!isset($bb_widget->info[$bb_profile]) || !$bb_widget->info[$bb_profile]["body_cached"] || $bb_widget->info[$bb_profile]["ts"] <= $bb_lastwidgetupdate || $bb_widget->info[$bb_profile]["body_doctype"] != $bb_page["doctype"])  $this->RegenerateContent(false);

					$this->temp_body_cache = $bb_widget->info[$bb_profile]["body_cache"];

					if (isset($bb_widget->info[$bb_profile]["css"]))  $bb_css = array_merge($bb_css, $bb_widget->info[$bb_profile]["css"]);
					if ($bb_widget->info[$bb_profile]["use_premainjs"])  $bb_use_premainjs = true;
					if (isset($bb_widget->info[$bb_profile]["js"]))  $bb_js = array_merge($bb_js, $bb_widget->info[$bb_profile]["js"]);
				}
			}
			else if ($bb_mode == "body")
			{
				if (isset($this->temp_body_cache))  echo $this->temp_body_cache;
			}
		}

		public function ProcessAction()
		{
			global $bb_widget;

			if (isset($_REQUEST["sid"]))
			{
				$sid = (int)$_REQUEST["sid"];
				if (isset($bb_widget->shortcodes[$sid]) && isset($bb_widget->shortcodes[$sid]["_sn"]))
				{
					$sname = $bb_widget->shortcodes[$sid]["_sn"];
					if (isset($g_bb_content_shortcodes[$sname]))
					{
						$shortcode = "bb_content_shortcode_" . $sname;
						$shortcode = new $shortcode;
						$this->currsid = $sid;
						$shortcode->ProcessShortcodeAction($this);
					}
				}
			}
		}

		public function ProcessBBAction()
		{
			global $bb_widget, $bb_widget_id, $bb_account, $bb_revision_num, $g_bb_content_shortcodes, $g_bb_content_security, $g_bb_content_security_path;

			if (!BB_IsMemberOfPageGroup("_p"))  exit();

			if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_content_configure_security_submit")
			{
				BB_RunPluginAction("pre_bb_content_configure_security_submit");

				// Rebuild the security context array.
				$g_bb_content_security = array();
				foreach ($g_bb_content_shortcodes as $sname => $info)
				{
					if (isset($info["security"]))
					{
						foreach ($info["security"] as $key => $desc)
						{
							$key2 = $sname . "|" . $key;
							if (isset($_REQUEST[$key2]) && $_REQUEST[$key2] != "" && ($_REQUEST[$key2] == "content" || $_REQUEST[$key2] == "design" || $_REQUEST[$key2] == "dev"))
							{
								if (!isset($g_bb_content_security[$sname]))  $g_bb_content_security[$sname] = array();
								$g_bb_content_security[$sname][$key] = $_REQUEST[$key2];
							}
						}
					}
				}

				// Save security contexts.
				$data = "<" . "?php\n\t\$g_bb_content_security = " . BB_CreatePHPStorageData($g_bb_content_security) . ";\n?" . ">";
				if (BB_WriteFile($g_bb_content_security_path, $data) === false)  BB_PropertyFormError("Unable to save the shortcode security options.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Global shortcode security options updated.")); ?></div>
<script type="text/javascript">
window.parent.CloseProperties();
window.parent.ReloadIFrame();
</script>
<?php

				BB_RunPluginAction("post_bb_content_configure_security_submit");
			}
			else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_content_configure_security")
			{
				BB_RunPluginAction("pre_bb_content_configure_security");

				$options = array(
					"title" => "Configure Global Shortcode Security",
					"desc" => "Configure the global shortcode display options based on login account type.",
					"fields" => array(),
					"submit" => "Save",
					"focus" => true
				);

				foreach ($g_bb_content_shortcodes as $sname => $info)
				{
					if (isset($info["security"]))
					{
						foreach ($info["security"] as $key => $desc)
						{
							if ($key == "")
							{
								$options["fields"][] = array(
									"title" => $desc[0],
									"type" => "select",
									"name" => $sname . "|" . $key,
									"options" => array(
										"dev" => "Developers only",
										"design" => "Developers and Web Designers",
										"content" => "Everyone"
									),
									"select" => (isset($g_bb_content_security[$sname]) && isset($g_bb_content_security[$sname][$key]) ? $g_bb_content_security[$sname][$key] : ""),
									"desc" => $desc[1]
								);
							}
							else
							{
								$options["fields"][] = array(
									"title" => $desc[0],
									"type" => "select",
									"name" => $sname . "|" . $key,
									"options" => array(
										"" => "None",
										"content" => "Content Editors only",
										"design" => "Web Designers and Content Editors",
										"dev" => "Everyone"
									),
									"select" => (isset($g_bb_content_security[$sname]) && isset($g_bb_content_security[$sname][$key]) ? $g_bb_content_security[$sname][$key] : ""),
									"desc" => $desc[1]
								);
							}
						}
					}
				}

				BB_RunPluginActionInfo("bb_content_configure_security_options", $options);

				BB_PropertyForm($options);

				BB_RunPluginAction("post_bb_content_configure_security");
			}
			else if ($_REQUEST["bb_action"] == "bb_content_edit_load")
			{
				BB_RunPluginAction("pre_bb_content_edit_load");

				if (isset($bb_widget->body))  echo rawurlencode(UTF8::ConvertToHTML($bb_widget->body));
				else  echo rawurlencode("<p></p>");

				BB_RunPluginAction("post_bb_content_edit_load");
			}
			else if ($_REQUEST["bb_action"] == "bb_content_edit_save")
			{
				BB_RunPluginAction("pre_bb_content_edit_save");

				$options = array(
					"shortcodes" => true,
					"shortcode_placeholder" => "bb_content_shortcode_placeholder",
					"shortcode_ids" => array()
				);
				$shortcodes = $bb_widget->shortcodes;
				$base = "wid_" . htmlspecialchars($bb_widget_id) . "_";
				foreach ($shortcodes as $num => $shortcode)  $options["shortcode_ids"][$base . $num] = (isset($shortcode["_sn"]) && isset($g_bb_content_shortcodes[$shortcode["_sn"]]) ? htmlspecialchars($g_bb_content_shortcodes[$shortcode["_sn"]]["mainicon"]) : "");
				$bb_widget->body = BB_HTMLPurifyForWYMEditor($_REQUEST["content"], $options);

				if (!$this->RegenerateContent(true))  echo htmlspecialchars(BB_Translate("Unable to save content.  Try again."));
				else
				{
					echo "OK\n";
					echo "<script type=\"text/javascript\">ReloadIFrame();</script>";
				}

				BB_RunPluginAction("post_bb_content_edit_save");
			}
			else if ($_REQUEST["bb_action"] == "bb_content_edit_add_shortcode" && BB_IsSecExtraOpt("sname"))
			{
				BB_RunPluginAction("pre_bb_content_edit_add_shortcode");

				if (!isset($_REQUEST["sname"]) || !isset($g_bb_content_shortcodes[$_REQUEST["sname"]]))
				{
?>
<script type="text/javascript">
alert('<?php echo htmlspecialchars(BB_JSSafe(BB_Translate("Shortcode handler not found."))); ?>');
</script>
<?php
				}
				else if (!$this->IsShortcodeAllowed($_REQUEST["sname"], ""))
				{
?>
<script type="text/javascript">
alert('<?php echo htmlspecialchars(BB_JSSafe(BB_Translate("Shortcode access denied."))); ?>');
</script>
<?php
				}
				else
				{
					$sname = $_REQUEST["sname"];
					$sid = count($bb_widget->shortcodes);
					$bb_widget->shortcodes[] = array("_sn" => $sname, "_id" => $sid);
					if (!BB_SaveLangPage($bb_revision_num))
					{
?>
<script type="text/javascript">
alert('<?php echo htmlspecialchars(BB_JSSafe(BB_Translate("Unable to add a new %s.", $g_bb_content_shortcodes[$sname]["name"]))); ?>');
</script>
<?php
					}
					else
					{
?>
<script type="text/javascript">
InsertWYMEditorContent('contenteditor', 'wid_<?php echo BB_JSSafe($bb_widget_id); ?>', '<img id="wid_<?php echo BB_JSSafe($bb_widget_id); ?>_<?php echo $sid; ?>" class="bb_content_shortcode_placeholder" src="<?php echo htmlspecialchars(BB_JSSafe($g_bb_content_shortcodes[$sname]["mainicon"])); ?>" />');
</script>
<?php
					}
				}

				BB_RunPluginAction("post_bb_content_edit_add_shortcode");
			}
			else if ($_REQUEST["bb_action"] == "bb_content_edit_edit_shortcode" && (!isset($_REQUEST["sc_action"]) || (BB_IsSecExtraOpt("sid") && BB_IsSecExtraOpt("sc_action"))))
			{
				BB_RunPluginAction("pre_bb_content_edit_edit_shortcode");

				if (!isset($_REQUEST["sid"]))  BB_PropertyFormLoadError("Shortcode ID not specified.");

				$sid = $_REQUEST["sid"];
				$pos = strrpos($sid, "_");
				if ($pos !== false)  $sid = substr($sid, $pos + 1);
				$sid = (int)$sid;
				if (!isset($bb_widget->shortcodes[$sid]) || !isset($bb_widget->shortcodes[$sid]["_sn"]))  BB_PropertyFormLoadError("Invalid shortcode ID.");

				$sname = $bb_widget->shortcodes[$sid]["_sn"];
				if (!isset($g_bb_content_shortcodes[$sname]))  BB_PropertyFormLoadError("Shortcode handler not found.");
				if (!$this->IsShortcodeAllowed($sname, ""))  BB_PropertyFormLoadError("Shortcode access denied.");

				if (!isset($_REQUEST["sc_action"]))  $_REQUEST["sc_action"] = $sname . "_configure";
				$shortcode = "bb_content_shortcode_" . $sname;
				$shortcode = new $shortcode;
				$this->currsid = $sid;
				$shortcode->ProcessShortcodeBBAction($this);

				BB_RunPluginAction("post_bb_content_edit_edit_shortcode");
			}
			else if ($_REQUEST["bb_action"] == "bb_content_edit")
			{
				BB_RunPluginAction("pre_bb_content_edit");

?>
<script type="text/javascript">
html = '<style type="text/css">\n';
<?php
				foreach ($g_bb_content_shortcodes as $sname => $info)
				{
					$sname2 = preg_replace('/[^A-Za-z0-9_]/', "_", trim($sname));
?>
html += '.wym_skin_barebones .wym_buttons li.wym_tools_custom_<?php echo htmlspecialchars(BB_JSSafe($sname2)); ?> a  { background-image: url(<?php echo htmlspecialchars(BB_JSSafe($info["toolbaricon"])); ?>); background-repeat: no-repeat; }\n';
<?php
				}
?>
html += '</style>\n';
$("head").append(html);

window.bb_content_WYMEditorPostInit = function(eid, id, wym) {
<?php
				foreach ($g_bb_content_shortcodes as $sname => $info)
				{
					if ($this->IsShortcodeAllowed($sname, ""))
					{
						$sname2 = preg_replace('/[^A-Za-z0-9_]/', "_", trim($sname));
?>
	var html = '<li class="wym_tools_custom_<?php echo htmlspecialchars(BB_JSSafe($sname2)); ?>"><a name="<?php echo htmlspecialchars(BB_JSSafe($info["name"])); ?>" href="#"><?php echo htmlspecialchars(BB_JSSafe($info["name"])); ?></a></li>';
	$(wym._box).find(wym._options.toolsSelector + wym._options.toolsListSelector).append(html);
	$(wym._box).find('li.wym_tools_custom_<?php echo BB_JSSafe($sname2); ?> a').click(function() {
		$('#' + eid + '_loader').load(Gx__URLBase, <?php echo BB_CreateWidgetPropertiesJS("bb_content_edit_add_shortcode", array("sname" => $sname), true); ?>);

		return false;
	});
<?php
					}
				}
?>

	$(wym._doc).bind('dblclick', function(e) {
		if (e.target.tagName == 'IMG' && $(e.target).hasClass('bb_content_shortcode_placeholder') && typeof(e.target.id) == 'string' && e.target.id != '')
		{
			window.parent.LoadProperties({ 'bb_action' : 'bb_content_edit_edit_shortcode', 'wid' : '<?php echo BB_JSSafe($bb_widget_id); ?>', 'sid' : e.target.id, 'bbt' : '<?php echo BB_JSSafe(BB_CreateSecurityToken("bb_content_edit_edit_shortcode", $bb_widget_id)); ?>' });
		}
	});
}

if (typeof(window.parent.CreateWYMEditorInstance) != 'function')
{
	window.bb_content_ClosedAllContent = function(eid) {
		setTimeout(function() { DestroyWYMEditorInstance(eid);  $('#' + eid).hide(); }, 250);
	}
}

window.parent.LoadConditionalScript(Gx__RootURL + '/' + Gx__SupportPath + '/editcontent.js?_=20090725', true, function(loaded) {
		return ((!loaded && typeof(window.CreateWYMEditorInstance) == 'function') || (loaded && !IsConditionalScriptLoading()));
	}, function(params) {
		$('#contenteditor').show();

		var fileopts = {
			loadurl : Gx__URLBase,
			loadparams : <?php echo BB_CreateWidgetPropertiesJS("bb_content_edit_load", array(), true); ?>,
			id : 'wid_<?php echo BB_JSSafe($bb_widget_id); ?>',
			display : '<?php echo BB_JSSafe($bb_widget->_f); ?>',
			saveurl : Gx__URLBase,
			saveparams : <?php echo BB_CreateWidgetPropertiesJS("bb_content_edit_save", array(), true); ?>,
			wymtoolbar : 'bold,italic,superscript,subscript,pasteword,undo,redo,createlink,unlink,insertorderedlist,insertunorderedlist,indent,outdent',
			wymeditorpostinit : bb_content_WYMEditorPostInit
		};

		var editopts = {
			ismulti : true,
			closelast : bb_content_ClosedAllContent,
			width : '100%',
			height : '300px'
		};

		CreateWYMEditorInstance('contenteditor', fileopts, editopts);
});
window.parent.CloseProperties2(false);
</script>
<?php

				BB_RunPluginAction("post_bb_content_edit");
			}
			else if (isset($_REQUEST["action"]))
			{
				// Pass other requests onto the shortcode action handler.
				if (isset($_REQUEST["sid"]))
				{
					$sid = (int)$_REQUEST["sid"];
					if (isset($bb_widget->shortcodes[$sid]) && isset($bb_widget->shortcodes[$sid]["_sn"]))
					{
						$sname = $bb_widget->shortcodes[$sid]["_sn"];
						if (isset($g_bb_content_shortcodes[$sname]))
						{
							$shortcode = "bb_content_shortcode_" . $sname;
							$shortcode = new $shortcode;
							$this->currsid = $sid;
							$shortcode->ProcessShortcodeAction($this);
						}
					}
				}
			}
		}
	}
?>