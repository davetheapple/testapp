<?php
	// Barebones CMS
	// (C) 2014 CubicleSoft.  All Rights Reserved.

	if (file_exists("config.php"))  exit();

	// Define ROOT_PATH and SUPPORT_PATH.  Required by the SMTP library.
	define("ROOT_PATH", str_replace("\\", "/", dirname(__FILE__)));
	define("SUPPORT_PATH", "support");

	require_once "support/debug.php";
	require_once "support/str_basics.php";
	require_once "support/bb_functions.php";
	require_once "support/utf8.php";
	require_once "support/http.php";
	require_once "support/random.php";

	SetDebugLevel();
	Str::ProcessAllInput();

	// Allow developers to inject code here.  For example, IP address restriction logic or a SSO bypass.
	if (file_exists("install_hook.php"))  require_once "install_hook.php";

	if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "checklist")
	{
?>
	<table align="center">
		<tr class="head"><th>Test</th><th>Passed?</th></tr>
		<tr class="row">
			<td>PHP 5.4.x or later</td>
			<td align="right">
<?php
		if ((double)phpversion() < 5.4)  echo "<span class=\"error\">No</span><br /><br />The server is running PHP " . phpversion() . ".  The installation may succeed but the rest of the Barebones CMS will be broken.  You will be unable to use Barebones CMS.  Running outdated versions of PHP poses a serious website security risk.  Please contact your system administrator to upgrade your PHP installation.";
		else  echo "<span class=\"success\">Yes</span>";
?>
			</td>
		</tr>
		<tr class="row altrow">
			<td>PHP 'safe_mode' off</td>
			<td align="right">
<?php
		if (ini_get('safe_mode'))  echo "<span class=\"error\">No</span><br /><br />PHP is running with 'safe_mode' enabled.  You will probably get additional failures below relating to file/directory creation.  This setting is generally accepted as a poor security solution that doesn't work and is deprecated.  Please turn it off.  If you are getting errors below, can't change this setting, and the fixes below aren't working, you may need to contact your hosting service provider.";
		else  echo "<span class=\"success\">Yes</span>";
?>
			</td>
		</tr>
		<tr class="row">
			<td>Able to create files in ./</td>
			<td align="right">
<?php
		if (file_put_contents("test.dat", "a") === false)  echo "<span class=\"error\">No</span><br /><br />chmod 777 on the directory may fix the problem.";
		else if (!unlink("test.dat"))  echo "<span class=\"error\">No</span><br /><br />Unable to delete test file.  chmod 777 on the directory may fix the problem.";
		else  echo "<span class=\"success\">Yes</span>";
?>
			</td>
		</tr>
		<tr class="row altrow">
			<td>Able to create files in widgets/</td>
			<td align="right">
<?php
		if (file_put_contents("widgets/test.dat", "a") === false)  echo "<span class=\"error\">No</span><br /><br />chmod 777 on the directory may fix the problem.";
		else if (!unlink("widgets/test.dat"))  echo "<span class=\"error\">No</span><br /><br />Unable to delete test file.  chmod 777 on the directory may fix the problem.";
		else  echo "<span class=\"success\">Yes</span>";
?>
			</td>
		</tr>
		<tr class="row">
			<td>Directory widgets/bb_layout/layouts/ exists</td>
			<td align="right">
<?php
		if (!is_dir("widgets/bb_layout/layouts/"))  echo "<span class=\"error\">No</span><br /><br />This is an empty directory that should have been extracted from the ZIP file that is used by the Layout widget.  Always use a tool like 7-Zip to extract files.";
		else  echo "<span class=\"success\">Yes</span>";
?>
			</td>
		</tr>
		<tr class="row altrow">
			<td>Able to create directories in ./</td>
			<td align="right">
<?php
		if (!mkdir("test"))  echo "<span class=\"error\">No</span><br /><br />chmod 777 on the directory may fix the problem.";
		else if (!rmdir("test"))  echo "<span class=\"error\">No</span><br /><br />Unable to delete test directory.  chmod 777 on the parent directory may fix the problem.";
		else  echo "<span class=\"success\">Yes</span>";
?>
			</td>
		</tr>
		<tr class="row">
			<td>Able to create directories in widgets/</td>
			<td align="right">
<?php
		if (!mkdir("widgets/test"))  echo "<span class=\"error\">No</span><br /><br />chmod 777 on the directory may fix the problem.";
		else if (!rmdir("widgets/test"))  echo "<span class=\"error\">No</span><br /><br />Unable to delete test directory.  chmod 777 on the parent directory may fix the problem.";
		else  echo "<span class=\"success\">Yes</span>";
?>
			</td>
		</tr>
		<tr class="row altrow">
			<td>No './index.html'</td>
			<td align="right">
<?php
		if (file_exists("index.html"))  echo "<span class=\"error\">No</span><br /><br />Depending on server settings, 'index.html' may interfere with the proper operation of Barebones.";
		else  echo "<span class=\"success\">Yes</span>";
?>
			</td>
		</tr>
		<tr class="row">
			<td>No './index.php'</td>
			<td align="right">
<?php
		if (file_exists("index.php"))  echo "<span class=\"error\">No</span><br /><br />'index.php' will get overwritten upon install.";
		else  echo "<span class=\"success\">Yes</span>";
?>
			</td>
		</tr>
		<tr class="row altrow">
			<td>No './accounts.php'</td>
			<td align="right">
<?php
		if (file_exists("accounts.php"))  echo "<span class=\"error\">No</span><br /><br />'accounts.php' will get overwritten upon install.";
		else  echo "<span class=\"success\">Yes</span>";
?>
			</td>
		</tr>
		<tr class="row">
			<td>No './translate.php'</td>
			<td align="right">
<?php
		if (file_exists("translate.php"))  echo "<span class=\"error\">No</span><br /><br />'translate.php' will get overwritten upon install.";
		else  echo "<span class=\"success\">Yes</span>";
?>
			</td>
		</tr>
		<tr class="row altrow">
			<td>No './lastupdated.php'</td>
			<td align="right">
<?php
		if (file_exists("lastupdated.php"))  echo "<span class=\"error\">No</span><br /><br />'lastupdated.php' will get overwritten upon install.";
		else  echo "<span class=\"success\">Yes</span>";
?>
			</td>
		</tr>
		<tr class="row">
			<td>Login system './login.php' exists</td>
			<td align="right">
<?php
		if (!file_exists("login.php"))  echo "<span class=\"error\">No</span><br /><br />'login.php' does not exist on the server.  Installation will likely fail.";
		else  echo "<span class=\"success\">Yes</span>";
?>
			</td>
		</tr>
		<tr class="row altrow">
			<td>$_SERVER["REQUEST_URI"] supported</td>
			<td align="right">
<?php
		if (!isset($_SERVER["REQUEST_URI"]))  echo "<span class=\"error\">No</span><br /><br />Server does not support this feature.  The installation may fail and the site might not work.";
		else  echo "<span class=\"success\">Yes</span>";
?>
			</td>
		</tr>
		<tr class="row">
			<td>PHP running as a module</td>
			<td align="right">
<?php
		if (substr(php_sapi_name(), 0, 3) == "cgi" && ini_get("cgi.rfc2616_headers") == "1")  echo "<span class=\"error\">No</span><br /><br />PHP is running as a CGI.  You will need to switch to the module version of PHP, modify main.php to eliminate caching, or use a different header() call.";
		else  echo "<span class=\"success\">Yes</span>";
?>
			</td>
		</tr>
		<tr class="row altrow">
			<td>Browser-level multilingual support</td>
			<td align="right">
<?php
		if (!isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]))  echo "<span class=\"error\">No</span><br /><br />Your browser is not configured for multilingual support.  Site will default to UTF-8/English.";
		else  echo "<span class=\"success\">Yes</span>";
?>
			</td>
		</tr>
		<tr class="row">
			<td>PHP 'register_globals' off</td>
			<td align="right">
<?php
		if (ini_get('register_globals'))  echo "<span class=\"error\">No</span><br /><br />PHP is running with 'register_globals' enabled.  This setting is generally accepted as a major security risk and is deprecated.  Please turn it off by editing the php.ini file for your site - you may need to contact your hosting provider to accomplish this task.";
		else  echo "<span class=\"success\">Yes</span>";
?>
			</td>
		</tr>
		<tr class="row altrow">
			<td>PHP 'magic_quotes_gpc' off</td>
			<td align="right">
<?php
		if (get_magic_quotes_gpc())  echo "<span class=\"error\">No</span><br /><br />PHP is running with 'magic_quotes_gpc' enabled.  This setting is generally accepted as a security risk AND causes all sorts of non-security-related problems.  It is also deprecated.  Please turn it off by editing the php.ini file for your site - you may need to contact your hosting provider to accomplish this task.";
		else  echo "<span class=\"success\">Yes</span>";
?>
			</td>
		</tr>
		<tr class="row">
			<td>PHP 'magic_quotes_sybase' off</td>
			<td align="right">
<?php
		if (ini_get('magic_quotes_sybase'))  echo "<span class=\"error\">No</span><br /><br />PHP is running with 'magic_quotes_sybase' enabled.  This setting is generally accepted as a security risk AND causes all sorts of non-security-related problems.  It is also deprecated.  Please turn it off by editing the php.ini file for your site - you may need to contact your hosting provider to accomplish this task.";
		else  echo "<span class=\"success\">Yes</span>";
?>
			</td>
		</tr>
		<tr class="row altrow">
			<td>Installation over SSL</td>
			<td align="right">
<?php
		if (!BB_IsSSLRequest())  echo "<span class=\"error\">No</span><br /><br />While Barebones CMS will install and run without using HTTPS/SSL, think about the implications of network sniffing access tokens and the fact that Barebones developer accounts can edit PHP code right inside the web browser.  Proceed only if this security risk is acceptable.";
		else  echo "<span class=\"success\">Yes</span>";
?>
			</td>
		</tr>
		<tr class="head">
			<th>Supported PHP functions</th>
			<th>&nbsp;</th>
		</tr>
<?php
		$functions = array(
			"fsockopen" => "Web automation/validation functions.  Your site will be significantly less secure and you will not receive upgrade notifications",
			"zip_open" => "ZIP file extraction functions.  You will have to install extensions manually",
		);

		$x = 0;
		foreach ($functions as $function => $info)
		{
			echo "<tr class=\"row" . ($x % 2 ? " altrow" : "") . "\"><td>" . htmlspecialchars($function) . "</td><td align=\"right\">" . (function_exists($function) ? "<span class=\"success\">Yes</span>" : "<span class=\"error\">No</span><br /><br />You will be unable to use " . $info . ".") . "</td></tr>\n";
			$x++;
		}
?>
		<tr class="head">
			<th>Supported PHP classes</th>
			<th>&nbsp;</th>
		</tr>
<?php
		$classes = array(
			"ZipArchive" => "ZIP file creation for site backups"
		);

		$x = 0;
		foreach ($classes as $class => $info)
		{
			echo "<tr class=\"row" . ($x % 2 ? " altrow" : "") . "\"><td>" . htmlspecialchars($class) . "</td><td align=\"right\">" . (class_exists($class) ? "<span class=\"success\">Yes</span>" : "<span class=\"error\">No</span><br /><br />You will be unable to use " . $info . ".") . "</td></tr>\n";
			$x++;
		}
?>
	</table>
<?php
	}
	else if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "install")
	{
		function InstallError($message)
		{
			echo "<span class=\"error\">" . $message . "  Click 'Prev' below to go back and correct the problem.</span>";
			echo "<script type=\"text/javascript\">InstallFailed();</script>";

			exit();
		}

		function InstallWarning($message)
		{
			echo "<span class=\"warning\">" . $message . "</span><br />";
		}

		function InstallSuccess($message)
		{
			echo "<span class=\"success\">" . $message . "</span><br />";
		}

		// Set up page-level calculation variables.
		$url = dirname(BB_GetRequestURLBase());
		if (substr($url, -1) == "/")  $url = substr($url, 0, -1);
		define("ROOT_URL", $url);
		define("WIDGET_PATH", "widgets");
		define("PLUGIN_PATH", "plugins");
		define("LANG_PATH", "lang");
		define("DEFAULT_LANG", $_REQUEST["default_lang"]);
		define("DEFAULT_PAGE_LANG", $_REQUEST["default_page_lang"]);

		if ($_REQUEST["write_perms"] == "g")  $bb_writeperms = 0220;
		else if ($_REQUEST["write_perms"] == "w")  $bb_writeperms = 0222;
		else  $bb_writeperms = 0200;

		try
		{
			$rng = new CSPRNG(true);
		}
		catch (Exception $e)
		{
			InstallError("Unable to initialize CSPRNG.  Insufficient entropy available to this host.");
		}

		$baserand = $rng->GenerateToken();
		if ($baserand === false)  InstallError("Unable to generate token with CSPRNG.");
		define("BASE_RAND_SEED", $baserand);

		$baserand = $rng->GenerateToken();
		if ($baserand === false)  InstallError("Unable to generate token with CSPRNG.");
		define("BASE_RAND_SEED2", $baserand);

		define("USE_LESS_SAFE_STORAGE", $_REQUEST["use_less_safe_storage"] == "yes");

		// Generate the last widget update file (used for refreshing cached files after a widget is changed).
		if (!BB_WidgetStatusUpdate())  InstallError("Unable to install the last update tracker.");
		InstallSuccess("Successfully set up the last update tracker.");

		// Generate the translation notification file.
		$bb_translate_notify = array();
		if (!BB_SaveTranslationNotifications())  InstallError("Unable to install the translation notification file.");
		InstallSuccess("Successfully set up the translation notification file.");

		// Generate the accounts.
		$bb_accounts = array("users" => array(), "sessions" => array());
		if (trim($_REQUEST["dev_user"]) == "" || trim($_REQUEST["dev_pass"]) == "")  InstallError("The developer username and password fields must be filled in.");
		if (!BB_CreateUser("dev", $_REQUEST["dev_user"], $_REQUEST["dev_pass"]))  InstallError("Unable to create the developer account.");
		InstallSuccess("Successfully set up the developer login account.");

		if (trim($_REQUEST["design_user"]) != "" && trim($_REQUEST["design_pass"]) != "")
		{
			if (!BB_CreateUser("design", $_REQUEST["design_user"], $_REQUEST["design_pass"]))  InstallError("Unable to create the web designer account.  Possible cause:  Duplicate usernames.  Usernames must be unique.");
			InstallSuccess("Successfully set up the web designer login account.");
		}
		if (trim($_REQUEST["content_group"]) != "" && trim($_REQUEST["content_user"]) != "" && trim($_REQUEST["content_pass"]) != "")
		{
			if (!BB_CreateUser("content", $_REQUEST["content_user"], $_REQUEST["content_pass"], $_REQUEST["content_group"]))  InstallError("Unable to create the content author account.  Possible cause:  Duplicate usernames.  Usernames must be unique.");
			InstallSuccess("Successfully set up the content author login account.");
		}

		InstallSuccess("Successfully set up the basic login account(s).");

		// Set up the root page.
		if (!BB_CreatePage("", "index"))  InstallError("Unable to create the root page.");
		InstallSuccess("Successfully created the root page.");

		// Set up the main configuration file.
		function GetTimeoutInSeconds($timeout)
		{
			$timeout = explode(":", trim($timeout));
			if (count($timeout) > 3)  $timeout = array_slice($timeout, -3);
			switch (count($timeout))
			{
				case 1:  $timeout = (int)$timeout[0] * 60;  break;
				case 2:  $timeout = (int)$timeout[0] * 60 * 60 + (int)$timeout[1] * 60;  break;
				case 3:  $timeout = (int)$timeout[0] * 24 * 60 * 60 + (int)$timeout[1] * 60 * 60 + (int)$timeout[2] * 60;  break;
				default:  $timeout = 15 * 24 * 60 * 60;  break;
			}

			return $timeout;
		}

		// Move the login system.
		if ($_REQUEST["sto_login"] > 0)
		{
			$srcfile = ROOT_PATH . "/login.php";
			$logindir = "login_" . $rng->GenerateToken();
			$destfile = ROOT_PATH . "/" . $logindir;
			if (!@mkdir($destfile, 0755))  InstallError("Unable to create endpoint directory.");
			$destfile .= "/login.php";
			if (!@rename($srcfile, $destfile))  InstallError("Unable to move 'login.php' to login directory.");
			$loginurl = dirname(BB_GetFullRequestURLBase());
			if (substr($loginurl, -1) != "/")  $loginurl .= "/";
			$loginurl .= $logindir . "/login.php";
			InstallSuccess("Successfully created a randomly named directory and moved 'login.php' into it.");
		}
		else
		{
			$loginurl = dirname(BB_GetFullRequestURLBase());
			if (substr($loginurl, -1) != "/")  $loginurl .= "/";
			$loginurl .= "login.php";
		}

		$data = "<" . "?php\n";
		$data .= "\tdefine(\"HTTP_SERVER\", \"\");\n";
		$data .= "\tdefine(\"HTTPS_SERVER\", \"\");\n";
		$data .= "\tdefine(\"USE_HTTPS\", " . var_export(BB_IsSSLRequest(), true) . ");\n";
		$data .= "\tdefine(\"ROOT_PATH\", " . var_export(ROOT_PATH, true) . ");\n";
		$data .= "\tdefine(\"ROOT_URL\", " . var_export(ROOT_URL, true) . ");\n";
		$data .= "\tdefine(\"SUPPORT_PATH\", " . var_export(SUPPORT_PATH, true) . ");\n";
		$data .= "\tdefine(\"WIDGET_PATH\", " . var_export(WIDGET_PATH, true) . ");\n";
		$data .= "\tdefine(\"PLUGIN_PATH\", " . var_export(PLUGIN_PATH, true) . ");\n";
		$data .= "\tdefine(\"LANG_PATH\", " . var_export(LANG_PATH, true) . ");\n";
		$data .= "\tdefine(\"DEFAULT_LANG\", " . var_export(DEFAULT_LANG, true) . ");\n";
		$data .= "\tdefine(\"DEFAULT_PAGE_LANG\", " . var_export(DEFAULT_PAGE_LANG, true) . ");\n";
		$data .= "\tdefine(\"BASE_RAND_SEED\", " . var_export(BASE_RAND_SEED, true) . ");\n";
		$data .= "\tdefine(\"BASE_RAND_SEED2\", " . var_export(BASE_RAND_SEED2, true) . ");\n";
		$data .= "\tdefine(\"DEV_SESSION_TIMEOUT\", " . GetTimeoutInSeconds($_REQUEST["dev_timeout"]) . ");\n";
		$data .= "\tdefine(\"DESIGN_SESSION_TIMEOUT\", " . GetTimeoutInSeconds($_REQUEST["design_timeout"]) . ");\n";
		$data .= "\tdefine(\"CONTENT_SESSION_TIMEOUT\", " . GetTimeoutInSeconds($_REQUEST["content_timeout"]) . ");\n";
		$data .= "\tdefine(\"STO_LOGIN\", " . var_export($_REQUEST["sto_login"] > 0, true) . ");\n";
		$data .= "\tdefine(\"WRITE_PERMS\", " . var_export($_REQUEST["write_perms"], true) . ");\n";
		$data .= "\tdefine(\"USE_LESS_SAFE_STORAGE\", " . var_export($_REQUEST["use_less_safe_storage"] == "yes", true) . ");\n";
		$data .= "?" . ">";
		if (BB_WriteFile("config.php", $data) === false)  InstallError("Unable to create the configuration file.");
		if ($_REQUEST["sto_login"] > 0 && BB_WriteFile(ROOT_PATH . "/" . $logindir . "/config.php", $data) === false)  InstallError("Unable to create the configuration file clone in the admin subdirectory.");
		InstallSuccess("Successfully created the configuration file.");

		InstallSuccess("The installation completed successfully.");

?>
		<br />
		Next:  <a href="<?php echo htmlspecialchars($loginurl); ?>">Start using Barebones</a><br />
		(Don't forget to bookmark the login screen.)<br />
<?php
	}
	else
	{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Barebones Installer</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="stylesheet" href="support/css/install.css" type="text/css" />
<script type="text/javascript" src="support/jquery-1.11.0.min.js"></script>

<script type="text/javascript">
function Page(curr, next)
{
	$('#page' + curr).hide();
	$('#page' + next).fadeIn('normal');

	return false;
}
</script>

</head>
<body>
<noscript><span class="error">Er...  You need Javascript enabled to install Barebones CMS.  You also need Javascript enabled to use the various tools in Barebones CMS.  End-users won't need Javascript enabled unless your site requires it, but you will need Javascript enabled to use this product.</span></noscript>
<form id="installform" method="post" enctype="multipart/form-data" action="install.php" accept-charset="utf-8">
<input type="hidden" name="action" value="install" />
<div id="main">
	<div id="page1" class="box">
		<h1>Barebones Installer</h1>
		<h3>Welcome to the Barebones CMS installer.</h3>
		<div class="boxmain">
			If you are a computer geek/nerd/website designer and find all those massive CMS, blogging, whatever systems
			(Joomla, Drupal, WordPress, etc.) to be incredibly heavyweight and unnecessarily restrictive but also hate
			designing sites completely from the ground up every single time OR perhaps you hand code all of your sites
			but then hate locating, opening, and editing a file to make one little content change when you can see the
			content on the screen you need to edit, then this is most likely what you are looking for:<br /><br />

			<div class="indent">
				A completely blank slate with editing tools focused on quickly building a site (or subsite) from scratch but
				allow content editors to easily make changes to the content without screwing up your beautiful design.
			</div>
			<br />

			If that sounds like you, Barebones CMS is the answer.  Just click "Next" below to get started.
		</div>

		<div class="boxbuttons">
			<a href="#" onclick="return Page(1, 2);">Next &raquo;</a>
		</div>
	</div>

	<div id="page2" class="box" style="display: none;">
		<h1>Barebones Requirements</h1>
		<h3>The Barebones system requirements.</h3>
		<div class="boxmain">
			In order to use Barebones, you will need to meet these logistical requirements:<br />
			<ul>
				<li>Someone who knows PHP (a PHP programmer)</li>
				<li>Someone who knows design (a web designer)</li>
				<li>Someone who will edit content (a content author)</li>
			</ul>

			You will also need to meet these technical requirements (most of these are auto-detected by this installation wizard):<br />
			<ul>
				<li><a href="http://www.php.net/" target="_blank">PHP 5.4.x or later</a> (preferably the latest)</li>
			</ul>

			Your website and web browser must also be able to run the following when editing without conflicts:<br />
			<ul>
				<li><a href="http://jquery.com/" target="_blank">jQuery 1.11.0</a> (Please avoid other Javascript libraries)</li>
				<li><a href="https://github.com/jeresig/jquery.hotkeys" target="_blank">jQuery Hotkeys (jeresig - April 18, 2014 - slightly modified to correctly handle ESC key)</a> (Keyboard shortcuts)</li>
				<li><a href="https://github.com/isocra/TableDnD" target="_blank">jQuery TableDnD (latest GitHub repo - April 18, 2014)</a> (Table-based drag-and-drop)</li>
				<li><a href="http://jqueryui.com/" target="_blank">jQuery UI 1.10.4</a></li>
				<li><a href="http://www.wymeditor.org/" target="_blank">WYMEditor (latest GitHub repo - January 10, 2015)</a> (What You Mean editor)</li>
				<li><a href="http://ace.c9.io/" target="_blank">ACE/Ajax.org Cloud9 Editor (latest GitHub repo - March 1, 2015)</a> (Code editor - newer browsers)</li>
				<li><a href="https://github.com/blueimp/jQuery-File-Upload" target="_blank">jQuery File Upload 9.9.1</a> (A modern HTML 5 web browser is required to upload files through the admin)</li>
			</ul>

			The following server-side, third-party components are also used:<br />
			<ul>
				<li><a href="http://htmlpurifier.org/" target="_blank">HTML Purifier 4.6.0 Standalone</a> (Clean up and remove XSS attempts from XHTML/HTML)</li>
				<li><a href="http://csstidy.sourceforge.net/" target="_blank">CSSTidy Pre-1.4 (SVN Revision 127, heavily modified)</a> (Clean up and remove XSS attempts from CSS)</li>
				<li><a href="http://simplehtmldom.sourceforge.net/" target="_blank">SimpleHTML DOM 1.11</a> (Parse XHTML/HTML documents into DOM)</li>
			</ul>
		</div>

		<div class="boxbuttons">
			<a href="#" onclick="return Page(2, 1);">&laquo; Prev</a> | <a href="#" onclick="return Page(2, 3);">Next &raquo;</a>
		</div>
	</div>

	<div id="page3" class="box" style="display: none;">
		<h1>Barebones CMS Checklist</h1>
		<h3>The Barebones compatability checklist.</h3>
		<div class="boxmain">
			Before beginning the installation, you should check to make sure that the server meets or exceeds
			the basic technical requirements.  Below is the checklist for compatability with Barebones CMS.<br /><br />

			<div id="checklist"></div>
			<br />

			<script type="text/javascript">
			function RefreshChecklist()
			{
				$('#checklist').load('install.php', { 'action' : 'checklist' });

				return false;
			}

			RefreshChecklist();
			</script>

			<a href="#" onclick="return RefreshChecklist();">Refresh the checklist</a><br /><br />

			NOTE:  You are allowed to install Barebones even if you don't meet the requirements above.  Just don't complain if your
			installation or this installer does not work.  Each web server is different - there is no way to satisfy all servers
			without a ton of code.  Besides, you may be able to get away with some missing things for some websites.
		</div>

		<div class="boxbuttons">
			<a href="#" onclick="return Page(3, 2);">&laquo; Prev</a> | <a href="#" onclick="return Page(3, 4);">Next &raquo;</a>
		</div>
	</div>

	<div id="page4" class="box" style="display: none;">
		<h1>Account Setup:  Developer/Programmer Account</h1>
		<h3>Set up the Developer/Programmer account.  (Required)</h3>
		<div class="boxmain">
			Set up the basic login account to access the entire Barebones toolset.  This account can do anything on the
			system including the ability to create large, gaping security holes via bad programming.  You can add more
			accounts of this type after installing Barebones.<br /><br />

			<div class="formfields">
				<div class="formitem">
					<div class="formitemtitle">Username</div>
					<input class="text" type="text" name="dev_user" />
					<div class="formitemdesc">The developer's username.</div>
				</div>
				<div class="formitem">
					<div class="formitemtitle">Password</div>
					<input class="text" type="password" name="dev_pass" />
					<div class="formitemdesc">The developer's password.</div>
				</div>
				<div class="formitem">
					<div class="formitemtitle">Session Timeout</div>
					<input class="text" type="text" name="dev_timeout" value="15:00:00" />
					<div class="formitemdesc">The length of time a developer account can stay logged in ([days:][hours:]minutes).</div>
				</div>
			</div>
			<br />

			Generate a random password:  <a href="https://www.grc.com/passwords.htm" targer="_blank">GRC</a> | <a href="http://keepass.info/" target="_blank">KeePass</a><br /><br />

			Barebones assumes that the people with accounts are working together and do not have malicious intent.
			Still, there is a revision system in place to protect against possible loss of data.
			If you can't trust the people with accounts to be responsible with them, do not use Barebones.
		</div>

		<div class="boxbuttons">
			<a href="#" onclick="return Page(4, 3);">&laquo; Prev</a> | <a href="#" onclick="return Page(4, 5);">Next &raquo;</a>
		</div>
	</div>

	<div id="page5" class="box" style="display: none;">
		<h1>Account Setup:  Web Designer Account</h1>
		<h3>Set up a Web Designer account.  (optional)</h3>
		<div class="boxmain">
			Set up a login account to access the Barebones web design tools and edit content.
			You can add more accounts of this type after installing Barebones.<br /><br />

			<div class="formfields">
				<div class="formitem">
					<div class="formitemtitle">Username</div>
					<input class="text" type="text" name="design_user" />
					<div class="formitemdesc">The web designer's username.</div>
				</div>
				<div class="formitem">
					<div class="formitemtitle">Password</div>
					<input class="text" type="password" name="design_pass" />
					<div class="formitemdesc">The web designer's password.</div>
				</div>
				<div class="formitem">
					<div class="formitemtitle">Session Timeout</div>
					<input class="text" type="text" name="design_timeout" value="15:00:00" />
					<div class="formitemdesc">The length of time a web designer account can stay logged in ([days:][hours:]minutes).</div>
				</div>
			</div>
			<br />

			Generate a random password:  <a href="https://www.grc.com/passwords.htm" targer="_blank">GRC</a> | <a href="http://keepass.info/" target="_blank">KeePass</a><br /><br />
		</div>

		<div class="boxbuttons">
			<a href="#" onclick="return Page(5, 4);">&laquo; Prev</a> | <a href="#" onclick="return Page(5, 6);">Next &raquo;</a>
		</div>
	</div>

	<div id="page6" class="box" style="display: none;">
		<h1>Account Setup:  Content Author Account</h1>
		<h3>Set up a Content Author account.  (optional)</h3>
		<div class="boxmain">
			Set up a login account to allow someone to only edit content.
			You can add more accounts of this type after installing Barebones.<br /><br />

			<div class="formfields">
				<div class="formitem">
					<div class="formitemtitle">Group</div>
					<input class="text" type="text" name="content_group" />
					<div class="formitemdesc">The content author's group.  You can set up additional groups later.</div>
				</div>
				<div class="formitem">
					<div class="formitemtitle">Username</div>
					<input class="text" type="text" name="content_user" />
					<div class="formitemdesc">The content author's username.</div>
				</div>
				<div class="formitem">
					<div class="formitemtitle">Password</div>
					<input class="text" type="password" name="content_pass" />
					<div class="formitemdesc">The content author's password.</div>
				</div>
				<div class="formitem">
					<div class="formitemtitle">Session Timeout</div>
					<input class="text" type="text" name="content_timeout" value="15:00:00" />
					<div class="formitemdesc">The length of time a content author account can stay logged in ([days:][hours:]minutes).</div>
				</div>
			</div>
			<br />

			Generate a random password:  <a href="https://www.grc.com/passwords.htm" targer="_blank">GRC</a> | <a href="http://keepass.info/" target="_blank">KeePass</a><br /><br />
		</div>

		<div class="boxbuttons">
			<a href="#" onclick="return Page(6, 5);">&laquo; Prev</a> | <a href="#" onclick="return Page(6, 7);">Next &raquo;</a>
		</div>
	</div>

	<div id="page7" class="box" style="display: none;">
		<h1>Miscellaneous Settings</h1>
		<h3>Adjust various miscellaneous settings.  (optional)</h3>
		<div class="boxmain">
			These miscellaneous settings will affect the global installation.
			They may be changed later but won't necessarily apply to existing files.<br /><br />

			<div class="formfields">
				<div class="formitem">
					<div class="formitemtitle">Login System Security Through Obscurity (STO)</div>
					<select id="sto_login" name="sto_login">
						<option value="1">Yes</option>
						<option value="0">No</option>
					</select>
					<div class="formitemdesc">Move the login screen into a randomly named subdirectory.  Difficult to guess but known to users.</div>
				</div>
				<div class="formitem">
					<div class="formitemtitle">Admin Default Language</div>
					<input class="text" id="default_lang" type="text" name="default_lang" value="" />
					<div class="formitemdesc">The IANA language code of an installed language pack to use as the default language with fallback to English.</div>
				</div>
				<div class="formitem">
					<div class="formitemtitle">Default New Page Language</div>
					<input class="text" id="default_page_lang" type="text" name="default_page_lang" value="" />
					<div class="formitemdesc">The IANA language code to use as the default language when creating a new page.  Leave empty to use the web browser's default language.</div>
				</div>
				<div class="formitem">
					<div class="formitemtitle">Use Less Safe Storage</div>
					<select name="use_less_safe_storage">
						<option value="no">No</option>
						<option value="yes">Yes</option>
					</select>
					<div class="formitemdesc">Enabling this gains code readability, a performance boost, and smaller file sizes but with a small reduction in website security.</div>
				</div>
				<div class="formitem">
					<div class="formitemtitle">Write Permissions For Directories/Files</div>
					<select name="write_perms">
						<option value="o">Owner</option>
						<option value="g">Group</option>
						<option value="w">World</option>
					</select>
					<div class="formitemdesc">Defines how directory/file write permissions will be set on non-Windows systems.  Owner is the least-permissive and World is the most-permissive (and may have unintended consequences).</div>
				</div>
			</div>
		</div>

		<div class="boxbuttons">
			<a href="#" onclick="return Page(7, 6);">&laquo; Prev</a> | <a href="#" onclick="return Page(7, 8);">Next &raquo;</a>
		</div>
	</div>

	<div id="page8" class="box" style="display: none;">
		<h1>Ready To Install</h1>
		<h3>Ready to install Barebones.</h3>
		<div class="boxmain">
			Barebones is ready to install.  Click the link below to complete the installation process.
			Upon successful completion, 'install.php' (this installer) will be disabled.
			NOTE:  Be patient during the installation process.  It takes 5 to 30 seconds to complete.<br /><br />

			<div id="installwrap" class="testresult">
				<div id="install"></div>
			</div>
			<br />

			<script type="text/javascript">
			function Install()
			{
				$('#installlink').hide();
				$('.boxbuttons').hide();
				$('#installwrap').fadeIn('slow');
				$('#install').load('install.php', $('#installform').serialize() + '&rnd_' + Math.floor(Math.random() * 1000000));

				return false;
			}

			function InstallFailed()
			{
				$('#installlink').fadeIn('slow');
				$('.boxbuttons').fadeIn('slow');
			}
			</script>

			<a id="installlink" href="#" onclick="return Install();">Install Barebones</a><br /><br />
		</div>

		<div class="boxbuttons">
			<a href="#" onclick="return Page(8, 7);">&laquo; Prev</a>
		</div>
	</div>

</div>
</form>
</body>
</html>
<?php
	}
?>