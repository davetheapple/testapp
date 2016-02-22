<?php
	// Barebones CMS
	// (C) 2015 CubicleSoft.  All Rights Reserved.

	require_once "config.php";
	require_once ROOT_PATH . "/" . SUPPORT_PATH . "/str_basics.php";
	require_once ROOT_PATH . "/" . SUPPORT_PATH . "/utf8.php";
	require_once ROOT_PATH . "/" . SUPPORT_PATH . "/bb_functions.php";

	Str::ProcessAllInput();

	// Don't proceed any further if this is an acciental re-upload of this file to the root path.
	if (defined("STO_LOGIN") && STO_LOGIN && ROOT_PATH == str_replace("\\", "/", dirname(__FILE__)))  exit();

	if (USE_HTTPS && !BB_IsSSLRequest())
	{
		header("Location: " . BB_GetFullRequestURLBase("https"));
		exit();
	}

	// Allow developers to inject code here.  For example, IP address restriction logic or a SSO bypass.
	if (file_exists("login_hook.php"))  require_once "login_hook.php";
	else if (defined("STO_LOGIN") && STO_LOGIN && file_exists(ROOT_PATH . "/login_hook.php"))  require_once ROOT_PATH . "/login_hook.php";

	if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "login")
	{
		require_once ROOT_PATH . "/accounts.php";

		$user = trim($_REQUEST["login_user"]);
		$pass = trim($_REQUEST["login_pass"]);
		if (!isset($bb_accounts["users"][$user]) || $bb_accounts["users"][$user]["pass"] != sha1($user . ":" . $pass))
		{
			echo "<span class=\"error\">Incorrect username or password.</span>";
			exit();
		}

		BB_DeleteExpiredUserSessions();

		if (isset($_REQUEST["login_reset"]) && $_REQUEST["login_reset"] == "yes")  BB_SetUserPassword($user, $pass);

		require_once ROOT_PATH . "/" . SUPPORT_PATH . "/cookie.php";

		$id = BB_NewUserSession($user, (isset($_REQUEST["bbl"]) ? $_REQUEST["bbl"] : ""));
		if ($id === false)  $id = BB_NewUserSession($user, "");
		if ($id === false)
		{
			echo "<span class=\"error\">Unable to create session.</span>";
			exit();
		}

		SetCookieFixDomain("bbl", $id, $bb_accounts["sessions"][$id]["expire"], ROOT_URL . "/", "", USE_HTTPS, true);
		SetCookieFixDomain("bbq", "1", $bb_accounts["sessions"][$id]["expire"], ROOT_URL . "/", "");
?>
<span class="success">Successfully logged in.</span><br />
<a href="<?php echo htmlspecialchars(BB_GetFullRootURLBase("http")); ?>/">Click here to continue</a>
<script type="text/javascript">
window.location = '<?php echo BB_JSSafe(BB_GetFullRootURLBase("http")); ?>/';
</script>
<?php
	}
	else
	{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Login</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="stylesheet" href="<?php echo htmlspecialchars(ROOT_URL); ?>/<?php echo htmlspecialchars(SUPPORT_PATH); ?>/css/install.css" type="text/css" />
<script type="text/javascript" src="<?php echo htmlspecialchars(ROOT_URL); ?>/<?php echo htmlspecialchars(SUPPORT_PATH); ?>/jquery-1.11.0<?php echo (defined("DEBUG_JS") ? "" : ".min"); ?>.js"></script>

<script type="text/javascript">
function Login()
{
	$('#loginwrap').fadeIn('slow');
	$('#login').load('login.php', $('#loginform').serialize() + '&rnd_' + Math.floor(Math.random() * 1000000));

	return false;
}
</script>

</head>
<body>
<noscript><span class="error">Er...  You need Javascript enabled to login.  You also need Javascript enabled to use the various tools.</span></noscript>
<form id="loginform" method="post" enctype="multipart/form-data" action="login.php" accept-charset="utf-8" onsubmit="return Login();">
<input type="hidden" name="action" value="login" />
<div id="main">
	<div class="box">
		<h1>Login</h1>
		<h3>Login to the system.</h3>
		<div class="boxmain">
Enter your login credentials below to login to the system.<br /><br />

<?php
		if (!file_exists("login_hook.php") && (ROOT_PATH == str_replace("\\", "/", dirname(__FILE__)) || file_exists(ROOT_PATH . "/login.php")))
		{
?>
<div class="testresult" style="display: block;">
	<span class="error">Improve the security of your system by doing the following:</span><br />
	<ol>
		<li>Create a file called 'login_hook.php' and implement your own login solution.  AND/OR:</li>
		<li>Create a subdirectory that is not easily guessed nor is a word in a dictionary.</li>
		<li>Copy the configuration file for this system into the new subdirectory.</li>
		<li>Move this file into the new subdirectory (delete the original file after moving it).</li>
		<li>Go to the new URL and bookmark it for easy access in the future.</li>
	</ol>
</div>
<br />
<?php
		}
?>

<div id="loginwrap" class="testresult">
	<div id="login"></div>
</div>
<br />

<div class="formfields">
	<div class="formitem">
		<div class="formitemtitle">Username</div>
		<input class="text" type="text" name="login_user" />
		<div class="formitemdesc">Your username.</div>
	</div>
	<div class="formitem">
		<div class="formitemtitle">Password</div>
		<input class="text" type="password" name="login_pass" />
		<div class="formitemdesc">Your password.</div>
	</div>
	<div class="formitem">
		<div class="formitemtitle">Reset Session</div>
		<input class="checkbox" type="checkbox" id="login_reset" name="login_reset" value="yes" /> <label for="login_reset">Reset the session</label>
		<div class="formitemdesc">Logs out all other computers using the account.</div>
	</div>
	<div class="formitem">
		<input class="submit" type="submit" value="Submit" />
	</div>
</div>
		</div>
	</div>

</div>
</form>
</body>
</html>
<?php
	}
?>