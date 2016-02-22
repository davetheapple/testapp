<?php
	// Barebones CMS
	// (C) 2015 CubicleSoft.  All Rights Reserved.

	if (!defined("BB_FILE"))  exit();

	// The editor should be used when the credentials and/or tools systems need to be used.
	define("BB_MODE_EDIT", 1);

	define("BB_CORE_VER", "1.3");

	// Drag in debugging capabilities.
	require_once ROOT_PATH . "/" . SUPPORT_PATH . "/debug.php";
	SetDebugLevel();

	// Load core functions.
	require_once ROOT_PATH . "/" . SUPPORT_PATH . "/bb_functions.php";

	if (USE_HTTPS && !BB_IsSSLRequest())
	{
		header("Location: " . BB_GetFullRequestURLBase("https") . "?bb_action=bb_main_edit");
		exit();
	}

	// Load backend multilingual support.
	if (!defined("LANG_PATH"))  define("LANG_PATH", "lang");
	if (!defined("DEFAULT_LANG"))  define("DEFAULT_LANG", "");
	BB_InitLangmap(ROOT_PATH . "/" . LANG_PATH . "/", DEFAULT_LANG);

	// Load plugins.
	$plugins = BB_GetPluginList();
	foreach ($plugins as $file)  require_once ROOT_PATH . "/" . PLUGIN_PATH . "/" . $file . "/index.php";

	BB_RunPluginAction("plugins_loaded");

	// Make sure an account is loaded.  Using REQUEST allows automation and Flash-based uploads to work.
	$bb_account = false;
	if (isset($_REQUEST["bbl"]))
	{
		require_once "accounts.php";

		BB_RunPluginAction("accounts_loaded");

		if (isset($bb_accounts["sessions"][$_REQUEST["bbl"]]))
		{
			$bb_session = $bb_accounts["sessions"][$_REQUEST["bbl"]];
			if ($bb_session["expire"] < time())  BB_DeleteExpiredUserSessions();
			else  $bb_account = $bb_accounts["users"][$bb_session["username"]];
		}
	}
	if ($bb_account === false)
	{
		BB_RunPluginAction("access_denied");

		echo htmlspecialchars(BB_Translate("Invalid credentials."));
		exit();
	}
	if (isset($bb_account["lang"]) && $bb_account["lang"] != "")  BB_SetLanguage(ROOT_PATH . "/" . LANG_PATH . "/", $bb_account["lang"]);

	BB_RunPluginAction("account_valid");

	// Load in a revision, if required.
	$bb_revision_num = -1;
	$bb_revision = false;
	$bb_revision_writeable = true;
	BB_RunPluginAction("pre_revision_load");
	if (isset($_REQUEST["bb_revnum"]) && (int)$_REQUEST["bb_revnum"] > -1)
	{
		require_once $bb_dir . "/" . $bb_file . "_" . $bb_pref_lang . "_rev.php";

		$bb_revision_num = (int)$_REQUEST["bb_revnum"];
		if (!isset($bb_langpagerevisions["revisions"][$bb_revision_num]))  $bb_revision_num = -1;
		else
		{
			$bb_revision = $bb_langpagerevisions["revisions"][$bb_revision_num];
			$bb_langpage = unserialize($bb_revision[1]);
			$bb_revision_writeable = BB_IsRevisionWriteable($bb_revision_num);

			BB_RunPluginAction("revision_loaded");
		}
	}
	BB_RunPluginAction("post_revision_load");

	// Create a valid language-level security token (also known as a 'nonce').
	function BB_CreateSecurityToken($bbaction, $wid = "", $extra = "")
	{
		global $bb_langpage, $bb_pref_lang, $bb_revision_num, $bb_session;

		$str = $bbaction . ":" . $bb_pref_lang . ":" . $wid . ":" . $bb_revision_num . ":";
		if (is_string($extra) && $extra != "")
		{
			$extra = explode(",", $extra);
			foreach ($extra as $key)
			{
				$key = trim($key);
				if ($key != "" && isset($_REQUEST[$key]))  $str .= (string)$_REQUEST[$key] . ":";
			}
		}
		else if (is_array($extra))
		{
			foreach ($extra as $val)  $str .= $val . ":";
		}

		return hash_hmac("sha1", $str, pack("H*", $bb_langpage["pagerand"]) . ":" . pack("H*", $bb_session["session2"]) . ":" . pack("H*", BASE_RAND_SEED2));
	}

	// Supporting functions to simplify generating and validating security token based links.
	function BB_IsSecExtraOpt($opt)
	{
		return (isset($_REQUEST["bb_sec_extra"]) && strpos("," . $_REQUEST["bb_sec_extra"] . ",", "," . $opt . ",") !== false);
	}

	function BB_CreatePropertiesLink($title, $bbaction, $extra = array(), $confirm = "", $useprops2 = false, $parentwindow = false)
	{
		if (!isset($extra["wid"]))  $wid = "";
		else
		{
			$wid = $extra["wid"];
			unset($extra["wid"]);
		}

		$extra2 = "";
		foreach ($extra as $key => $val)  $extra2 .= ", '" . htmlspecialchars(BB_JSSafe($key)) . "' : '" . htmlspecialchars(BB_JSSafe($val)) . "'";

		return "<a href=\"#\" onclick=\"" . ($confirm != "" ? "if (confirm('" . htmlspecialchars(BB_JSSafe($confirm)) . "'))  " : "return ") . ($parentwindow ? "window.parent." : "") . "LoadProperties" . ($useprops2 ? "2" : "") . "({'bb_action' : '" . htmlspecialchars(BB_JSSafe($bbaction)) . "'" . ($wid != "" ? ", 'wid' : '" . htmlspecialchars(BB_JSSafe($wid)) . "'" : "") . $extra2 . ", 'bbt' : '" . htmlspecialchars(BB_JSSafe(BB_CreateSecurityToken($bbaction, $wid, array_values($extra)))) . "', 'bb_sec_extra' : '" . htmlspecialchars(BB_JSSafe(implode(",", array_keys($extra)))) . "'});" . ($confirm != "" ? "  return false;" : "") . "\">" . htmlspecialchars($title) . "</a>";
	}

	function BB_CreatePropertiesJS($bbaction, $extra = array(), $full = false, $usebbl = false)
	{
		global $bb_pref_lang, $bb_revision_num;

		if (!isset($extra["wid"]))  $wid = "";
		else
		{
			$wid = $extra["wid"];
			unset($extra["wid"]);
		}

		$options = array();
		if ($full)
		{
			if ($usebbl)  $options["bbl"] = (string)$_REQUEST["bbl"];
			$options["lang"] = (string)$bb_pref_lang;
			if ($bb_revision_num > -1)  $options["bb_revnum"] = (string)$bb_revision_num;
		}
		$options["bb_action"] = $bbaction;
		if ($wid != "")  $options["wid"] = (string)$wid;

		foreach ($extra as $key => $val)  $options[(string)$key] = (string)$val;

		$options["bbt"] = BB_CreateSecurityToken($bbaction, $wid, array_values($extra));
		$options["bb_sec_extra"] = implode(",", array_keys($extra));

		BB_RunPluginActionInfo("bb_createpropertiesjs", $options);

		return json_encode($options);
	}

	function BB_CreateWidgetPropertiesLink($title, $bbaction, $extra = array(), $confirm = "", $useprops2 = false)
	{
		global $bb_widget_id;

		$extra["wid"] = $bb_widget_id;

		return BB_CreatePropertiesLink($title, $bbaction, $extra, $confirm, $useprops2, true);
	}

	function BB_CreateWidgetPropertiesJS($bbaction, $extra = array(), $full = false, $usebbl = false)
	{
		global $bb_widget_id;

		$extra["wid"] = $bb_widget_id;

		return BB_CreatePropertiesJS($bbaction, $extra, $full, $usebbl);
	}

	// Check the security token.  If it doesn't exist, load the main page.
	if ($_REQUEST["bb_action"] != "bb_main_edit" && (!isset($_REQUEST["bbt"]) || $_REQUEST["bbt"] != BB_CreateSecurityToken($_REQUEST["bb_action"], (isset($_REQUEST["wid"]) ? $_REQUEST["wid"] : ""), (isset($_REQUEST["bb_sec_extra"]) ? $_REQUEST["bb_sec_extra"] : ""))))
	{
		echo htmlspecialchars(BB_Translate("Invalid security token."));
		exit();
	}

	BB_RunPluginAction("access_granted");

	// Select cache profile.
	if (isset($_REQUEST["bb_profile"]) && isset($bb_profiles[$_REQUEST["bb_profile"]]))  $bb_profile = (string)$_REQUEST["bb_profile"];
	else  $bb_profile = "";

	if (preg_replace('/[^A-Za-z0-9_\-]/', "", $bb_profile) !== $bb_profile)
	{
		echo htmlspecialchars(BB_Translate("Invalid cache profile.  Cache profile can only contain alphanumeric (A-Z, 0-9), '_', and '-' characters."));
		exit();
	}

	// Load widgets.
	BB_RunPluginAction("pre_widgets_load");
	foreach ($bb_langpage["widgets"] as $id => $data)
	{
		$bb_widget->SetID($id);
		BB_RunPluginAction("pre_widget_load");
		if ($bb_widget->_m === false && $bb_widget->_a !== false && file_exists(ROOT_PATH . "/" . WIDGET_PATH . "/" . $bb_widget->_file))
		{
			require_once ROOT_PATH . "/" . WIDGET_PATH . "/" . $bb_widget->_file;
			BB_InitWidget();
		}
		BB_RunPluginAction("post_widget_load");
	}
	$bb_widget->SetID("");
	BB_RunPluginAction("post_widgets_load");

	// Master widget functions.
	function BB_MasterWidgetManager($name)
	{
		global $bb_account, $bb_mode, $bb_widget, $bb_langpage, $bb_css, $bb_js;

		if ($bb_mode == "head")
		{
			$bb_css[ROOT_URL . "/" . SUPPORT_PATH . "/css/widgets.css"] = ROOT_PATH . "/" . SUPPORT_PATH . "/css/widgets.css";
			$bb_js[ROOT_URL . "/" . SUPPORT_PATH . "/js/widgets.js"] = ROOT_PATH . "/" . SUPPORT_PATH . "/js/widgets.js";
		}
		else if ($bb_account["type"] == "dev" || $bb_account["type"] == "design")
		{
			$widget = $bb_langpage["widgets"][$name];
			if ($bb_mode == "body")
			{
?>
<div class="bb_mw_manager">
	<b><?php echo htmlspecialchars(BB_Translate("%s Options", $widget["_f"])); ?></b><br />
	<div class="bb_mw_manager_links">
<?php
				echo BB_CreatePropertiesLink(BB_Translate("Add Widget"), "bb_main_edit_widgets_add_widget", array("wid" => $name), "", false, true);
				if (count($widget["_ids"]) > 1)  echo BB_CreatePropertiesLink(BB_Translate("Reorder Widgets"), "bb_main_edit_widgets_reorder", array("wid" => $name), "", false, true);

				// Find orphaned widgets.
				foreach ($bb_langpage["widgets"] as $id => $widget2)
				{
					if ($widget2["_a"] === false)
					{
						echo BB_CreatePropertiesLink(BB_Translate("Delete '%s'.", $widget2["_f"]), "bb_main_edit_widgets_delete_widget", array("wid" => $id), BB_Translate("Are you sure you want to delete '%s'?", $widget2["_f"]), false, true);
						if ($widget2["_m"] === false)  echo BB_CreatePropertiesLink(BB_Translate("Attach '%s'.", $widget2["_f"]), "bb_main_edit_widgets_attach_widget", array("wid" => $id, "pid" => $name), "", false, true);
					}
				}
?>
	</div>
</div>
<?php
			}
		}
	}

	function BB_MasterWidgetPreWidget($displaymwm)
	{
		global $bb_account, $bb_widget, $bb_widget_id, $bb_widget_instances;

		ob_start();

		$bb_widget_instances[$bb_widget_id]->PreWidget();

		if ($displaymwm && ($bb_account["type"] == "dev" || $bb_account["type"] == "design"))
		{
			echo BB_CreateWidgetPropertiesLink(BB_Translate("Detach"), "bb_main_edit_widgets_detach_widget");
			echo BB_CreateWidgetPropertiesLink(BB_Translate("Delete"), "bb_main_edit_widgets_delete_widget", array(), BB_Translate("Are you sure you want to detach and delete this widget?"));
		}

		$data = ob_get_contents();
		ob_end_clean();

		$data = trim($data);
		if ($data != "")
		{
?>
<div class="bb_mw_manager">
	<div class="bb_mw_manager_type">[<?php echo htmlspecialchars($bb_widget->_n); ?>]</div>
	<b><?php echo htmlspecialchars(BB_Translate("%s Options", $bb_widget->_f)); ?></b><br />
	<div class="bb_mw_manager_links">
<?php echo $data; ?>
	</div>
</div>
<?php
		}
	}

	function BB_PropertyForm($options)
	{
		global $bb_pref_lang, $bb_revision_num;

		BB_RunPluginActionInfo("pre_bb_propertyform", $options);

		$autofocus = false;

?>
	<div class="propclose"><a href="#" onclick="return CloseProperties();">X</a></div>
	<div class="proptitle"><?php echo htmlspecialchars(BB_Translate($options["title"])); ?></div>
	<div class="propdesc"><?php echo htmlspecialchars(BB_Translate($options["desc"])); ?><?php if (isset($options["htmldesc"]))  echo $options["htmldesc"]; ?></div>
	<div class="propinfo"></div>
	<div class="propmain">
<?php
		if (isset($options["submit"]) || (isset($options["useform"]) && $options["useform"]))
		{
?>
		<form id="propform" method="post" onsubmit="return SendProperties();">
		<input type="hidden" name="bb_action" value="<?php echo htmlspecialchars(isset($options["bb_action"]) && is_string($options["bb_action"]) ? $options["bb_action"] : $_REQUEST["bb_action"] . "_submit"); ?>" />
		<input type="hidden" name="lang" value="<?php echo htmlspecialchars($bb_pref_lang); ?>" />
<?php
			if ($bb_revision_num > -1)
			{
?>
		<input type="hidden" name="bb_revnum" value="<?php echo $bb_revision_num; ?>" />
<?php
			}

			if (isset($_REQUEST["wid"]))
			{
?>
		<input type="hidden" name="wid" value="<?php echo htmlspecialchars($_REQUEST["wid"]); ?>" />
<?php
			}

			$extra = array();
			if (isset($options["hidden"]))
			{
				foreach ($options["hidden"] as $name => $value)
				{
?>
		<input type="hidden" name="<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars($value); ?>" />
<?php
					$extra[] = $value;
				}

?>
		<input type="hidden" name="bb_sec_extra" value="<?php echo htmlspecialchars(implode(",", array_keys($options["hidden"]))); ?>" />
<?php
			}
?>
		<input type="hidden" name="bbt" value="<?php echo htmlspecialchars(BB_CreateSecurityToken((isset($options["bb_action"]) && is_string($options["bb_action"]) ? $options["bb_action"] : $_REQUEST["bb_action"] . "_submit"), (isset($_REQUEST["wid"]) ? $_REQUEST["wid"] : ""), $extra)); ?>" />
<?php
			unset($extra);
		}

		if (isset($options["fields"]))
		{
?>
		<div class="formfields<?php if (count($options["fields"]) == 1 && !isset($options["fields"][0]["title"]))  echo " alt"; ?>">
<?php
			foreach ($options["fields"] as $num => $field)
			{
?>
			<div class="formitem">
<?php
				if (isset($field["title"]))
				{
?>
			<div class="formitemtitle"><?php echo htmlspecialchars(BB_Translate($field["title"])); ?></div>
<?php
				}

				switch ($field["type"])
				{
					case "static":
					{
?>
			<div class="static"><?php echo htmlspecialchars($field["value"]); ?></div>
<?php
					break;
					}
					case "text":
					{
						if ($autofocus === false)  $autofocus = htmlspecialchars("f" . $num . "_" . $field["name"]);
?>
			<input class="text" type="text" id="<?php echo htmlspecialchars("f" . $num . "_" . $field["name"]); ?>" name="<?php echo htmlspecialchars($field["name"]); ?>" value="<?php echo htmlspecialchars($field["value"]); ?>" />
<?php
						break;
					}
					case "password":
					{
						if ($autofocus === false)  $autofocus = htmlspecialchars("f" . $num . "_" . $field["name"]);
?>
			<input class="text" type="password" id="<?php echo htmlspecialchars("f" . $num . "_" . $field["name"]); ?>" name="<?php echo htmlspecialchars($field["name"]); ?>" value="<?php echo htmlspecialchars($field["value"]); ?>" />
<?php
						break;
					}
					case "checkbox":
					{
						if ($autofocus === false)  $autofocus = htmlspecialchars("f" . $num . "_" . $field["name"]);
?>
			<input class="checkbox" type="checkbox" id="<?php echo htmlspecialchars("f" . $num . "_" . $field["name"]); ?>" name="<?php echo htmlspecialchars($field["name"]); ?>" value="<?php echo htmlspecialchars($field["value"]); ?>" <?php if (isset($field["check"]) && $field["check"])  echo "checked"; ?> />
			<label for="<?php echo htmlspecialchars("f" . $num . "_" . $field["name"]); ?>"><?php echo htmlspecialchars(BB_Translate($field["display"])); ?></label>
<?php
						break;
					}
					case "select":
					{
						if ($autofocus === false)  $autofocus = htmlspecialchars("f" . $num . "_" . $field["name"]);

						if (!isset($field["multiple"]) || $field["multiple"] !== true)  $mode = "select";
						else  $mode = "checkbox";

						if (!isset($field["select"]))  $field["select"] = array();
						else if (is_string($field["select"]))  $field["select"] = array($field["select"] => true);

						$idbase = htmlspecialchars("f" . $num . "_" . $field["name"]);
						if ($mode == "checkbox")
						{
							$idnum = 0;
							foreach ($field["options"] as $name => $value)
							{
								if (is_array($value))
								{
									foreach ($value as $name2 => $value2)
									{
										$id = $idbase . ($idnum ? "_" . $idnum : "");
?>
			<input class="checkbox" type="checkbox" id="<?php echo $id; ?>" name="<?php echo htmlspecialchars($field["name"]); ?>[]" value="<?php echo htmlspecialchars($name2); ?>"<?php if (isset($field["select"][$name2]))  echo " checked"; ?> />
			<label for="<?php echo $id; ?>"><?php echo htmlspecialchars(BB_Translate($name)); ?> - <?php echo ($value2 == "" ? "&nbsp;" : htmlspecialchars(BB_Translate($value2))); ?></label><br />
<?php
										$idnum++;
									}
								}
								else
								{
									$id = $idbase . ($idnum ? "_" . $idnum : "");
?>
			<input class="checkbox" type="checkbox" id="<?php echo $id; ?>" name="<?php echo htmlspecialchars($field["name"]); ?>[]" value="<?php echo htmlspecialchars($name); ?>"<?php if (isset($field["select"][$name]))  echo " checked"; ?> />
			<label for="<?php echo $id; ?>"><?php echo ($value == "" ? "&nbsp;" : htmlspecialchars(BB_Translate($value))); ?></label><br />
<?php
									$idnum++;
								}
							}
						}
						else
						{
?>
			<select id="<?php echo $idbase; ?>" name="<?php echo htmlspecialchars($field["name"]) . (isset($field["multiple"]) && $field["multiple"] === true ? "[]" : ""); ?>"<?php if (isset($field["multiple"]) && $field["multiple"] === true)  echo " multiple"; ?>>
<?php
							foreach ($field["options"] as $name => $value)
							{
								if (is_array($value))
								{
?>
				<optgroup label="<?php echo htmlspecialchars($name); ?>">
<?php
									foreach ($value as $name2 => $value2)
									{
?>
					<option value="<?php echo htmlspecialchars($name2); ?>"<?php if (isset($field["select"][$name2]))  echo " selected"; ?>><?php echo ($value2 == "" ? "&nbsp;" : htmlspecialchars(BB_Translate($value2))); ?></option>
<?php
									}
?>
				</optgroup>
<?php
								}
								else
								{
?>
				<option value="<?php echo htmlspecialchars($name); ?>"<?php if (isset($field["select"][$name]))  echo " selected"; ?>><?php echo ($value == "" ? "&nbsp;" : htmlspecialchars(BB_Translate($value))); ?></option>
<?php
								}
							}
?>
			</select>
<?php
						}

						break;
					}
					case "textarea":
					{
						if ($autofocus === false)  $autofocus = htmlspecialchars("f" . $num . "_" . $field["name"]);
?>
			<div class="textareawrap"><textarea class="text" id="<?php echo htmlspecialchars("f" . $num . "_" . $field["name"]); ?>" name="<?php echo htmlspecialchars($field["name"]); ?>" rows="5" cols="50"><?php echo htmlspecialchars($field["value"]); ?></textarea></div>
<?php
						break;
					}
					case "table":
					{
						$order = isset($field["order"]) && (isset($options["submit"]) || (isset($options["useform"]) && $options["useform"])) ? $field["order"] : "";
?>
			<table id="<?php echo htmlspecialchars("f" . $num . "_" . (isset($field["name"]) ? $field["name"] : "table")); ?>">
				<tr class="head<?php if ($order != "")  echo " nodrag nodrop"; ?>">
<?php
						if ($order != "")
						{
?>
					<th><?php echo htmlspecialchars(BB_Translate($order)); ?></th>
<?php
						}

						foreach ($field["cols"] as $col)
						{
?>
					<th><?php echo htmlspecialchars(BB_Translate($col)); ?></th>
<?php
						}
?>
				</tr>
<?php
						$num2 = 0;
						foreach ($field["rows"] as $row)
						{
?>
				<tr id="<?php echo htmlspecialchars("f" . $num . "_" . (isset($field["name"]) ? $field["name"] : "table") . "_" . $num2); ?>" class="row<?php if ($num2 % 2)  echo " altrow"; ?>">
<?php
							if ($order != "")
							{
?>
					<td class="draghandle">&nbsp;</td>
<?php
							}

							foreach ($row as $col)
							{
?>
					<td><?php echo $col; ?></td>
<?php
							}
?>
				</tr>
<?php
							$num2++;
						}
?>
			</table>
<?php

						if ($order != "")
						{
?>
			<script type="text/javascript">
			InitPropertiesTableDragAndDrop('<?php echo BB_JSSafe("f" . $num . "_" . (isset($field["name"]) ? $field["name"] : "table")); ?>');
			</script>
<?php
						}

						break;
					}
					case "custom":
					{
						echo $field["value"];
						break;
					}
				}

				if (isset($field["desc"]) && $field["desc"] != "")
				{
?>
			<div class="formitemdesc"><?php echo htmlspecialchars(BB_Translate($field["desc"])); ?></div>
<?php
				}
				else if (isset($field["htmldesc"]) && $field["htmldesc"] != "")
				{
?>
			<div class="formitemdesc"><?php echo BB_Translate($field["htmldesc"]); ?></div>
<?php
				}
?>
			</div>
<?php
			}
?>
		</div>
<?php
		}

		if (isset($options["submit"]))
		{
?>
		<div class="formsubmit">
			<input class="submit" type="submit" value="<?php echo htmlspecialchars(BB_Translate($options["submit"])); ?>" />
		</div>
<?php
		}

		if (isset($options["submit"]) || (isset($options["useform"]) && $options["useform"]))
		{
?>
		</form>
<?php
		}
?>
	</div>
<?php
		if (isset($options["focus"]) && (is_string($options["focus"]) || ($options["focus"] === true && $autofocus !== false)))
		{
?>
	<script type="text/javascript">
	$('#<?php echo BB_JSSafe(is_string($options["focus"]) ? $options["focus"] : $autofocus); ?>').focus();
	</script>
<?php
		}
	}

	function BB_PropertyFormError($message)
	{
		BB_RunPluginActionInfo("pre_bb_propertyformerror", $message);

?>
<div class="error"><?php echo htmlspecialchars(BB_Translate($message)); ?></div>
<?php
		exit();
	}

	function BB_PropertyFormLoadError($message, $alt = false)
	{
		BB_RunPluginActionInfo("pre_bb_propertyformloaderror", $message);

?>
<div class="error"><?php echo htmlspecialchars(BB_Translate($message)); ?></div>
<script type="text/javascript">
CloseProperties<?php if ($alt)  echo "2"; ?>();
</script>
<?php
		exit();
	}

	// A couple oddball bits of functionality.
	$bb_def_extmap = array(
		".php" => array("edit" => "ea", "syntax" => "php"),
		".css" => array("edit" => "ea", "syntax" => "css"),
		".scss" => array("edit" => "ea", "syntax" => "scss"),
		".js" => array("edit" => "ea", "syntax" => "js"),
		".html" => array("edit" => "ea", "syntax" => "html"),
		".htm" => array("edit" => "ea", "syntax" => "html"),
		".xhtml" => array("edit" => "ea", "syntax" => "html"),
		".xml" => array("edit" => "ea", "syntax" => "xml"),
		".rss" => array("edit" => "ea", "syntax" => "rss"),
		".svg" => array("edit" => "ea", "syntax" => "svg"),
		".txt" => array("edit" => "ea", "syntax" => "text"),
		".cfm" => array("edit" => "ea", "syntax" => "coldfusion"),
		".cfml" => array("edit" => "ea", "syntax" => "coldfusion"),
		".java" => array("edit" => "ea", "syntax" => "java"),
		".py" => array("edit" => "ea", "syntax" => "python"),
		".rb" => array("edit" => "ea", "syntax" => "ruby"),
		".rbx" => array("edit" => "ea", "syntax" => "ruby"),
		".rhtml" => array("edit" => "ea", "syntax" => "ruby"),
		".pl" => array("edit" => "ea", "syntax" => "perl"),
		".sql" => array("edit" => "ea", "syntax" => "sql"),
		".cpp" => array("edit" => "ea", "syntax" => "cpp"),
		".c" => array("edit" => "ea", "syntax" => "c"),
		".cs" => array("edit" => "ea", "syntax" => "csharp"),
		".vb" => array("edit" => "ea", "syntax" => "vb"),
		".vbs" => array("edit" => "ea", "syntax" => "vb"),
		".ml" => array("edit" => "ea", "syntax" => "ocaml"),
		".bas" => array("edit" => "ea", "syntax" => "basic"),
		".pas" => array("edit" => "ea", "syntax" => "pascal")
	);

	function BB_FileExplorer_ReplaceStr($find, $replace, $str)
	{
		return str_replace(array("%%HTML_" . $find . "%%", "%%HTML_JS_" . $find . "%%"), array(htmlspecialchars($replace), htmlspecialchars(BB_JSSafe($replace))), $str);
	}

	function BB_FileExplorer_GetActionStr($dir, $file, $wid = "")
	{
		global $editmap, $extmap;

		$pos = strrpos($file, ".");
		if ($pos === false)  return "";
		$ext = substr($file, $pos);
		if (!isset($extmap[$ext]) || !isset($extmap[$ext]["edit"]) || !isset($editmap[$extmap[$ext]["edit"]]))  return "";

		$data = $editmap[$extmap[$ext]["edit"]];
		$result = $data[0];
		$result = BB_FileExplorer_ReplaceStr("DIR", $dir, $result);
		$result = BB_FileExplorer_ReplaceStr("FILE", $file, $result);
		$result = BB_FileExplorer_ReplaceStr("LOADTOKEN", BB_CreateSecurityToken("bb_main_edit_site_opt_file_explorer_edit_load", $wid, array($dir . "/" . $file)), $result);
		$result = BB_FileExplorer_ReplaceStr("SAVETOKEN", BB_CreateSecurityToken("bb_main_edit_site_opt_file_explorer_edit_save", $wid, array($dir . "/" . $file)), $result);
		$opts = explode(",", $data[1]);
		foreach ($opts as $opt)
		{
			$result = BB_FileExplorer_ReplaceStr($opt, (isset($extmap[$ext][$opt]) ? $extmap[$ext][$opt] : ""), $result);
		}

		return $result;
	}

	BB_RunPluginAction("init");

	if ($_REQUEST["bb_action"] == "bb_main_edit")
	{
		header("Content-Type: text/html; charset=UTF-8");

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?php echo BB_Translate("Loading..."); ?></title>
<link rel="stylesheet" href="<?php echo htmlspecialchars(ROOT_URL); ?>/<?php echo htmlspecialchars(SUPPORT_PATH); ?>/css/mainedit.css" type="text/css" media="all" />
<?php BB_PreMainJS(); ?>
<script type="text/javascript">
var Gx__PageInfo = {
	'menu_token' : '<?php echo BB_JSSafe(BB_CreateSecurityToken("bb_main_edit_menu")); ?>',
	'mainviewinfo_token' : '<?php echo BB_JSSafe(BB_CreateSecurityToken("bb_main_edit_mainviewinfo")); ?>',
	'mainviewinfo_notify' : <?php echo (isset($_REQUEST["notify"]) ? (int)$_REQUEST["notify"] : -1); ?>,
	'mainviewinfo_profile' : '<?php echo BB_JSSafe(htmlspecialchars($bb_profile)); ?>',
	'user' : '<?php echo BB_JSSafe(htmlspecialchars($bb_account["user"])); ?>',
	'type' : '<?php echo BB_JSSafe(htmlspecialchars($bb_account["type"])); ?>',
	'group' : '<?php echo BB_JSSafe(htmlspecialchars($bb_account["group"])); ?>',
	'expire' : <?php echo $bb_session["expire"] - time(); ?>,
	'start' : new Date()
};
</script>
<script type="text/javascript" src="<?php echo htmlspecialchars(ROOT_URL); ?>/<?php echo htmlspecialchars(SUPPORT_PATH); ?>/jquery-1.11.0<?php echo (defined("DEBUG_JS") ? "" : ".min"); ?>.js"></script>
<script type="text/javascript" src="<?php echo htmlspecialchars(ROOT_URL); ?>/<?php echo htmlspecialchars(SUPPORT_PATH); ?>/js/mainedit.js"></script>
</head>
<body>
<div class="pagewrap">
	<div class="contentwrap">
		<div class="colmask">
			<div class="colright">
				<div class="col1wrap">
					<div class="col1">
						<div class="col1inner">
							<div class="maincontent">
								<div id="mainprops"></div>
								<div id="mainprops2"></div>
								<div id="loadcondscripts"></div>
								<div id="contenteditor"></div>
								<div id="fileeditor"></div>
								<div id="mainviewwrap">
									<div id="navbutton"><?php echo htmlspecialchars(BB_Translate("Menu")); ?></div><div id="navdropdown"><div class="leftnav"></div></div><div id="mainviewinfo"></div>
									<iframe id="mainview" src="<?php echo BB_GetRequestURLBase(); ?>?bb_action=bb_main_edit_view&lang=<?php echo urlencode($bb_pref_lang); ?><?php if ($bb_revision_num > -1)  echo "&bb_revnum=" . $bb_revision_num; ?><?php if ($bb_profile != "")  echo "&bb_profile=" . urlencode($bb_profile); ?>&bbt=<?php echo htmlspecialchars(BB_CreateSecurityToken("bb_main_edit_view")); ?>" width="100%" height="1" frameborder="0"></iframe>
									<div id="sessioninfo"></div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col2"><div class="leftnav"></div></div>
			</div>
		</div>
	</div>
	<div class="stickycol"></div>
</div>
</body>
</html>
<?php
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_mainviewinfo")
	{
		BB_RunPluginAction("pre_bb_main_edit_mainviewinfo");

		// Extensions check.
		BB_LoadExtensionsCache();
		BB_UpdateExtensionsCache();

		if (count($bb_extensions_info["vulnerabilities"]))  echo "<div id=\"mainviewinfo_vulnerabilities\">" . BB_HTMLPurify(implode("<br /><br />", $bb_extensions_info["vulnerabilities"])) . "</div>";
		if (count($bb_extensions_info["updates"]))  echo "<div id=\"mainviewinfo_updates\">" . BB_HTMLPurify(implode("<br /><br />", $bb_extensions_info["updates"])) . "</div>";

		echo ($bb_revision_writeable ? "" : BB_Translate("<b>[Read Only]</b> "));
		echo htmlspecialchars(BB_Translate(BB_GetIANADesc($bb_pref_lang)));
		echo ($bb_pref_lang == $bb_page["defaultlang"] ? BB_Translate(" [default]") : "");
		echo ($bb_profile != "" ? htmlspecialchars(BB_Translate(", " . $bb_profiles[$bb_profile])) : "");

		if ($bb_revision_num < 0)  echo ", <a href=\"" . htmlspecialchars(BB_GetFullRequestURLBase("http") . "?lang=" . urlencode($bb_pref_lang)) . "\">" . BB_Translate("Live Page") . "</a>";
		else  echo BB_Translate(", Revision #%d, %s, Reason: %s | %s", $bb_revision_num, ($bb_revision[0] == "" ? BB_Translate("<i>[Root]</i>") : htmlspecialchars($bb_revision[0])), htmlspecialchars($bb_revision[4]), "<a href=\"" . htmlspecialchars(BB_GetFullRequestURLBase("http") . "?lang=" . urlencode($bb_pref_lang)) . "\">" . BB_Translate("Live Page") . "</a>");

		if (isset($_REQUEST["notify"]))
		{
			require_once "translate.php";

			if (isset($bb_translate_notify[(int)$_REQUEST["notify"]]))
			{
				$entry = $bb_translate_notify[(int)$_REQUEST["notify"]];
				echo BB_Translate("<br />%s, %s, %s =&gt; %s, %s", htmlspecialchars($entry[0]), BB_FormatTimestamp($entry[1]), htmlspecialchars(BB_Translate(BB_GetIANADesc($entry[4], true, true))), htmlspecialchars(BB_Translate(BB_GetIANADesc($entry[5], true, true))), htmlspecialchars($entry[6]));
			}
		}

		BB_RunPluginAction("post_bb_main_edit_mainviewinfo");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_view")
	{
		BB_RunPluginAction("pre_bb_main_edit_view");

		if ($bb_page["redirect"] != "")  header("Location: " . $bb_page["redirect"]);
		else  BB_ProcessPage(false, true, false);

		BB_RunPluginAction("post_bb_main_edit_view");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_menu")
	{
		BB_RunPluginAction("pre_bb_main_edit_menu");

?>
	<div class="menu">
		<div class="title"><?php echo BB_Translate("Page Options"); ?></div>
<?php
		BB_RunPluginAction("bb_main_edit_menu_pre_page_options");

		echo BB_CreatePropertiesLink(BB_Translate("Edit Properties"), "bb_main_edit_page_opt_properties");
		if (count($bb_profiles) > 1)  echo BB_CreatePropertiesLink(BB_Translate("View Cache Profiles"), "bb_main_edit_page_opt_view_cache_profiles");
		echo BB_CreatePropertiesLink(BB_Translate("Create New Branch"), "bb_main_edit_page_opt_create_branch");

		if ($bb_revision_num > -1)
		{
			if ($bb_revision_writeable && $bb_revision[0] != "")  echo BB_CreatePropertiesLink(BB_Translate("Close Branch"), "bb_main_edit_page_opt_close_branch");
			echo BB_CreatePropertiesLink(BB_Translate("Copy To Live Page"), "bb_main_edit_page_opt_overwrite_live_page");
		}

		echo BB_CreatePropertiesLink(BB_Translate("Create New Revision"), "bb_main_edit_page_opt_create_revision");
		echo BB_CreatePropertiesLink(BB_Translate("View Revisions"), "bb_main_edit_page_opt_view_revisions");

		if ($bb_revision_num < 0)  echo BB_CreatePropertiesLink(BB_Translate("Notify Translators"), "bb_main_edit_page_opt_ping_translation");

		echo BB_CreatePropertiesLink(BB_Translate("Create New Translation"), "bb_main_edit_page_opt_create_translation");
		echo BB_CreatePropertiesLink(BB_Translate("View Translations"), "bb_main_edit_page_opt_view_translations");

		BB_RunPluginAction("bb_main_edit_menu_post_page_options");
?>
	</div>
	<div class="menu">
		<div class="title"><?php echo BB_Translate("Site Options"); ?></div>
<?php
		BB_RunPluginAction("bb_main_edit_menu_pre_site_options");

		echo BB_CreatePropertiesLink(BB_Translate("File Explorer"), "bb_main_edit_site_opt_file_explorer");
		echo BB_CreatePropertiesLink(BB_Translate("View Notifications"), "bb_main_edit_site_opt_view_translation_notifications");
		echo BB_CreatePropertiesLink(BB_Translate("Edit Profile"), "bb_main_edit_site_opt_profile");

		if ($bb_account["type"] == "dev")
		{
			echo BB_CreatePropertiesLink(BB_Translate("Create New Account"), "bb_main_edit_site_opt_create_account");
			if (count($bb_accounts["users"]) > 1)  echo BB_CreatePropertiesLink(BB_Translate("Delete an Account"), "bb_main_edit_site_opt_delete_account");
			if (function_exists("zip_open"))  echo BB_CreatePropertiesLink(BB_Translate("Manage Extensions"), "bb_main_edit_site_opt_manage_extensions");
			echo BB_CreatePropertiesLink(BB_Translate("Create New Widget"), "bb_main_edit_site_opt_create_widget");
			echo BB_CreatePropertiesLink(BB_Translate("Delete Widget"), "bb_main_edit_site_opt_delete_widget");
		}

		echo BB_CreatePropertiesLink(BB_Translate("Flush Cache"), "bb_main_edit_site_opt_flush_cache", array(), "", true);
		echo BB_CreatePropertiesLink(BB_Translate("Logout"), "bb_main_edit_site_opt_logout");

		BB_RunPluginAction("bb_main_edit_menu_post_site_options");
?>
	</div>
	<div class="menu">
		<div class="title">Barebones CMS</div>
<?php
		BB_RunPluginAction("bb_main_edit_menu_pre_support_options");
?>
		<a href="http://barebonescms.com/" target="_blank"><?php echo BB_Translate("Homepage"); ?></a>
		<a style="color: #008800;" href="http://barebonescms.com/donate/" target="_blank" title="Don't be a cheapskate.  Support the author to keep Barebones CMS development going."><?php echo BB_Translate("Donate"); ?></a>
		<a href="http://barebonescms.com/forums/" target="_blank"><?php echo BB_Translate("Forums"); ?></a>
<?php
		BB_RunPluginAction("bb_main_edit_menu_post_support_options");
?>
	</div>
<?php

		BB_RunPluginAction("post_bb_main_edit_menu");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_page_opt_properties_submit")
	{
		BB_RunPluginAction("pre_bb_main_edit_page_opt_properties_submit");

		if (!BB_IsMemberOfPageGroup("_p"))  BB_PropertyFormError("You do not have permissions to edit the page properties.");

		if ($bb_account["type"] == "content")
		{
			if (!$bb_revision_writeable)  BB_PropertyFormError("Page properties are not writeable.  This can occur when a new revision within an existing branch is created while editing an editable revision.");

			$bb_langpage["title"] = $_REQUEST["title"];
			$bb_langpage["metadesc"] = $_REQUEST["metadesc"];

			BB_SaveLangPage($bb_revision_num);
			BB_UpdateSitemaps();
		}
		else if ($bb_account["type"] == "dev" || $bb_account["type"] == "design")
		{
			$bb_page["redirect"] = $_REQUEST["redirect"];
			$bb_page["cachetime"] = (int)$_REQUEST["cachetime"];
			if ($bb_page["cachetime"] < 0)  $bb_page["cachetime"] = -1;
			$bb_page["easyedit"] = ($_REQUEST["easyedit"] == "enable");
			$bb_page["sitemap"] = ($_REQUEST["sitemap"] == "include");
			$bb_page["sitemappriority"] = $_REQUEST["sitemappriority"];
			$bb_page["doctype"] = $_REQUEST["doctype"];
			$bb_page["metarobots"] = (isset($_REQUEST["metarobots"]) && is_array($_REQUEST["metarobots"]) ? implode(",", $_REQUEST["metarobots"]) : "");
			$bb_page["perms"]["_p"] = array();
			if (isset($_REQUEST["perms"]) && is_array($_REQUEST["perms"]))
			{
				foreach ($_REQUEST["perms"] as $group)  $bb_page["perms"]["_p"][$group] = true;
			}

			if ($bb_revision_writeable)
			{
				$bb_langpage["title"] = $_REQUEST["title"];
				$bb_langpage["metadesc"] = $_REQUEST["metadesc"];

				BB_SaveLangPage($bb_revision_num);
			}

			BB_SavePage();
			BB_UpdateSitemaps();
		}

		BB_DeletePageCache();

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Options saved.")); ?></div>
<script type="text/javascript">
ReloadIFrame();
CloseProperties();
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_page_opt_properties_submit");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_page_opt_properties")
	{
		BB_RunPluginAction("pre_bb_main_edit_page_opt_properties");

		if (!BB_IsMemberOfPageGroup("_p"))  BB_PropertyFormLoadError("You do not have permissions to edit the page properties.");

		if ($bb_account["type"] == "content")
		{
			if (!$bb_revision_writeable)  BB_PropertyFormLoadError("Page properties are not writeable.");

			$options = array(
				"title" => "Edit Properties",
				"desc" => "Edit the page properties.",
				"fields" => array(
					array(
						"title" => "Page Title",
						"type" => "text",
						"name" => "title",
						"value" => $bb_langpage["title"],
						"desc" => "The localized title of this page."
					),
					array(
						"title" => "Meta Description",
						"type" => "text",
						"name" => "metadesc",
						"value" => $bb_langpage["metadesc"],
						"desc" => "The localized HTML meta description tag.  Used by some search engines."
					)
				),
				"submit" => "Save",
				"focus" => true
			);

			BB_RunPluginAction("bb_main_edit_page_opt_properties_content_lang_options");

			BB_PropertyForm($options);
		}
		else if ($bb_account["type"] == "dev" || $bb_account["type"] == "design")
		{
			$doctypes = array();
			foreach ($bb_doctypes as $doctype => $html)  $doctypes[$doctype] = $doctype;

			$metarobots = array();
			foreach (explode(",", $bb_page["metarobots"]) as $val)  $metarobots[$val] = true;

			$options = array(
				"title" => "Edit Properties",
				"desc" => "Edit the page properties.",
				"fields" => array(
					array(
						"title" => "Redirect Page",
						"type" => "text",
						"name" => "redirect",
						"value" => $bb_page["redirect"],
						"desc" => "Redirect this page elsewhere.  Prefer redirection over deletion to avoid 404 errors."
					),
					array(
						"title" => "Cache Time",
						"type" => "text",
						"name" => "cachetime",
						"value" => $bb_page["cachetime"],
						"desc" => "The amount of time to cache this page in seconds."
					),
					array(
						"title" => "Easy Edit",
						"type" => "select",
						"name" => "easyedit",
						"options" => array(
							"enable" => "Enable",
							"disable" => "Disable"
						),
						"select" => ($bb_page["easyedit"] ? "enable" : "disable"),
						"desc" => "Easy Edit is a small piece of Javascript that makes it easy to enter this editor."
					),
					array(
						"title" => "XML Sitemap",
						"type" => "select",
						"name" => "sitemap",
						"options" => array(
							"include" => "Include",
							"exclude" => "Exclude"
						),
						"select" => ($bb_page["sitemap"] ? "include" : "exclude"),
						"desc" => "XML Sitemaps make it easy for search engines to find all of your content."
					),
					array(
						"title" => "XML Sitemap Priority",
						"type" => "select",
						"name" => "sitemappriority",
						"options" => array(
							"high" => "High",
							"normal" => "Normal",
							"low" => "Low"
						),
						"select" => $bb_page["sitemappriority"],
						"desc" => "The XML Sitemap priority for this page.  Only a suggestion to search engines."
					),
					array(
						"title" => "Document Type",
						"type" => "select",
						"name" => "doctype",
						"options" => $doctypes,
						"select" => $bb_page["doctype"],
						"desc" => "The document type (DOCTYPE) of this page."
					),
					array(
						"title" => "Meta Robots",
						"type" => "select",
						"name" => "metarobots",
						"multiple" => true,
						"options" => array(
							"NOINDEX" => "NOINDEX - Do not index this page.",
							"NOFOLLOW" => "NOFOLLOW - Do not follow/rank links on this page.",
							"NOARCHIVE" => "NOARCHIVE - Do not cache a copy of this page.",
							"NOODP" => "NOODP - Do not include in the Open Directory Project (ODP).",
							"NOSNIPPET" => "NOSNIPPET - Do not show a description in search results [Google].",
							"NOYDIR" => "NOYDIR - Do not show Yahoo! directory results next to ODP listings [Yahoo]."
						),
						"select" => $metarobots,
						"desc" => "HTML meta robots tag.  Tells search engines how to interact with this page."
					),
					array(
						"title" => "Group Permissions",
						"type" => "select",
						"name" => "perms",
						"multiple" => true,
						"options" => BB_GetAccountGroups(),
						"select" => BB_GetPageGroupPermissions("_p"),
						"desc" => "Specifies which content editor groups can edit this page's localized properties."
					)
				),
				"submit" => "Save",
				"focus" => true
			);

			BB_RunPluginAction("bb_main_edit_page_opt_properties_page_options");

			if ($bb_revision_writeable)
			{
				$options["fields"][] = array(
					"title" => "Page Title",
					"type" => "text",
					"name" => "title",
					"value" => $bb_langpage["title"],
					"desc" => BB_Translate("The localized (%s) title of this page.", $bb_pref_lang)
				);
				$options["fields"][] = array(
					"title" => "Meta Description",
					"type" => "text",
					"name" => "metadesc",
					"value" => $bb_langpage["metadesc"],
					"desc" => BB_Translate("The localized (%s) HTML meta description tag.  Used by some search engines.", $bb_pref_lang)
				);
			}

			BB_RunPluginAction("bb_main_edit_page_opt_properties_lang_options");

			BB_PropertyForm($options);
		}

		BB_RunPluginAction("post_bb_main_edit_page_opt_properties");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_page_opt_view_cache_profiles")
	{
		BB_RunPluginAction("pre_bb_main_edit_page_opt_view_cache_profiles");

		$rows = array();
		$urlbase = BB_GetRequestURLBase();
		foreach ($bb_profiles as $profile => $disp)
		{
			$rows[] = array(htmlspecialchars($disp), "<a href=\"" . $urlbase . "?bb_action=bb_main_edit&lang=" . urlencode($bb_pref_lang) . ($bb_revision_num > -1 ? "&bb_revnum=" . $bb_revision_num : "") . ($profile != "" ? "&bb_profile=" . urlencode($profile) : "") . "\" target=\"_blank\">" . BB_Translate("Edit") . "</a>");
		}

		$options = array(
			"title" => "View Cache Profiles",
			"desc" => "View and edit this page using alternate site cache profiles.",
			"fields" => array(
				array(
					"type" => "table",
					"cols" => array("Cache Profile", "Options"),
					"rows" => $rows
				)
			)
		);

		BB_RunPluginAction("bb_main_edit_page_opt_view_cache_profiles_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("post_bb_main_edit_page_opt_view_cache_profiles");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_page_opt_create_branch_submit")
	{
		BB_RunPluginAction("pre_bb_main_edit_page_opt_create_branch_submit");

		$name = $_REQUEST["name"];
		if ($name == "")  BB_PropertyFormError("Field not filled out.");

		require_once $bb_dir . "/" . $bb_file . "_" . $bb_pref_lang . "_rev.php";

		if (isset($bb_langpagerevisions["branches"][$name]))  BB_PropertyFormError("Revision branch already exists.");

		if (!BB_CreateRevisionBranch($name))  BB_PropertyFormError("Unable to create revision branch.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Branch created.")); ?></div>
<script type="text/javascript">
window.location.href = Gx__URLBase + '?bb_action=bb_main_edit&lang=<?php echo urlencode($bb_pref_lang); ?>&bb_revnum=<?php echo count($bb_langpagerevisions["revisions"]) - 1; ?><?php if ($bb_profile != "")  echo "&bb_profile=" . urlencode($bb_profile); ?>';
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_page_opt_create_branch_submit");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_page_opt_create_branch")
	{
		BB_RunPluginAction("pre_bb_main_edit_page_opt_create_branch");

		$options = array(
			"title" => "Create Branch",
			"desc" => "Create a new branch in the revision system.",
			"fields" => array(
				array(
					"type" => "text",
					"name" => "name",
					"value" => "",
					"desc" => "The name of the new branch."
				)
			),
			"submit" => "Create",
			"focus" => true
		);

		BB_RunPluginAction("bb_main_edit_page_opt_create_branch_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("post_bb_main_edit_page_opt_create_branch");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_page_opt_close_branch_submit")
	{
		BB_RunPluginAction("pre_bb_main_edit_page_opt_close_branch_submit");

		if ($bb_revision_num < 0 || !$bb_revision_writeable || $bb_revision[0] == "")  BB_PropertyFormError("Unable to close the branch.  Most likely no longer writeable.");

		if (!BB_CloseRevisionBranch($bb_revision[0]))  BB_PropertyFormError("Unable to close the branch.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Branch closed.")); ?></div>
<script type="text/javascript">
ReloadMenu();
ReloadMainViewInfo();
ReloadIFrame();
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_page_opt_close_branch_submit");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_page_opt_close_branch")
	{
		BB_RunPluginAction("pre_bb_main_edit_page_opt_close_branch");

		if ($bb_revision_num < 0 || !$bb_revision_writeable || $bb_revision[0] == "")  BB_PropertyFormLoadError("Unable to close the branch.");

		$options = array(
			"title" => "Close Branch",
			"desc" => BB_Translate("Close the '%s' branch in the revision system?", $bb_revision[0]),
			"submit" => "Close Branch",
			"focus" => true
		);

		BB_RunPluginAction("bb_main_edit_page_opt_close_branch_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("post_bb_main_edit_page_opt_close_branch");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_page_opt_overwrite_live_page_submit")
	{
		BB_RunPluginAction("pre_bb_main_edit_page_opt_overwrite_live_page_submit");

		if ($bb_revision_num < 0)  BB_PropertyFormError("Unable to copy to live page.  Invalid revision source.");

		$name = $_REQUEST["name"];
		if (substr($name, 0, 2) == "b;")
		{
			if (!BB_CreateRevision("Revision #" . $bb_revision_num . ":  Backup of live page.", substr($name, 2)))  BB_PropertyFormError("Unable to create revision.");
		}

		if (!BB_SaveLangPage(-1))  BB_PropertyFormError("Unable to overwrite live page.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Copied to live page.")); ?></div>
<script type="text/javascript">
window.location.href = Gx__URLBase + '?bb_action=bb_main_edit&lang=<?php echo urlencode($bb_pref_lang); ?>';
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_page_opt_overwrite_live_page_submit");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_page_opt_overwrite_live_page")
	{
		BB_RunPluginAction("pre_bb_main_edit_page_opt_overwrite_live_page");

		if ($bb_revision_num < 0)  BB_PropertyFormLoadError("Unable to copy to live page.");

		$branches = array("n;" => "No Backup", "b;" => "[Root]");
		foreach ($bb_langpagerevisions["branches"] as $name => $data)  $branches["b;" . $name] = $name;

		$options = array(
			"title" => "Copy To Live Page",
			"desc" => "Copies this revision to the live page with optional backup of the live page.",
			"fields" => array(
				array(
					"title" => "Backup Branch",
					"type" => "select",
					"name" => "name",
					"options" => $branches,
					"desc" => "The branch to create the new revision in."
				)
			),
			"submit" => "Copy",
			"focus" => true
		);

		BB_RunPluginAction("bb_main_edit_page_opt_overwrite_live_page_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("post_bb_main_edit_page_opt_overwrite_live_page");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_page_opt_create_revision_submit")
	{
		$reason = $_REQUEST["reason"];
		if ($reason == "")  BB_PropertyFormError("Field not filled out.");

		require_once $bb_dir . "/" . $bb_file . "_" . $bb_pref_lang . "_rev.php";

		BB_RunPluginAction("pre_bb_main_edit_page_opt_create_revision_submit");

		if (!BB_CreateRevision($reason, $_REQUEST["name"]))  BB_PropertyFormError("Unable to create revision.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Revision created.")); ?></div>
<script type="text/javascript">
<?php
		if ($bb_revision_num > -1)
		{
?>
window.location.href = Gx__URLBase + '?bb_action=bb_main_edit&lang=<?php echo urlencode($bb_pref_lang); ?>&bb_revnum=<?php echo count($bb_langpagerevisions["revisions"]) - 1; ?><?php if ($bb_profile != "")  echo "&bb_profile=" . urlencode($bb_profile); ?>';
<?php
		}
		else
		{
?>
CloseProperties();
<?php
		}
?>
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_page_opt_create_revision_submit");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_page_opt_create_revision")
	{
		BB_RunPluginAction("pre_bb_main_edit_page_opt_create_revision");

		require_once $bb_dir . "/" . $bb_file . "_" . $bb_pref_lang . "_rev.php";

		$branches = array("" => "[Root]");
		foreach ($bb_langpagerevisions["branches"] as $name => $data)  $branches[$name] = $name;

		$options = array(
			"title" => "Create Revision",
			"desc" => ($bb_revision_num > -1 && $bb_revision[0] != "" ? BB_Translate("Create a new revision of the '%s' branch in the revision system.", $bb_revision[0]) : "Create a new revision in the revision system."),
			"fields" => array(
				array(
					"title" => "Reason",
					"type" => "text",
					"name" => "reason",
					"value" => "",
					"desc" => "The reason for the new revision."
				),
				array(
					"title" => "Branch",
					"type" => "select",
					"name" => "name",
					"options" => $branches,
					"select" => ($bb_revision_num > -1 && $bb_revision[0] != "" ? $bb_revision[0] : ""),
					"desc" => "The branch to create the new revision in."
				)
			),
			"submit" => "Create",
			"focus" => true
		);

		BB_RunPluginAction("bb_main_edit_page_opt_create_revision_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("post_bb_main_edit_page_opt_create_revision");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_page_opt_view_revisions")
	{
		BB_RunPluginAction("pre_bb_main_edit_page_opt_view_revisions");

		require_once $bb_dir . "/" . $bb_file . "_" . $bb_pref_lang . "_rev.php";

		$urlbase = BB_GetRequestURLBase();
		$ignorerevs = array();
		$rows = array();

		$revision = $bb_langpagerevisions["revisions"][$bb_langpagerevisions["rootrev"]];
		$rows[] = array("<a href=\"" . $urlbase . "?bb_action=bb_main_edit&lang=" . urlencode($bb_pref_lang) . "&bb_revnum=" . $bb_langpagerevisions["rootrev"] . ($bb_profile != "" ? "&bb_profile=" . urlencode($bb_profile) : "") . "\" target=\"_blank\">" . $bb_langpagerevisions["rootrev"] . "</a>", BB_Translate("<i>[Root]</i>"), htmlspecialchars($revision[4]), BB_FormatTimestamp($revision[2]), BB_FormatTimestamp($revision[3]));
		$ignorerevs[$bb_langpagerevisions["rootrev"]] = true;

		BB_RunPluginAction("bb_main_edit_page_opt_view_revisions_rootrev");

		foreach ($bb_langpagerevisions["branches"] as $name => $data)
		{
			$revision = $bb_langpagerevisions["revisions"][$data[0]];
			$rows[] = array("<a href=\"" . $urlbase . "?bb_action=bb_main_edit&lang=" . urlencode($bb_pref_lang) . "&bb_revnum=" . $data[0] . ($bb_profile != "" ? "&bb_profile=" . urlencode($bb_profile) : "") . "\" target=\"_blank\">" . $data[0] . "</a>", htmlspecialchars($name), htmlspecialchars($revision[4]), BB_FormatTimestamp($revision[2]), BB_FormatTimestamp($revision[3]));
			$ignorerevs[$data[0]] = true;
		}

		BB_RunPluginAction("bb_main_edit_page_opt_view_revisions_branches");

		if (count($bb_langpagerevisions["revisions"]) - count($ignorerevs) > 0)
		{
			$rows[] = array("<hr />", "<hr />", "<hr />", "<hr />", "<hr />");
		}

		foreach ($bb_langpagerevisions["revisions"] as $num => $revision)
		{
			if (!isset($ignorerevs[$num]))
			{
				$rows[] = array("<a href=\"" . $urlbase . "?bb_action=bb_main_edit&lang=" . urlencode($bb_pref_lang) . "&bb_revnum=" . $num . ($bb_profile != "" ? "&bb_profile=" . urlencode($bb_profile) : "") . "\" target=\"_blank\">" . $num . "</a>", ($revision[0] == "" ? BB_Translate("<i>[Root]</i>") : htmlspecialchars($revision[0])), htmlspecialchars($revision[4]), BB_FormatTimestamp($revision[2]), BB_FormatTimestamp($revision[3]));
			}
		}

		BB_RunPluginAction("bb_main_edit_page_opt_view_revisions_revisions");

		$options = array(
			"title" => "View Revisions",
			"desc" => "View revisions and branches in the revision system for this translation.",
			"fields" => array(
				array(
					"type" => "table",
					"cols" => array("#", "Branch", "Reason", "Created", "Last Update"),
					"rows" => $rows
				)
			)
		);

		BB_RunPluginAction("bb_main_edit_page_opt_view_revisions_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("post_bb_main_edit_page_opt_view_revisions");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_page_opt_ping_translation_submit")
	{
		BB_RunPluginAction("pre_bb_main_edit_page_opt_ping_translation_submit");

		if ($bb_revision_num > -1)  BB_PropertyFormError("Unable to notify.  Must be a live page.");
		if (!isset($_REQUEST["langs"]))  BB_PropertyFormError("No language(s) selected.");

		require_once "translate.php";

		foreach ($_REQUEST["langs"] as $lang)
		{
			if ($lang != $bb_pref_lang && isset($bb_page["langs"][$lang]) && is_array($bb_page["langs"][$lang]))
			{
				if (!BB_AddTranslationNotification($lang, $_REQUEST["reason"]))  BB_PropertyFormError("Unable to send notification.");
			}
		}

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Notification sent.")); ?></div>
<script type="text/javascript">
CloseProperties();
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_page_opt_ping_translation_submit");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_page_opt_ping_translation")
	{
		BB_RunPluginAction("pre_bb_main_edit_page_opt_ping_translation");

		if ($bb_revision_num > -1)  BB_PropertyFormLoadError("Unable to notify.  Must be a live page.");

		$langs = array();
		foreach ($bb_page["langs"] as $lang => $map)
		{
			if ($lang != $bb_pref_lang && is_array($map))  $langs[$lang] = BB_GetIANADesc($lang);
		}

		if (!count($langs))  BB_PropertyFormLoadError("Unable to notify.  No translations of this page exist.");

		$options = array(
			"title" => "Notify Translators",
			"desc" => "Notifies translators that this page has changes that need translating to another language.",
			"fields" => array(
				array(
					"title" => "Changes",
					"type" => "text",
					"name" => "reason",
					"value" => "",
					"desc" => "The changes the translator needs to make in order to make the translation current."
				),
				array(
					"title" => "Language",
					"type" => "select",
					"name" => "langs",
					"multiple" => true,
					"options" => $langs,
					"select" => $langs,
					"desc" => "The language translators to send the notification to."
				)
			),
			"submit" => "Send",
			"focus" => true
		);

		BB_RunPluginAction("bb_main_edit_page_opt_ping_translation_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("post_bb_main_edit_page_opt_ping_translation");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_page_opt_create_translation_submit")
	{
		BB_RunPluginAction("pre_bb_main_edit_page_opt_create_translation_submit");

		$lang = BB_GetCleanLang($_REQUEST["lang2"] != "" ? $_REQUEST["lang2"] : $lang = $_REQUEST["otherlang"]);
		$desc = BB_GetIANADesc($lang, false);
		if ($desc === false)  BB_PropertyFormError("Invalid language code specified.");

		$lang = str_replace("-", "_", $lang);
		if (isset($bb_page["langs"][$lang]))  BB_PropertyFormError("Translation already exists.");

		$langmap = str_replace("-", "_", BB_GetCleanLang($_REQUEST["langmap"]));
		if ($langmap != "" && (!isset($bb_page["langs"][$langmap]) || is_string($bb_page["langs"][$langmap])))  BB_PropertyFormError("Unable to create translation mapping.");

		if (!BB_CreateLangPage($lang, $langmap))  BB_PropertyFormError("Unable to create translation.");

		// Map root ('en') to translation ('en_us').
		$Pos = strpos($lang, "_");
		if ($Pos !== false)
		{
			$langmap2 = $lang;
			$lang2 = substr($lang, 0, $Pos);

			BB_CreateLangPage($lang2, $langmap2);
		}

		if ($langmap == "" && $_REQUEST["notify"] == "yes")
		{
			require_once "translate.php";

			if (!BB_AddTranslationNotification($lang, "Full page translation."))  BB_PropertyFormError("Unable to send notification.");
		}

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Translation created.")) . ($langmap == "" && $_REQUEST["notify"] == "yes" ? BB_Translate("  Notification sent.") : ""); ?></div>
<script type="text/javascript">
CloseProperties();
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_page_opt_create_translation_submit");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_page_opt_create_translation")
	{
		BB_RunPluginAction("pre_bb_main_edit_page_opt_create_translation");

		if (file_exists("iana_lang_desc_cache.php"))  require_once "iana_lang_desc_cache.php";
		else  $bb_iana_lang_desc_cache = array();
		require_once ROOT_PATH . "/" . SUPPORT_PATH . "/php/common_lang.php";

		$langs4 = array("" => "");
		foreach ($bb_page["langs"] as $lang => $map)
		{
			if (is_array($map))  $langs4[str_replace("_", "-", $lang)] = BB_GetIANADesc($lang);
		}

		$langs2 = array();
		$langs3 = array();
		foreach ($bb_iana_lang_desc_cache as $lang => $map)
		{
			if ($map !== false && !isset($bb_page["langs"][str_replace("-", "_", $lang)]))  $langs2[$lang] = implode(", ", $map[0]) . " (" . implode(" => ", $map[1]) . ")";
		}
		foreach ($bb_common_lang as $lang => $map)
		{
			if ($map !== false && !isset($langs2[$lang]) && !isset($bb_page["langs"][str_replace("-", "_", $lang)]))  $langs3[$lang] = implode(", ", $map[0]) . " (" . implode(" => ", $map[1]) . ")";
		}

		$langs = array("Site Languages" => $langs2, "Common Languages" => $langs3, "" => "Other Language");

		$options = array(
			"title" => "Create Translation",
			"desc" => "Creates a new translation of this page.",
			"fields" => array(
				array(
					"title" => "Language",
					"type" => "select",
					"name" => "lang2",
					"options" => $langs,
					"desc" => "The language of the new page."
				),
				array(
					"title" => "Other Language",
					"type" => "text",
					"name" => "otherlang",
					"value" => "",
					"desc" => "Optional.  Only used when 'Language' is 'Other Language'.  Must be a valid language code (e.g. 'en-us')."
				),
				array(
					"title" => "Language Mapping",
					"type" => "select",
					"name" => "langmap",
					"options" => $langs4,
					"desc" => "Optional.  The language to map the new 'Language' above to."
				),
				array(
					"title" => "Notify Translator?",
					"type" => "select",
					"name" => "notify",
					"options" => array(
						"yes" => "Yes",
						"no" => "No"
					),
					"select" => "yes",
					"desc" => "Optional.  Notifies a translator that the page needs translation to the new language.  Ignored when a mapping is created."
				)
			),
			"submit" => "Create",
			"focus" => true
		);

		BB_RunPluginAction("bb_main_edit_page_opt_create_translation_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("postbb_main_edit_page_opt_create_translation");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_page_opt_delete_translation")
	{
		BB_RunPluginAction("pre_bb_main_edit_page_opt_delete_translation");

		if (!BB_DeleteLangPage($_REQUEST["lang2"]))  BB_PropertyFormLoadError("Unable to delete the translation.");

?>
<script type="text/javascript">
LoadProperties(<?php echo BB_CreatePropertiesJS("bb_main_edit_page_opt_view_translations"); ?>);
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_page_opt_delete_translation");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_page_opt_set_default_translation")
	{
		BB_RunPluginAction("pre_bb_main_edit_page_opt_set_default_translation");

		if (!BB_SetDefaultLangPage($_REQUEST["lang2"]))  BB_PropertyFormLoadError("Unable to set the default translation.");

?>
<script type="text/javascript">
LoadProperties(<?php echo BB_CreatePropertiesJS("bb_main_edit_page_opt_view_translations"); ?>);
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_page_opt_set_default_translation");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_page_opt_view_translations")
	{
		BB_RunPluginAction("pre_bb_main_edit_page_opt_view_translations");

		$rows = array();
		$urlbase = BB_GetRequestURLBase();
		foreach ($bb_page["langs"] as $lang => $map)
		{
			$rows[] = array(htmlspecialchars(BB_Translate(BB_GetIANADesc($lang, is_array($map)))), htmlspecialchars(is_string($map) ? BB_Translate(BB_GetIANADesc($map)) : ""), "<a href=\"" . $urlbase . "?lang=" . urlencode($lang) . "\" target=\"_blank\">" . BB_Translate("View") . "</a>" . (is_array($map) ? " | <a href=\"" . $urlbase . "?bb_action=bb_main_edit&lang=" . urlencode($lang) . "\" target=\"_blank\">" . BB_Translate("Edit") . "</a>" : "") . ($lang != $bb_pref_lang && $lang != $bb_page["defaultlang"] ? " | " . BB_CreatePropertiesLink(BB_Translate("Delete"), "bb_main_edit_page_opt_delete_translation", array("lang2" => $lang), BB_Translate("Are you sure you want to delete the translation " . (is_string($map) ? "mapping " : "") . " '%s'" . (is_array($map) ? " and all mappings" : "") . "?", $lang)) : "") . (is_array($map) && $lang != $bb_page["defaultlang"] ? " | " . BB_CreatePropertiesLink(BB_Translate("Set Default"), "bb_main_edit_page_opt_set_default_translation", array("lang2" => $lang)) : ""));
		}

		$options = array(
			"title" => "View Translations",
			"desc" => "View and manage page translations and translation mappings.",
			"fields" => array(
				array(
					"type" => "table",
					"cols" => array("Translation", "Mapping", "Options"),
					"rows" => $rows
				)
			)
		);

		BB_RunPluginAction("bb_main_edit_page_opt_view_translations_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("post_bb_main_edit_page_opt_view_translations");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_file_explorer_create_submit" && (BB_IsSecExtraOpt("dir") || BB_IsSecExtraOpt("file")))
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_file_explorer_create_submit");

		if (isset($_REQUEST["dir"]) && BB_IsSecExtraOpt("dir"))
		{
			$dirfile = preg_replace('/\.+/', ".", preg_replace('/[^A-Za-z0-9_.\-]/', "_", $_REQUEST["dirfile"]));
			if ($dirfile == ".")  $dirfile = "";
			if ($dirfile == "")  BB_PropertyFormError("Field not filled out.");
			$dir = BB_GetRealPath($_REQUEST["dir"]);
			if ($dir == "")  $dir = ".";
			$dir .= "/" . $dirfile;
			if (is_dir($dir))  BB_PropertyFormError("Directory already exists.");

			mkdir($dir, 0777, true);
?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Directory created.")); ?></div>
<script type="text/javascript">
LoadProperties(<?php echo BB_CreatePropertiesJS("bb_main_edit_site_opt_file_explorer", array("dir" => $_REQUEST["dir"])); ?>);
</script>
<?php
		}
		else if (isset($_REQUEST["file"]) && BB_IsSecExtraOpt("file"))
		{
			$dirfile = preg_replace('/\.+/', ".", preg_replace('/[^A-Za-z0-9_.\-]/', "_", $_REQUEST["dirfile"]));
			if ($dirfile == ".")  $dirfile = "";
			if ($dirfile == "")  BB_PropertyFormError("Field not filled out.");
			$file = BB_GetRealPath($_REQUEST["file"]);
			if ($file == "")  $file = ".";
			$file .= "/" . $dirfile;
			if (file_exists($file))  BB_PropertyFormError("File already exists.");

			BB_WriteFile($file, "");
?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("File created.")); ?></div>
<script type="text/javascript">
LoadProperties(<?php echo BB_CreatePropertiesJS("bb_main_edit_site_opt_file_explorer", array("dir" => $_REQUEST["file"])); ?>);
</script>
<?php
		}

		BB_RunPluginAction("post_bb_main_edit_site_opt_file_explorer_create_submit");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_file_explorer_create" && (BB_IsSecExtraOpt("dir") || BB_IsSecExtraOpt("file")))
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_file_explorer_create");

		if (isset($_REQUEST["dir"]) && BB_IsSecExtraOpt("dir"))
		{
			$desc = "<br />";
			$desc .= BB_CreatePropertiesLink(BB_Translate("Back"), "bb_main_edit_site_opt_file_explorer", array("dir" => $_REQUEST["dir"]));

			$options = array(
				"title" => "File Explorer - Create Directory",
				"desc" => BB_Translate("Create a new directory in '%s'.", "/" . ($_REQUEST["dir"] == "." ? "" : $_REQUEST["dir"] . "/")),
				"htmldesc" => $desc,
				"hidden" => array(
					"dir" => $_REQUEST["dir"]
				),
				"fields" => array(
					array(
						"type" => "text",
						"name" => "dirfile",
						"value" => "",
						"desc" => "Directory name can only contain alphanumeric (A-Z, 0-9), '_', '.', and '-' characters."
					)
				),
				"submit" => "Create",
				"focus" => true
			);

			BB_RunPluginAction("pre_bb_main_edit_site_opt_file_explorer_create_dir_options");

			BB_PropertyForm($options);
		}
		else if (isset($_REQUEST["file"]) && BB_IsSecExtraOpt("file"))
		{
			$desc = "<br />";
			$desc .= BB_CreatePropertiesLink(BB_Translate("Back"), "bb_main_edit_site_opt_file_explorer", array("dir" => $_REQUEST["file"]));

			$options = array(
				"title" => "File Explorer - Create File",
				"desc" => BB_Translate("Create a new file in '%s'.", "/" . ($_REQUEST["file"] == "." ? "" : $_REQUEST["file"] . "/")),
				"htmldesc" => $desc,
				"hidden" => array(
					"file" => $_REQUEST["file"]
				),
				"fields" => array(
					array(
						"type" => "text",
						"name" => "dirfile",
						"value" => "",
						"desc" => "Filename can only contain alphanumeric (A-Z, 0-9), '_', '.', and '-' characters."
					)
				),
				"submit" => "Create",
				"focus" => true
			);

			BB_RunPluginAction("pre_bb_main_edit_site_opt_file_explorer_create_file_options");

			BB_PropertyForm($options);
		}

		BB_RunPluginAction("post_bb_main_edit_site_opt_file_explorer_create");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_file_explorer_ajaxupload" && BB_IsSecExtraOpt("dir"))
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_file_explorer_ajaxupload");

		$msg = BB_ValidateAJAXUpload();
		if ($msg != "")
		{
			echo htmlspecialchars(BB_Translate($msg));
			exit();
		}

		$dir = BB_GetRealPath($_REQUEST["dir"]);
		if ($dir == "")  $dir = ".";
		$dirfile = preg_replace('/\.+/', ".", preg_replace('/[^A-Za-z0-9_.\-]/', "_", trim($_FILES["Filedata"]["name"])));
		if ($dirfile == ".")  $dirfile = "";

		if ($dirfile == "")  echo BB_Translate("A filename was not specified.");
		else if (!@move_uploaded_file($_FILES["Filedata"]["tmp_name"], $dir . "/" . $dirfile))  echo BB_Translate("Unable to move temporary file to final location.  Check the permissions of the target directory and destination file.");
		else
		{
			@chmod($dir . "/" . $dirfile, 0444 | $bb_writeperms);

			echo "OK";

			BB_RunPluginAction("post_bb_main_edit_site_opt_file_explorer_ajaxupload");
		}
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_file_explorer_upload_submit" && BB_IsSecExtraOpt("dir"))
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_file_explorer_upload_submit");

		$info = BB_IsValidURL($_REQUEST["url"], array("protocol" => "http"));
		if (!$info["success"])  BB_PropertyFormError($info["error"]);

		$dir = BB_GetRealPath($_REQUEST["dir"]);
		if ($dir == "")  $dir = ".";
		$dirfile = preg_replace('/\.+/', ".", preg_replace('/[^A-Za-z0-9_.\-]/', "_", $_REQUEST["destfile"]));
		if ($dirfile == ".")  $dirfile = "";

		// Automatically calculate the new filename based on the URL.
		if ($dirfile == "")
		{
			$ext = "";
			if (isset($info["headers"]["content-type"]))
			{
				$type = explode(";", $info["headers"]["content-type"][0]);
				if ($type[0] == "text/html")  $ext = ".html";
				else if ($type[0] == "text/plain")  $ext = ".txt";
			}

			$dirfile = BB_MakeFilenameFromURL($info["url"], $ext, true, $dir);
		}

		BB_RunPluginAction("bb_main_edit_site_opt_file_explorer_upload_submit_save");

		if (BB_WriteFile($dir . "/" . $dirfile, $info["data"]) === false)  BB_PropertyFormError("Unable to save the file.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("File transferred.")); ?></div>
<script type="text/javascript">
LoadProperties(<?php echo BB_CreatePropertiesJS("bb_main_edit_site_opt_file_explorer", array("dir" => $_REQUEST["dir"])); ?>);
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_site_opt_file_explorer_upload_submit");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_file_explorer_upload" && BB_IsSecExtraOpt("dir"))
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_file_explorer_upload");

		$desc = "<br />";
		$desc .= BB_CreatePropertiesLink("Back", "bb_main_edit_site_opt_file_explorer", array("dir" => $_REQUEST["dir"]));

		$options = array(
			"title" => "File Explorer - Upload/Transfer Files",
			"desc" => BB_Translate("Upload/Transfer one or more files to '%s'.", "/" . ($_REQUEST["dir"] == "." ? "" : $_REQUEST["dir"] . "/")),
			"htmldesc" => $desc,
			"hidden" => array(
				"dir" => $_REQUEST["dir"]
			),
			"fields" => array(
				array(
					"title" => "Select Files",
					"type" => "custom",
					"value" => '<div id="upload_inject" class="uploadinject"></div>',
					"desc" => "Click the button to select the files to upload.  Selected files will automatically begin uploading."
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

		BB_RunPluginAction("bb_main_edit_site_opt_file_explorer_upload_options");

		BB_PropertyForm($options);

		// AJAX uploader is delay-loaded and loaded exactly one time.
?>
<script type="text/javascript">
LoadConditionalScript(Gx__RootURL + '/' + Gx__SupportPath + '/upload.js?_=20090725', true, function(loaded) {
		return ((!loaded && typeof(window.CreateUploadInterface) == 'function') || (loaded && !IsConditionalScriptLoading()));
	}, function() {
		AddPropertyChange(ManageFileUploadDestroy, CreateUploadInterface('upload_inject', <?php echo BB_CreatePropertiesJS("bb_main_edit_site_opt_file_explorer_ajaxupload", array("dir" => $_REQUEST["dir"])); ?>, ManageFileUploadResults, <?php echo BB_CreatePropertiesJS("bb_main_edit_site_opt_file_explorer", array("dir" => $_REQUEST["dir"])); ?>));
});
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_site_opt_file_explorer_upload");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_file_explorer_zip_run_submit" && BB_IsSecExtraOpt("dirfile"))
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_file_explorer_zip_run_submit");

		$t1 = microtime(true);
		$t2 = microtime(true);

		function BB_ZipPropertyFormError($msg, $cleanup)
		{
			global $dirfile, $info;

			$tempinfo = array(&$msg, &$cleanup);
			BB_RunPluginActionInfo("pre_bb_zippropertyerror", $tempinfo);

			if ($cleanup)
			{
				unlink($dirfile);
				if (file_exists($info["dir"] . "/" . $info["basename"] . ".zip"))  unlink($info["dir"] . "/" . $info["basename"] . ".zip");
			}

?>
<script type="text/javascript">
Gx__ZipRunDone = true;
$('#cancel_run').html('Back');
</script>
<?php
			BB_PropertyFormError($msg);
		}

		$dirfile = BB_GetRealPath($_REQUEST["dirfile"]);
		if ($dirfile == "")  BB_ZipPropertyFormError("Unable to continue creating the ZIP file.", false);

		if (!file_exists($dirfile) || !is_file($dirfile))  BB_ZipPropertyFormError("Unable to continue creating the ZIP file.  Data file is missing.", false);

		$info = unserialize(file_get_contents($dirfile));
		if (!is_array($info) || !isset($info["type"]) || $info["type"] != "ZipArchive")  BB_ZipPropertyFormError("Unable to continue creating the ZIP file.  Data file is corrupt.", false);

		$sizequeued = 0;
		$filesqueued = 0;
		$lastprocessed = "";

		$zip = new ZipArchive;
		if (file_exists($info["dir"] . "/" . $info["basename"] . ".zip"))
		{
			$res = $zip->open($info["dir"] . "/" . $info["basename"] . ".zip");
			if ($res !== true)  BB_ZipPropertyFormError(BB_Translate("Unable to continue creating the ZIP file.  Unable to open existing file.  Error code:  %s", $res), true);
		}
		else
		{
			if ($zip->open($info["dir"] . "/" . $info["basename"] . ".zip", ZipArchive::CREATE) !== true)  BB_ZipPropertyFormError("Unable to create ZIP file.", true);
			if ($info["basedir"] != "")  $zip->addEmptyDir($info["basedir"]);
			$info["numdirs"]++;
		}

		$t2 = microtime(true);
		while ((count($info["dirs"]) || count($info["files"])) && $t2 - $t1 < $info["maxtime"])
		{
			if (!count($info["files"]))
			{
				$dir = array_shift($info["dirs"]);
				$path = BB_GetRealPath($info["dir"] . "/" . $dir);
				$dirlist = BB_GetDirectoryList($path);
				foreach ($dirlist["dirs"] as $name)
				{
					if ($info["symlinks"] || !is_link($path . "/" . $name))  $info["dirs"][] = BB_GetRealPath($path . "/" . $name);
				}
				foreach ($dirlist["files"] as $name)
				{
					if (($dir != "" || ($name != $info["basename"] . ".zip" && $name != $info["basename"] . ".dat_")) && ($info["symlinks"] || !is_link($path . "/" . $name)))  $info["files"][] = BB_GetRealPath($path . "/" . $name);
				}

				$zip->addEmptyDir(($info["basedir"] != "" ? $info["basedir"] . "/" : "") . $dir);
				$info["numdirs"]++;
				$lastprocessed = $dir;

				$t2 = microtime(true);
			}

			if (count($info["files"]) && $t2 - $t1 < $info["maxtime"])
			{
				$maxsize = ($info["speed"] * 1048576 * ($info["maxtime"] - ($t2 - $t1))) - $sizequeued;
				$maxsize2 = ($info["speed"] * 1048576 * ($info["updatetime"] - ($t2 - $t1))) - $sizequeued;
				$size = filesize($info["files"][0]);
				if ($size > $maxsize)
				{
					if ($filesqueued)  break;

					$info["numerrors"]++;
					$filename = array_shift($info["files"]);
?>
<script type="text/javascript">
AddStatusUpdaterMessage('currstatus_inject', '<?php echo htmlspecialchars(BB_JSSafe(BB_Translate("File '%s' was predicted to be too large to compress within %d seconds.", $filename, $info["maxtime"] + 2))); ?>', 'Error', 'Errors');
</script>
<?php
				}
				else
				{
					if ($size > $maxsize2 && $filesqueued)  break;

					$filename = array_shift($info["files"]);
					if ($zip->addFile($filename, ($info["basedir"] != "" ? $info["basedir"] . "/" : "") . $filename) === false)  BB_ZipPropertyFormError("Unable to add '" . $filename . "' to the ZIP file.", true);
					$sizequeued += $size;
					$filesqueued++;
					$lastprocessed = $filename;

					if ($filesqueued == 50)
					{
						if ($zip->close() === false)  BB_ZipPropertyFormError("Unable to save contents to the ZIP file.", true);
						$zip = new ZipArchive;
						$res = $zip->open($info["dir"] . "/" . $info["basename"] . ".zip");
						if ($res !== true)  BB_ZipPropertyFormError(BB_Translate("Unable to continue creating the ZIP file.  Unable to reopen existing file.  Error code:  %s", $res), true);

						$info["numfiles"] += $filesqueued;
						$info["totalsize"] += $sizequeued;
						$filesqueued = 0;
						$sizequeued = 0;
					}
				}
			}

			$t2 = microtime(true);
		}

		if ($zip->close() === false)  BB_ZipPropertyFormError("Unable to save contents to the ZIP file.", true);

		$t2 = microtime(true);

		$info["numfiles"] += $filesqueued;
		$info["totalsize"] += $sizequeued;
		$info["runtime"] += $t2 - $t1;
		$info["cycles"]++;

		BB_WriteFile($dirfile, serialize($info));
?>
<script type="text/javascript">
SetStatusUpdaterInfo('currstatus_inject', '<?php echo htmlspecialchars(BB_JSSafe(BB_Translate("%d directories processed.  %d files processed.", $info["numdirs"], $info["numfiles"]))); ?><br /><?php echo htmlspecialchars(BB_JSSafe(BB_Translate("Total data processed:  "))); ?>' + ConvertBytesToFriendlyLimit(<?php echo htmlspecialchars(BB_JSSafe($info["totalsize"])); ?>) + '<br /><?php echo htmlspecialchars(BB_JSSafe(BB_Translate("Last Processed:  %s", $lastprocessed))); ?><br /><?php echo htmlspecialchars(BB_JSSafe(BB_Translate("Total time:  "))); ?>' + ConvertSecondsElapsedToFriendlyLimit(<?php echo BB_JSSafe($t2 - $info["starttime"]); ?>, true) + '<br /><?php echo htmlspecialchars(BB_JSSafe(BB_Translate("Run time:  "))); ?>' + ConvertSecondsElapsedToFriendlyLimit(<?php echo $info["runtime"]; ?>, true));
</script>
<?php
		if (!count($info["dirs"]) && !count($info["files"]))
		{
			// Finished compressing, clean up.
			unlink($dirfile);

?>
<script type="text/javascript">
Gx__ZipRunDone = true;
$('#cancel_run').html('Back');
</script>
<?php
			if ($info["numerrors"])  BB_PropertyFormError("One or more errors occurred while creating the ZIP file.");
?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("ZIP file created successfully.")); ?></div>
<script type="text/javascript">
ZipCancelBack();
</script>
<?php
		}
		else
		{
?>
<div class="info"><?php echo htmlspecialchars(BB_Translate("Cycle %d complete...", $info["cycles"])); ?></div>
<script type="text/javascript">
if (Gx__ZipRunCancel)  ZipCancel();
else  SendProperties();
</script>
<?php
		}

		BB_RunPluginAction("post_bb_main_edit_site_opt_file_explorer_zip_run_submit");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_file_explorer_zip_run_cancel" && BB_IsSecExtraOpt("dir") && BB_IsSecExtraOpt("dirfile"))
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_file_explorer_zip_run_cancel");

		$dirfile = BB_GetRealPath($_REQUEST["dirfile"]);
		if ($dirfile == "")  BB_PropertyFormError("Unable to cancel creating the ZIP file.");

		if (!file_exists($dirfile) || !is_file($dirfile))  BB_PropertyFormError("Unable to cancel creating the ZIP file.  Data file is missing.");

		$info = unserialize(file_get_contents($dirfile));
		if (!is_array($info) || !isset($info["type"]) || $info["type"] != "ZipArchive")  BB_PropertyFormError("Unable to cancel creating the ZIP file.  Data file is corrupt.");

		unlink($dirfile);

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("ZIP file cancelled successfully.")); ?></div>
<script type="text/javascript">
LoadProperties(<?php echo BB_CreatePropertiesJS("bb_main_edit_site_opt_file_explorer", array("dir" => $_REQUEST["dir"])); ?>);
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_site_opt_file_explorer_zip_run_cancel");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_file_explorer_zip_run" && BB_IsSecExtraOpt("dir") && BB_IsSecExtraOpt("dirfile"))
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_file_explorer_zip_run");

		$desc = "<br />";
		$desc .= "<a id=\"cancel_run\" href=\"#\" onclick=\"return ZipCancelBack();\">" . htmlspecialchars(BB_Translate("Cancel")) . "</a>";

		$options = array(
			"title" => "File Explorer - Creating ZIP File",
			"desc" => BB_Translate("Creating a new ZIP file in '%s'.  Depending on the size of the content to be compressed, this can take a while.", "/" . ($_REQUEST["dir"] == "." ? "" : $_REQUEST["dir"] . "/")),
			"htmldesc" => $desc,
			"useform" => true,
			"hidden" => array(
				"dirfile" => $_REQUEST["dirfile"]
			),
			"fields" => array(
				array(
					"title" => "Current Status",
					"type" => "custom",
					"value" => '<div id="currstatus_inject" class="currstatusinject"></div>'
				)
			),
			"focus" => false
		);

		BB_RunPluginAction("bb_main_edit_site_opt_file_explorer_zip_run_options");

		BB_PropertyForm($options);

		// Status Updater is delay-loaded and loaded exactly one time.
?>
<script type="text/javascript">
var Gx__ZipRunCancel = false;
var Gx__ZipRunDone = false;

function ZipCancelBack()
{
	if (Gx__ZipRunDone)  LoadProperties(<?php echo BB_CreatePropertiesJS("bb_main_edit_site_opt_file_explorer", array("dir" => $_REQUEST["dir"])); ?>);
	else
	{
		Gx__ZipRunCancel = true;
		$('#cancel_run').html('<?php echo htmlspecialchars(BB_JSSafe("Waiting for cycle completion, please be patient...")); ?>');
	}

	return false;
}

function ZipCancel()
{
	LoadProperties(<?php echo BB_CreatePropertiesJS("bb_main_edit_site_opt_file_explorer_zip_run_cancel", array("dir" => $_REQUEST["dir"], "dirfile" => $_REQUEST["dirfile"])); ?>);
}

LoadConditionalScript(Gx__RootURL + '/' + Gx__SupportPath + '/status.js?_=20090725', true, function(loaded) {
		return ((!loaded && typeof(window.CreateStatusUpdater) == 'function') || (loaded && !IsConditionalScriptLoading()));
	}, function() {
		CreateStatusUpdater('currstatus_inject', { useinfo : true, usemessages : true });
		SendProperties();
});
</script>
<?php
		BB_RunPluginAction("post_bb_main_edit_site_opt_file_explorer_zip_run");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_file_explorer_zip_submit" && BB_IsSecExtraOpt("dir"))
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_file_explorer_zip_submit");

		if (!class_exists("ZipArchive"))  BB_PropertyFormError("Unable to create the ZIP file since the PHP class 'ZipArchive' does not exist.");

		$dir = BB_GetRealPath($_REQUEST["dir"]);
		if ($dir == "")  $dir = ".";
		$basename = preg_replace('/[^A-Za-z0-9_\-]/', "_", $_REQUEST["basename"]);
		if ($basename == "")  BB_PropertyFormError("Base filename field not filled in.");

		if (file_exists($dir . "/" . $basename . ".zip"))
		{
			if (is_dir($dir . "/" . $basename . ".zip"))  BB_PropertyFormError(BB_Translate("Unable to create ZIP file since '%s.zip' is a directory.", $basename));
			unlink($dir . "/" . $basename . ".zip");
		}
		if (file_exists($dir . "/" . $basename . ".dat_"))
		{
			if (is_dir($dir . "/" . $basename . ".dat_"))  BB_PropertyFormError(BB_Translate("Unable to create ZIP file since '%s.dat_' is a directory.", $basename));
			unlink($dir . "/" . $basename . ".dat_");
		}

		$basedir = preg_replace('/[^A-Za-z0-9_\-]/', "_", $_REQUEST["basedir"]);

		// Generate a random file, compress it, and measure the time taken.
		$data = uniqid(mt_rand(), true);
		while (strlen($data) < 1048576)  $data .= uniqid(mt_rand(), true);
		BB_WriteFile($dir . "/" . $basename . ".rand", $data);

		do
		{
			$t1 = microtime(true);
			$zip = new ZipArchive;
			if ($zip->open($dir . "/" . $basename . ".zip", ZipArchive::CREATE) !== true)  BB_PropertyFormError(BB_Translate("Unable to create ZIP file '%s.zip' to measure compression speed.", $basename));
			if ($basedir != "")  $zip->addEmptyDir($basedir);
			$zip->addFile($dir . "/" . $basename . ".rand", ($basedir != "" ? $basedir . "/" : "") . $basename . ".rand");
			$zip->close();
			$t2 = microtime(true);

			unlink($dir . "/" . $basename . ".zip");
		} while ($t1 > $t2);

		do
		{
			$t1 = microtime(true);
			$zip = new ZipArchive;
			$res = $zip->open($dir . "/" . $basename . ".zip", ZipArchive::CREATE);
			if ($res !== true)  BB_PropertyFormError(BB_Translate("Unable to create ZIP file '%s.zip' to measure compression speed.  Error code:  %s", $basename, $res));
			if ($basedir != "")  $zip->addEmptyDir($basedir);
			$zip->addFile($dir . "/" . $basename . ".rand", ($basedir != "" ? $basedir . "/" : "") . $basename . ".rand");
			$zip->close();
			$t2 = microtime(true);

			unlink($dir . "/" . $basename . ".zip");
		} while ($t1 > $t2);

		unlink($dir . "/" . $basename . ".rand");

		$info = array(
			"type" => "ZipArchive",
			"basename" => $basename,
			"dir" => $dir,
			"basedir" => $basedir,
			"subdirs" => ($_REQUEST["type"] == "subdir"),
			"symlinks" => ($_REQUEST["symlinks"] == "follow"),
			"comment" => $_REQUEST["comment"],
			"maxtime" => (int)$_REQUEST["maxtime"] - 2,
			"updatetime" => (int)$_REQUEST["updatetime"],
			"speed" => (1.0 / ($t2 - $t1)),
			"starttime" => $t1,
			"runtime" => 0,
			"dirs" => array(""),
			"files" => array(),
			"numdirs" => 0,
			"numfiles" => 0,
			"totalsize" => 0,
			"numerrors" => 0,
			"cycles" => 0
		);

		BB_WriteFile($dir . "/" . $basename . ".dat_", serialize($info));

?>
<div class="info"><?php echo htmlspecialchars(BB_Translate("Setup completed.  Calculated speed:  %s MB/sec", number_format($info["speed"], 2))); ?></div>
<script type="text/javascript">
LoadProperties(<?php echo BB_CreatePropertiesJS("bb_main_edit_site_opt_file_explorer_zip_run", array("dir" => $dir, "dirfile" => $dir . "/" . $basename . ".dat_")); ?>);
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_site_opt_file_explorer_zip_submit");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_file_explorer_zip" && BB_IsSecExtraOpt("dir"))
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_file_explorer_zip");

		if (!class_exists("ZipArchive"))  BB_PropertyFormLoadError("ZipArchive class does not exist.");

		$desc = "<br />";
		$desc .= BB_CreatePropertiesLink(BB_Translate("Back"), "bb_main_edit_site_opt_file_explorer", array("dir" => $_REQUEST["dir"]));

		$dir = ROOT_PATH . "/" . BB_GetRealPath($_REQUEST["dir"]);
		$basename = substr($dir, strrpos($dir, "/") + 1);
		$basename = preg_replace('/[^A-Za-z0-9_\-]/', "_", trim($basename));

		$options = array(
			"title" => "File Explorer - Create ZIP File",
			"desc" => BB_Translate("Create a new ZIP file in '%s'.  Depending on the size of the content to be compressed, this can take a while.", "/" . ($_REQUEST["dir"] == "." ? "" : $_REQUEST["dir"] . "/")),
			"htmldesc" => $desc,
			"hidden" => array(
				"dir" => $_REQUEST["dir"]
			),
			"fields" => array(
				array(
					"title" => "Base Filename",
					"type" => "text",
					"name" => "basename",
					"value" => ($basename == "" ? "backup" : ""),
					"desc" => "Filename can only contain alphanumeric (A-Z, 0-9), '_', and '-' characters."
				),
				array(
					"title" => "Compress Subdirectories or the Current Directory?",
					"type" => "select",
					"name" => "type",
					"options" => array(
						"subdir" => "Subdirectories",
						"file" => "Current Directory"
					),
					"desc" => "Declares what will be compressed.  Subdirectories includes the current directory and all subdirectories."
				),
				array(
					"title" => "Ignore or Follow symbolic links?",
					"type" => "select",
					"name" => "symlinks",
					"options" => array(
						"ignore" => "Ignore",
						"follow" => "Follow"
					),
					"desc" => "Following symbolic links can potentially lead to an infinite loop."
				),
				array(
					"title" => "Maximum Time Per Cycle",
					"type" => "select",
					"name" => "maxtime",
					"options" => array(
						"5" => "5 seconds",
						"10" => "10 seconds",
						"15" => "15 seconds",
						"20" => "20 seconds",
						"25" => "25 seconds",
						"30" => "30 seconds",
						"35" => "35 seconds",
						"40" => "40 seconds",
						"45" => "45 seconds",
						"50" => "50 seconds",
						"55" => "55 seconds",
						"60" => "1 minute"
					),
					"select" => "15",
					"desc" => "The maximum length the script can run before PHP or the server times out."
				),
				array(
					"title" => "UI Update Time",
					"type" => "select",
					"name" => "updatetime",
					"options" => array(
						"1" => "1 seconds",
						"2" => "2 seconds",
						"3" => "3 seconds",
						"4" => "4 seconds",
						"5" => "5 seconds",
						"10" => "10 seconds",
						"15" => "15 seconds"
					),
					"select" => "3",
					"desc" => "The maximum length of time the script should run before updating the progress."
				),
				array(
					"title" => "Base Directory",
					"type" => "text",
					"name" => "basedir",
					"value" => "",
					"desc" => "Optional.  Base directory can only contain alphanumeric (A-Z, 0-9), '_', and '-' characters."
				),
				array(
					"title" => "Comment",
					"type" => "textarea",
					"name" => "comment",
					"value" => "",
					"desc" => "Optional.  Sets the ZIP file comment."
				)
			),
			"submit" => "Create",
			"focus" => true
		);

		BB_RunPluginAction("bb_main_edit_site_opt_file_explorer_zip_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("post_bb_main_edit_site_opt_file_explorer_zip");
	}
	else if (($bb_account["type"] == "dev" || $bb_account["type"] == "design") && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_file_explorer_newpage_submit" && BB_IsSecExtraOpt("dir"))
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_file_explorer_newpage_submit");

		$dirfile = preg_replace('/\.+/', ".", preg_replace('/[^A-Za-z0-9_.\-]/', "_", $_REQUEST["dirfile"]));
		if ($dirfile == ".")  $dirfile = "";
		if ($dirfile == "")  BB_PropertyFormError("'Page Name' not filled in.");
		if ($_REQUEST["type"] == "file")  $dirfile = str_replace(".", "_", $dirfile);

		if ($_REQUEST["type"] == "subdir")
		{
			$dirfile = BB_GetRealPath($_REQUEST["dir"] . "/" . $dirfile);
			if ($dirfile == "")  BB_PropertyFormError("Invalid 'Page Name'.");
			if (is_dir($dirfile))  BB_PropertyFormError("The subdirectory already exists.");

			if (!BB_CreatePage($dirfile, "index"))  BB_PropertyFormError("Page creation failed.");
		}
		else if ($_REQUEST["type"] == "file")
		{
			$dir = BB_GetRealPath($_REQUEST["dir"]);
			if (file_exists(($dir == "" ? "." : $dir) . "/" . $dirfile . ".php"))  BB_PropertyFormError("The page already exists.");

			if (!BB_CreatePage($dir, $dirfile))  BB_PropertyFormError("Page creation failed.");
		}

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Page created.")); ?></div>
<script type="text/javascript">
LoadProperties(<?php echo BB_CreatePropertiesJS("bb_main_edit_site_opt_file_explorer", array("dir" => $_REQUEST["dir"])); ?>);
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_site_opt_file_explorer_newpage_submit");
	}
	else if (($bb_account["type"] == "dev" || $bb_account["type"] == "design") && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_file_explorer_newpage" && BB_IsSecExtraOpt("dir"))
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_file_explorer_newpage");

		$desc = "<br />";
		$desc .= BB_CreatePropertiesLink(BB_Translate("Back"), "bb_main_edit_site_opt_file_explorer", array("dir" => $_REQUEST["dir"]));

		$options = array(
			"title" => "File Explorer - Create Page",
			"desc" => BB_Translate("Create a new page in '%s'.", "/" . ($_REQUEST["dir"] == "." ? "" : $_REQUEST["dir"] . "/")),
			"htmldesc" => $desc,
			"hidden" => array(
				"dir" => $_REQUEST["dir"]
			),
			"fields" => array(
				array(
					"title" => "Page Name",
					"type" => "text",
					"name" => "dirfile",
					"value" => "",
					"desc" => "Page name can only contain alphanumeric (A-Z, 0-9), '_', and '-' characters."
				),
				array(
					"title" => "Create Subdirectory or File?",
					"type" => "select",
					"name" => "type",
					"options" => array(
						"subdir" => "Subdirectory",
						"file" => "File"
					),
					"desc" => "Declares what 'Page Name' is to be used for.  Subdirectories should be preferred."
				)
			),
			"submit" => "Create",
			"focus" => true
		);

		BB_RunPluginAction("bb_main_edit_site_opt_file_explorer_newpage_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("post_bb_main_edit_site_opt_file_explorer_newpage");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_file_explorer_download" && BB_IsSecExtraOpt("file"))
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_file_explorer_download");

		$dirfile = BB_GetRealPath($_REQUEST["file"]);

		$dirfile = ROOT_PATH . "/" . $dirfile;
		$isfile = is_file($dirfile);

		if ($isfile)
		{
			$pos = strrpos($dirfile, "/");
			$filename = substr($dirfile, $pos + 1);
			$filename = preg_replace('/\.+/', ".", preg_replace('/[^A-Za-z0-9_.\-]/', "_", trim($filename)));
			if ($filename == ".")  $filename = "";
			header("Content-Type: application/octet-stream");
			header("Content-Disposition: attachment; filename=" . $filename);

			echo file_get_contents($dirfile);
		}

		BB_RunPluginAction("post_bb_main_edit_site_opt_file_explorer_download");
	}
	else if (($bb_account["type"] == "dev" || $bb_account["type"] == "design") && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_file_explorer_edit_load" && BB_IsSecExtraOpt("file"))
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_file_explorer_edit_load");

		$designextmap = array(
			".html" => true,
			".htm" => true,
			".css" => true
		);

		BB_RunPluginAction("bb_main_edit_site_opt_file_explorer_edit_load_designextmap");

		$dirfile = BB_GetRealPath($_REQUEST["file"]);

		if ($bb_account["type"] == "design")
		{
			$pos = strrpos($dirfile, ".");
			if ($pos === false)  exit();
			$ext = substr($dirfile, $pos);
			if (!isset($designextmap[$ext]))  exit();
		}

		$dirfile = ROOT_PATH . "/" . $dirfile;
		$isfile = is_file($dirfile);

		if ($isfile)  echo rawurlencode(file_get_contents($dirfile));

		BB_RunPluginAction("post_bb_main_edit_site_opt_file_explorer_edit_load");
	}
	else if (($bb_account["type"] == "dev" || $bb_account["type"] == "design") && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_file_explorer_edit_save" && BB_IsSecExtraOpt("file"))
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_file_explorer_edit_save");

		$designextmap = array(
			".html" => "HTML",
			".htm" => "HTML",
			".css" => "CSS"
		);

		BB_RunPluginAction("bb_main_edit_site_opt_file_explorer_edit_save_designextmap");

		$dirfile = BB_GetRealPath($_REQUEST["file"]);

		$pos = strrpos($dirfile, ".");
		if ($pos === false)
		{
			echo htmlspecialchars(BB_Translate("File extension not found."));
			exit();
		}
		$ext = substr($dirfile, $pos);

		if ($bb_account["type"] == "design" && !isset($designextmap[$ext]))
		{
			echo htmlspecialchars(BB_Translate("Unable to save contents.  Invalid extension permissions."));
			exit();
		}

		$dirfile = ROOT_PATH . "/" . $dirfile;
		$isfile = (is_file($dirfile) || !file_exists($dirfile));

		if (!$isfile)  echo htmlspecialchars(BB_Translate("Invalid filename."));
		else if (BB_WriteFile($dirfile, $_REQUEST["content"]) !== false && BB_WidgetStatusUpdate())
		{
			echo "OK\n";
			echo "<script type=\"text/javascript\">ReloadIFrame();</script>";
		}
		else  echo htmlspecialchars(BB_Translate("Unable to save contents.  Possible cause:  Incorrect permissions."));

		BB_RunPluginAction("post_bb_main_edit_site_opt_file_explorer_edit_save");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_file_explorer_rename_submit" && BB_IsSecExtraOpt("dir") && BB_IsSecExtraOpt("srcfile"))
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_file_explorer_rename_submit");

		$destfile = preg_replace('/\.+/', ".", preg_replace('/[^A-Za-z0-9_.\-]/', "_", $_REQUEST["destfile"]));
		if ($destfile == ".")  $destfile = "";
		if ($destfile == "")  BB_PropertyFormError("Field not filled in.");
		$srcfile = ROOT_PATH . "/" . BB_GetRealPath($_REQUEST["dir"] . "/" . $_REQUEST["srcfile"]);
		$destfile = ROOT_PATH . "/" . BB_GetRealPath($_REQUEST["dir"] . "/" . $destfile);

		$isfile = is_file($srcfile);

		if (!rename($srcfile, $destfile))  BB_PropertyFormError("Unable to rename " . ($isfile ? "file" : "directory") . ".");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate($isfile ? "File renamed." : "Directory renamed.")); ?></div>
<script type="text/javascript">
LoadProperties(<?php echo BB_CreatePropertiesJS("bb_main_edit_site_opt_file_explorer", array("dir" => $_REQUEST["dir"])); ?>);
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_site_opt_file_explorer_rename_submit");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_file_explorer_rename" && BB_IsSecExtraOpt("dir") && BB_IsSecExtraOpt("file"))
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_file_explorer_rename");

		$desc = "<br />";
		$desc .= BB_CreatePropertiesLink(BB_Translate("Back"), "bb_main_edit_site_opt_file_explorer", array("dir" => $_REQUEST["dir"]));

		$dirfile = BB_GetRealPath($_REQUEST["dir"] . "/" . $_REQUEST["file"]);
		$isfile = is_file(ROOT_PATH . "/" . $dirfile);

		$options = array(
			"title" => "File Explorer - Rename " . ($isfile ? "File" : "Directory"),
			"desc" => BB_Translate("Rename the '%s' " . ($isfile ? "file" : "directory") . " in '%s'.", $_REQUEST["file"], "/" . ($_REQUEST["dir"] == "." ? "" : $_REQUEST["dir"] . "/")),
			"htmldesc" => $desc,
			"hidden" => array(
				"dir" => $_REQUEST["dir"],
				"srcfile" => $_REQUEST["file"]
			),
			"fields" => array(
				array(
					"type" => "text",
					"name" => "destfile",
					"value" => $_REQUEST["file"],
					"desc" => ($isfile ? "Filename" : "Directory name") . " can only contain alphanumeric (A-Z, 0-9), '_', '.', and '-' characters."
				)
			),
			"submit" => "Rename",
			"focus" => true
		);

		BB_RunPluginAction("bb_main_edit_site_opt_file_explorer_rename_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("post_bb_main_edit_site_opt_file_explorer_rename");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_file_explorer_delete" && BB_IsSecExtraOpt("dir") && BB_IsSecExtraOpt("file"))
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_file_explorer_delete");

		$dirfile = BB_GetRealPath($_REQUEST["dir"] . "/" . $_REQUEST["file"]);
		if ($dirfile == "")  BB_PropertyFormError("Not allowed to delete root.");

		$dirfile = ROOT_PATH . "/" . $dirfile;
		$isfile = is_file($dirfile);
		if (!BB_RemoveDirectory($dirfile))  BB_PropertyFormError("Unable to delete.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate($isfile ? "File deleted." : "Directory deleted.")); ?></div>
<script type="text/javascript">
LoadProperties(<?php echo BB_CreatePropertiesJS("bb_main_edit_site_opt_file_explorer", array("dir" => $_REQUEST["dir"])); ?>);
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_site_opt_file_explorer_delete");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_site_opt_file_explorer")
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_file_explorer");

		if (!isset($_REQUEST["dir"]))  $_REQUEST["dir"] = $bb_dir;
		else if (!BB_IsSecExtraOpt("dir"))
		{
			echo htmlspecialchars(BB_Translate("Invalid security token."));
			exit();
		}

		$editmap = array(
			"ea" => array("<a href=\"#\" onclick=\"return EditFile('%%HTML_JS_DIR%%', '%%HTML_JS_FILE%%', '%%HTML_JS_syntax%%', '%%HTML_JS_LOADTOKEN%%', '%%HTML_JS_SAVETOKEN%%');\">" . BB_Translate("Edit") . "</a>", "syntax")
		);

		$extmap = $bb_def_extmap;

		BB_RunPluginAction("bb_main_edit_site_opt_file_explorer_exteditmaps");

		$dir = BB_GetRealPath($_REQUEST["dir"]);
		if ($dir == "")  $dir = ".";
		$dirlist = BB_GetDirectoryList($dir);
		$rows = array();
		if ($dir != ".")
		{
			$row = array(BB_CreatePropertiesLink("<..>", "bb_main_edit_site_opt_file_explorer", array("dir" => BB_GetRealPath($dir . "/.."))), "", "");
			if ($bb_account["type"] == "dev")
			{
				$row[] = "";
				$row[] = "";
			}

			$rows[] = $row;
		}
		foreach ($dirlist["dirs"] as $dirfile)
		{
			$row = array(BB_CreatePropertiesLink("<" . $dirfile . ">", "bb_main_edit_site_opt_file_explorer", array("dir" => $dir . "/" . $dirfile)));
			if ($bb_account["type"] == "dev")  $row[] = "";
			$row[] = "";
			$row[] = BB_FormatTimestamp(filemtime($dir . "/" . $dirfile));
			if ($bb_account["type"] == "dev")  $row[] = BB_CreatePropertiesLink(BB_Translate("Rename"), "bb_main_edit_site_opt_file_explorer_rename", array("dir" => $dir, "file" => $dirfile)) . " | " . BB_CreatePropertiesLink(BB_Translate("Delete"), "bb_main_edit_site_opt_file_explorer_delete", array("dir" => $dir, "file" => $dirfile), BB_Translate("Are you sure you want to delete '%s' and all subdirectories?", $dirfile));
			$rows[] = $row;
		}

		BB_RunPluginAction("bb_main_edit_site_opt_file_explorer_dirs");

		$urlbase = BB_GetRequestURLBase();
		foreach ($dirlist["files"] as $dirfile)
		{
			$row = array(($bb_account["type"] == "dev" ? "<a class=\"download\" href=\"" . $urlbase . "?bb_action=bb_main_edit_site_opt_file_explorer_download&file=" . htmlspecialchars(urlencode($dir . "/" . $dirfile)) . "&bbt=" . htmlspecialchars(BB_CreateSecurityToken("bb_main_edit_site_opt_file_explorer_download", "", array($dir . "/" . $dirfile))) . "&bb_sec_extra=file\" target=\"_blank\" title=\"Download\">D</a> " : "") . "<a href=\"" . htmlspecialchars(ROOT_URL . "/" . $dir . "/" . $dirfile) . "\" target=\"_blank\">" . htmlspecialchars($dirfile) . "</a>");
			if ($bb_account["type"] == "dev")  $row[] = BB_FileExplorer_GetActionStr($dir, $dirfile);
			$row[] = number_format(filesize($dir . "/" . $dirfile), 0);
			$row[] = BB_FormatTimestamp(filemtime($dir . "/" . $dirfile));
			if ($bb_account["type"] == "dev")  $row[] = BB_CreatePropertiesLink(BB_Translate("Rename"), "bb_main_edit_site_opt_file_explorer_rename", array("dir" => $dir, "file" => $dirfile)) . " | " . BB_CreatePropertiesLink(BB_Translate("Delete"), "bb_main_edit_site_opt_file_explorer_delete", array("dir" => $dir, "file" => $dirfile), BB_Translate("Are you sure you want to delete '%s'?", $dirfile));

			$rows[] = $row;
		}

		BB_RunPluginAction("bb_main_edit_site_opt_file_explorer_files");

		$desc = "";
		if ($bb_account["type"] == "dev" || $bb_account["type"] == "design")
		{
			$desc .= "<br />";
			if ($bb_account["type"] == "dev")  $desc .= BB_CreatePropertiesLink(BB_Translate("Create New Directory"), "bb_main_edit_site_opt_file_explorer_create", array("dir" => $dir)) . " | " . BB_CreatePropertiesLink(BB_Translate("Create New File"), "bb_main_edit_site_opt_file_explorer_create", array("file" => $dir)) . " | " . BB_CreatePropertiesLink(BB_Translate("Upload/Transfer Files"), "bb_main_edit_site_opt_file_explorer_upload", array("dir" => $dir)) . " | " . (class_exists("ZipArchive") ? BB_CreatePropertiesLink(BB_Translate("Create ZIP File"), "bb_main_edit_site_opt_file_explorer_zip", array("dir" => $dir)) . " | " : "");
			$desc .= BB_CreatePropertiesLink(BB_Translate("Create New Page"), "bb_main_edit_site_opt_file_explorer_newpage", array("dir" => $dir));
		}
		if ($dir != ".")
		{
			$desc .= "<br /><br />";
			$desc .= BB_CreatePropertiesLink("/", "bb_main_edit_site_opt_file_explorer", array("dir" => "."));
			$levels = explode("/", $dir);
			$path = "";
			foreach ($levels as $level)
			{
				if ($path != "")  $path .= "/";
				$path .= $level;
				$desc .= BB_CreatePropertiesLink($level . "/", "bb_main_edit_site_opt_file_explorer", array("dir" => $path));
			}
		}

		if ($bb_account["type"] == "dev")  $cols = array("Filename", "Edit", "File Size", "Last Modified", "Rename/Delete");
		else  $cols = array("Filename", "File Size", "Last Modified");

		$options = array(
			"title" => "File Explorer",
			"desc" => "View and edit files directly in your web browser.",
			"htmldesc" => $desc,
			"fields" => array(
				array(
					"type" => "table",
					"cols" => $cols,
					"rows" => $rows
				)
			)
		);

		BB_RunPluginAction("bb_main_edit_site_opt_file_explorer_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("post_bb_main_edit_site_opt_file_explorer");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_site_opt_delete_translation_notification")
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_delete_translation_notification");

		require_once "translate.php";

		if (!BB_DeleteTranslationNotification((int)$_REQUEST["notify"]))  BB_PropertyFormLoadError("Unable to delete notification.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Notification deleted.")); ?></div>
<script type="text/javascript">
LoadProperties(<?php echo BB_CreatePropertiesJS("bb_main_edit_site_opt_view_translation_notifications"); ?>);
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_site_opt_delete_translation_notification");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_site_opt_view_translation_notifications")
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_view_translation_notifications");

		require_once "translate.php";

		$rows = array();
		$urlbase = BB_GetRequestURLBase();
		foreach ($bb_translate_notify as $num => $entry)
		{
			if (!file_exists($entry[2] . "/" . $entry[3] . "_" . $entry[5] . "_page.php"))  unset($bb_translate_notify[$num]);
			else  $rows[] = array(BB_FormatTimestamp($entry[1]), "<a href=\"" . $urlbase . "?bb_action=bb_main_edit&lang=" . urlencode($entry[4]) . "&notify=" . $num . "\" target=\"_blank\">" . htmlspecialchars(BB_Translate(BB_GetIANADesc($entry[4], true, true))) . "</a>", "<a href=\"" . $urlbase . "?bb_action=bb_main_edit&lang=" . urlencode($entry[5]) . "&notify=" . $num . "\" target=\"_blank\">" . htmlspecialchars(BB_Translate(BB_GetIANADesc($entry[5], true, true))) . "</a>", htmlspecialchars($entry[6]), BB_CreatePropertiesLink(BB_Translate("Delete"), "bb_main_edit_site_opt_delete_translation_notification", array("notify" => $num)));
		}

		BB_RunPluginAction("bb_main_edit_site_opt_view_translation_notifications_active");

		BB_SaveTranslationNotifications();

		if (!count($rows))  BB_PropertyFormLoadError("No notifications found.");

		$options = array(
			"title" => "View Notifications",
			"desc" => "View and manage translation notifications.",
			"fields" => array(
				array(
					"type" => "table",
					"cols" => array("Timestamp", "Source", "Target", "Reason", "Options"),
					"rows" => $rows
				)
			)
		);

		BB_RunPluginAction("bb_main_edit_site_opt_view_translation_notifications_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("post_bb_main_edit_site_opt_view_translation_notifications");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_site_opt_profile_submit")
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_profile_submit");

		$pass = $_REQUEST["oldpass"];
		if ($pass != "")
		{
			if ($bb_account["pass"] !== sha1($bb_account["user"] . ":" . $pass))  BB_PropertyFormError("'Current Password' is incorrect.");
			if ($_REQUEST["newpass"] === "")  BB_PropertyFormError("New password field not filled out.");
			if ($_REQUEST["newpass"] !== $_REQUEST["newpass2"])  BB_PropertyFormError("New password fields are not the same.");

			BB_SetUserPassword($bb_account["user"], $_REQUEST["newpass"]);

			// BB_SetUserPassword wipes out the existing session.  Create a new session.
			require_once ROOT_PATH . "/" . SUPPORT_PATH . "/cookie.php";

			$id = BB_NewUserSession($bb_account["user"], $_REQUEST["bbl"]);
			if ($id === false)  $id = BB_NewUserSession($bb_account["user"], "");
			if ($id === false)
			{
				echo "<span class=\"error\">Unable to create session.</span>";
				exit();
			}

			SetCookieFixDomain("bbl", $id, $bb_accounts["sessions"][$id]["expire"], ROOT_URL . "/", "", USE_HTTPS, true);
			unset($id);
		}

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Account Saved.")); ?></div>
<script type="text/javascript">
ReloadPage();
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_site_opt_profile_submit");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_site_opt_profile")
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_profile");

		$options = array(
			"title" => "Edit Profile",
			"desc" => "View and make changes to your global user profile.  Changing profile information will change your login session and reload the page.  Save your work before proceeding.",
			"fields" => array(
				array(
					"title" => "Username",
					"type" => "static",
					"value" => $bb_account["user"],
					"desc" => "Your username."
				),
				array(
					"title" => "Profile Type",
					"type" => "static",
					"value" => BB_Translate($bb_account["type"] == "dev" ? "Developer/Programmer" : ($bb_account["type"] == "design" ? "Web Designer" : "Content Author")),
					"desc" => "Your profile type."
				),
				array(
					"title" => "Group",
					"type" => "static",
					"value" => ($bb_account["group"] == "" ? "[N/A]" : $bb_account["group"]),
					"desc" => "Your profile group."
				),
				array(
					"title" => "Current Password",
					"type" => "password",
					"name" => "oldpass",
					"value" => "",
					"desc" => "To change your password, enter your current password and then enter a new password below."
				),
				array(
					"title" => "New Password",
					"type" => "password",
					"name" => "newpass",
					"value" => "",
					"desc" => "Enter a new password."
				),
				array(
					"title" => "Repeat New Password",
					"type" => "password",
					"name" => "newpass2",
					"value" => "",
					"desc" => "Enter the new password again."
				)
			),
			"submit" => "Save",
			"focus" => true
		);

		BB_RunPluginAction("bb_main_edit_site_opt_profile_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("post_bb_main_edit_site_opt_profile");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_create_account_submit")
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_create_account_submit");

		if ($_REQUEST["user"] == "")  BB_PropertyFormError("The 'Username' field is empty.");
		if ($_REQUEST["pass"] == "")  BB_PropertyFormError("The 'Password' field is empty.");
		if (!BB_CreateUser($_REQUEST["type"], $_REQUEST["user"], $_REQUEST["pass"], $_REQUEST["group"]))  BB_PropertyFormError("Unable to create the account.  An account with that username already exists.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Account Created.")); ?></div>
<script type="text/javascript">
ReloadMenu();
CloseProperties();
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_site_opt_create_account_submit");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_create_account")
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_create_account");

		$options = array(
			"title" => "Create New Account",
			"desc" => "Create a new user account.",
			"fields" => array(
				array(
					"title" => "Username",
					"type" => "text",
					"name" => "user",
					"value" => "",
					"desc" => "The username for this account."
				),
				array(
					"title" => "Password",
					"type" => "password",
					"name" => "pass",
					"value" => "",
					"desc" => "The password for this account."
				),
				array(
					"title" => "Type",
					"type" => "select",
					"name" => "type",
					"options" => array(
						"content" => "Content Author",
						"design" => "Web Designer",
						"dev" => "Developer/Programmer"
					),
					"desc" => "The type of account."
				),
				array(
					"title" => "Group",
					"type" => "text",
					"name" => "group",
					"value" => "",
					"desc" => "The group for this account.  Only used for content authors."
				)
			),
			"submit" => "Create",
			"focus" => true
		);

		BB_RunPluginAction("bb_main_edit_site_opt_create_account_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("post_bb_main_edit_site_opt_create_account");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_delete_account_submit")
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_delete_account_submit");

		if ($_REQUEST["user"] != $bb_account["user"])
		{
			if (!BB_DeleteUser($_REQUEST["user"]))  BB_PropertyFormError("Unable to delete the user.");
		}

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Account Deleted.")); ?></div>
<script type="text/javascript">
ReloadMenu();
CloseProperties();
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_site_opt_delete_account_submit");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_delete_account")
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_delete_account");

		$users = array();
		foreach ($bb_accounts["users"] as $user => $account)
		{
			if ($user != $bb_account["user"])  $users[$user] = $user;
		}

		$options = array(
			"title" => "Delete Account",
			"desc" => "Select an account to delete.  Deletion is permanent!",
			"fields" => array(
				array(
					"type" => "select",
					"name" => "user",
					"options" => $users
				),
			),
			"submit" => "Delete",
			"focus" => true
		);

		BB_RunPluginAction("bb_main_edit_site_opt_delete_account_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("post_bb_main_edit_site_opt_delete_account");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_uninstall_extension")
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_uninstall_extension");

		if (!isset($_REQUEST["id"]) || (int)$_REQUEST["id"] < 1)  BB_PropertyFormLoadError("Invalid Extension ID.");

		BB_LoadExtensionsCache();

		if (!BB_UninstallExtension((string)(int)$_REQUEST["id"]))  BB_PropertyFormLoadError("Unable to uninstall extension.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Extension uninstalled.")); ?></div>
<script type="text/javascript">
LoadProperties(<?php echo BB_CreatePropertiesJS("bb_main_edit_site_opt_manage_extensions"); ?>);
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_site_opt_uninstall_extension");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_install_extension2_submit")
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_install_extension2_submit");

		if (!isset($_REQUEST["id"]) || (int)$_REQUEST["id"] < 1)  BB_PropertyFormError("Invalid Extension ID.");

		$result = BB_GetExtensionInfo((int)$_REQUEST["id"]);
		if ($result === false)  BB_PropertyFormError("Unable to get information about the extension.");
		else if (!$result["success"])  BB_PropertyFormError(BB_Translate("%s (%s)", $result["error"], $result["errorcode"]));
		else if ($result["status"] === "security_vulnerability")  BB_PropertyFormError("The specified extension is not compatible with this version of Barebones CMS.  Installing the extension would introduce a security vulnerability.");
		else if (!$result["can_install"])  BB_PropertyFormError("The specified extension is not compatible with this version of Barebones CMS.");

		BB_LoadExtensionsCache();

		$result = BB_InstallExtension($result["id"]);
		if (!$result["success"])  BB_PropertyFormError($result["error"]);

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Extension installed.")); ?></div>
<script type="text/javascript">
LoadProperties(<?php echo BB_CreatePropertiesJS("bb_main_edit_site_opt_manage_extensions"); ?>);
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_site_opt_install_extension2_submit");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_install_extension2")
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_install_extension2");

		if (!isset($_REQUEST["id"]) || (int)$_REQUEST["id"] < 1)  BB_PropertyFormLoadError("Invalid Extension ID.");

		$result = BB_GetExtensionInfo((int)$_REQUEST["id"]);
		if ($result === false)  BB_PropertyFormLoadError("Unable to get information about the extension.");
		else if (!$result["success"])  BB_PropertyFormLoadError(BB_Translate("%s (%s)", $result["error"], $result["errorcode"]));
		else if ($result["status"] === "security_vulnerability")  BB_PropertyFormLoadError("The specified extension is not compatible with this version of Barebones CMS.  Installing the extension would introduce a security vulnerability.");
		else if (!$result["can_install"])  BB_PropertyFormLoadError("The specified extension is not compatible with this version of Barebones CMS.");

		$desc = "<div style=\"float: right; margin-left: 20px;\"><a href=\"" . htmlspecialchars($result["screen_url"]) . "\" target=\"blank\"><img src=\"" . htmlspecialchars($result["screenshot"]) . "\" /></a></div>";
		$desc .= htmlspecialchars($result["desc"]);
		$desc .= "<ul>";
		$desc .= "<li>" . BB_Translate("Extension ID:  %s", htmlspecialchars($result["id"])) . "</li>";
		$desc .= "<li>" . BB_Translate("Type:  %s", htmlspecialchars($result["type_disp"])) . "</li>";
		$desc .= "<li>" . BB_Translate("Author:  %s", htmlspecialchars($result["author"])) . "</li>";
		if ($result["ver"] === $result["latest"])  $desc .= "<li>" . BB_Translate("Installable version:  %s (Latest release)", htmlspecialchars($result["latest"])) . "</li>";
		else
		{
			$desc .= "<li>" . BB_Translate("Installable version:  %s", htmlspecialchars($result["ver"])) . "</li>";
			$desc .= "<li>" . BB_Translate("Latest version:  %s", htmlspecialchars($result["latest"])) . "</li>";
		}
		$desc .= "</ul>";
		$desc .= "<div style=\"clear: both;\"></div>";

		$options = array(
			"title" => BB_Translate("Install Extension - %s", $result["name"]),
			"desc" => "",
			"htmldesc" => $desc,
			"hidden" => array(
				"id" => $result["id"],
			),
			"fields" => array(
			),
			"submit" => "Install"
		);

		BB_RunPluginAction("bb_main_edit_site_opt_install_extension2_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("post_bb_main_edit_site_opt_install_extension2");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_install_extension_submit")
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_install_extension_submit");

		BB_LoadExtensionsCache();

		if (!isset($_REQUEST["id"]) || (int)$_REQUEST["id"] < 1)  BB_PropertyFormError("Invalid Extension ID.");
		if (isset($bb_extensions_info["exts"][$_REQUEST["id"]]))  BB_PropertyFormError("Extension is already installed.");

		$result = BB_GetExtensionInfo((int)$_REQUEST["id"]);
		if ($result === false)  BB_PropertyFormError("Unable to get information about the extension.");
		else if (!$result["success"])  BB_PropertyFormError(BB_Translate("%s (%s)", $result["error"], $result["errorcode"]));
		else if ($result["status"] === "security_vulnerability")  BB_PropertyFormError("The specified extension is not compatible with this version of Barebones CMS.  Installing the extension would introduce a security vulnerability.");
		else if (!$result["can_install"])  BB_PropertyFormError("The specified extension is not compatible with this version of Barebones CMS.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Extension found.")); ?></div>
<script type="text/javascript">
LoadProperties(<?php echo BB_CreatePropertiesJS("bb_main_edit_site_opt_install_extension2", array("id" => $_REQUEST["id"])); ?>);
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_site_opt_install_extension_submit");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_install_extension")
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_install_extension");

		$desc = "<br />";
		$desc .= "<a href=\"https://barebonescms.com/extend/\" target=\"_blank\">Find an extension</a>";

		$options = array(
			"title" => "Install New Extension",
			"desc" => "Find the extension to install, get the extension ID, and install it automatically.",
			"htmldesc" => $desc,
			"fields" => array(
				array(
					"title" => "Extension ID",
					"type" => "text",
					"name" => "id",
					"value" => "",
					"desc" => "Each extension has an extension ID.  Use the link above to find the ID of the extension you want to install."
				)
			),
			"submit" => "Next",
			"focus" => true
		);

		BB_RunPluginAction("bb_main_edit_site_opt_install_extension_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("post_bb_main_edit_site_opt_install_extension");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_manage_extensions")
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_manage_extensions");

		// Load the extensions cache and make sure it is up to date.
		BB_LoadExtensionsCache();
		BB_UpdateExtensionsCache();

		$rows = array();
		foreach ($bb_extensions_info["exts"] as $extinfo)
		{
			$rows[] = array(htmlspecialchars($extinfo["id"]), htmlspecialchars($extinfo["name"]), htmlspecialchars(BB_Translate($extinfo["type_disp"])), htmlspecialchars($extinfo["author"]), htmlspecialchars($extinfo["ver"]), htmlspecialchars(BB_Translate(isset($bb_extensions_info["vulnerabilities"][$extinfo["id"]]) ? "Critical update available" : (isset($bb_extensions_info["updates"][$extinfo["id"]]) ? "Update available" : "OK"))), BB_CreatePropertiesLink(BB_Translate(isset($bb_extensions_info["updates"][$extinfo["id"]]) || isset($bb_extensions_info["vulnerabilities"][$extinfo["id"]]) ? "Update now" : "Reinstall"), "bb_main_edit_site_opt_install_extension2", array("id" => $extinfo["id"])) . " | " . BB_CreatePropertiesLink(BB_Translate("Uninstall"), "bb_main_edit_site_opt_uninstall_extension", array("id" => $extinfo["id"]), BB_Translate("Are you sure you want to uninstall the '%s' extension?", $extinfo["name"])));
		}

		$desc = "<br />";
		$desc .= BB_CreatePropertiesLink(BB_Translate("Install New Extension"), "bb_main_edit_site_opt_install_extension");

		$options = array(
			"title" => "Manage Extensions",
			"desc" => "View and manage installed extensions using the options below.",
			"htmldesc" => $desc,
			"fields" => array(
				array(
					"type" => "table",
					"cols" => array("ID", "Extension", "Type", "Author", "Version", "Status", "Options"),
					"rows" => $rows
				)
			)
		);

		BB_RunPluginAction("bb_main_edit_site_opt_manage_extensions_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("post_bb_main_edit_site_opt_manage_extensions");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_create_widget_submit")
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_create_widget_submit");

		if ($_REQUEST["sname"] == "")  BB_PropertyFormError("'Widget Base Name' field not filled in.");
		if ($_REQUEST["name"] == "")  BB_PropertyFormError("'Widget Name' field not filled in.");
		if (!BB_CreateWidget($_REQUEST["sname"], $_REQUEST["name"]))  BB_PropertyFormError("Unable to create the widget.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Widget Created.")); ?></div>
<script type="text/javascript">
CloseProperties();
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_site_opt_create_widget_submit");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_create_widget")
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_create_widget");

		$options = array(
			"title" => "Create New Widget",
			"desc" => "Create a new widget.  A widget is a reusable component such as a header, a footer, navigation, etc.",
			"fields" => array(
				array(
					"title" => "Widget Base Name",
					"type" => "text",
					"name" => "sname",
					"value" => "",
					"desc" => "The base name of this widget.  MUST be unique with no spaces and be able to be used in PHP function call names.  Difficult to change."
				),
				array(
					"title" => "Widget Name",
					"type" => "text",
					"name" => "name",
					"value" => "",
					"desc" => "The name of this widget.  You can change this later."
				)
			),
			"submit" => "Create",
			"focus" => true
		);

		BB_RunPluginAction("bb_main_edit_site_opt_create_widget_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("post_bb_main_edit_site_opt_create_widget");
	}
	else if (($bb_account["type"] == "dev" || $bb_account["type"] == "design") && $_REQUEST["bb_action"] == "bb_main_edit_widgets_add_widget_submit")
	{
		BB_RunPluginAction("pre_bb_main_edit_widgets_add_widget_submit");

		if (!isset($_REQUEST["wid"]))  BB_PropertyFormError("Master widget ID not specified.");
		$name = $_REQUEST["wid"];
		if (!isset($bb_langpage["widgets"][$name]) || $bb_langpage["widgets"][$name]["_m"] === false)  BB_PropertyFormError("Invalid master widget ID.");

		$data = BB_GetWidgetList();
		if (!count($data))  BB_PropertyFormError("No widgets found.");
		for ($x = 0; $x < count($data) && $data[$x]["_dir"] != $_REQUEST["dir"]; $x++);
		if ($x == count($data))  BB_PropertyFormError("Unknown widget specified.");

		if ($_REQUEST["name"] == "")  BB_PropertyFormError("Display Name not filled out.");

		if (!BB_AddWidget($_REQUEST["dir"], $_REQUEST["name"], $_REQUEST["wid"]))  BB_PropertyFormError("Unable to add widget.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Added Widget.")); ?></div>
<script type="text/javascript">
ReloadMenu();
ReloadIFrame();
CloseProperties();
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_widgets_add_widget_submit");
	}
	else if (($bb_account["type"] == "dev" || $bb_account["type"] == "design") && $_REQUEST["bb_action"] == "bb_main_edit_widgets_add_widget")
	{
		BB_RunPluginAction("pre_bb_main_edit_widgets_add_widget");

		if (!isset($_REQUEST["wid"]))  BB_PropertyFormLoadError("Master widget ID not specified.");
		$name = $_REQUEST["wid"];
		if (!isset($bb_langpage["widgets"][$name]) || $bb_langpage["widgets"][$name]["_m"] === false)  BB_PropertyFormLoadError("Invalid master widget ID.");

		$data = BB_GetWidgetList();
		if (!count($data))  BB_PropertyFormLoadError("No widgets found.");

		$widgets = array();
		foreach ($data as $widget)  $widgets[$widget["_dir"]] = $widget["_n"];

		$options = array(
			"title" => "Add Widget",
			"desc" => BB_Translate("Add a widget to the page's '%s' section.", $bb_langpage["widgets"][$name]["_f"]),
			"fields" => array(
				array(
					"title" => "Widget",
					"type" => "select",
					"name" => "dir",
					"options" => $widgets,
					"desc" => "The widget to add to this page's section."
				),
				array(
					"title" => "Display Name",
					"type" => "text",
					"name" => "name",
					"value" => "",
					"desc" => "The display name of this widget/page section."
				)
			),
			"submit" => "Add",
			"focus" => true
		);

		BB_RunPluginAction("bb_main_edit_widgets_add_widget_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("post_bb_main_edit_widgets_add_widget");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_delete_widget_submit")
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_delete_widget_submit");

		if (!BB_DeleteWidgetFiles($_REQUEST["dir"]))  BB_PropertyFormError("Unable to delete the widget.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Widget Deleted.")); ?></div>
<script type="text/javascript">
ReloadIFrame();
CloseProperties();
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_site_opt_delete_widget_submit");
	}
	else if ($bb_account["type"] == "dev" && $_REQUEST["bb_action"] == "bb_main_edit_site_opt_delete_widget")
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_delete_widget");

		$data = BB_GetWidgetList();
		if (!count($data))  BB_PropertyFormLoadError("No widgets found.");

		$widgets = array();
		foreach ($data as $widget)  $widgets[$widget["_dir"]] = $widget["_n"];

		$options = array(
			"title" => "Delete Widget",
			"desc" => "Select a widget to delete.  Deletion is permanent and may delete all related content!",
			"fields" => array(
				array(
					"type" => "select",
					"name" => "dir",
					"options" => $widgets
				)
			),
			"submit" => "Delete",
			"focus" => true
		);

		BB_RunPluginAction("bb_main_edit_site_opt_delete_widget_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("post_bb_main_edit_site_opt_delete_widget");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_site_opt_flush_cache")
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_flush_cache");

		if (!BB_WidgetStatusUpdate())  BB_PropertyFormLoadError("Unable to flush the cache.", true);

		BB_LoadExtensionsCache();
		$bb_extensions_info["nextcheck"] = 0;
		BB_SaveExtensionsCache();

		BB_PropertyFormLoadError("Successfully flushed the cache.", true);

		BB_RunPluginAction("post_bb_main_edit_site_opt_flush_cache");
	}
	else if ($_REQUEST["bb_action"] == "bb_main_edit_site_opt_logout")
	{
		BB_RunPluginAction("pre_bb_main_edit_site_opt_logout");

		if (!BB_LogoutUserSession($bb_account["user"], $_REQUEST["bbl"]))  BB_PropertyFormLoadError("Unable to logout.  Token mismatch.");

		require_once ROOT_PATH . "/" . SUPPORT_PATH . "/cookie.php";

		SetCookieFixDomain("bbl", "", 1, ROOT_URL . "/", "", USE_HTTPS, true);
		SetCookieFixDomain("bbq", "", 1, ROOT_URL . "/", "", USE_HTTPS, true);

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Successfully Logged Out.")); ?></div>
<script type="text/javascript">
window.location.href = Gx__FullURLBaseHTTP;
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_site_opt_logout");
	}
	else if (($bb_account["type"] == "dev" || $bb_account["type"] == "design") && $_REQUEST["bb_action"] == "bb_main_edit_widgets_attach_widget")
	{
		BB_RunPluginAction("pre_bb_main_edit_widgets_attach_widget");

		if (!isset($_REQUEST["wid"]))  BB_PropertyFormLoadError("Widget ID not specified.");
		if (!isset($_REQUEST["pid"]))  BB_PropertyFormLoadError("Master widget ID not specified.");
		if (!isset($bb_langpage["widgets"][$_REQUEST["wid"]]) || $bb_langpage["widgets"][$_REQUEST["wid"]]["_m"] === true)  BB_PropertyFormLoadError("Invalid widget ID.");
		if (!isset($bb_langpage["widgets"][$_REQUEST["pid"]]) || $bb_langpage["widgets"][$_REQUEST["pid"]]["_m"] === false)  BB_PropertyFormLoadError("Invalid master widget ID.");

		if (!BB_AttachWidget($_REQUEST["wid"], $_REQUEST["pid"]))  BB_PropertyFormLoadError("Unable to attach widget.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Widget attached.")); ?></div>
<script type="text/javascript">
ReloadIFrame();
CloseProperties();
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_widgets_attach_widget");
	}
	else if (($bb_account["type"] == "dev" || $bb_account["type"] == "design") && $_REQUEST["bb_action"] == "bb_main_edit_widgets_detach_widget")
	{
		BB_RunPluginAction("pre_bb_main_edit_widgets_detach_widget");

		if (!isset($_REQUEST["wid"]))  BB_PropertyFormLoadError("Widget ID not specified.");
		if (!isset($bb_langpage["widgets"][$_REQUEST["wid"]]))  BB_PropertyFormLoadError("Invalid widget ID.");

		$bb_widget->SetID($_REQUEST["wid"]);
		if ($bb_widget->_m === false && $bb_widget->_a !== false)  $bb_widget_instances[$_REQUEST["wid"]]->ProcessBBAction();

		if (!BB_DetachWidget($_REQUEST["wid"]))  BB_PropertyFormLoadError("Unable to detach widget.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Widget detached.")); ?></div>
<script type="text/javascript">
ReloadIFrame();
CloseProperties();
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_widgets_detach_widget");
	}
	else if (($bb_account["type"] == "dev" || $bb_account["type"] == "design") && $_REQUEST["bb_action"] == "bb_main_edit_widgets_delete_widget")
	{
		BB_RunPluginAction("pre_bb_main_edit_widgets_delete_widget");

		if (!isset($_REQUEST["wid"]))  BB_PropertyFormLoadError("Widget ID not specified.");
		if (!isset($bb_langpage["widgets"][$_REQUEST["wid"]]))  BB_PropertyFormLoadError("Invalid widget ID.");

		if (!BB_DeleteWidget($_REQUEST["wid"]))  BB_PropertyFormLoadError("Unable to delete widget.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Widget deleted.")); ?></div>
<script type="text/javascript">
ReloadIFrame();
CloseProperties();
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_widgets_delete_widget");
	}
	else if (($bb_account["type"] == "dev" || $bb_account["type"] == "design") && $_REQUEST["bb_action"] == "bb_main_edit_widgets_reorder_submit")
	{
		BB_RunPluginAction("pre_bb_main_edit_widgets_reorder_submit");

		if (!isset($_REQUEST["wid"]))  BB_PropertyFormLoadError("Master widget ID not specified.");
		if (!isset($bb_langpage["widgets"][$_REQUEST["wid"]]) || $bb_langpage["widgets"][$_REQUEST["wid"]]["_m"] === false)  BB_PropertyFormLoadError("Invalid master widget ID.");

		if (isset($_REQUEST["wids"]) && is_array($_REQUEST["wids"]))
		{
			$neworder = array();
			foreach ($_REQUEST["wids"] as $num)
			{
				if (isset($bb_langpage["widgets"][$_REQUEST["wid"]]["_ids"][$num]))
				{
					$neworder[$num] = $bb_langpage["widgets"][$_REQUEST["wid"]]["_ids"][$num];
					unset($bb_langpage["widgets"][$_REQUEST["wid"]]["_ids"][$num]);
				}
			}

			foreach ($bb_langpage["widgets"][$_REQUEST["wid"]]["_ids"] as $num => $id)  $neworder[$num] = $id;

			$bb_langpage["widgets"][$_REQUEST["wid"]]["_ids"] = $neworder;

			if (!BB_SaveLangPage($bb_revision_num))  BB_PropertyFormError("Unable to save the widget order.");
		}

?>
<script type="text/javascript">
LoadProperties(<?php echo BB_CreatePropertiesJS("bb_main_edit_widgets_reorder", array("wid" => $_REQUEST["wid"])); ?>);
ReloadIFrame();
</script>
<?php

		BB_RunPluginAction("post_bb_main_edit_widgets_reorder_submit");
	}
	else if (($bb_account["type"] == "dev" || $bb_account["type"] == "design") && $_REQUEST["bb_action"] == "bb_main_edit_widgets_reorder")
	{
		BB_RunPluginAction("pre_bb_main_edit_widgets_reorder");

		if (!isset($_REQUEST["wid"]))  BB_PropertyFormLoadError("Master widget ID not specified.");
		if (!isset($bb_langpage["widgets"][$_REQUEST["wid"]]) || $bb_langpage["widgets"][$_REQUEST["wid"]]["_m"] === false)  BB_PropertyFormLoadError("Invalid master widget ID.");

		$rows = array();
		foreach ($bb_langpage["widgets"][$_REQUEST["wid"]]["_ids"] as $num => $id)
		{
			if (isset($bb_langpage["widgets"][$id]))
			{
				$rows[] = array(htmlspecialchars($bb_langpage["widgets"][$id]["_f"]) . "<input type=\"hidden\" name=\"wids[]\" value=\"" . $num . "\" />");
			}
		}

		$options = array(
			"title" => BB_Translate("Reorder %s Widgets", $bb_langpage["widgets"][$_REQUEST["wid"]]["_f"]),
			"desc" => "Drag and drop the rows to reorder the widgets on the page.",
			"useform" => true,
			"fields" => array(
				array(
					"type" => "table",
					"order" => "Order",
					"cols" => array("Widget Name"),
					"rows" => $rows
				)
			)
		);

		BB_RunPluginAction("bb_main_edit_widgets_reorder_options");

		BB_PropertyForm($options);

		BB_RunPluginAction("post_bb_main_edit_widgets_reorder");
	}
	else
	{
		if (isset($_REQUEST["wid"]) && isset($bb_langpage["widgets"][$_REQUEST["wid"]]))
		{
			$bb_widget->SetID($_REQUEST["wid"]);
			if ($bb_widget->_m === false && $bb_widget->_a !== false)  $bb_widget_instances[$_REQUEST["wid"]]->ProcessBBAction();
		}
	}

	BB_RunPluginAction("done");
?>