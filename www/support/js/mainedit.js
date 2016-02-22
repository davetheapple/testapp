// CubicleSoft Barebones CMS Main Editing functions.
// (C) 2015 CubicleSoft.  All Rights Reserved.

function ResizeIFrame()
{
	$('#mainview').each(function() {
		try
		{
			if (this.contentWindow && this.contentWindow.document && this.contentWindow.document.body && (this.style.height != (this.contentWindow.document.body.offsetHeight + 30) + 'px' || document.title != this.contentWindow.document.title))
			{
				this.style.height = (this.contentWindow.document.body.offsetHeight + 30) + 'px';
				document.title = this.contentWindow.document.title;
			}
		}
		catch (ex)
		{
			document.title = '';
			this.style.height = ($(window).height() - 30) + 'px';
		}
	});
}

var Gx__IntervalTracker;

function BeginResizeIFrame()
{
	Gx__IntervalTracker = window.setInterval(function() { ResizeIFrame(); }, 1000);
}

function EndResizeIFrame()
{
	if (window.Gx__IntervalTracker)
	{
		window.clearInterval(Gx__IntervalTracker);
		Gx__IntervalTracker = null;
	}
}

function ReloadMenu()
{
	var params = { 'bb_action' : 'bb_main_edit_menu', 'lang' : Gx__PrefLang, 'bbt' : Gx__PageInfo.menu_token };
	if (typeof(window.Gx__RevisionNum) != 'undefined')  params.bb_revnum = Gx__RevisionNum;
	$('.leftnav').load(Gx__URLBase, params);
}

function ReloadMainViewInfo()
{
	var params = { 'bb_action' : 'bb_main_edit_mainviewinfo', 'lang' : Gx__PrefLang, 'bbt' : Gx__PageInfo.mainviewinfo_token };
	if (typeof(window.Gx__RevisionNum) != 'undefined')  params.bb_revnum = Gx__RevisionNum;
	if (Gx__PageInfo.mainviewinfo_notify > -1)  params.notify = Gx__PageInfo.mainviewinfo_notify;
	if (Gx__PageInfo.mainviewinfo_profile != '')  params.bb_profile = Gx__PageInfo.mainviewinfo_profile;
	$('#mainviewinfo').load(Gx__URLBase, params);
}

function UpdateSessionInfo()
{
	var temphtml, tempdate, numleft;

	tempdate = new Date();
	numleft = Gx__PageInfo.expire - Math.floor((tempdate.getTime() - Gx__PageInfo.start.getTime()) / 1000);
	delete tempdate;
	if (numleft <= 0)
	{
		alert('Session has expired.');
		window.location.href = Gx__FullURLBaseHTTP;
	}
	else
	{
		temphtml = 'Login: ' + Gx__PageInfo.user + ' [' + Gx__PageInfo.type;
		if (Gx__PageInfo.group != '')  temphtml += ', ' + Gx__PageInfo.group;
		temphtml += '], ';
		if (numleft < 60)  temphtml += numleft + ' second' + (numleft == 1 ? '' : 's');
		else
		{
			numleft = Math.floor(numleft / 60);
			if (numleft < 60)  temphtml += numleft + ' minute' + (numleft == 1 ? '' : 's');
			else
			{
				numleft = Math.floor(numleft / 60);
				if (numleft < 24)  temphtml += numleft + ' hour' + (numleft == 1 ? '' : 's');
				else
				{
					numleft = Math.floor(numleft / 24);
					temphtml += numleft + ' day' + (numleft == 1 ? '' : 's');
				}
			}
		}
		temphtml += ' left';

		if (temphtml != $('#sessioninfo').html())  $('#sessioninfo').html(temphtml);
	}
}

function ReloadIFrame()
{
	$('#mainview').each(function() {
		try
		{
			if (this.contentWindow && this.contentWindow.location)
			{
				EndResizeIFrame();
				this.contentWindow.location.reload(true);
			}
		}
		catch (ex)
		{
			var oldsrc;

			oldsrc = this.src;
			this.src = '';
			this.src = oldsrc;
		}
	});
}

function ReloadPage()
{
	window.location.reload(true);
}

function FindPagePosition(elem)
{
	var x, y;

	if (typeof(elem.offsetParent) == 'undefined')  return [elem.x, elem.y];

	for (x = 0, y = 0; elem; elem = elem.offsetParent)
	{
		x += elem.offsetLeft;
		y += elem.offsetTop;
	}

	return [x, y];
}

var Gx__ScrollSaved = false;
var Gx__Scroll = 0;
var Gx__PropertyChangeQueue = [];

function AddPropertyChange(callback, params)
{
	Gx__PropertyChangeQueue.push([callback, params]);
}

function ProcessPropertyChangeQueue()
{
	var data;

	while (Gx__PropertyChangeQueue.length)
	{
		data = Gx__PropertyChangeQueue.pop();
		data[0](data[1]);
	}
}

function LoadProperties(params, callback)
{
	ProcessPropertyChangeQueue();

	if (!Gx__ScrollSaved)
	{
		Gx__ScrollSaved = true;
		Gx__Scroll = $(window).scrollTop();
	}
	$(window).scrollTop(0);
	window.focus();
	$('#mainprops').html('Loading...');
	$('#mainprops').fadeIn('slow');
	params.lang = Gx__PrefLang;
	if (typeof(window.Gx__RevisionNum) != 'undefined')  params.bb_revnum = Gx__RevisionNum;
	$('#mainprops').load(Gx__URLBase, params, callback);
	$(document).bind('keydown', 'esc', CloseProperties);

	return false;
}

function CloseProperties(restorescroll)
{
	ProcessPropertyChangeQueue();

	$(document).unbind('keydown', 'esc', CloseProperties);
	$('#mainprops').slideUp('normal');
	if (Gx__ScrollSaved && (typeof(restorescroll) != 'boolean' || restorescroll))
	{
		$(window).scrollTop(Gx__Scroll);
		Gx__ScrollSaved = false;
	}

	return false;
}

function SendProperties()
{
	$('.propinfo').load(Gx__URLBase, $('#propform').serialize() + '&rnd_' + Math.floor(Math.random() * 1000000));
	$('.propinfo').show();
	$(window).scrollTop(0);

	return false;
}

// Lighter weight loader designed for no-form functionality.
function LoadProperties2(params, callback)
{
	window.focus();
	$('#mainprops2').html('Loading...');
	$('#mainprops2').fadeIn('slow');
	params.lang = Gx__PrefLang;
	if (typeof(window.Gx__RevisionNum) != 'undefined')  params.bb_revnum = Gx__RevisionNum;
	$('#mainprops2').load(Gx__URLBase, params, callback);

	return false;
}

function CloseProperties2()
{
	$('#mainprops2').slideUp('normal');

	return false;
}

window.onbeforeunload = ProcessOnBeforeUnload;
var Gx__OnBeforeUnloadQueue = [];

function AddOnBeforeUnload(callback)
{
	Gx__OnBeforeUnloadQueue.push(callback);

	return true;
}

function ProcessOnBeforeUnload()
{
	var temp, result = [];

	for (var callback in Gx__OnBeforeUnloadQueue)
	{
		temp = Gx__OnBeforeUnloadQueue[callback]();
		if (typeof(temp) == 'string')  result.push(temp);
	}

	if (result.length)  return result.join('\n\n');
}

var Gx__ConditionalScriptsQueue = [];
var Gx__ConditionalScriptsLoading = 0;

function DisplayConditionalScripts()
{
	var temphtml;

	if (!IsConditionalScriptLoading())  $('#loadcondscripts').hide();
	else
	{
		$('#loadcondscripts').show();
		temphtml = '';
		for (var x = 0; x < Gx__ConditionalScriptsQueue.length; x++)  temphtml += "Loading '" + Gx__ConditionalScriptsQueue[x][3] + "'...<br />";
		$('#loadcondscripts').html(temphtml);
	}
}

function InternalProcessConditionalScripts()
{
	var x, found, data;

	if (Gx__ConditionalScriptsLoading > 0)  Gx__ConditionalScriptsLoading--;

	do
	{
		found = false;

		for (x = 0; x < Gx__ConditionalScriptsQueue.length && !Gx__ConditionalScriptsQueue[x][0](true); x++)  {}
		if (x < Gx__ConditionalScriptsQueue.length)
		{
			data = Gx__ConditionalScriptsQueue.splice(x, 1);
			data[0][1](data[0][2]);
			found = true;
		}
	} while (found);

	DisplayConditionalScripts();
}

function IsConditionalScriptLoading()
{
	return (Gx__ConditionalScriptsLoading > 0);
}

function LoadConditionalScript(url, cachescript, cond, callback, params)
{
	if (cond(false))  callback(params);
	else
	{
		Gx__ConditionalScriptsQueue.unshift([cond, callback, params, url]);
		Gx__ConditionalScriptsLoading++;
		if (cachescript)  $.ajaxSetup({ cache : true });
		$.getScript(url, InternalProcessConditionalScripts);
		if (cachescript)  $.ajaxSetup({ cache : false });

		DisplayConditionalScripts();
	}
}

function InjectScriptTag(url)
{
	var script;

	script = document.createElement('script');
	script.type = 'text/javascript';
	script.src = url;

	document.getElementsByTagName('head')[0].appendChild(script);
}

function LoadCSS(url, cssmedia)
{
	if (document.createStyleSheet)
	{
		sheet = document.createStyleSheet(url);
		sheet.media = (cssmedia != undefined ? cssmedia : 'all');
	}
	else
	{
		var tag = document.createElement('link');
		$(tag).attr({
			href : url,
			type : 'text/css',
			media : (cssmedia != undefined ? cssmedia : 'all'),
			rel : 'stylesheet'
		}).appendTo($('head').get(0));
	}
}

function InitPropertiesTableDragAndDrop(tableid)
{
	LoadConditionalScript(Gx__RootURL + '/' + Gx__SupportPath + '/jquery.tablednd-20140418.min.js', true, function(loaded) {
			return ((!loaded && typeof(window.jQuery.tableDnD) == 'object') || (loaded && !IsConditionalScriptLoading()));
		}, function(params) {
			$('#' + tableid).tableDnD({
				dragHandle : '.draghandle',
				onDragClass : 'dragactive',
				onDragStart : function (table, row) {
					var num = 0;

					$('#' + tableid + ' tr').each(function(x) {
						$(this).removeClass('altrow');
						if (this.id != row.id)
						{
							if (num % 2)  $(this).addClass('altrow');
							num++;
						}
					});
				},
				onDrop : function (table, row) {
					SendProperties();
				}
			});
	});

	return false;
}

function ManageFileUploadResults(data, params)
{
	if (data.context.m_errors)
	{
		$('.propinfo').html('<div class="error">One or more errors occurred while uploading.</div>');
		$('.propinfo').show();
		$(window).scrollTop(0);
	}
	else
	{
		LoadProperties(params);
		ReloadIFrame();
	}
}

function ManageFileUploadDestroy(id)
{
	DestroyUploadInstance(id);
}

function EditFile(dir, file, syntaxopt, loadtoken, savetoken)
{
	LoadConditionalScript(Gx__RootURL + '/' + Gx__SupportPath + '/editfile.js?_=20150110', true, function(loaded) {
			return ((!loaded && typeof(window.CreateEditAreaInstance) == 'function') || (loaded && !IsConditionalScriptLoading()));
		}, function(params) {
			setTimeout(function() {
				$('#fileeditor').show();

				var fileopts = {
					loadurl : Gx__URLBase,
					loadparams : {
						'bb_action' : 'bb_main_edit_site_opt_file_explorer_edit_load',
						'lang' : Gx__PrefLang,
						'bb_revnum' : (typeof(window.Gx__RevisionNum) != 'undefined' ? Gx__RevisionNum : '-1'),
						'file' : dir + '/' + file,
						'bbt' : loadtoken,
						'bb_sec_extra' : 'file'
					},
					id : dir + '/' + file,
					display : file,
					saveurl : Gx__URLBase,
					saveparams : {
						'bb_action' : 'bb_main_edit_site_opt_file_explorer_edit_save',
						'lang' : Gx__PrefLang,
						'bb_revnum' : (typeof(window.Gx__RevisionNum) != 'undefined' ? Gx__RevisionNum : '-1'),
						'file' : dir + '/' + file,
						'bbt' : savetoken,
						'bb_sec_extra' : 'file'
					},
					syntax : syntaxopt,
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
			}, 250);
	});

	return false;
}

function ClosedAllFiles(eid)
{
	setTimeout(function() { HideEditAreaInstance(eid);  $('#' + eid).hide(); }, 250);
}

$(function() {
	ResizeIFrame();
	$(window).resize(function() {
		ResizeIFrame();
	});
	$('#mainview').load(function() {
		ResizeIFrame();
		BeginResizeIFrame();
	});

	window.setInterval(function() { UpdateSessionInfo(); }, 5000);

	// Initialize the navigation button.
	$('#navbutton').click(function() {
		$('#navbutton').toggleClass("clicked");
		$('#navdropdown').toggle().each(function() {
			pos = $('#navbutton').position();
			height = $('#navbutton').outerHeight();
			$(this).css({ top: (pos.top + height) + "px" });
		});
	});

	ReloadMenu();
	ReloadMainViewInfo();
	UpdateSessionInfo();
});

document.write('<script type="text/javascript" src="' + Gx__RootURL + '/' + Gx__SupportPath + '/jquery.hotkeys-20140418.js"></script>');
