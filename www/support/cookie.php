<?php
	// CubicleSoft PHP HTTP cookie functions.
	// (C) 2012 CubicleSoft.  All Rights Reserved.

	function SetCookieFixDomain($name, $value = "", $expires = 0, $path = "", $domain = "", $secure = false, $httponly = false)
	{
		if (!empty($domain))
		{
			// Remove port information.
			$pos = strpos($domain, "]");
			if (substr($domain, 0, 1) == "[" && $pos !== false)  $domain = substr($domain, 0, $pos + 1);
			else
			{
				$port = strpos($domain, ":");
				if ($port !== false)  $domain = substr($domain, 0, $port);

				// Fix the domain to accept domains with and without 'www.'.
				if (strtolower(substr($domain, 0, 4)) == "www.")  $domain = substr($domain, 4);
				if (strpos($domain, ".") === false)  $domain = "";
				else if (substr($domain, 0, 1) != ".")  $domain = "." . $domain;
			}
		}

		header('Set-Cookie: ' . rawurlencode($name) . "=" . rawurlencode($value)
							. (empty($expires) ? "" : "; expires=" . gmdate("D, d-M-Y H:i:s", $expires) . " GMT")
							. (empty($path) ? "" : "; path=" . $path)
							. (empty($domain) ? "" : "; domain=" . $domain)
							. (!$secure ? "" : "; secure")
							. (!$httponly ? "" : "; HttpOnly"), false);
	}
?>