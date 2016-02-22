<?php
	// Barebones CMS
	// (C) 2014 CubicleSoft.  All Rights Reserved.

	// Load the basic functionality required to generate a sitemap.
	require_once "config.php";
	require_once ROOT_PATH . "/" . SUPPORT_PATH . "/str_basics.php";
	require_once ROOT_PATH . "/" . SUPPORT_PATH . "/utf8.php";
	require_once ROOT_PATH . "/" . SUPPORT_PATH . "/bb_functions.php";

	Str::ProcessAllInput();

	$langs = array();
	$dirlist = BB_GetDirectoryList(".");
	foreach ($dirlist["files"] as $name)
	{
		if (substr($name, 0, 8) == "sitemap_" && substr($name, -4) == ".php")  $langs[substr($name, 8, -4)] = true;
	}

	header("Content-Type: text/xml; charset=UTF-8");
	echo '<' . '?xml version="1.0" encoding="UTF-8"?' . ">\n";

	if (isset($_REQUEST["lang"]) && isset($langs[$_REQUEST["lang"]]))
	{
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php
		$url = BB_GetFullRequestURLBase();
		$pos = strrpos($url, "/");
		$url = substr($url, 0, $pos + 1);

		require_once "sitemap_" . $_REQUEST["lang"] . ".php";
		$bb_sitemap = array_slice($bb_sitemap, -50000);
		foreach ($bb_sitemap as $page => $opts)
		{
			switch ($opts[1])
			{
				case "high":  $priority = "0.9";  break;
				case "low":  $priority = "0.2";  break;
				default:  $priority = "0.5";  break;
			}

?>
	<url>
		<loc><?php echo htmlspecialchars($url . $page); ?></loc>
		<changefreq><?php echo htmlspecialchars($opts[0]); ?></changefreq>
		<priority><?php echo htmlspecialchars($priority); ?></priority>
	</url>
<?php
		}
?>
</urlset>
<?php
	}
	else
	{
?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php
		foreach ($langs as $lang => $val)
		{
?>
	<sitemap>
		<loc><?php echo htmlspecialchars(BB_GetFullRequestURLBase() . "?lang=" . $lang); ?></loc>
	</sitemap>
<?php
		}
?>
</sitemapindex>
<?php
	}
?>