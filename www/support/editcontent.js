// CubicleSoft Barebones CMS WYMEditor functions.
// (C) 2014 CubicleSoft.  All Rights Reserved.

var Gx__WYMEditorInstances = {};

function CreateWYMEditorInstance(eid, fileopts, editopts)
{
	var tempstyle, fileinfo;

	fileopts.isopen = false;
	if (typeof(editopts) == 'undefined')  editopts = { ismulti : false, create : false };
	if (typeof(editopts.ismulti) != 'boolean')  editopts.ismulti = false;
	if (typeof(editopts.create) != 'boolean')  editopts.create = (editopts.ismulti ? true : false);

	if (typeof(Gx__WYMEditorInstances[eid]) == 'object' && Gx__WYMEditorInstances[eid].ismulti != editopts.ismulti)
	{
		alert("CreateWYMEditorInstance():  'editopts.ismulti' value must be consistent when used with the same 'eid'.");

		return false;
	}

	if (typeof(Gx__WYMEditorInstances[eid]) != 'object')
	{
		tempstyle = '';
		if (typeof(editopts.width) == 'string' || typeof(editopts.height) == 'string')
		{
			tempstyle += ' style="';
			if (typeof(editopts.width) == 'string')  tempstyle += 'width: ' + editopts.width + ';';
			if (typeof(editopts.height) == 'string')  tempstyle += 'height: ' + editopts.height + ';';
			tempstyle += '"';
		}
		if (editopts.create)
		{
			if (!editopts.ismulti)  $('#' + eid).html('<textarea id="' + eid + '_edit" class="wymedit"' + tempstyle + '></textarea><div id="' + eid + '_loader" style="display: none;"></div>');
			else
			{
				$('#' + eid).html('<div class="barebones_multi_wymeditor_wrapper"><div id="' + eid + '_tabs"><ul><li><a href="#' + eid + '_tabs_0">' + fileopts.display + '</a></li></ul><div id="' + eid + '_tabs_0"><textarea id="' + eid + '_edit_0" class="wymedit"' + tempstyle + '></textarea></div><div id="' + eid + '_newtab"></div></div></div><div id="' + eid + '_loader" style="display: none;"></div>');
				$('#' + eid + '_tabs').tabs({ activate : function(e, ui) {
					Gx__WYMEditorInstances[eid].activetab = '#' + ui.newTab.attr('aria-controls');

					WYMUI_ResetTimeoutTracker('all', WYMUI_UpdateWYMEditorStatus_All, 50);
				} }).find(".ui-tabs-nav").sortable({axis : 'x', helper : 'clone', delay : 150});
				$('a[href=\'#' + eid + '_tabs_0\']').after(' <a href="#" class="wymeditclose" onclick="return WYMUI_FileClose(\'' + eid + '\', \'' + fileopts.id + '\');">X</a>');
			}
		}

		Gx__WYMEditorInstances[eid] = {
			created : editopts.create,
			ismulti : editopts.ismulti,
			activetab : (editopts.ismulti ? '#' + eid + '_tabs_0' : ''),
			closelast : (typeof(editopts.closelast) == 'function' ? editopts.closelast : undefined),
			optmap : {},
			editor : {},
			initoptid : (editopts.ismulti ? fileopts.id : eid),
			numopen : 0,
			nexttab : 1
		};
		Gx__WYMEditorInstances[eid].optmap[Gx__WYMEditorInstances[eid].initoptid] = fileopts;
		Gx__WYMEditorInstances[eid].editor[Gx__WYMEditorInstances[eid].initoptid] = {
			edited : false,
			tabnum : 0,
			origcontent : ''
		};

		$('#' + eid + '_edit' + (editopts.ismulti ? '_0' : '')).wymeditor(WYMUI_Initwymopts(editopts, eid, fileopts));
	}
	else if (!editopts.ismulti)
	{
		if (Gx__WYMEditorInstances[eid].numopen > 0)  Gx__WYMEditorInstances[eid].numopen--;
		Gx__WYMEditorInstances[eid].optmap[eid] = fileopts;
		Gx__WYMEditorInstances[eid].editor[eid].edited = false;
		Gx__WYMEditorInstances[eid].editor[eid].origcontent = '';

		WYMUI_LoadContent(eid, eid);
	}
	else
	{
		if (typeof(Gx__WYMEditorInstances[eid].optmap[fileopts.id]) == 'object')
		{
			WYMUI_UpdateWYMEditorStatuses("content");
			if (typeof(Gx__WYMEditorInstances[eid].editor[fileopts.id]) == 'object' && Gx__WYMEditorInstances[eid].editor[fileopts.id].edited && !confirm('Reloading the edited content \'' + Gx__WYMEditorInstances[eid].optmap[fileopts.id].display + '\' will lose unsaved modifications.  Continue?'))  return false;

			WYMUI_CloseTab(eid, fileopts.id);
		}

		if (editopts.create)
		{
			tempstyle = '';
			if (typeof(editopts.width) == 'string' || typeof(editopts.height) == 'string')
			{
				tempstyle += ' style="';
				if (typeof(editopts.width) == 'string')  tempstyle += 'width: ' + editopts.width + ';';
				if (typeof(editopts.height) == 'string')  tempstyle += 'height: ' + editopts.height + ';';
				tempstyle += '"';
			}
			$('#' + eid + '_newtab').before('<div id="' + eid + '_tabs_' + Gx__WYMEditorInstances[eid].nexttab + '"><textarea id="' + eid + '_edit_' + Gx__WYMEditorInstances[eid].nexttab + '" class="wymedit"' + tempstyle + '></textarea></div>');
			$('#' + eid + '_tabs').find('.ui-tabs-nav').append('<li><a href="#' + eid + '_tabs_' + Gx__WYMEditorInstances[eid].nexttab + '">&nbsp;</a></li>');
			$('#' + eid + '_tabs').tabs('refresh');
			$('#' + eid + '_tabs').tabs('option', 'active', $('#' + eid + '_tabs').find('.ui-tabs-nav li').length - 1);

			$('a[href=\'#' + eid + '_tabs_' + Gx__WYMEditorInstances[eid].nexttab + '\']').after(' <a href="#" class="wymeditclose" onclick="return WYMUI_FileClose(\'' + eid + '\', \'' + fileopts.id + '\');">X</a>');
		}

		Gx__WYMEditorInstances[eid].optmap[fileopts.id] = fileopts;
		Gx__WYMEditorInstances[eid].editor[fileopts.id] = {
			edited : false,
			tabnum : Gx__WYMEditorInstances[eid].nexttab,
			origcontent : ''
		};

		$('#' + eid + '_edit_' + Gx__WYMEditorInstances[eid].nexttab).wymeditor(WYMUI_Initwymopts(editopts, eid, fileopts));

		Gx__WYMEditorInstances[eid].nexttab++;
	}

	return true;
}

function DestroyWYMEditorInstance(eid)
{
	if (typeof(Gx__WYMEditorInstances[eid]) == 'object' && typeof(Gx__WYMEditorInstances[eid].initoptid) == 'undefined')
	{
		$('#' + eid + '_tabs').tabs('destroy');
		if (Gx__WYMEditorInstances[eid].created)  $('#' + eid).html('');

		delete Gx__WYMEditorInstances[eid];
	}
}

function InsertWYMEditorContent(eid, id, content)
{
	if (typeof(Gx__WYMEditorInstances[eid]) == 'object' && typeof(Gx__WYMEditorInstances[eid].editor[id]) == 'object' && typeof(Gx__WYMEditorInstances[eid].editor[id].wym) == 'object')
	{
		Gx__WYMEditorInstances[eid].editor[id].wym.insert(content);

		return true;
	}

	return false;
}

function WYMUI_Initwymopts(editopts, eid, fileopts)
{
	var wymopts, toollist, toolmap;

	wymopts = {
		lang : 'en',
		stylesheet : Gx__RootURL + '/' + Gx__SupportPath + '/css/wymeditor.css',
		skin : 'barebones',
		toolsItems : [],
		classesItems: [
			{ 'name' : 'left-align', 'title' : 'Left-Align', 'expr' : 'p:not(.center-align,.right-align,.justify-align),h1:not(.center-align,.right-align,.justify-align),h2:not(.center-align,.right-align,.justify-align),h3:not(.center-align,.right-align,.justify-align),h4:not(.center-align,.right-align,.justify-align),h5:not(.center-align,.right-align,.justify-align),h6:not(.center-align,.right-align,.justify-align),pre:not(.center-align,.right-align,.justify-align)' },
			{ 'name' : 'center-align', 'title' : 'Center-Align', 'expr' : 'p:not(.left-align,.right-align,.justify-align),h1:not(.left-align,.right-align,.justify-align),h2:not(.left-align,.right-align,.justify-align),h3:not(.left-align,.right-align,.justify-align),h4:not(.left-align,.right-align,.justify-align),h5:not(.left-align,.right-align,.justify-align),h6:not(.left-align,.right-align,.justify-align),pre:not(.left-align,.right-align,.justify-align)' },
			{ 'name' : 'right-align', 'title' : 'Right-Align', 'expr' : 'p:not(.left-align,.center-align,.justify-align),h1:not(.left-align,.center-align,.justify-align),h2:not(.left-align,.center-align,.justify-align),h3:not(.left-align,.center-align,.justify-align),h4:not(.left-align,.center-align,.justify-align),h5:not(.left-align,.center-align,.justify-align),h6:not(.left-align,.center-align,.justify-align),pre:not(.left-align,.center-align,.justify-align)' },
			{ 'name' : 'justify-align', 'title' : 'Justify-Align', 'expr' : 'p:not(.left-align,.center-align,.right-align),h1:not(.left-align,.center-align,.right-align),h2:not(.left-align,.center-align,.right-align),h3:not(.left-align,.center-align,.right-align),h4:not(.left-align,.center-align,.right-align),h5:not(.left-align,.center-align,.right-align),h6:not(.left-align,.center-align,.right-align),pre:not(.left-align,.center-align,.right-align)' },
			{ 'name' : 'wrap-shortcode', 'title' : 'Wrap Shortcode', 'expr' : 'p:has(img):not(.wrap-start,.wrap-end,.table-row,.table-cell,.table-end)' },
			{ 'name' : 'wrap-start', 'title' : 'Wrap Start', 'expr' : 'p:not(.wrap-shortcode,.wrap-end,.table-row,.table-cell,.table-end)' },
			{ 'name' : 'wrap-end', 'title' : 'Wrap End', 'expr' : 'p:not(.wrap-shortcode,.wrap-start,.table-row,.table-cell,.table-end)' },
			{ 'name' : 'float-left', 'title' : 'Float Left', 'expr' : 'p:not(.float-right,.float-clear)' },
			{ 'name' : 'float-right', 'title' : 'Float Right', 'expr' : 'p:not(.float-left,.float-clear)' },
			{ 'name' : 'float-clear', 'title' : 'Clear Floats', 'expr' : 'p:not(.float-left,.float-right),h1:not(.float-left,.float-right),h2:not(.float-left,.float-right),h3:not(.float-left,.float-right),h4:not(.float-left,.float-right),h5:not(.float-left,.float-right),h6:not(.float-left,.float-right),pre:not(.float-left,.float-right)' },
			{ 'name' : 'first-line', 'title' : 'First Line', 'expr' : 'p,h1,h2,h3,h4,h5,h6,pre' },
			{ 'name' : 'table-row', 'title' : 'New Table Row', 'expr' : 'p:not(.wrap-shortcode,.wrap-start,.wrap-end,.table-cell,.table-end)' },
			{ 'name' : 'table-cell', 'title' : 'New Table Cell', 'expr' : 'p:not(.wrap-shortcode,.wrap-start,.wrap-end,.table-row,.table-end)' },
			{ 'name' : 'vert-align-top', 'title' : 'Vertical-Align Top', 'expr' : 'p.table-row:not(.vert-align-bottom),p.table-cell:not(.vert-align-bottom)' },
			{ 'name' : 'vert-align-bottom', 'title' : 'Vertical-Align Bottom', 'expr' : 'p.table-row:not(.vert-align-top),p.table-cell:not(.vert-align-top)' },
			{ 'name' : 'table-end', 'title' : 'Table End', 'expr' : 'p:not(.wrap-shortcode,.wrap-start,.wrap-end,.table-row,.table-cell)' }
		]
	};

	toollist = (typeof(fileopts.wymtoolbar) == 'string' ? fileopts.wymtoolbar : 'bold,italic,superscript,subscript,pasteword,undo,redo,createlink,unlink,insertimage,insertorderedlist,insertunorderedlist,indent,outdent');
	toolmap = {
		'bold' : { 'name' : 'Bold', 'title' : 'Strong', 'css' : 'wym_tools_strong' },
		'italic' : { 'name' : 'Italic', 'title' : 'Emphasis', 'css' : 'wym_tools_emphasis' },
		'superscript' : { 'name' : 'Superscript', 'title' : 'Superscript', 'css' : 'wym_tools_superscript' },
		'subscript' : { 'name' : 'Subscript', 'title' : 'Subscript', 'css' : 'wym_tools_subscript' },
		'pasteword' : { 'name' : 'Paste', 'title' : 'Paste_From_Word', 'css' : 'wym_tools_paste' },
		'undo' : { 'name' : 'Undo', 'title' : 'Undo', 'css' : 'wym_tools_undo' },
		'redo' : { 'name' : 'Redo', 'title' : 'Redo', 'css' : 'wym_tools_redo' },
		'createlink' : { 'name' : 'CreateLink', 'title' : 'Link', 'css' : 'wym_tools_link' },
		'unlink' : { 'name' : 'Unlink', 'title' : 'Unlink', 'css' : 'wym_tools_unlink' },
		'insertimage' : { 'name' : 'InsertImage', 'title' : 'Image', 'css' : 'wym_tools_image' },
		'insertorderedlist' : { 'name' : 'InsertOrderedList', 'title' : 'Ordered_List', 'css' : 'wym_tools_ordered_list' },
		'insertunorderedlist' : { 'name' : 'InsertUnorderedList', 'title' : 'Unordered_List', 'css' : 'wym_tools_unordered_list' },
		'indent' : { 'name' : 'Indent', 'title' : 'Indent', 'css' : 'wym_tools_indent' },
		'outdent' : { 'name' : 'Outdent', 'title' : 'Outdent', 'css' : 'wym_tools_outdent' },
		'inserttable' : { 'name' : 'InsertTable', 'title' : 'Table', 'css' : 'wym_tools_table' },
		'togglehtml' : { 'name' : 'ToggleHtml', 'title' : 'HTML', 'css' : 'wym_tools_html' },
		'preview' : { 'name' : 'Preview', 'title' : 'Preview', 'css' : 'wym_tools_preview' }
	};
	toollist = toollist.toLowerCase().split(",");
	for (var x in toollist)
	{
		if (typeof(toolmap[$.trim(toollist[x])]) == 'object')  wymopts.toolsItems.push(toolmap[$.trim(toollist[x])]);
	}

	if (typeof(fileopts.wymopts) == 'object')  wymopts = $.extend(wymopts, fileopts.wymopts);

	wymopts.postInit = function(wym) {
		var html, tempid = (editopts.ismulti ? fileopts.id : eid);

		Gx__WYMEditorInstances[eid].editor[tempid].wym = wym;
		Gx__WYMEditorInstances[eid].editor[tempid].lastcontainer = false;

		if (editopts.ismulti)
		{
			// Add a save button.
			html = '<li class="wym_tools_save"><a name="Save" href="#">Save</a></li>';
			$(wym._box).find(wym._options.toolsSelector + wym._options.toolsListSelector).prepend(html);
			$(wym._box).find('li.wym_tools_save a').click(function() {
				WYMUI_Save(eid, fileopts.id);

				return false;
			});
		}

		if (typeof(fileopts.wymeditorpostinit) == 'function')  fileopts.wymeditorpostinit(eid, tempid, wym);

		if (typeof(fileopts.usehovertools) != 'boolean' || fileopts.usehovertools)  wym.hovertools(false);

		Gx__WYMEditorInstances[eid].editor[tempid].clientHeight = 0;
		Gx__WYMEditorInstances[eid].editor[tempid].arearightHeight = 0;
		Gx__WYMEditorInstances[eid].editor[tempid].minHeight = $(wym._box).find(wym._options.iframeSelector).height();

		// Build the tools menu copy for the right side.
		$(wym._box).find(wym._options.toolsSelector).clone(true).addClass('wym_tools_copy').prependTo($(wym._box).find('.wym_area_right'));

		// Force visual updates to be faster when actions are clicked.
		$(wym._box).find(wym._options.toolSelector + ',' + wym._options.containerSelector + ',' + wym._options.classSelector).click(function() {
			this.blur();
			Gx__WYMEditorInstances[eid].editor[tempid].lastcontainer = false;

			WYMUI_ResetTimeoutTracker('content', WYMUI_UpdateWYMEditorStatus_Content, 50);
		});

		WYMUI_UpdateWYMEditorStatuses("all");

		WYMUI_LoadContent(eid, tempid);

		if (typeof(Gx__WYMEditorInstances[eid].initoptid) != 'undefined')  delete Gx__WYMEditorInstances[eid].initoptid;
	}

	return wymopts;
}

function WYMUI_OnBeforeUnload()
{
	var unsavedcontent = [];

	WYMUI_UpdateWYMEditorStatuses("content");

	for (var eid in Gx__WYMEditorInstances)
	{
		if (typeof(Gx__WYMEditorInstances[eid]) == 'object' && Gx__WYMEditorInstances[eid].ismulti)
		{
			for (var id in Gx__WYMEditorInstances[eid].optmap)
			{
				if (typeof(Gx__WYMEditorInstances[eid].editor[id]) == 'object' && Gx__WYMEditorInstances[eid].editor[id].edited)  unsavedcontent.push(Gx__WYMEditorInstances[eid].optmap[id].display);
			}
		}
	}

	if (unsavedcontent.length)  return 'The following content instance' + (unsavedcontent.length == 1 ? ' is' : 's are') + ' not saved:\n\n\t\'' + unsavedcontent.join('\',\n\t\'') + '\'';
}

// Updates the visual aspects of the editor and some internal flags.
function WYMUI_UpdateWYMEditorStatuses(mode)
{
	var temp, temp2, wym, container, container2;

	for (var eid in Gx__WYMEditorInstances)
	{
		if (typeof(Gx__WYMEditorInstances[eid]) == 'object')
		{
			for (var id in Gx__WYMEditorInstances[eid].optmap)
			{
				if (typeof(Gx__WYMEditorInstances[eid].editor[id]) == 'object' && typeof(Gx__WYMEditorInstances[eid].editor[id].wym) == 'object')
				{
					wym = Gx__WYMEditorInstances[eid].editor[id].wym;

					if (!Gx__WYMEditorInstances[eid].ismulti || Gx__WYMEditorInstances[eid].activetab == '#' + eid + '_tabs_' + Gx__WYMEditorInstances[eid].editor[id].tabnum)
					{
						if (mode == "all" || mode == "resize")
						{
							// If the browser window was resized or the CSS classes were simply slow loading, recalculate display heights.
							if (Gx__WYMEditorInstances[eid].editor[id].clientHeight != document.body.clientHeight || Gx__WYMEditorInstances[eid].editor[id].arearightHeight != $(wym._box).find('.wym_area_right').height())
							{
								// Determine the correct size of the boxes on the right.
								$(wym._box).find('.wym_tools_copy').removeClass('wym_dropdown').addClass('wym_panel');
								$(wym._box).find(wym._options.classesSelector).removeClass('wym_dropdown').addClass('wym_panel');
								temp = parseInt($(wym._box).find('.wym_tools_copy').show().css('marginTop'));
								Gx__WYMEditorInstances[eid].editor[id].showHeight = $(wym._box).find('.wym_area_right').height() - temp + 5;
								if (Gx__WYMEditorInstances[eid].editor[id].showHeight >= document.body.clientHeight)
								{
									// Height too big for browser window (small screen).  Switch tools to dropdown.
									$(wym._box).find('.wym_tools_copy').removeClass('wym_panel').addClass('wym_dropdown');
									Gx__WYMEditorInstances[eid].editor[id].showHeight = $(wym._box).find('.wym_area_right').height() - temp + 5;
									if (Gx__WYMEditorInstances[eid].editor[id].showHeight >= document.body.clientHeight)
									{
										// Height still too big for browser window (ridiculously small screen).  Switch classes to dropdown.
										$(wym._box).find(wym._options.classesSelector).removeClass('wym_panel').addClass('wym_dropdown');
										Gx__WYMEditorInstances[eid].editor[id].showHeight = $(wym._box).find('.wym_area_right').height() - temp + 5;
									}
								}
								$(wym._box).find('.wym_tools_copy').hide();

								Gx__WYMEditorInstances[eid].editor[id].clientHeight = document.body.clientHeight;
								Gx__WYMEditorInstances[eid].editor[id].arearightHeight = $(wym._box).find('.wym_area_right').height();

								// Resize the iframe to the height of the boxes on the right.
								if (typeof(Gx__WYMEditorInstances[eid].optmap[id].resizeiframe) != 'boolean' || Gx__WYMEditorInstances[eid].optmap[id].resizeiframe)
								{
									Gx__WYMEditorInstances[eid].editor[id].minHeight = Gx__WYMEditorInstances[eid].editor[id].arearightHeight - $(wym._box).find('.wym_area_main').height() + $(wym._box).find(wym._options.iframeSelector).height();
								}
							}

							// Resize the iframe to be the height of the content inside.
							// Fixes the issue with IE's scrollbar jumping around and flickering on mouseover/mouseout.
							$(wym._box).find(wym._options.iframeSelector).each(function() {
								try
								{
									temp = $(this).contents().find('body').children().last();
									if (temp.length)
									{
										temp = temp.offset().top + temp.height() + 30;
										if (temp < Gx__WYMEditorInstances[eid].editor[id].minHeight)  temp = Gx__WYMEditorInstances[eid].editor[id].minHeight;
										if (this.style.height == "" || parseInt(this.style.height) < temp)  this.style.height = temp + 'px';
										else if (parseInt(this.style.height) - 20 > temp)  this.style.height = temp + 'px';
									}
								}
								catch (ex)
								{
									this.style.height = Gx__WYMEditorInstances[eid].editor[id].minHeight + 'px';
								}
							});
						}

						if (mode == "all" || mode == "resize" || mode == "sidebar")
						{
							// Of course, now that that height of the iframe is as tall as the content, the toolbar and options aren't always visible.
							// Show the hidden tools clone and make the sidebar travel with the scrollbar.
							temp = $(wym._box).find('.wym_area_main').height();
							if (temp <= Gx__WYMEditorInstances[eid].editor[id].showHeight)  $(wym._box).find('.wym_tools_copy').hide();
							else
							{
								$(wym._box).find('.wym_area_right').each(function() {
									temp2 = FindPagePosition(this);
									if ($(window).scrollTop() <= temp2[1])  $(wym._box).find('.wym_tools_copy').hide();
									else
									{
										temp = ($(window).scrollTop() - temp2[1] + Gx__WYMEditorInstances[eid].editor[id].showHeight < temp ? $(window).scrollTop() - temp2[1] + 5 : temp - Gx__WYMEditorInstances[eid].editor[id].showHeight) + 'px';
										$(wym._box).find('.wym_tools_copy').show().stop().animate({ 'marginTop' : temp }, "fast");
										Gx__WYMEditorInstances[eid].editor[id].arearightHeight = $(wym._box).find('.wym_area_right').height();
									}
								});
							}
						}

						if (mode == "all" || mode == "content")
						{
							try
							{
								container = wym.container();
							}
							catch (ex)
							{
								container = null;
							}
							try
							{
								if (Gx__WYMEditorInstances[eid].editor[id].lastcontainer != container)
								{
									// Disable the indent/outdent buttons if the cursor is not inside a list.
									if (typeof(container) == 'object' && container && $(container).closest(WYMeditor.LI).size())
									{
										$(wym._box).find(wym._options.toolsSelector + ' .wym_tools_indent').removeClass('wym_button_disabled');
										$(wym._box).find(wym._options.toolsSelector + ' .wym_tools_outdent').removeClass('wym_button_disabled');
									}
									else
									{
										$(wym._box).find(wym._options.toolsSelector + ' .wym_tools_indent').addClass('wym_button_disabled');
										$(wym._box).find(wym._options.toolsSelector + ' .wym_tools_outdent').addClass('wym_button_disabled');
									}

									// Disable classes that can't be applied to the current container.
									if (typeof(container) == 'object' && container)
									{
										for (var cid in wym._options.classesItems)
										{
											var found = false, used = false;
											$(wym._doc).find(wym._options.classesItems[cid].expr).each(function(x) {
												if (container == this)
												{
													found = true;
													used = $(container).hasClass(wym._options.classesItems[cid].name);
												}
												else
												{
													container2 = container;
													while (container2.parentNode.nodeName.toLowerCase() != "body" && container2 != this)  container2 = container2.parentNode;
													if (container2 == this)
													{
														found = true;
														used = $(container2).hasClass(wym._options.classesItems[cid].name);
													}
												}
											});

											if (found)  $(wym._box).find(wym._options.classSelector + '[' + WYMeditor.NAME + '="' + wym._options.classesItems[cid].name + '"]').removeClass('wym_class_disabled');
											else  $(wym._box).find(wym._options.classSelector + '[' + WYMeditor.NAME + '="' + wym._options.classesItems[cid].name + '"]').addClass('wym_class_disabled');

											if (used)  $(wym._box).find(wym._options.classSelector + '[' + WYMeditor.NAME + '="' + wym._options.classesItems[cid].name + '"]').addClass('wym_class_used');
											else  $(wym._box).find(wym._options.classSelector + '[' + WYMeditor.NAME + '="' + wym._options.classesItems[cid].name + '"]').removeClass('wym_class_used');
										}
									}
									else
									{
										$(wym._box).find(wym._options.classSelector).addClass('wym_class_disabled');
										$(wym._box).find(wym._options.classSelector).removeClass('wym_class_used');
									}

									Gx__WYMEditorInstances[eid].editor[id].lastcontainer = container;
								}
							}
							catch (ex)
							{
								$(wym._box).find(wym._options.toolsSelector + ' .wym_tools_indent').addClass('wym_button_disabled');
								$(wym._box).find(wym._options.toolsSelector + ' .wym_tools_outdent').addClass('wym_button_disabled');
								$(wym._box).find(wym._options.classSelector).addClass('wym_class_disabled');
								$(wym._box).find(wym._options.classSelector).removeClass('wym_class_used');

								Gx__WYMEditorInstances[eid].editor[id].lastcontainer = false;
							}

							// Apply an asterisk if the content area has changed ('ismulti' only).  Remove it if the content exactly matches the original.  Also sets the edited flag.
							if (Gx__WYMEditorInstances[eid].ismulti)
							{
								wym.update();
								temp = $('#' + eid + '_edit_' + Gx__WYMEditorInstances[eid].editor[id].tabnum).val();
								if (Gx__WYMEditorInstances[eid].editor[id].origcontent == temp && Gx__WYMEditorInstances[eid].editor[id].edited)
								{
									$('a[href=\'#' + eid + '_tabs_' + Gx__WYMEditorInstances[eid].editor[id].tabnum + '\']').html(Gx__WYMEditorInstances[eid].optmap[id].display);
									Gx__WYMEditorInstances[eid].editor[id].edited = false;
								}
								else if (Gx__WYMEditorInstances[eid].editor[id].origcontent != temp && !Gx__WYMEditorInstances[eid].editor[id].edited)
								{
									$('a[href=\'#' + eid + '_tabs_' + Gx__WYMEditorInstances[eid].editor[id].tabnum + '\']').html('* ' + Gx__WYMEditorInstances[eid].optmap[id].display);
									Gx__WYMEditorInstances[eid].editor[id].edited = true;
								}
							}
						}
					}
				}
			}
		}
	}
}

function WYMUI_UpdateWYMEditorStatus_All()
{
	WYMUI_UpdateWYMEditorStatuses("resize");
}

function WYMUI_UpdateWYMEditorStatus_Resize()
{
	WYMUI_UpdateWYMEditorStatuses("resize");
}

function WYMUI_UpdateWYMEditorStatus_Sidebar()
{
	WYMUI_UpdateWYMEditorStatuses("sidebar");
}

function WYMUI_UpdateWYMEditorStatus_Content()
{
	WYMUI_UpdateWYMEditorStatuses("content");
}


function WYMUI_ContentLoaded(eid, id, content)
{
	if (Gx__WYMEditorInstances[eid].ismulti)  $('a[href=\'#' + eid + '_tabs_' + Gx__WYMEditorInstances[eid].editor[id].tabnum + '\']').html(Gx__WYMEditorInstances[eid].optmap[id].display);
	Gx__WYMEditorInstances[eid].editor[id].origcontent = content;
	Gx__WYMEditorInstances[eid].editor[id].edited = false;
	Gx__WYMEditorInstances[eid].editor[id].wym.html(content);

	if (typeof(Gx__WYMEditorInstances[eid].optmap[id].oncontentloaded) == 'function')  Gx__WYMEditorInstances[eid].optmap[id].oncontentloaded(eid, id, Gx__WYMEditorInstances[eid].editor[id].wym);

	if (!Gx__WYMEditorInstances[eid].optmap[id].isopen)
	{
		Gx__WYMEditorInstances[eid].numopen++;
		Gx__WYMEditorInstances[eid].optmap[id].isopen = true;
	}

	WYMUI_UpdateWYMEditorStatus_Content();

	WYMUI_ResetTimeoutTracker('all', WYMUI_UpdateWYMEditorStatus_All, 1000);
}

var Gx__WYMEditorInstances_Loader = [];

function WYMUI_URLContentLoaded(responsetext, requeststatus, request)
{
	var data;

	data = Gx__WYMEditorInstances_Loader.shift();
	if (typeof(Gx__WYMEditorInstances[data.eid]) == 'object' && typeof(Gx__WYMEditorInstances[data.eid].optmap[data.id]) == 'object')  WYMUI_ContentLoaded(data.eid, data.id, unescape(responsetext));
	$('#' + data.eid + '_loader').html('');

	if (Gx__WYMEditorInstances_Loader.length)  WYMUI_URLContentLoad();
}

function WYMUI_URLContentLoad()
{
	$('#' + Gx__WYMEditorInstances_Loader[0].eid + '_loader').load(Gx__WYMEditorInstances_Loader[0].loadurl, Gx__WYMEditorInstances_Loader[0].loadparams, WYMUI_URLContentLoaded);
}

function WYMUI_LoadContent(eid, id)
{
	var opts;

	if (typeof(Gx__WYMEditorInstances[eid]) == 'object' && typeof(Gx__WYMEditorInstances[eid].optmap[id]) == 'object')
	{
		opts = Gx__WYMEditorInstances[eid].optmap[id];
		if (typeof(opts.loadurl) == 'string')
		{
			Gx__WYMEditorInstances_Loader.push({ eid : eid, id : id, loadurl : opts.loadurl, loadparams : opts.loadparams });
			if (Gx__WYMEditorInstances_Loader.length == 1)  WYMUI_URLContentLoad();
		}
		else if (typeof(opts.loadcontent) == 'string')
		{
			WYMUI_ContentLoaded(eid, id, opts.loadcontent);
		}
		else if (Gx__WYMEditorInstances[eid].ismulti)
		{
			alert('WYMUI_LoadContent():  CreateWYMEditorInstance() called without a content loading option.');
		}
		else
		{
			if (typeof(opts.oncontentloaded) == 'function')  opts.oncontentloaded(eid, id, Gx__WYMEditorInstances[eid].editor[id].wym);

			if (!opts.isopen)
			{
				Gx__WYMEditorInstances[eid].numopen++;
				Gx__WYMEditorInstances[eid].optmap[id].isopen = true;
			}
		}
	}
}

var Gx__WYMEditorInstances_Saver = [];

function WYMUI_URLContentSaved(responsetext, requeststatus, request)
{
	var data;

	data = Gx__WYMEditorInstances_Saver.shift();
	if (typeof(Gx__WYMEditorInstances[data.eid]) == 'object' && typeof(Gx__WYMEditorInstances[data.eid].optmap[data.id]) == 'object')
	{
		if ($.trim(responsetext).substring(0, 2) != 'OK')  alert('Content \'' + Gx__WYMEditorInstances[data.eid].optmap[data.id].display + '\' not saved.  Try again later.\n\n' + responsetext);
		else
		{
			Gx__WYMEditorInstances[data.eid].editor[data.id].origcontent = $('#' + data.eid + '_edit_' + Gx__WYMEditorInstances[data.eid].editor[data.id].tabnum).val();
			$('a[href=\'#' + data.eid + '_tabs_' + Gx__WYMEditorInstances[data.eid].editor[data.id].tabnum + '\']').html(Gx__WYMEditorInstances[data.eid].optmap[data.id].display);
			Gx__WYMEditorInstances[data.eid].editor[data.id].edited = false;
		}
	}
	$('#' + data.eid + '_loader').html('');

	if (Gx__WYMEditorInstances_Saver.length)  WYMUI_URLContentSave();
}

function WYMUI_URLContentSave()
{
	$('#' + Gx__WYMEditorInstances_Saver[0].eid + '_loader').load(Gx__WYMEditorInstances_Saver[0].url, Gx__WYMEditorInstances_Saver[0].params, WYMUI_URLContentSaved);
}

function WYMUI_Save(eid, id)
{
	var params, file;

	if (typeof(Gx__WYMEditorInstances[eid]) == 'object' && Gx__WYMEditorInstances[eid].ismulti && typeof(Gx__WYMEditorInstances[eid].optmap[id]) == 'object')
	{
		WYMUI_UpdateWYMEditorStatuses("content");

		if (Gx__WYMEditorInstances[eid].editor[id].edited && typeof(Gx__WYMEditorInstances[eid].optmap[id].saveurl) != 'undefined')
		{
			params = Gx__WYMEditorInstances[eid].optmap[id].saveparams;
			if (typeof(params) == 'undefined')  params = {};
			params.content = $('#' + eid + '_edit_' + Gx__WYMEditorInstances[eid].editor[id].tabnum).val();
			Gx__WYMEditorInstances_Saver.push({ eid : eid, id : id, url : Gx__WYMEditorInstances[eid].optmap[id].saveurl, params : params });
			if (Gx__WYMEditorInstances_Saver.length == 1)  WYMUI_URLContentSave();
		}
	}
}

function WYMUI_FileClose(eid, id)
{
	var retval;

	if (typeof(Gx__WYMEditorInstances[eid]) == 'object' && Gx__WYMEditorInstances[eid].ismulti && typeof(Gx__WYMEditorInstances[eid].optmap[id]) == 'object')
	{
		WYMUI_UpdateWYMEditorStatuses("content");

		retval = true;
		if (Gx__WYMEditorInstances[eid].editor[id].edited && typeof(Gx__WYMEditorInstances[eid].optmap[id].saveurl) != 'undefined')  retval = confirm('The content \'' + Gx__WYMEditorInstances[eid].optmap[id].display + '\' has not been saved.  Close anyway?');

		if (retval)
		{
			WYMUI_CloseTab(eid, id);
			if (Gx__WYMEditorInstances[eid].numopen == 0 && typeof(Gx__WYMEditorInstances[eid].closelast) == 'function')  Gx__WYMEditorInstances[eid].closelast(eid);
		}
	}

	return false;
}

function WYMUI_CloseTab(eid, id)
{
	if (typeof(Gx__WYMEditorInstances[eid]) == 'object' && typeof(Gx__WYMEditorInstances[eid].editor[id]) == 'object')
	{
		// Can't just simply do a '.tabs('remove', '#id');'
		// http://dev.jqueryui.com/ticket/3171  (feature was slated for jQuery UI 1.8)
		$('li[aria-controls=' + eid + '_tabs_' + Gx__WYMEditorInstances[eid].editor[id].tabnum + ']').remove();
		$('#' + eid + '_tabs_' + Gx__WYMEditorInstances[eid].editor[id].tabnum).remove();
		$('#' + eid + '_tabs').tabs('refresh');

		if (typeof(Gx__WYMEditorInstances[eid].optmap[id]) == 'object')
		{
			delete Gx__WYMEditorInstances[eid].editor[id];
			delete Gx__WYMEditorInstances[eid].optmap[id];
			if (Gx__WYMEditorInstances[eid].numopen > 0)  Gx__WYMEditorInstances[eid].numopen--;
		}
	}

	return true;
}


//var Gx__WYMEditor__IntervalTracker = window.setInterval(WYMUI_UpdateWYMEditorStatuses, 1000);
var Gx__WYMEditor__TimeoutTrackers = {
	all : false,
	resize : false,
	sidebar : false,
	content : false
};

function WYMUI_ResetTimeoutTracker(type, callback, timeout)
{
	if (Gx__WYMEditor__TimeoutTrackers[type] !== false)  window.clearTimeout(Gx__WYMEditor__TimeoutTrackers[type]);
	Gx__WYMEditor__TimeoutTrackers[type] = window.setTimeout(callback, timeout);
}

$(window).resize(function() {
	WYMUI_ResetTimeoutTracker('resize', WYMUI_UpdateWYMEditorStatus_Resize, 250);
});

$(window).scroll(function() {
	WYMUI_ResetTimeoutTracker('sidebar', WYMUI_UpdateWYMEditorStatus_Sidebar, 250);
});

if (typeof(window.AddOnBeforeUnload) == 'function')  AddOnBeforeUnload(WYMUI_OnBeforeUnload);
else  window.onbeforeunload = WYMUI_OnBeforeUnload;

if (typeof(window.LoadConditionalScript) == 'function')
{
	LoadCSS(Gx__RootURL + '/' + Gx__SupportPath + '/jquery_ui_themes/smoothness/jquery-ui-1.10.4.css?_=20140418', 'all');
	LoadCSS(Gx__RootURL + '/' + Gx__SupportPath + '/wymeditor/skins/barebones/skin.css?_=20140418', 'all');

	LoadConditionalScript(Gx__RootURL + '/' + Gx__SupportPath + '/jquery-ui-1.10.4' + (Gx__SupportDebug ? '' : '.min') + '.js?_=20140418', true, function(loaded) { return (loaded && !IsConditionalScriptLoading()); }, function() {
		// jQuery.browser required for WYMEditor to function.
		LoadConditionalScript(Gx__RootURL + '/' + Gx__SupportPath + '/wymeditor/jquery.browser' + (Gx__SupportDebug ? '' : '.min') + '.js?_=20150110', true, function(loaded) { return (loaded && !IsConditionalScriptLoading()); }, function() {
			InjectScriptTag(Gx__RootURL + '/' + Gx__SupportPath + '/wymeditor/jquery.wymeditor' + (Gx__SupportDebug ? '' : '.min') + '.js');
			LoadConditionalScript(Gx__RootURL + '/' + Gx__SupportPath + '/wymeditor/jquery.wymeditor' + (Gx__SupportDebug ? '' : '.min') + '.js?_=20150110', true, function(loaded) { return (loaded && !IsConditionalScriptLoading()); }, function() {
				LoadConditionalScript(Gx__RootURL + '/' + Gx__SupportPath + '/wymeditor/skins/barebones/skin.js?_=20150110', true, function(loaded) { return (loaded && !IsConditionalScriptLoading()); }, function() {
					LoadConditionalScript(Gx__RootURL + '/' + Gx__SupportPath + '/wymeditor/plugins/hovertools/jquery.wymeditor.hovertools.js?_=20150110', true, function(loaded) { return (loaded && !IsConditionalScriptLoading()); }, function() { });
				});
			});
		});
	});
}
else
{
	document.write('<link rel="stylesheet" href="' + Gx__RootURL + '/' + Gx__SupportPath + '/jquery_ui_themes/smoothness/jquery-ui-1.10.4.css" type="text/css" media="all" />');
	document.write('<link rel="stylesheet" href="' + Gx__RootURL + '/' + Gx__SupportPath + '/wymeditor/skins/barebones/skin.css" type="text/css" media="all" />');
	document.write('<script type="text/javascript" src="' + Gx__RootURL + '/' + Gx__SupportPath + '/jquery-ui-1.10.4' + (Gx__SupportDebug ? '' : '.min') + '.js"></script>');
	document.write('<script type="text/javascript" src="' + Gx__RootURL + '/' + Gx__SupportPath + '/wymeditor/jquery.browser' + (Gx__SupportDebug ? '' : '.min') + '.js"></script>');
	document.write('<script type="text/javascript" src="' + Gx__RootURL + '/' + Gx__SupportPath + '/wymeditor/jquery.wymeditor' + (Gx__SupportDebug ? '' : '.min') + '.js"></script>');
	document.write('<script type="text/javascript" src="' + Gx__RootURL + '/' + Gx__SupportPath + '/wymeditor/skins/barebones/skin.js"></script>');
	document.write('<script type="text/javascript" src="' + Gx__RootURL + '/' + Gx__SupportPath + '/wymeditor/plugins/hovertools/jquery.wymeditor.hovertools.js"></script>');
}
