// CubicleSoft Barebones CMS conversion functions.
// (C) 2011 CubicleSoft.  All Rights Reserved.

function ConvertIntCharToHex(num)
{
	return "0123456789ABCDEF".charAt((num - num % 16) / 16) + "0123456789ABCDEF".charAt(num % 16);
}

function ConvertRGBToHex(red, green, blue)
{
	if (red < 0)  red = 0;
	if (red > 255)  red = 255;
	if (green < 0)  green = 0;
	if (green > 255)  green = 255;
	if (blue < 0)  blue = 0;
	if (blue > 255)  blue = 255;

	return ConvertIntCharToHex(red) + ConvertIntCharToHex(green) + ConvertIntCharToHex(blue);
}

// Photoshop-like.  Hue from 0 to 360.  Saturation from 0 to 100.  Brightness from 0 to 100.
function ConvertHSBToRGB(hue, saturation, brightness)
{
	var x, red, green, blue, num1, num2, num3;

	// Threshold inputs.
	while (hue < 0)  hue += 360;
	while (hue > 360)  hue -= 360;
	hue = hue * 6 / 360;
	if (hue >= 6)  hue = 0;
	if (saturation < 0.0)  saturation = 0.0;
	if (saturation > 100.0)  saturation = 100.0;
	if (brightness < 0.0)  brightness = 0.0;
	if (brightness > 100.0)  brightness = 100.0;

	saturation /= 100;
	brightness /= 100;

	if (saturation < 0.00000001)  red = green = blue = brightness * 255;
	else
	{
		x = Math.floor(hue);
		num1 = brightness * (1 - saturation);
		num2 = brightness * (1 - (saturation * (hue - x)));
		num3 = brightness * (1 - (saturation * (1 - (hue - x))));

		switch (x)
		{
			case 0:  red = brightness;  green = num3;  blue = num1;  break;
			case 1:  red = num2;  green = brightness;  blue = num1;  break;
			case 2:  red = num1;  green = brightness;  blue = num3;  break;
			case 3:  red = num1;  green = num2;  blue = brightness;  break;
			case 4:  red = num3;  green = num1;  blue = brightness;  break;
			case 5:  red = brightness;  green = num1;  blue = num2;  break;
		}

		red *= 255;
		green *= 255;
		blue *= 255;
	}

	return [Math.round(red), Math.round(green), Math.round(blue)];
}

function ConvertIntToFriendlyNum(num)
{
	var x, start, result = '';

	num = '' + Math.floor(num);
	start = num.length % 3;
	if (start)  result = num.substring(0, start);
	for (x = start; x < num.length; x += 3)  result += (x ? ',' : '') + num.substring(x, x + 3);

	return result;
}

function ConvertBytesToFriendlyLimit(num)
{
	var append, pos, dec, frac, places = 0;

	if (num < 0)  num = 0;

	if (num < 1024)  append = " bytes";
	else
	{
		num /= 1024;
		if (num < 1024)  append = " KB";
		else
		{
			num /= 1024;
			if (num < 1024)  append = " MB";
			else
			{
				num /= 1024;
				append = " GB";
			}
		}

		places = (num < 10 ? 2 : 1);
	}

	if (places)
	{
		dec = Math.floor(num);
		frac = '' + (num - dec).toFixed(places);
		frac = frac.substring(1);
		if (frac == '')  frac = '.';
		while (frac.length < places + 1)  frac += '0';
	}
	else
	{
		dec = Math.floor(num);
		frac = '';
	}

	return ConvertIntToFriendlyNum(dec) + frac + append;
}

function ConvertSecondsElapsedToFriendlyLimit(num, longform)
{
	var seconds, minutes, hours, days, result;

	seconds = Math.floor(num);
	if (seconds < 0)  seconds = 0;
	minutes = Math.floor(seconds / 60);
	seconds %= 60;
	hours = Math.floor(minutes / 60);
	minutes %= 60;
	days = Math.floor(hours / 24);
	hours %= 24;

	result = '';
	if (longform)
	{
		if (days > 0)  result += days + ' day' + (days != 1 ? 's' : '');
		if (hours > 0 || result != '')  result += (result != '' ? ', ' : '') + hours + ' hour' + (hours != 1 ? 's' : '');
		if (minutes > 0 || result != '')  result += (result != '' ? ', ' : '') + minutes + ' minute' + (minutes != 1 ? 's' : '');
		if (seconds > 0 || result != '')  result += (result != '' ? ', ' : '') + seconds + ' second' + (seconds != 1 ? 's' : '');
	}
	else
	{
		if (days > 0)  result += days + ' d';
		if (hours > 0)  result += (result != '' ? ' ' : '') + hours + ' h';
		if (minutes > 0)  result += (result != '' ? ' ' : '') + minutes + ' m';
		if (seconds > 0)  result += (result != '' ? ' ' : '') + seconds + ' s';
		if (result == '')  result = '0 s';
	}

	return result;
}
