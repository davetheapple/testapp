// CubicleSoft Barebones CMS progress bar functions.
// (C) 2011 CubicleSoft.  All Rights Reserved.

function CreateProgressBar(id)
{
	$('#' + id).html('<div id="' + id + '_outerwrap" class="progressbarouterwrap"><div id="' + id + '_innerwrap" class="progressbarinnerwrap"><div id="' + id + '_main" class="progressbarmain" style="width: 0; background-color: #D60300;"></div></div></div>');
}

function UpdateProgressBar(id, percent, color)
{
	percent = Math.floor(percent);
	if (percent < 0)  percent = 0;
	if (percent > 100)  percent = 100;

	$('#' + id + '_main').css({width : percent + '%', backgroundColor : color});
}

function ResetProgressBar(id)
{
	$('#' + id + '_main').css({width : '0', backgroundColor : '#D60300'});
}

// Takes 'percent equals color' points (HSB format) and converts them to a gradient-based color.
function ConvertProgressBarPercentToColor(percent, colorinfo)
{
	var x, lastnum, lastrange, currrange, scaledpercent, result;

	percent = Math.floor(percent);
	if (percent < 0)  percent = 0;
	if (percent > 100)  percent = 100;

	lastnum = 0;
	lastrange = colorinfo.first;
	for (var num in colorinfo.ranges)
	{
		if (lastnum <= percent && percent < num)
		{
			scaledpercent = (percent - lastnum) / (num - lastnum);
			currrange = colorinfo.ranges[num];
			result = [];
			for (x = 0; x < 3; x++)
			{
				if (currrange[x] < lastrange[x])  result[x] = ((lastrange[x] - currrange[x]) * (1.0 - scaledpercent)) + currrange[x];
				else  result[x] = ((currrange[x] - lastrange[x]) * scaledpercent) + currrange[x];
			}

			return result;
		}

		lastnum = num;
		lastrange = colorinfo.ranges[num];
	}

	return colorinfo.last;
}

if (typeof(window.LoadConditionalScript) == 'function')
{
	LoadCSS(Gx__RootURL + '/' + Gx__SupportPath + '/css/progress.css?_=20090725', 'all');
}
else
{
	document.write('<link rel="stylesheet" href="' + Gx__RootURL + '/' + Gx__SupportPath + '/css/progress.css" type="text/css" media="all" />');
}
