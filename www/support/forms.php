<?php
	// Barebones CMS frontend forms support.
	// (C) 2014 CubicleSoft.  All Rights Reserved.

	require_once str_replace("\\", "/", dirname(__FILE__)) . "/bb_functions.php";

	class BB_Forms
	{
		protected $formtables, $formwidths, $output, $numforms, $basepath, $secretkey, $extrainfo;

		public function __construct()
		{
			$this->formtables = true;
			$this->formwidths = true;
			$this->output = array(
				"jquery" => false,
				"jqueryui" => false,
				"css" => false,
			);
			$this->numforms = 0;
			$this->secretkey = false;
			$this->extrainfo = "";

			if (defined("BB_ROOT_URL"))  $rooturl = BB_ROOT_URL;
			else if (defined("ROOT_URL"))  $rooturl = ROOT_URL;
			else
			{
				$rooturl = BB_GetRequestURLBase();
				if (substr($rooturl, -1) != "/")  $rooturl = dirname($rooturl);
				if (substr($rooturl, -1) == "/")  $rooturl = substr($rooturl, 0, -1);
			}

			if (defined("BB_SUPPORT_PATH"))  $supportpath = BB_SUPPORT_PATH;
			else if (defined("SUPPORT_PATH"))  $supportpath = SUPPORT_PATH;
			else  $supportpath = "support";

			$this->basepath = $rooturl . "/" . $supportpath;
		}

		public function SetFormTables($formtables)
		{
			$this->formtables = (bool)$formtables;
		}

		public function SetFormWidths($formwidths)
		{
			$this->formwidths = (bool)$formwidths;
		}

		public function SetBasePath($basepath)
		{
			$this->basepath = (string)$basepath;
		}

		public function SetSecretKey($secretkey)
		{
			$this->secretkey = (string)$secretkey;
		}

		public function SetExtraInfo($extrainfo)
		{
			$this->extrainfo = (string)$extrainfo;
		}

		public function CreateSecurityToken($action, $extra = "")
		{
			if ($this->secretkey === false)
			{
				echo BB_Translate("Secret key not set for form.");
				exit();
			}

			$str = $action . ":" . $this->extrainfo;
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

			return hash_hmac("sha1", $str, $this->secretkey);
		}

		public static function IsSecExtraOpt($opt)
		{
			return (isset($_REQUEST["sec_extra"]) && strpos("," . $_REQUEST["sec_extra"] . ",", "," . $opt . ",") !== false);
		}

		public function CheckSecurityToken($action)
		{
			if (isset($_REQUEST[$action]) && (!isset($_REQUEST["sec_t"]) || $_REQUEST["sec_t"] != $this->CreateSecurityToken($_REQUEST[$action], (isset($_REQUEST["sec_extra"]) ? $_REQUEST["sec_extra"] : ""))))
			{
				echo BB_Translate("Invalid security token.  Cross-site scripting (XSRF attack) attempt averted.");
				exit();
			}
		}

		public function OutputFormCSS()
		{
			if (!$this->output["css"])
			{
?>
	<link rel="stylesheet" href="<?php echo htmlspecialchars($this->basepath . "/css/forms.css"); ?>" type="text/css" media="all" />
<?php
				$this->output["css"] = true;
			}
		}

		public function OutputMessage($type, $message)
		{
			$type = strtolower((string)$type);
			if ($type == "warn")  $type = "warning";

			$this->OutputFormCSS();

?>
	<div class="bb_formmessagewrap">
		<div class="bb_formmessagewrapinner">
			<div class="message message<?php echo htmlspecialchars($type); ?>">
				<?php echo (string)$message; ?>
			</div>
		</div>
	</div>
<?php
		}

		public function GetEncodedSignedMessage($type, $message, $prefix = "")
		{
			$message = BB_Translate($message);

			return urlencode($prefix . "msgtype") . "=" . urlencode($type) . "&" . urlencode($prefix . "msg") . "=" . urlencode($message) . "&" . urlencode($prefix . "msg_t") . "=" . $this->CreateSecurityToken("forms__message", array($type, $message));
		}

		public function OutputSignedMessage($prefix = "")
		{
			if (isset($_REQUEST[$prefix . "msgtype"]) && isset($_REQUEST[$prefix . "msg"]) && isset($_REQUEST[$prefix . "msg_t"]) && $_REQUEST[$prefix . "msg_t"] === $this->CreateSecurityToken("forms__message", array($_REQUEST[$prefix . "msgtype"], $_REQUEST[$prefix . "msg"])))
			{
				$this->OutputMessage($_REQUEST[$prefix . "msgtype"], htmlspecialchars($_REQUEST[$prefix . "msg"]));
			}
		}

		public function OutputJQuery()
		{
			if (!$this->output["jquery"])
			{
?>
	<script type="text/javascript" src="<?php echo htmlspecialchars($this->basepath . "/jquery-1.11.0" . (defined("DEBUG_JS") ? "" : ".min") . ".js"); ?>"></script>
<?php
				$this->output["jquery"] = true;
			}
		}

		public function OutputJQueryUI()
		{
			if (!$this->output["jqueryui"])
			{
?>
	<link rel="stylesheet" href="<?php echo htmlspecialchars($this->basepath . "/jquery_ui_themes/smoothness/jquery-ui-1.10.4.css"); ?>" type="text/css" media="all" />
	<script type="text/javascript" src="<?php echo htmlspecialchars($this->basepath . "/jquery-ui-1.10.4" . (defined("DEBUG_JS") ? "" : ".min") . ".js"); ?>"></script>
<?php
				$this->output["jqueryui"] = true;
			}
		}

		public static function GetValue($key, $default)
		{
			return (isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default);
		}

		public static function GetSelectValues($data)
		{
			$result = array();
			foreach ($data as $val)  $result[$val] = true;

			return $result;
		}

		public static function ProcessInfoDefaults($info, $defaults)
		{
			foreach ($defaults as $key => $val)
			{
				if (!isset($info[$key]))  $info[$key] = $val;
			}

			return $info;
		}

		public static function GetIDDiff($origids, $newids)
		{
			$result = array("remove" => array(), "add" => array());
			foreach ($origids as $id => $val)
			{
				if (!isset($newids[$id]))  $result["remove"][$id] = $val;
			}

			foreach ($newids as $id => $val)
			{
				if (!isset($origids[$id]))  $result["add"][$id] = $val;
			}

			return $result;
		}

		public function GetRandomizedFieldName($name)
		{
			if ($this->secretkey === false)
			{
				echo BB_Translate("Secret key not set.");
				exit();
			}

			return "f_" . hash_hmac("md5", $name, $this->secretkey);
		}

		public function GetRandomizedFieldValues($nameswithdefaults)
		{
			if ($this->secretkey === false)
			{
				echo BB_Translate("Secret key not set.");
				exit();
			}

			$result = array();
			foreach ($nameswithdefaults as $name => $default)
			{
				$name2 = "f_" . hash_hmac("md5", $name, $this->secretkey);

				$result[$name] = (isset($_REQUEST[$name2]) ? $_REQUEST[$name2] : $default);
			}

			return $result;
		}

		protected function InitFormVars(&$options)
		{
			if (!isset($this->output["date"]))  $this->output["date"] = true;
			if (!isset($this->output["accordion"]))  $this->output["accordion"] = true;
			if (!isset($this->output["tableorder"]))  $this->output["tableorder"] = false;
			if (!isset($this->output["tablestickyheader"]))  $this->output["tablestickyheader"] = false;

			$result = array(
				"multiselectused" => array(),
				"multiselectheight" => 200,
				"autofocus" => false,
				"insiderow" => false,
				"insideaccordion" => false,
			);

			return $result;
		}

		protected function AlterField(&$formvars, &$field, $id)
		{
		}

		protected function ProcessField(&$formvars, &$field, $id)
		{
			if (is_string($field))
			{
				if ($field == "split" && !$formvars["insiderow"])  echo "<hr />";
				else if ($field == "endaccordion" || $field == "endaccordian")
				{
					if ($formvars["insiderow"])
					{
?>
			</tr></table></div>
<?php
						$formvars["insiderow"] = false;
					}
?>
				</div>
			</div>
<?php
					$formvars["insideaccordion"] = false;
				}
				else if ($field == "nosplit")
				{
					if ($formvars["insideaccordion"])  $firstaccordionitem = true;
				}
				else if ($field == "startrow")
				{
					if ($formvars["insiderow"])  echo "</tr><tr>";
					else if ($this->formtables)
					{
						$formvars["insiderow"] = true;
?>
			<div class="fieldtablewrap<?php if ($formvars["insideaccordion"] && $firstaccordionitem)  echo " firstitem"; ?>"><table class="rowwrap"><tr>
<?php
						$firstaccordionitem = false;
					}
				}
				else if ($field == "endrow" && $this->formtables)
				{
?>
			</tr></table></div>
<?php
					$formvars["insiderow"] = false;
				}
				else if (substr($field, 0, 5) == "html:")
				{
					echo substr($field, 5);
				}
			}
			else if ($field["type"] == "accordion" || $field["type"] == "accordian")
			{
				if ($formvars["insiderow"])
				{
?>
			</tr></table></div>
<?php
					$formvars["insiderow"] = false;
				}

				if ($formvars["insideaccordion"])
				{
?>
				</div>
				<h3><?php echo htmlspecialchars(BB_Translate($field["title"])); ?></h3>
				<div class="formaccordionitems">
<?php
				}
				else
				{
?>
			<div class="formaccordionwrap">
				<h3><?php echo htmlspecialchars(BB_Translate($field["title"])); ?></h3>
				<div class="formaccordionitems">
<?php
					$formvars["insideaccordion"] = true;
					$this->output["accordion"] = false;
				}

				$firstaccordionitem = true;
			}
			else
			{
				if ($formvars["insiderow"])  echo "<td>";
?>
			<div class="formitem<?php echo ((isset($field["split"]) && $field["split"] === false) || ($formvars["insideaccordion"] && $firstaccordionitem) ? " firstitem" : ""); ?>">
<?php
				$firstaccordionitem = false;
				if (isset($field["title"]))
				{
					if (is_string($field["title"]))
					{
?>
			<div class="formitemtitle"><?php echo htmlspecialchars(BB_Translate($field["title"])); ?></div>
<?php
					}
				}
				else if (isset($field["htmltitle"]))
				{
?>
			<div class="formitemtitle"><?php echo BB_Translate($field["htmltitle"]); ?></div>
<?php
				}
				else if ($field["type"] == "checkbox" && $formvars["insiderow"])
				{
?>
			<div class="formitemtitle">&nbsp;</div>
<?php
				}

				if (isset($field["width"]) && !$this->formwidths)  unset($field["width"]);

				if (isset($field["name"]) && isset($field["default"]))
				{
					if ($field["type"] == "select")
					{
						if (!isset($field["select"]))
						{
							$field["select"] = self::GetValue($field["name"], $field["default"]);
							if (is_array($field["select"]))  $field["select"] = self::GetSelectValues($field["select"]);
						}
					}
					else
					{
						if (!isset($field["value"]))  $field["value"] = self::GetValue($field["name"], $field["default"]);
					}
				}

				$this->AlterField($formvars, $field, $id);

				switch ($field["type"])
				{
					case "static":
					{
?>
			<div class="formitemdata">
				<div class="staticwrap"<?php if (isset($field["width"]))  echo " style=\"width: " . htmlspecialchars($field["width"]) . ";\""; ?>><?php echo htmlspecialchars($field["value"]); ?></div>
			</div>
<?php
						break;
					}
					case "text":
					{
						if ($formvars["autofocus"] === false)  $formvars["autofocus"] = htmlspecialchars($id);
?>
			<div class="formitemdata">
				<div class="textitemwrap"><input class="text"<?php if (isset($field["width"]))  echo " style=\"width: " . htmlspecialchars($field["width"]) . ";\""; ?> type="text" id="<?php echo htmlspecialchars($id); ?>" name="<?php echo htmlspecialchars($field["name"]); ?>" value="<?php echo htmlspecialchars($field["value"]); ?>" /></div>
			</div>
<?php
						break;
					}
					case "password":
					{
						if ($formvars["autofocus"] === false)  $formvars["autofocus"] = htmlspecialchars($id);
?>
			<div class="formitemdata">
				<div class="textitemwrap"><input class="text"<?php if (isset($field["width"]))  echo " style=\"width: " . htmlspecialchars($field["width"]) . ";\""; ?> type="password" id="<?php echo htmlspecialchars($id); ?>" name="<?php echo htmlspecialchars($field["name"]); ?>" value="<?php echo htmlspecialchars($field["value"]); ?>" /></div>
			</div>
<?php
						break;
					}
					case "checkbox":
					{
						if ($formvars["autofocus"] === false)  $formvars["autofocus"] = htmlspecialchars($id);
?>
			<div class="formitemdata">
				<div class="checkboxitemwrap">
					<input class="checkbox" type="checkbox" id="<?php echo htmlspecialchars($id); ?>" name="<?php echo htmlspecialchars($field["name"]); ?>" value="<?php echo htmlspecialchars($field["value"]); ?>"<?php if (isset($field["check"]) && $field["check"])  echo " checked"; ?> />
					<label for="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars(BB_Translate($field["display"])); ?></label>
				</div>
			</div>
<?php
						break;
					}
					case "select":
					{
						if ($formvars["autofocus"] === false)  $formvars["autofocus"] = htmlspecialchars($id);

						if (!isset($field["multiple"]) || $field["multiple"] !== true)  $mode = (isset($field["mode"]) && $field["mode"] == "radio" ? "radio" : "select");
						else if (!isset($field["mode"]) || ($field["mode"] != "flat" && $field["mode"] != "dropdown" && $field["mode"] != "tags" && $field["mode"] != "select"))  $mode = "checkbox";
						else  $mode = $field["mode"];

						if (!isset($field["width"]) && !isset($field["height"]))  $style = "";
						else
						{
							$style = array();
							if (isset($field["width"]))  $style[] = "width: " . htmlspecialchars($field["width"]);
							if (isset($field["height"]) && isset($field["multiple"]) && $field["multiple"] === true)
							{
								$style[] = "height: " . htmlspecialchars($field["height"]);
								$formvars["multiselectheight"] = (int)$field["height"];
							}
							$style = " style=\"" . implode("; ", $style) . ";\"";
						}

						if (!isset($field["select"]))  $field["select"] = array();
						else if (is_string($field["select"]))  $field["select"] = array($field["select"] => true);

?>
			<div class="formitemdata">
<?php

						$idbase = htmlspecialchars($id);
						if ($mode == "checkbox" || $mode == "radio")
						{
							$idnum = 0;
							foreach ($field["options"] as $name => $value)
							{
								if (is_array($value))
								{
									foreach ($value as $name2 => $value2)
									{
										$id2 = $idbase . ($idnum ? "_" . $idnum : "");
?>
				<div class="<?=$mode?>itemwrap">
					<input class="<?=$mode?>" type="<?=$mode?>" id="<?php echo $id2; ?>" name="<?php echo htmlspecialchars($field["name"]); ?><?php if ($mode == "checkbox")  echo "[]"; ?>" value="<?php echo htmlspecialchars($name2); ?>"<?php if (isset($field["select"][$name2]))  echo " checked"; ?> />
					<label for="<?php echo $id2; ?>"><?php echo htmlspecialchars(BB_Translate($name)); ?> - <?php echo ($value2 == "" ? "&nbsp;" : htmlspecialchars(BB_Translate($value2))); ?></label>
				</div>
<?php
										$idnum++;
									}
								}
								else
								{
									$id2 = $idbase . ($idnum ? "_" . $idnum : "");
?>
				<div class="<?=$mode?>itemwrap">
					<input class="<?=$mode?>" type="<?=$mode?>" id="<?php echo $id2; ?>" name="<?php echo htmlspecialchars($field["name"]); ?><?php if ($mode == "checkbox")  echo "[]"; ?>" value="<?php echo htmlspecialchars($name); ?>"<?php if (isset($field["select"][$name]))  echo " checked"; ?> />
					<label for="<?php echo $id2; ?>"><?php echo ($value == "" ? "&nbsp;" : htmlspecialchars(BB_Translate($value))); ?></label>
				</div>
<?php
									$idnum++;
								}
							}
						}
						else
						{
?>
				<div class="selectitemwrap">
					<select class="<?php echo (isset($field["multiple"]) && $field["multiple"] === true ? "multi" : "single"); ?>" id="<?php echo $idbase; ?>" name="<?php echo htmlspecialchars($field["name"]) . (isset($field["multiple"]) && $field["multiple"] === true ? "[]" : ""); ?>"<?php if (isset($field["multiple"]) && $field["multiple"] === true)  echo " multiple"; ?><?php echo $style; ?>>
<?php
							foreach ($field["options"] as $name => $value)
							{
								if (is_array($value))
								{
?>
						<optgroup label="<?php echo htmlspecialchars(BB_Translate($name)); ?>">
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
				</div>
<?php
							if (isset($field["multiple"]) && $field["multiple"] === true)
							{
								$this->OutputJQueryUI();

								if ($mode == "tags")
								{
									if (!isset($formvars["multiselectused"][$mode]))
									{
?>
	<link rel="stylesheet" href="<?php echo htmlspecialchars($this->basepath . "/multiselect-select2/select2.css"); ?>" type="text/css" media="all" />
	<script type="text/javascript" src="<?php echo htmlspecialchars($this->basepath . "/multiselect-select2/select2.min.js"); ?>"></script>
<?php
									}
?>
	<script type="text/javascript">
	jQuery(function() {
		if (jQuery.fn.select2)  jQuery('div.formfields div.formitem select.multi[name="<?php echo BB_JSSafe($field["name"] . "[]"); ?>"]').select2({ <?php if (isset($field["mininput"]))  echo "minimumInputLength: " . (int)$field["mininput"]; ?> });
		else  alert('<?php echo BB_JSSafe(BB_Translate("Warning:  Missing jQuery UI select2 for multiple selection field.\n\nThis feature requires AdminPack Extras.")); ?>');
	});
	</script>
<?php
								}
								else if ($mode == "dropdown")
								{
									if (!isset($formvars["multiselectused"][$mode]))
									{
?>
	<link rel="stylesheet" href="<?php echo htmlspecialchars($this->basepath . "/multiselect-widget/jquery.multiselect.css"); ?>" type="text/css" media="all" />
	<link rel="stylesheet" href="<?php echo htmlspecialchars($this->basepath . "/multiselect-widget/jquery.multiselect.filter.css"); ?>" type="text/css" media="all" />
	<script type="text/javascript" src="<?php echo htmlspecialchars($this->basepath . "/multiselect-widget/jquery.multiselect.min.js"); ?>"></script>
	<script type="text/javascript" src="<?php echo htmlspecialchars($this->basepath . "/multiselect-widget/jquery.multiselect.filter.js"); ?>"></script>
<?php
									}
?>
	<script type="text/javascript">
	jQuery(function() {
		if (jQuery.fn.multiselect && jQuery.fn.multiselectfilter)  jQuery('div.formfields div.formitem select.multi[name="<?php echo BB_JSSafe($field["name"] . "[]"); ?>"]').multiselect({ selectedText: '<?php echo BB_JSSafe(BB_Translate("# of # selected")); ?>', selectedList: 5, height: <?php echo $formvars["multiselectheight"]; ?>, position: { my: 'left top', at: 'left bottom', collision: 'flip' } }).multiselectfilter();
		else  alert('<?php echo BB_JSSafe(BB_Translate("Warning:  Missing jQuery UI multiselect widget or multiselectfilter for dropdown multiple selection field.\n\nThis feature requires AdminPack Extras.")); ?>');
	});
	</script>
<?php
								}
								else if ($mode == "flat")
								{
									if (!isset($formvars["multiselectused"][$mode]))
									{
?>
	<link rel="stylesheet" href="<?php echo htmlspecialchars($this->basepath . "/multiselect-flat/css/jquery.uix.multiselect.css"); ?>" type="text/css" media="all" />
	<script type="text/javascript" src="<?php echo htmlspecialchars($this->basepath . "/multiselect-flat/js/jquery.uix.multiselect.js"); ?>"></script>
<?php
									}
?>
	<script type="text/javascript">
	jQuery(function() {
		if (jQuery.fn.multiselect)
		{
			jQuery('div.formfields div.formitem select.multi[name="<?php echo BB_JSSafe($field["name"] . "[]"); ?>"]').multiselect({ availableListPosition: <?php echo ($this->formtables ? "'left'" : "'top'"); ?>, sortable: true, sortMethod: null });
			jQuery(window).resize(function() {
				jQuery('div.formfields div.formitem select.multi[name="<?php echo BB_JSSafe($field["name"] . "[]"); ?>"]').multiselect('refresh');
			});
		}
		else
		{
			alert('<?php echo BB_JSSafe(BB_Translate("Warning:  Missing jQuery UI multiselect plugin for flat multiple selection field.\n\nThis feature requires AdminPack Extras.")); ?>');
		}
	});
	</script>
	<div style="clear: both;"></div>
<?php
								}

								$formvars["multiselectused"][$mode] = true;
							}
						}

?>
			</div>
<?php

						break;
					}
					case "textarea":
					{
						if ($formvars["autofocus"] === false)  $formvars["autofocus"] = htmlspecialchars($id);
						if (!isset($field["width"]) && !isset($field["height"]))  $style = "";
						else
						{
							$style = array();
							if (isset($field["width"]))  $style[] = "width: " . htmlspecialchars($field["width"]);
							if (isset($field["height"]))  $style[] = "height: " . htmlspecialchars($field["height"]);
							$style = " style=\"" . implode("; ", $style) . ";\"";
						}
?>
			<div class="formitemdata">
				<div class="textareawrap"><textarea class="text"<?php echo $style; ?> id="<?php echo htmlspecialchars($id); ?>" name="<?php echo htmlspecialchars($field["name"]); ?>" rows="5" cols="50"><?php echo htmlspecialchars($field["value"]); ?></textarea></div>
			</div>
<?php
						break;
					}
					case "table":
					{
						$order = (isset($field["order"]) ? $field["order"] : "");
						$idbase = "f" . $num . "_" . (isset($field["name"]) ? $field["name"] : "table");

?>
			<div class="formitemdata">
<?php
						if ($this->formtables)
						{
?>
				<table id="<?php echo htmlspecialchars($idbase); ?>"<?php if (isset($field["class"]))  echo " class=\"" . htmlspecialchars($field["class"]) . "\""; ?><?php if (isset($field["width"]))  echo " style=\"width: " . htmlspecialchars($field["width"]) . "\""; ?>>
					<thead>
					<tr<?php if ($order != "")  echo " id=\"" . htmlspecialchars($idbase . "_head") . "\""; ?> class="head<?php if ($order != "")  echo " nodrag nodrop"; ?>">
<?php
							if ($order != "")
							{
?>
						<th><?php echo htmlspecialchars(BB_Translate($order)); ?></th>
<?php
							}

							foreach ($field["cols"] as $num2 => $col)
							{
?>
						<th><?php echo htmlspecialchars(BB_Translate($col)); ?></th>
<?php
							}
?>
					</tr>
					</thead>
					<tbody>
<?php
							$rownum = 0;
							$altrow = false;
							if (isset($field["callback"]) && function_exists($field["callback"]))  $field["rows"] = $field["callback"]();
							while (count($field["rows"]))
							{
								foreach ($field["rows"] as $row)
								{
?>
					<tr<?php if ($order != "")  echo " id=\"" . htmlspecialchars($idbase . "_" . $rownum) . "\""; ?> class="row<?php if ($altrow)  echo " altrow"; ?>">
<?php
									if ($order != "")
									{
?>
						<td class="draghandle">&nbsp;</td>
<?php
									}

									$num2 = 0;
									foreach ($row as $col)
									{
?>
						<td<?php if (count($row) < count($field["cols"]) && $num2 + 1 == count($row))  echo " colspan=\"" . (count($field["cols"]) - count($row) + 1) . "\""; ?>><?php echo $col; ?></td>
<?php
										$num2++;
									}
?>
					</tr>
<?php
									$rownum++;
									$altrow = !$altrow;
								}

								if (isset($field["callback"]) && function_exists($field["callback"]))  $field["rows"] = $field["callback"]();
								else  $field["rows"] = array();
							}
?>
					</tbody>
				</table>
<?php

							if ($order != "")
							{
								if (!$this->output["tableorder"])
								{
?>
				<script type="text/javascript" src="<?php echo htmlspecialchars($this->basepath . "/jquery.tablednd-20140418.min.js"); ?>"></script>
<?php
									$this->output["tableorder"] = true;
								}
?>
				<script type="text/javascript">
				if (jQuery.fn.tableDnD)
				{
					InitPropertiesTableDragAndDrop('<?php echo BB_JSSafe($idbase); ?>'<?php if (isset($field["reordercallback"]))  echo ", " . $field["reordercallback"]; ?>);
				}
				else
				{
					alert('<?php echo BB_JSSafe(BB_Translate("Warning:  Missing jQuery TableDnD plugin for drag-and-drop row ordering.")); ?>');
				}
				</script>
<?php
							}

							if (isset($field["stickyheader"]) && $field["stickyheader"])
							{
								if (!$this->output["tablestickyheader"])
								{
?>
				<script type="text/javascript" src="<?php echo htmlspecialchars($this->basepath . "/jquery.stickytableheaders.min.js"); ?>"></script>
<?php
									$this->output["tablestickyheader"] = true;
								}
?>
				<script type="text/javascript">
				if (jQuery.fn.stickyTableHeaders)
				{
					jQuery('#<?php echo BB_JSSafe($idbase); ?>').stickyTableHeaders();
				}
				else
				{
					alert('<?php echo BB_JSSafe(BB_Translate("Warning:  Missing jQuery Sticky Table Headers plugin.")); ?>');
				}
				</script>
<?php
							}
						}
						else
						{
?>
				<div class="nontablewrap" id="<?php echo htmlspecialchars("f" . $num . "_" . (isset($field["name"]) ? $field["name"] : "table")); ?>">
<?php
							$altrow = false;
							foreach ($field["rows"] as $num2 => $row)
							{
?>
					<div class="nontable_row<?php if ($altrow)  echo " altrow"; ?><?php if (!$num2)  echo " firstrow"; ?>">
<?php
								foreach ($row as $num3 => $col)
								{
?>
						<div class="nontable_th<?php if (!$num3)  echo " firstcol"; ?>"><?php echo htmlspecialchars(BB_Translate($field["cols"][$num3])); ?></div>
						<div class="nontable_td"><?php echo $col; ?></div>
<?php
								}
?>
					</div>
<?php
								$altrow = !$altrow;
							}
?>
				</div>
<?php
						}
?>
			</div>
<?php

						break;
					}
					case "file":
					{
						if ($formvars["autofocus"] === false)  $formvars["autofocus"] = htmlspecialchars($id);
?>
			<div class="formitemdata">
				<div class="textitemwrap"><input class="text" type="file" id="<?php echo htmlspecialchars($id); ?>" name="<?php echo htmlspecialchars($field["name"]); ?>" /></div>
			</div>
<?php
						break;
					}
					case "date":
					{
						if ($formvars["autofocus"] === false)  $formvars["autofocus"] = htmlspecialchars($id);
?>
			<div class="formitemdata">
				<div class="textitemwrap"><input class="date"<?php if (isset($field["width"]))  echo " style=\"width: " . htmlspecialchars($field["width"]) . "\""; ?> type="text" id="<?php echo htmlspecialchars($id); ?>" name="<?php echo htmlspecialchars($field["name"]); ?>" value="<?php echo htmlspecialchars($field["value"]); ?>" /></div>
			</div>
<?php
						$this->output["date"] = false;

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
			<div class="formitemdesc"><?php echo $field["htmldesc"]; ?></div>
<?php
				}

				if (isset($field["error"]) && $field["error"] != "")
				{
?>
			<div class="formitemresult">
				<div class="formitemerror"><?php echo htmlspecialchars(BB_Translate($field["error"])); ?></div>
			</div>
<?php
				}
?>
			</div>
<?php
				if ($formvars["insiderow"])  echo "</td>";
			}
		}

		protected function CleanupFields(&$formvars)
		{
			if ($formvars["insiderow"])
			{
?>
			</tr></table></div>
<?php
			}

			if ($formvars["insideaccordion"])
			{
?>
				</div>
			</div>
<?php
			}
		}

		protected function ProcessSubmit(&$formvars, &$options)
		{
			if (is_string($options["submit"]))  $options["submit"] = array($options["submit"]);
?>
		<div class="formsubmit">
<?php
			foreach ($options["submit"] as $val)
			{
?>
			<input class="submit" type="submit"<?php if (isset($options["submitname"]))  echo " name=\"" . htmlspecialchars($options["submitname"]) . "\""; ?> value="<?php echo htmlspecialchars(BB_Translate($val)); ?>" />
<?php
			}
?>
		</div>
<?php
		}

		protected function Finalize(&$formvars)
		{
			if (!$this->output["date"])
			{
				$this->OutputJQueryUI();

?>
	<script type="text/javascript">
	jQuery(function() {
		if (jQuery.fn.datepicker)  jQuery('div.formfields div.formitem input.date').datepicker({ dateFormat: 'yy-mm-dd' });
		else  alert('<?php echo BB_JSSafe(BB_Translate("Warning:  Missing jQuery UI for date field.")); ?>');
	});
	</script>
<?php

				$this->output["date"] = true;
			}

			if (!$this->output["accordion"])
			{
				$this->OutputJQueryUI();

?>
	<script type="text/javascript">
	jQuery(function() {
		if (jQuery.fn.accordion)  jQuery('div.formaccordionwrap').accordion({ collapsible : true, active : false, heightStyle : 'content' });
		else  alert('<?php echo BB_JSSafe(BB_Translate("Warning:  Missing jQuery UI for accordion.")); ?>');
	});
	</script>
<?php

				$this->output["accordion"] = true;
			}
		}

		public function Generate($options, $errors = array(), $lastform = true)
		{
			$formvars = $this->InitFormVars($options);

			$this->OutputFormCSS();

?>
	<div class="bb_formwrap">
	<div class="bb_formwrapinner">
<?php
			if (isset($options["submit"]) || (isset($options["useform"]) && $options["useform"]))
			{
				$this->numforms++;
?>
		<form id="form_<?php echo $this->numforms; ?>" method="post" enctype="multipart/form-data" action="<?php echo htmlspecialchars(BB_GetRequestURLBase()); ?>">
<?php

				$extra = array();
				if (isset($options["hidden"]))
				{
					foreach ($options["hidden"] as $name => $value)
					{
?>
		<input type="hidden" name="<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars($value); ?>" />
<?php
						if (isset($options["nonce"]) && $options["nonce"] != $name)  $extra[$name] = $value;
					}

					if ($options["nonce"])
					{
?>
		<input type="hidden" name="sec_extra" value="<?php echo htmlspecialchars(implode(",", array_keys($extra))); ?>" />
		<input type="hidden" name="sec_t" value="<?php echo htmlspecialchars($this->CreateSecurityToken($options["hidden"][$options["nonce"]], $extra)); ?>" />
<?php
					}
				}
				unset($extra);
			}

			if (isset($options["fields"]))
			{
?>
		<div class="formfields<?php if (count($options["fields"]) == 1 && !isset($options["fields"][0]["title"]) && !isset($options["fields"][0]["htmltitle"]))  echo " alt"; ?>">
<?php
				foreach ($options["fields"] as $num => $field)
				{
					$id = "f" . $this->numforms . "_" . $num;
					if (!is_string($field) && isset($field["name"]))
					{
						if (isset($errors[$field["name"]]))  $field["error"] = $errors[$field["name"]];

						if (isset($options["randomnames"]) && $options["randomnames"])
						{
							$field["origname"] = $field["name"];
							$field["name"] = $this->GetRandomizedFieldName($field["name"]);
						}

						$id .= "_" . $field["name"];

						if (isset($options["randomnames"]) && $options["randomnames"] && isset($options["focus"]) && is_string($options["focus"]) && $options["focus"] == $field["origname"])  $options["focus"] = $id;
					}

					$this->ProcessField($formvars, $field, $id);
				}

				$this->CleanupFields($formvars);
?>
		</div>
<?php
			}

			if (isset($options["submit"]))  $this->ProcessSubmit($formvars, $options);

			if (isset($options["submit"]) || (isset($options["useform"]) && $options["useform"]))
			{
?>
		</form>
<?php
			}
?>
	</div>
	</div>
<?php
			if ($lastform)  $this->Finalize($formvars);

			if (isset($options["focus"]) && (is_string($options["focus"]) || ($options["focus"] === true && $formvars["autofocus"] !== false)))
			{
?>
	<script type="text/javascript">
	jQuery('#<?php echo BB_JSSafe(is_string($options["focus"]) ? $options["focus"] : $formvars["autofocus"]); ?>').focus();
	</script>
<?php
			}
		}
	}
?>