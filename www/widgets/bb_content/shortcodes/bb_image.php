<?php
	// Barebones CMS Content Widget Shortcode Handler for Images
	// (C) 2011 CubicleSoft.  All Rights Reserved.
	// Icons are licensed from the IconShock library.

	if (!defined("BB_FILE"))  exit();

	$g_bb_content_shortcodes["bb_image"] = array(
		"name" => "Image",
		"toolbaricon" => $g_fullurl . "/bb_image_small.png",
		"mainicon" => $g_fullurl . "/bb_image_large.png",
		"cache" => true,
		"security" => array(
			"" => array("Images", "Defines who can add and edit images."),
			"local" => array("Local Images", "Defines who can preview images on the local server."),
			"remote" => array("Remote Images", "Defines who can preview images on remote servers.")
		)
	);

	class bb_content_shortcode_bb_image extends BB_ContentShortcodeBase
	{
		private function GetInfo($sid)
		{
			global $bb_widget;

			$info = $bb_widget->shortcodes[$sid];
			if (!isset($info["src"]))  $info["src"] = "";
			if (!isset($info["alt"]))  $info["alt"] = "";
			if (!isset($info["opt-caption"]))  $info["opt-caption"] = 0;
			if (!isset($info["opt-caption-width"]))  $info["opt-caption-width"] = 0;

			return $info;
		}

		public function GenerateShortcode($parent, $sid, $depth)
		{
			global $g_bb_content_shortcodes;

			$info = $this->GetInfo($sid);
			if ($info["src"] == "")  return "";

			if ($parent !== false && !$parent->IsShortcodeAllowed("bb_image", BB_IsLocalURL($info["src"]) ? "local" : "remote"))  $info["src"] = $g_bb_content_shortcodes["bb_image"]["mainicon"];

			$data = "";
			if ($info["opt-caption"])  $data .= '<div class="image-caption-wrap"' . ($info["opt-caption-width"] ? ' style="width: ' . $info["opt-caption-width"] . 'px;"' : '') . '><div class="image-caption-image">';
			$data .= '<img src="' . htmlspecialchars($info["src"]) . '"' . ($info["alt"] != "" ? ' alt="' . htmlspecialchars($info["alt"]) . '"' : '') . ' />';
			if ($info["opt-caption"])  $data .= '</div><div class="image-caption-text">' . htmlspecialchars($info["alt"]) . '</div></div>';

			return $data;
		}

		public function ProcessShortcodeBBAction($parent)
		{
			global $bb_dir, $bb_pref_lang, $bb_revision_num, $bb_writeperms;

			$info = $this->GetInfo($parent->GetSID());

			if ($_REQUEST["sc_action"] == "bb_image_upload_ajaxupload")
			{
				BB_RunPluginAction("pre_bb_content_shortcode_bb_image_upload_ajaxupload");

				$msg = BB_ValidateAJAXUpload();
				if ($msg != "")
				{
					echo htmlspecialchars(BB_Translate($msg));
					exit();
				}

				// Use official magic numbers for each format to determine the real content type.
				$data = file_get_contents($_FILES["Filedata"]["tmp_name"]);
				$type = BB_GetImageType($data);
				if ($type != "gif" && $type != "jpg" && $type != "png")
				{
					echo htmlspecialchars(BB_Translate("Uploaded file is not a valid web image.  Must be PNG, JPG, or GIF."));
					exit();
				}

				if (!is_dir($bb_dir . "/images"))  mkdir($bb_dir . "/images", 0777, true);
				$dirfile = preg_replace('/\.+/', ".", preg_replace('/[^A-Za-z0-9_.\-]/', "_", $bb_pref_lang . "_" . ($bb_revision_num > -1 ? $bb_revision_num . "_" : "") . trim($_FILES["Filedata"]["name"])));
				if ($dirfile == ".")  $dirfile = "";

				if ($dirfile == "")
				{
					echo htmlspecialchars(BB_Translate("A filename was not specified."));
					exit();
				}

				$pos = strrpos($dirfile, ".");
				if ($pos === false || substr($dirfile, $pos + 1) != $type)  $dirfile .= "." . $type;
				if (!@move_uploaded_file($_FILES["Filedata"]["tmp_name"], $bb_dir . "/images/" . $dirfile))
				{
					echo htmlspecialchars(BB_Translate("Unable to move temporary file to final location.  Check the permissions of the target directory and destination file."));
					exit();
				}

				@chmod($bb_dir . "/images/" . $dirfile, 0444 | $bb_writeperms);

				$info["src"] = "images/" . $dirfile;
				if (!$parent->SaveShortcode($info))
				{
					echo htmlspecialchars(BB_Translate("Unable to save the shortcode."));
					exit();
				}

				echo "OK";

				BB_RunPluginAction("post_bb_content_shortcode_bb_image_upload_ajaxupload");
			}
			else if ($_REQUEST["sc_action"] == "bb_image_upload_submit")
			{
				BB_RunPluginAction("pre_bb_content_shortcode_bb_image_upload_submit");

				$imginfo = BB_IsValidHTMLImage($_REQUEST["url"], array("protocol" => "http"));
				if (!$imginfo["success"])  BB_PropertyFormError($imginfo["error"]);

				$dirfile = preg_replace('/\.+/', ".", preg_replace('/[^A-Za-z0-9_.\-]/', "_", $_REQUEST["destfile"]));
				if ($dirfile == ".")  $dirfile = "";

				// Automatically calculate the new filename based on the URL.
				if ($dirfile == "")  $dirfile = $bb_pref_lang . "_" . ($bb_revision_num > -1 ? $bb_revision_num . "_" : "") . BB_MakeFilenameFromURL($imginfo["url"], $imginfo["type"]);

				if (!is_dir($bb_dir . "/images"))  mkdir($bb_dir . "/images", 0777, true);
				if (BB_WriteFile($bb_dir . "/images/" . $dirfile, $imginfo["data"]) === false)  BB_PropertyFormError("Unable to save the image.");

				$info["src"] = "images/" . $dirfile;
				if (!$parent->SaveShortcode($info))  BB_PropertyFormError("Unable to save the shortcode.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Image transferred.")); ?></div>
<script type="text/javascript">
LoadProperties(<?php echo $parent->CreateShortcodePropertiesJS(""); ?>);
ReloadIFrame();
</script>
<?php

				BB_RunPluginAction("post_bb_content_shortcode_bb_image_upload_submit");
			}
			else if ($_REQUEST["sc_action"] == "bb_image_upload")
			{
				$parent->CreateShortcodeUploader("", array(), "Configure Image", "Image", "image", "*.png;*.jpg;*.gif", "Web Image Files");
			}
			else if ($_REQUEST["sc_action"] == "bb_image_configure_submit")
			{
				BB_RunPluginAction("pre_bb_content_shortcode_bb_image_configure_submit");

				$src = trim($_REQUEST["src"]);
				if ($info["src"] != $src)
				{
					if ($src != "")
					{
						$imginfo = BB_IsValidHTMLImage($src, array("protocol" => "http"));
						if (!$imginfo["success"] && function_exists("fsockopen"))  BB_PropertyFormError("'Image URL' field does not point to a valid image file.");
					}
					$info["src"] = $src;
				}
				$info["alt"] = $_REQUEST["alt"];
				$info["opt-caption"] = ($_REQUEST["opt-caption"] == "enable");
				$info["opt-caption-width"] = (int)$_REQUEST["opt-caption-width"];
				if ($info["opt-caption-width"] < 0)  $info["opt-caption-width"] = 0;
				if (!$parent->SaveShortcode($info))  BB_PropertyFormError("Unable to save the shortcode.");

?>
<div class="success"><?php echo htmlspecialchars(BB_Translate("Options saved.")); ?></div>
<script type="text/javascript">
CloseProperties();
ReloadIFrame();
</script>
<?php

				BB_RunPluginAction("post_bb_content_shortcode_bb_image_configure_submit");
			}
			else if ($_REQUEST["sc_action"] == "bb_image_configure")
			{
				BB_RunPluginAction("pre_bb_content_shortcode_bb_image_configure");

				$desc = "<br />";
				$desc .= $parent->CreateShortcodePropertiesLink(BB_Translate("Upload/Transfer Image"), "bb_image_upload");

				$options = array(
					"title" => "Configure Image",
					"desc" => "Configure the image or upload/transfer a new image.",
					"htmldesc" => $desc,
					"bb_action" => $_REQUEST["bb_action"],
					"hidden" => array(
						"sid" => $parent->GetSID(),
						"sc_action" => "bb_image_configure_submit"
					),
					"fields" => array(
						array(
							"title" => "Image URL",
							"type" => "text",
							"name" => "src",
							"value" => $info["src"],
							"desc" => "The URL of this image."
						),
						array(
							"title" => "Alternate Text",
							"type" => "text",
							"name" => "alt",
							"value" => $info["alt"],
							"desc" => "The alternate text to display if images are not able to be seen (e.g. visually impaired visitors)."
						),
						array(
							"title" => "Display Caption",
							"type" => "select",
							"name" => "opt-caption",
							"options" => array(
								"enable" => "Enable",
								"disable" => "Disable"
							),
							"select" => ($info["opt-caption"] ? "enable" : "disable"),
							"desc" => "Display the alternate text as a caption below the image."
						),
						array(
							"title" => "Caption Width",
							"type" => "text",
							"name" => "opt-caption-width",
							"value" => $info["opt-caption-width"],
							"desc" => "The width in pixels to constrain the caption to.  Typically the width of the image."
						)
					),
					"submit" => "Save",
					"focus" => true
				);

				BB_RunPluginActionInfo("bb_content_shortcode_bb_image_configure_options", $options);

				BB_PropertyForm($options);

				BB_RunPluginAction("post_bb_content_shortcode_bb_image_configure");
			}
		}
	}
?>