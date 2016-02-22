<?php
	// Barebones CMS Code Widget
	// (C) 2014 CubicleSoft.  All Rights Reserved.

	if (!defined("BB_FILE"))  exit();

	$bb_widget->_s = "bb_code";
	$bb_widget->_n = "Code";
	$bb_widget->_key = "";
	$bb_widget->_ver = "";

	class bb_code extends BB_WidgetBase
	{
		private $initrun;

		public function Init()
		{
			global $bb_widget;

			$this->initrun = false;

			if (!isset($bb_widget->langmap))  $bb_widget->langmap = "";
		}

		public function PreWidget()
		{
			global $bb_account;

			if (BB_IsMemberOfPageGroup("_p"))
			{
				echo BB_CreateWidgetPropertiesLink(BB_Translate("Edit Language Map"), "bb_code_edit_langmap");

				if ($bb_account["type"] == "dev")
				{
					echo BB_CreateWidgetPropertiesLink(BB_Translate("Edit Init"), "bb_code_edit_init");
					echo BB_CreateWidgetPropertiesLink(BB_Translate("Edit Pre-HTML"), "bb_code_edit_prehtml");
					echo BB_CreateWidgetPropertiesLink(BB_Translate("Edit Head"), "bb_code_edit_head");
					echo BB_CreateWidgetPropertiesLink(BB_Translate("Edit Body"), "bb_code_edit_body");
					echo BB_CreateWidgetPropertiesLink(BB_Translate("Edit Action"), "bb_code_edit_action");
				}
			}
		}

		private function ProcessLangmap()
		{
			global $bb_widget, $bb_admin_lang, $bb_admin_def_lang, $bb_langmap;

			if (!isset($bb_langmap))
			{
				$bb_admin_lang = "";
				$bb_admin_def_lang = "";
				$bb_langmap = array("" => array());
			}

			$lines = explode("\n", $bb_widget->langmap);
			foreach ($lines as $line)
			{
				$line = rtrim($line);
				if ($line != "")
				{
					$chr = substr($line, 0, 1);
					$pos = strpos($line, $chr, 1);
					if ($pos !== false)
					{
						$key = substr($line, 1, $pos - 1);
						if ($key !== "")  $bb_langmap[""][$key] = (string)@substr($line, $pos + 1);
					}
				}
			}
		}

		public function Process()
		{
			foreach ($GLOBALS as $key => $val)
			{
				if (substr($key, 0, 3) == "bb_" || substr($key, 0, 2) == "g_")  global $$key;
			}

			if ($bb_mode == "prehtml")
			{
				if (!$this->initrun)
				{
					$this->initrun = true;

					$this->ProcessLangmap();

					if (isset($bb_widget->init))  eval("?" . ">" . $bb_widget->init);
				}

				if (isset($bb_widget->prehtml))  eval("?" . ">" . $bb_widget->prehtml);
			}
			else if ($bb_mode == "head")
			{
				if (isset($bb_widget->head))  eval("?" . ">" . $bb_widget->head);
			}
			else if ($bb_mode == "body")
			{
				if (isset($bb_widget->body))  eval("?" . ">" . $bb_widget->body);
			}
		}

		public function ProcessAction()
		{
			foreach ($GLOBALS as $key => $val)
			{
				if (substr($key, 0, 3) == "bb_" || substr($key, 0, 2) == "g_")  global $$key;
			}

			if (!$this->initrun)
			{
				$this->initrun = true;

				$this->ProcessLangmap();

				if (isset($bb_widget->init))  eval("?" . ">" . $bb_widget->init);
			}

			if (!isset($bb_widget->action) || $bb_widget->action === "")  echo BB_ProcessPage(true, false, true);
			else  eval("?" . ">" . $bb_widget->action);
		}

		public function ProcessBBAction()
		{
			global $bb_widget, $bb_widget_id, $bb_account, $bb_revision_num;

			if (!BB_IsMemberOfPageGroup("_p"))  exit();

			if ($_REQUEST["bb_action"] == "bb_code_edit_langmap_submit")
			{
				BB_RunPluginAction("pre_bb_code_edit_langmap_submit");

				$bb_widget->langmap = $_REQUEST["langmap"];

				if (!BB_SaveLangPage($bb_revision_num))  BB_PropertyFormError("Unable to save the language mapping.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Language mapping saved.")); ?></div>
<script type="text/javascript">
window.parent.CloseProperties();
window.parent.ReloadIFrame();
</script>
<?php

				BB_RunPluginAction("post_bb_code_edit_langmap_submit");
			}
			else if ($_REQUEST["bb_action"] == "bb_code_edit_langmap")
			{
				BB_RunPluginAction("pre_bb_code_edit_langmap");

				$options = array(
					"title" => BB_Translate("Edit %s Language Map", $bb_widget->_f),
					"desc" => "Edit the language map.  One mapping entry per line.  First character indicates the termination character of the key.  Empty keys are ignored.",
					"fields" => array(
						array(
							"title" => "",
							"type" => "textarea",
							"name" => "langmap",
							"value" => $bb_widget->langmap,
							"desc" => "Example:  |key|value"
						)
					),
					"submit" => "Save",
					"focus" => true
				);

				BB_RunPluginActionInfo("bb_code_edit_langmap_options", $options);

				BB_PropertyForm($options);

				BB_RunPluginAction("post_bb_code_edit_langmap");

				return;
			}

			if ($bb_account["type"] == "dev")
			{
				$types = array(
					"init" => array("ltitle" => "init", "utitle" => "Init"),
					"action" => array("ltitle" => "action", "utitle" => "Action"),
					"prehtml" => array("ltitle" => "pre-HTML", "utitle" => "Pre-HTML"),
					"head" => array("ltitle" => "head", "utitle" => "Head"),
					"body" => array("ltitle" => "body", "utitle" => "Body"),
				);

				foreach ($types as $key => $typeinfo)
				{
					if ($_REQUEST["bb_action"] == "bb_code_edit_" . $key . "_load")
					{
						BB_RunPluginAction("pre_bb_code_edit_" . $key . "_load");

						if (isset($bb_widget->$key))  echo rawurlencode($bb_widget->$key);

						BB_RunPluginAction("post_bb_code_edit_" . $key . "_load");

						return;
					}
					else if ($_REQUEST["bb_action"] == "bb_code_edit_" . $key . "_save")
					{
						BB_RunPluginAction("pre_bb_code_edit_" . $key . "_save");

						$bb_widget->$key = $_REQUEST["content"];
						if (!BB_SaveLangPage($bb_revision_num))  echo htmlspecialchars(BB_Translate("Unable to save " . $typeinfo["ltitle"] . " content.  Try again."));
						else
						{
							echo "OK\n";
							echo "<script type=\"text/javascript\">ReloadIFrame();</script>";
						}

						BB_RunPluginAction("post_bb_code_edit_" . $key . "_save");

						return;
					}
					else if ($_REQUEST["bb_action"] == "bb_code_edit_" . $key)
					{
						BB_RunPluginAction("pre_bb_code_edit_" . $key);

?>
<script type="text/javascript">
window.parent.LoadConditionalScript(Gx__RootURL + '/' + Gx__SupportPath + '/editfile.js?_=20140418', true, function(loaded) {
		return ((!loaded && typeof(window.CreateEditAreaInstance) == 'function') || (loaded && !IsConditionalScriptLoading()));
	}, function(params) {
		$('#fileeditor').show();

		var fileopts = {
			loadurl : Gx__URLBase,
			loadparams : <?php echo BB_CreateWidgetPropertiesJS("bb_code_edit_" . $key . "_load", array(), true); ?>,
			id : 'wid_<?php echo BB_JSSafe($bb_widget_id); ?>_<?php echo BB_JSSafe($key); ?>',
			display : '<?php echo BB_JSSafe($bb_widget->_f . " - " . $typeinfo["utitle"]); ?>',
			saveurl : Gx__URLBase,
			saveparams : <?php echo BB_CreateWidgetPropertiesJS("bb_code_edit_" . $key . "_save", array(), true); ?>,
			syntax : 'php',
			aceopts : {
				'focus' : true,
				'theme' : 'crimson_editor'
			}
		};

		var editopts = {
			ismulti : true,
			closelast : ClosedAllFiles,
			width : '100%',
			height : '500px'
		};

		CreateEditAreaInstance('fileeditor', fileopts, editopts);
});
window.parent.CloseProperties(false);
</script>
<?php

						BB_RunPluginAction("post_bb_code_edit_" . $key);

						return;
					}
				}
			}

			// Pass other requests onto the action handler.
			if (isset($_REQUEST["action"]))
			{
				foreach ($GLOBALS as $key => $val)
				{
					if (substr($key, 0, 3) == "bb_" || substr($key, 0, 2) == "g_")  global $$key;
				}

				if (isset($bb_widget->action))  eval("?" . ">" . $bb_widget->action);
			}
		}
	}
?>