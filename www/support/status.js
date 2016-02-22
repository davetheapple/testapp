// CubicleSoft Barebones CMS status update functions.
// (C) 2011 CubicleSoft.  All Rights Reserved.

var Gx__StatusUpdaters = {};

function CreateStatusUpdater(id, opts)
{
	var x, TempHTML;

	TempHTML = '<div id="' + id + '_statuswrap" class="statuswrap">';
	if (typeof(opts.progressbars) == 'object' && opts.progressbars.length > 0)
	{
		for (x = 0; x < opts.progressbars.length; x++)  TempHTML += '<div id="' + id + '_statusprogressbar' + x + '" class="statusprogressbar"></div><div id="' + id + '_statusprogressinfo' + x + '" class="statusprogressinfo"></div>';
	}
	if (typeof(opts.useinfo) == 'boolean' && opts.useinfo)  TempHTML += '<div id="' + id + '_statusinfowrap" class="statusinfowrap"></div>';
	if (typeof(opts.usemessages) == 'boolean' && opts.usemessages)  TempHTML += '<a href="#" id="' + id + '_statusshowhidemessages" class="statusshowhidemessages" onclick="$(\'#' + id + '_statusmessages\').toggle(\'slow\');  this.blur();  return false;"></a><div id="' + id + '_statusmessages" class="statusmessages"></div>';
	TempHTML += '</div>';

	$('#' + id).html(TempHTML);

	if (typeof(opts.progressbars) == 'object' && opts.progressbars.length > 0)
	{
		for (x = 0; x < opts.progressbars.length; x++)  CreateProgressBar(id + '_statusprogressbar' + x);
	}

	Gx__StatusUpdaters[id] = {
		opts : opts,
		messages : []
	};

	return true;
}

function DestroyStatusUpdater(id)
{
	if (typeof(Gx__StatusUpdaters[id]) == 'object')
	{
		delete Gx__StatusUpdaters[id];
		$('#' + id).html('');
	}
}

function ShowStatusUpdaterProgressBar(id, num, show, resetbar)
{
	if (num < 0 || typeof(Gx__StatusUpdaters[id]) != 'object' || typeof(Gx__StatusUpdaters[id].opts.progressbars) != 'object' || num >= Gx__StatusUpdaters[id].opts.progressbars.length)  return false;

	if (resetbar)  ResetProgressBar(id + '_statusprogressbar' + num);

	if (show)
	{
		$('#' + id + '_statusprogressbar' + num).show();
		$('#' + id + '_statusprogressinfo' + num).html('');
		$('#' + id + '_statusprogressinfo' + num).show();
	}
	else
	{
		$('#' + id + '_statusprogressbar' + num).fadeOut('normal');
		$('#' + id + '_statusprogressinfo' + num).fadeOut('normal');
	}

	return true;
}

function SetStatusUpdaterProgressBar(id, num, percent, data)
{
	var hsb, rgb, color;

	if (num < 0 || typeof(Gx__StatusUpdaters[id]) != 'object' || typeof(Gx__StatusUpdaters[id].opts.progressbars) != 'object' || num >= Gx__StatusUpdaters[id].opts.progressbars.length)  return false;

	if (typeof(Gx__StatusUpdaters[id].opts.progressbars[num].color) == 'object')  rgb = Gx__StatusUpdaters[id].opts.color;
	else if (typeof(Gx__StatusUpdaters[id].opts.progressbars[num].first) == 'object')
	{
		hsb = ConvertProgressBarPercentToColor(percent, Gx__StatusUpdaters[id].opts.progressbars[num]);
		rgb = ConvertHSBToRGB(hsb[0], hsb[1], hsb[2]);
	}
	else  return false;

	color = '#' + ConvertRGBToHex(rgb[0], rgb[1], rgb[2]);

	UpdateProgressBar(id + '_statusprogressbar' + num, percent, color);
	$('#' + id + '_statusprogressinfo' + num).html(data);

	return true;
}

function SetStatusUpdaterInfo(id, data)
{
	if (typeof(Gx__StatusUpdaters[id]) != 'object' || typeof(Gx__StatusUpdaters[id].opts.useinfo) != 'boolean' || !Gx__StatusUpdaters[id].opts.useinfo)  return false;

	$('#' + id + '_statusinfowrap').html(data);

	return true;
}

function AddStatusUpdaterMessage(id, message, msgtype, msgtypeplural)
{
	var x, x2, TempHTML, types = [];

	if (typeof(Gx__StatusUpdaters[id]) != 'object' || typeof(Gx__StatusUpdaters[id].opts.usemessages) != 'boolean' || !Gx__StatusUpdaters[id].opts.usemessages)  return false;

	Gx__StatusUpdaters[id].messages.push([message, msgtype, msgtypeplural]);
	TempHTML = '';
	for (x = 0; x < Gx__StatusUpdaters[id].messages.length; x++)
	{
		TempHTML += Gx__StatusUpdaters[id].messages[x][1] + ':  ' + Gx__StatusUpdaters[id].messages[x][0] + '<br />';

		for (x2 = 0; x2 < types.length && types[x2][0] != Gx__StatusUpdaters[id].messages[x][1]; x2++);
		if (x2 == types.length)  types.push([Gx__StatusUpdaters[id].messages[x][1], Gx__StatusUpdaters[id].messages[x][2], 0]);
		types[x2][2]++;
	}
	$('#' + id + '_statusmessages').html(TempHTML);

	TempHTML = '';
	for (x = 0; x < types.length; x++)
	{
		if (x)  TempHTML += ", ";
		TempHTML += types[x][2] + ' ' + (types[x][2] == 1 ? types[x][0] : types[x][1]);
	}
	$('#' + id + '_statusshowhidemessages').html(TempHTML);

	return true;
}


if (typeof(window.LoadConditionalScript) == 'function')
{
	LoadCSS(Gx__RootURL + '/' + Gx__SupportPath + '/css/status.css?_=20090725', 'all');

	LoadConditionalScript(Gx__RootURL + '/' + Gx__SupportPath + '/convert.js?_=20090725', true, function(loaded) {
		return ((!loaded && typeof(window.ConvertRGBToHex) == 'function') || (loaded && !IsConditionalScriptLoading()));
	}, function() {});

	LoadConditionalScript(Gx__RootURL + '/' + Gx__SupportPath + '/progress.js?_=20090725', true, function(loaded) {
		return ((!loaded && typeof(window.CreateProgressBar) == 'function') || (loaded && !IsConditionalScriptLoading()));
	}, function() {});
}
else
{
	document.write('<link rel="stylesheet" href="' + Gx__RootURL + '/' + Gx__SupportPath + '/css/status.css" type="text/css" media="all" />');
	document.write('<script type="text/javascript" src="' + Gx__RootURL + '/' + Gx__SupportPath + '/convert.js"></script>');
	document.write('<script type="text/javascript" src="' + Gx__RootURL + '/' + Gx__SupportPath + '/progress.js"></script>');
}
