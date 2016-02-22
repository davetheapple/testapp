// CubicleSoft Barebones CMS ACE/EditArea functions.
// (C) 2014 CubicleSoft.  All Rights Reserved.

var Gx__EditAreaInstances = {};

if (!window.IsCanvasSupported)
{
	window.IsCanvasSupported = function() {
		var elem = document.createElement("canvas");
		return !!(elem.getContext && elem.getContext("2d"));
	}
}

// ACE replaces EditArea in 'canvas'-capable browsers.
var Gx__EditAreaInstances_CanvasSupported = window.IsCanvasSupported();

function CreateEditAreaInstance(eid, fileopts, editopts)
{
	if (Gx__EditAreaInstances_CanvasSupported)
	{
		var tempstyle, fileinfo;

		fileopts.isopen = false;
		if (typeof(editopts) == 'undefined')  editopts = { ismulti : false, create : false };
		if (typeof(editopts.ismulti) != 'boolean')  editopts.ismulti = false;
		if (typeof(editopts.create) != 'boolean')  editopts.create = (editopts.ismulti ? true : false);

		if (typeof(Gx__EditAreaInstances[eid]) == 'object' && Gx__EditAreaInstances[eid].ismulti != editopts.ismulti)
		{
			alert("CreateEditAreaInstance():  'editopts.ismulti' value must be consistent when used with the same 'eid'.");

			return false;
		}

		if (typeof(Gx__EditAreaInstances[eid]) != 'object')
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
				if (!editopts.ismulti)  $('#' + eid).html('<div class="barebones_single_eauieditor_wrapper"><div id="' + eid + '_toolbar" class="barebones_single_eauieditor_toolbar"></div><div id="' + eid + '_edit_real" class="barebones_single_eauieditor_real"' + tempstyle + '><div id="' + eid + '_edit_placeholder" class="barebones_single_eauieditor_placeholder" style="position: absolute; top: 0; bottom: 0; left: 0; right: 0;"></div></div></div><div id="' + eid + '_loader" style="display: none;"></div><div id="' + eid + '_dialog" style="display: none;"></div>');
				else
				{
					$('#' + eid).html('<div class="barebones_multi_eauieditor_wrapper"><div id="' + eid + '_tabs"><ul><li><a href="#' + eid + '_tabs_0">' + fileopts.display + '</a></li></ul><div id="' + eid + '_tabs_0"><div class="barebones_single_eauieditor_wrapper"><div id="' + eid + '_toolbar_0" class="barebones_single_eauieditor_toolbar"></div><div id="' + eid + '_edit_real_0" class="barebones_single_eauieditor_real"' + tempstyle + '><div id="' + eid + '_edit_placeholder_0" class="barebones_single_eauieditor_placeholder" style="position: absolute; top: 0; bottom: 0; left: 0; right: 0;"></div></div></div></div><div id="' + eid + '_newtab"></div></div></div><div id="' + eid + '_loader" style="display: none;"></div><div id="' + eid + '_dialog" style="display: none;"></div>');
					$('#' + eid + '_tabs').tabs({
						activate : function(e, ui) {
							Gx__EditAreaInstances[eid].activetab = '#' + ui.newTab.attr('aria-controls');

							// BUG:  Occasionally ACE weirds out on hidden tabs and content is hidden, so resize().
							// Activate the cursor in this tab with focus().
							var tabnum = parseInt(Gx__EditAreaInstances[eid].activetab.substring(Gx__EditAreaInstances[eid].activetab.lastIndexOf('_') + 1));
							for (var x in Gx__EditAreaInstances[eid].editor)
							{
								if (Gx__EditAreaInstances[eid].editor[x].tabnum == tabnum)
								{
									setTimeout(function() {
										Gx__EditAreaInstances[eid].editor[x].aceeditor.resize();
										Gx__EditAreaInstances[eid].editor[x].aceeditor.focus();
									}, 250);

									break;
								}
							}
						}
					}).find(".ui-tabs-nav").sortable({axis : 'x', helper : 'clone', delay : 150});
					$('a[href=\'#' + eid + '_tabs_0\']').after(' <a href="#" class="eauieditclose" onclick="return EAUI_FileClose_ACE(\'' + eid + '\', \'' + fileopts.id + '\');">X</a>');
				}
			}

			Gx__EditAreaInstances[eid] = {
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
			Gx__EditAreaInstances[eid].optmap[Gx__EditAreaInstances[eid].initoptid] = fileopts;
			Gx__EditAreaInstances[eid].editor[Gx__EditAreaInstances[eid].initoptid] = {
				edited : false,
				tabnum : 0,
				origcontent : '',
				orignumlines : -1
			};

			var aceeditor = ace.edit(eid + '_edit_placeholder' + (editopts.ismulti ? '_0' : ''));
			Gx__EditAreaInstances[eid].editor[Gx__EditAreaInstances[eid].initoptid].aceeditor = aceeditor;
			Gx__EditAreaInstances[eid].editor[Gx__EditAreaInstances[eid].initoptid].acesession = aceeditor.getSession();
			EAUI_InitACE(eid, editopts, fileopts);
		}
 		else if (!editopts.ismulti)
		{
			if (Gx__EditAreaInstances[eid].numopen > 0)  Gx__EditAreaInstances[eid].numopen--;
			Gx__EditAreaInstances[eid].optmap[eid] = fileopts;

			EAUI_LoadContent(eid, eid);
		}
		else
		{
			if (typeof(Gx__EditAreaInstances[eid].optmap[fileopts.id]) == 'object')
			{
				if (typeof(Gx__EditAreaInstances[eid].editor[fileopts.id]) == 'object' && Gx__EditAreaInstances[eid].editor[fileopts.id].edited && !confirm('Reloading the edited file \'' + Gx__EditAreaInstances[eid].optmap[fileopts.id].display + '\' will lose unsaved modifications.  Continue?'))  return false;

				EAUI_CloseTab(eid, fileopts.id);
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
				$('#' + eid + '_newtab').before('<div id="' + eid + '_tabs_' + Gx__EditAreaInstances[eid].nexttab + '"><div class="barebones_single_eauieditor_wrapper"><div id="' + eid + '_toolbar_' + Gx__EditAreaInstances[eid].nexttab + '" class="barebones_single_eauieditor_toolbar"></div><div id="' + eid + '_edit_real_' + Gx__EditAreaInstances[eid].nexttab + '" class="barebones_single_eauieditor_real"' + tempstyle + '><div id="' + eid + '_edit_placeholder_' + Gx__EditAreaInstances[eid].nexttab + '" class="barebones_single_eauieditor_placeholder" style="position: absolute; top: 0; bottom: 0; left: 0; right: 0;"></div></div></div></div>');
				$('#' + eid + '_tabs').find('.ui-tabs-nav').append('<li><a href="#' + eid + '_tabs_' + Gx__EditAreaInstances[eid].nexttab + '">&nbsp;</a></li>');
				$('#' + eid + '_tabs').tabs('refresh');
				$('#' + eid + '_tabs').tabs('option', 'active', $('#' + eid + '_tabs').find('.ui-tabs-nav li').length - 1);

				$('a[href=\'#' + eid + '_tabs_' + Gx__EditAreaInstances[eid].nexttab + '\']').after(' <a href="#" class="eauieditclose" onclick="return EAUI_FileClose_ACE(\'' + eid + '\', \'' + fileopts.id + '\');">X</a>');
			}

			Gx__EditAreaInstances[eid].optmap[fileopts.id] = fileopts;
			Gx__EditAreaInstances[eid].editor[fileopts.id] = {
				edited : false,
				tabnum : Gx__EditAreaInstances[eid].nexttab,
				origcontent : ''
			};

			var aceeditor = ace.edit(eid + '_edit_placeholder_' + Gx__EditAreaInstances[eid].nexttab);
			Gx__EditAreaInstances[eid].editor[fileopts.id].aceeditor = aceeditor;
			Gx__EditAreaInstances[eid].editor[fileopts.id].acesession = aceeditor.getSession();
			EAUI_InitACE(eid, editopts, fileopts);

			Gx__EditAreaInstances[eid].nexttab++;
		}
	}
	else
	{
		var fileinfo;

		fileopts.isopen = false;
		if (typeof(editopts) == 'undefined')  editopts = { ismulti : false, create : false };
		if (typeof(editopts.ismulti) != 'boolean')  editopts.ismulti = false;
		if (typeof(editopts.create) != 'boolean')  editopts.create = (editopts.ismulti ? true : false);

		if (typeof(Gx__EditAreaInstances[eid]) == 'object' && Gx__EditAreaInstances[eid].ismulti != editopts.ismulti)
		{
			alert("CreateEditAreaInstance():  'editopts.ismulti' value must be consistent when used with the same 'eid'.");

			return false;
		}

		if (typeof(Gx__EditAreaInstances[eid]) != 'object')
		{
			if (editopts.create)
			{
				var tempstyle = '';
				if (typeof(editopts.width) == 'string' || typeof(editopts.height) == 'string')
				{
					tempstyle += ' style="';
					if (typeof(editopts.width) == 'string')  tempstyle += 'width: ' + editopts.width + ';';
					if (typeof(editopts.height) == 'string')  tempstyle += 'height: ' + editopts.height + ';';
					tempstyle += '"';
				}

				$('#' + eid).html('<textarea id="' + eid + '_edit" class="editareaedit"' + tempstyle + '></textarea><div id="' + eid + '_loader" style="display: none;"></div>');
			}

			Gx__EditAreaInstances[eid] = {
				created : editopts.create,
				displayed : true,
				ismulti : editopts.ismulti,
				closelast : (typeof(editopts.closelast) == 'function' ? editopts.closelast : undefined),
				optmap : {},
				initoptid : (editopts.ismulti ? fileopts.id : eid),
				numopen : 0
			};
			Gx__EditAreaInstances[eid].optmap[Gx__EditAreaInstances[eid].initoptid] = fileopts;

			editAreaLoader.init({
				id : eid + '_edit',
				syntax : 'php',
				syntax_selection_allow : 'php,css,js,html,xml,robotstxt,coldfusion,java,python,ruby,perl,sql,tsql,cpp,c,vb,basic,pas',
				start_highlight : true,
				is_multi_files : editopts.ismulti,
				allow_resize : 'y',
				allow_toggle : false,
				toolbar : (editopts.ismulti ? 'save,|,' : '') + 'undo,redo,|,search,go_to_line,word_wrap,|,syntax_selection,select_font,reset_highlight,|,fullscreen,charmap,|,help',
				plugins : 'charmap',
				charmap_default: 'basic latin',
				max_undo : 500,
				EA_load_callback : 'EAUI_Loaded',
				save_callback : 'EAUI_Save_EditArea',
				EA_file_close_callback : 'EAUI_FileClose_EditArea'
			});
		}
		else if (!editopts.ismulti)
		{
			if (Gx__EditAreaInstances[eid].numopen > 0)  Gx__EditAreaInstances[eid].numopen--;
			Gx__EditAreaInstances[eid].optmap[eid] = fileopts;

			EAUI_LoadContent(eid, eid);
		}
		else
		{
			if (typeof(Gx__EditAreaInstances[eid].optmap[fileopts.id]) == 'object')
			{
				var fileinfo = editAreaLoader.getFile(eid + '_edit', fileopts.id);
				if (typeof(fileinfo) == 'object' && fileinfo.edited && !confirm('Reloading the edited file \'' + Gx__EditAreaInstances[eid].optmap[fileopts.id].display + '\' will lose unsaved modifications.  Continue?'))  return false;

				delete Gx__EditAreaInstances[eid].optmap[fileopts.id];
				if (Gx__EditAreaInstances[eid].numopen > 0)  Gx__EditAreaInstances[eid].numopen--;

				editAreaLoader.closeFile(eid + '_edit', fileopts.id);
			}

			ShowEditAreaInstance(eid);
			Gx__EditAreaInstances[eid].optmap[fileopts.id] = fileopts;
			EAUI_LoadContent(eid, fileopts.id);
		}
	}

	return true;
}

// Warning:  Buggy in Firefox.  EditAreas with the same eid can't be created twice.
// This function is only relevant for EditArea.
function DestroyEditAreaInstance(eid)
{
	if (!Gx__EditAreaInstances_CanvasSupported)
	{
		if (typeof(Gx__EditAreaInstances[eid]) == 'object' && typeof(Gx__EditAreaInstances[eid].initoptid) == 'undefined')
		{
			editAreaLoader.delete_instance(eid + '_edit');
			if (Gx__EditAreaInstances[eid].created)  $('#' + eid).html('');

			delete Gx__EditAreaInstances[eid];
		}
	}
}

// This function is only relevant for EditArea.
function HideEditAreaInstance(eid)
{
	if (!Gx__EditAreaInstances_CanvasSupported)
	{
		if (typeof(Gx__EditAreaInstances[eid]) == 'object' && typeof(Gx__EditAreaInstances[eid].initoptid) == 'undefined' && Gx__EditAreaInstances[eid].displayed)
		{
			editAreaLoader.hide(eid + '_edit');

			Gx__EditAreaInstances[eid].displayed = false;
		}
	}
}

// This function is only relevant for EditArea.
function ShowEditAreaInstance(eid)
{
	if (!Gx__EditAreaInstances_CanvasSupported)
	{
		if (typeof(Gx__EditAreaInstances[eid]) == 'object' && typeof(Gx__EditAreaInstances[eid].initoptid) == 'undefined' && !Gx__EditAreaInstances[eid].displayed)
		{
			editAreaLoader.show(eid + '_edit');

			Gx__EditAreaInstances[eid].displayed = true;
		}
	}
}

// Adds a syntax highlighting mode and English string mapping to the initialization.
function EAUI_AddACEMode(name, dispname, modes, aceopts)
{
	modes[name] = 'ace/mode/' + name;
	aceopts.langmap['mode_' + name] = dispname;
}

// Initializes a newly created ACE instance with common options.
function EAUI_InitACE(eid, editopts, fileopts)
{
	var html, tempid = (editopts.ismulti ? fileopts.id : eid);
	var aceeditor = Gx__EditAreaInstances[eid].editor[tempid].aceeditor;
	var acesession = Gx__EditAreaInstances[eid].editor[tempid].acesession;

	var aceopts, toollist, toolmap;

	aceopts = {
		'theme' : 'textmate',
		'fontsize' : '14px',
		'fulllineselect' : true,
		'highlightline' : true,
		'invisiblechars' : false,
		'persisthscroll' : false,
		'animatescrolling' : false,
		'useworkers' : false,
		'defaultsyntax' : 'php',
		'keybinding' : 'ace',
		'scrollspeed' : 3,
		'wrap' : false,
		'gutter' : true,
		'printmargin' : false,
		'tabsize' : 4,
		'softtab' : false,
		'highlightselected' : true,
		'behaviors' : false,
		'codefolding' : 'markbegin',
		'fadefolds' : true,
		'readonly' : false,
		'toolbar' : (editopts.ismulti ? 'save,|,' : '') + 'undo,redo,|,search,goto,wrap,|,syntaxselect,fontsize,keybinding,|,help',
		'syntaxselect' : 'php,css,javascript,html,json,xml,text',
		'syntaxaddcallback' : null,
		'fontsizeselect' : '9px,10px,11px,12px,13px,14px,15px,16px,17px,18px,19px,20px,24px,28px,30px',
		'keybindingselect' : 'ace,vim,emacs',
		'resize' : true,
		'focus' : false,
		'langmap' : {
			'save' : 'Save',
			'undo' : 'Undo',
			'redo' : 'Redo',
			'search' : 'Find/Replace',
			'goto' : 'Goto Line',
			'wrap' : 'Word Wrap',
			'help' : 'Help',

			'searchtip' : "Search the document with optional replacement.",
			'findmissingerror' : "Please fill in the 'Find' field.",
			'finderror' : 'Unable to find the selected text in the document.',
			'find' : 'Find',
			'replacewith' : 'Replace With',
			'options' : 'Options',
			'backwards' : 'Backwards',
			'wrap' : 'Wrap',
			'casesensitive' : 'Case sensitive',
			'wholeword' : 'Whole word',
			'regexp' : 'Regular expression',
			'next' : 'Next',
			'prev' : 'Previous',
			'replace' : 'Replace',
			'replaceall' : 'Replace All',
			'cancel' : 'Cancel',

			'mode' : 'Syntax Highlighting Mode',

			'fontsize' : 'Font Size',

			'keybinding' : 'Keybinding',
			'keybinding_ace' : 'ACE',
			'keybinding_vim' : 'Vim',
			'keybinding_emacs' : 'Emacs',

			'helpbox' : 'ACE (Ajax.org Cloud9 Editor) is an in-browser code editor (MPL/MIT/LGPL).\n\nThe multi-file support, AJAX wrapper, and toolbar all operate under the Barebones CMS license (MIT/LGPL).\n\nSupport for Barebones CMS can be found at:  http://barebonescms.com/'
		}
	};

	// Set up modes and English language mappings.
	Gx__EditAreaInstances[eid].editor[tempid].acemodes = {};
	EAUI_AddACEMode("php", "PHP", Gx__EditAreaInstances[eid].editor[tempid].acemodes, aceopts);
	EAUI_AddACEMode("css", "CSS", Gx__EditAreaInstances[eid].editor[tempid].acemodes, aceopts);
	EAUI_AddACEMode("javascript", "Javascript", Gx__EditAreaInstances[eid].editor[tempid].acemodes, aceopts);
	EAUI_AddACEMode("html", "HTML", Gx__EditAreaInstances[eid].editor[tempid].acemodes, aceopts);
	EAUI_AddACEMode("json", "JSON", Gx__EditAreaInstances[eid].editor[tempid].acemodes, aceopts);
	EAUI_AddACEMode("xml", "XML", Gx__EditAreaInstances[eid].editor[tempid].acemodes, aceopts);
	EAUI_AddACEMode("text", "Text", Gx__EditAreaInstances[eid].editor[tempid].acemodes, aceopts);

	// Merge options.
	if (typeof(fileopts.aceopts) == 'object')  aceopts = $.extend(aceopts, fileopts.aceopts);

	// Let the caller add any additional modes to the editor.
	if (typeof(aceopts.syntaxaddcallback) == 'function')  aceopts.syntaxaddcallback.call(Gx__EditAreaInstances[eid].editor[tempid].acemodes, aceopts);

	Gx__EditAreaInstances[eid].editor[tempid].acekeybindings = {
		ace : null,
		vim : require("ace/keyboard/vim").handler,
		emacs : require("ace/keyboard/emacs").handler
	};

	// Set the editor options.
	if (aceopts.theme != 'textmate')  aceeditor.setTheme("ace/theme/" + aceopts.theme);
	aceeditor.setFontSize(aceopts.fontsize);
	aceeditor.setSelectionStyle(aceopts.fulllineselect ? "line" : "text");
	aceeditor.setHighlightActiveLine(aceopts.highlightline);
	aceeditor.setShowInvisibles(aceopts.invisiblechars);
	aceeditor.renderer.setHScrollBarAlwaysVisible(aceopts.persisthscroll);
    aceeditor.setAnimatedScroll(aceopts.animatescrolling);

	// BUG:  ACE workers continue reporting issues even after the mode is switched.  This feature is disabled by default for that reason and the fact that workers are heavy on resources.
	acesession.setUseWorker(aceopts.useworkers);

	acesession.setMode(Gx__EditAreaInstances[eid].editor[tempid].acemodes[aceopts.defaultsyntax]);
	aceeditor.setKeyboardHandler(Gx__EditAreaInstances[eid].editor[tempid].acekeybindings[aceopts.keybinding]);
	aceeditor.setScrollSpeed(aceopts.scrollspeed);
	if (aceopts.wrap)
	{
		acesession.setUseWrapMode(true);
		acesession.setWrapLimitRange(null, null);
		aceeditor.renderer.setPrintMarginColumn(80);
	}
	else
	{
		acesession.setUseWrapMode(false);
		aceeditor.renderer.setPrintMarginColumn(80);
	}
	aceeditor.renderer.setShowGutter(aceopts.gutter);
	aceeditor.renderer.setShowPrintMargin(aceopts.printmargin);
	acesession.setTabSize(aceopts.tabsize);
	acesession.setUseSoftTabs(aceopts.softtab);
	aceeditor.setHighlightSelectedWord(aceopts.highlightselected);
	aceeditor.setBehavioursEnabled(aceopts.behaviors);
	acesession.setFoldStyle(aceopts.codefolding);
    aceeditor.setShowFoldWidgets(aceopts.codefolding !== "manual");
    aceeditor.setFadeFoldWidgets(aceopts.fadefolds);
    aceeditor.setReadOnly(aceopts.readonly);

	// Create the toolbar.
	var tag, imgtag, selecttag, selectfunc;
	var html = $('#' + eid + '_toolbar' + (editopts.ismulti ? '_' + Gx__EditAreaInstances[eid].editor[tempid].tabnum : ''));
	var list = aceopts.toolbar.toLowerCase().split(",");
	for (var x in list)
	{
		tag = null;
		imgtag = null;
		selectfunc = null;
		x = $.trim(list[x]);
		switch (x)
		{
			case 'save':
			{
				if (editopts.ismulti)
				{
					imgtag = $('<img src="' + Gx__RootURL + '/' + Gx__SupportPath + '/ajaxorg_ace/images/save.gif" alt="' + aceopts.langmap.save + '" />');
					tag = $('<a href="#" title="' + aceopts.langmap.save + '"></a>').click(function() {
						EAUI_Save_ACE(eid, fileopts.id);

						return false;
					});
				}

				break;
			}
			case 'undo':
			{
				imgtag = $('<img src="' + Gx__RootURL + '/' + Gx__SupportPath + '/ajaxorg_ace/images/undo.gif" alt="' + aceopts.langmap.undo + '" />');

				tag = $('<a href="#" title="' + aceopts.langmap.undo + '"></a>').click(function(e) {
					aceeditor.undo();

					return false;
				});

				var undotag = imgtag;
				if (!acesession.getUndoManager().hasUndo())  undotag.addClass('barebones_single_eauieditor_toolbar_disabled');
				acesession.on('change', function() {
					setTimeout(function() {
						if (acesession.getUndoManager().hasUndo())  undotag.removeClass('barebones_single_eauieditor_toolbar_disabled');
						else  undotag.addClass('barebones_single_eauieditor_toolbar_disabled');
					}, 50);
				});

				break;
			}
			case 'redo':
			{
				imgtag = $('<img src="' + Gx__RootURL + '/' + Gx__SupportPath + '/ajaxorg_ace/images/redo.gif" alt="' + aceopts.langmap.redo + '" />');
				tag = $('<a href="#" title="' + aceopts.langmap.redo + '"></a>').click(function(e) {
					aceeditor.redo();

					return false;
				});

				var redotag = imgtag;
				if (!acesession.getUndoManager().hasRedo())  redotag.addClass('barebones_single_eauieditor_toolbar_disabled');
				acesession.on('change', function() {
					setTimeout(function() {
						if (acesession.getUndoManager().hasRedo())  redotag.removeClass('barebones_single_eauieditor_toolbar_disabled');
						else  redotag.addClass('barebones_single_eauieditor_toolbar_disabled');
					}, 50);
				});

				break;
			}
			case 'search':
			{
				imgtag = $('<img src="' + Gx__RootURL + '/' + Gx__SupportPath + '/ajaxorg_ace/images/search.gif" alt="' + aceopts.langmap.search + '" />');
				tag = $('<a href="#" title="' + aceopts.langmap.search + '"></a>').click(function(e) {
					var html = '<form class="barebones_single_eauieditor_form">';
					html += '<div id="' + eid + '_search_tip" class="barebones_single_eauieditor_tip">' + aceopts.langmap.searchtip + '</div>';
					html += '<table><tr><td>';
					html += '<div class="formitem"><div class="formitemtitle">' + aceopts.langmap.find + '</div><input type="text" class="text ui-widget-content ui-corner-all" name="find" id="' + eid + '_search_find" /></div>';
					html += '<div class="formitem formitem2"><div class="formitemtitle">' + aceopts.langmap.replacewith + '</div><input type="text" class="text ui-widget-content ui-corner-all" name="replacewith" id="' + eid + '_search_replacewith" /></div>';
					html += '</td><td style="white-space: nowrap; width: 1px;">';
					html += '<div class="formitem"><div class="formitemtitle">' + aceopts.langmap.options + '</div>';
					html += '<input type="checkbox" class="checkbox" name="backwards" id="' + eid + '_search_backwards" /> <label for="' + eid + '_search_backwards">' + aceopts.langmap.backwards + '</label><br />';
					html += '<input type="checkbox" class="checkbox" name="wrap" id="' + eid + '_search_wrap" checked="checked" /> <label for="' + eid + '_search_wrap">' + aceopts.langmap.wrap + '</label><br />';
					html += '<input type="checkbox" class="checkbox" name="casesensitive" id="' + eid + '_search_casesensitive" /> <label for="' + eid + '_search_casesensitive">' + aceopts.langmap.casesensitive + '</label><br />';
					html += '<input type="checkbox" class="checkbox" name="wholeword" id="' + eid + '_search_wholeword" /> <label for="' + eid + '_search_wholeword">' + aceopts.langmap.wholeword + '</label><br />';
					html += '<input type="checkbox" class="checkbox" name="regexp" id="' + eid + '_search_regexp" /> <label for="' + eid + '_search_regexp">' + aceopts.langmap.regexp + '</label>';
					html += '</div></td></tr></table>';
					html += '</form>';

					var buttons = {};
					var lastsearch = '';
					buttons[aceopts.langmap.prev] = function() {
						var searchtip = $('#' + eid + '_search_tip');
						var searchfind = $('#' + eid + '_search_find');

						if (searchfind.val() == "")
						{
							searchfind.addClass('ui-state-error');
							searchtip.text(aceopts.langmap.findmissingerror).addClass('ui-state-highlight');
							setTimeout(function() { searchtip.removeClass('ui-state-highlight', 1500); }, 500);
						}
						else
						{
							searchfind.removeClass('ui-state-error');
							if (lastsearch != searchfind.val())
							{
								aceeditor.find(searchfind.val(), {
									'backwards' : !$('#' + eid + '_search_backwards').is(':checked'),
									'wrap' : $('#' + eid + '_search_wrap').is(':checked'),
									'caseSensitive' : $('#' + eid + '_search_casesensitive').is(':checked'),
									'wholeWord' : $('#' + eid + '_search_wholeword').is(':checked'),
									'regExp' : $('#' + eid + '_search_regexp').is(':checked')
								});

								lastsearch = searchfind.val();
							}
							else if ($('#' + eid + '_search_backwards').is(':checked'))  aceeditor.findNext();
							else  aceeditor.findPrevious();

							if (aceeditor.getSelectionRange())  searchtip.text(aceopts.langmap.searchtip);
							else
							{
								searchtip.text(aceopts.langmap.finderror).addClass("ui-state-highlight");
								setTimeout(function() { searchtip.removeClass("ui-state-highlight", 1500); }, 500);
							}
						}
					};

					buttons[aceopts.langmap.next] = function() {
						var searchtip = $('#' + eid + '_search_tip');
						var searchfind = $('#' + eid + '_search_find');

						if (searchfind.val() == '')
						{
							searchfind.addClass('ui-state-error');
							searchtip.text(aceopts.langmap.findmissingerror).addClass('ui-state-highlight');
							setTimeout(function() { searchtip.removeClass('ui-state-highlight', 1500); }, 500);
						}
						else
						{
							searchfind.removeClass('ui-state-error');
							if (lastsearch != searchfind.val())
							{
								aceeditor.find(searchfind.val(), {
									'backwards' : $('#' + eid + '_search_backwards').is(':checked'),
									'wrap' : $('#' + eid + '_search_wrap').is(':checked'),
									'caseSensitive' : $('#' + eid + '_search_casesensitive').is(':checked'),
									'wholeWord' : $('#' + eid + '_search_wholeword').is(':checked'),
									'regExp' : $('#' + eid + '_search_regexp').is(':checked')
								});

								lastsearch = searchfind.val();
							}
							else if ($('#' + eid + '_search_backwards').is(':checked'))  aceeditor.findPrevious();
							else  aceeditor.findNext();

							if (aceeditor.getSelectionRange())  searchtip.text(aceopts.langmap.searchtip);
							else
							{
								searchtip.text(aceopts.langmap.finderror).addClass('ui-state-highlight');
								setTimeout(function() { searchtip.removeClass('ui-state-highlight', 1500); }, 500);
							}
						}
					};

					buttons[aceopts.langmap.replace] = function() {
						var searchtip = $('#' + eid + '_search_tip');
						var searchfind = $('#' + eid + '_search_find');

						if (searchfind.val() == '')
						{
							searchfind.addClass('ui-state-error');
							searchtip.text(aceopts.langmap.findmissingerror).addClass('ui-state-highlight');
							setTimeout(function() { searchtip.removeClass('ui-state-highlight', 1500); }, 500);
						}
						else
						{
							searchfind.removeClass('ui-state-error');
							if (lastsearch != searchfind.val())
							{
								aceeditor.find(searchfind.val(), {
									'backwards' : $('#' + eid + '_search_backwards').is(':checked'),
									'wrap' : $('#' + eid + '_search_wrap').is(':checked'),
									'caseSensitive' : $('#' + eid + '_search_casesensitive').is(':checked'),
									'wholeWord' : $('#' + eid + '_search_wholeword').is(':checked'),
									'regExp' : $('#' + eid + '_search_regexp').is(':checked')
								});

								lastsearch = searchfind.val();
							}
							else
							{
								var replacewith = $('#' + eid + '_search_replacewith').val();
								// These lines of code are necessary because aceeditor.replace() is broken.
								range = aceeditor.getSelectionRange();
								if (range !== null)
								{
									aceeditor.$tryReplace(range, replacewith);
									if (range !== null)  aceeditor.selection.setSelectionRange(range);
								}

								if ($('#' + eid + '_search_backwards').is(':checked'))  aceeditor.findPrevious();
								else  aceeditor.findNext();
							}

							if (aceeditor.getSelectionRange())  searchtip.text(aceopts.langmap.searchtip);
							else
							{
								searchtip.text(aceopts.langmap.finderror).addClass('ui-state-highlight');
								setTimeout(function() { searchtip.removeClass('ui-state-highlight', 1500); }, 500);
							}
						}
					};

					buttons[aceopts.langmap.replaceall] = function() {
						var searchtip = $('#' + eid + '_search_tip');
						var searchfind = $('#' + eid + '_search_find');

						if (searchfind.val() == '')
						{
							searchfind.addClass('ui-state-error');
							searchtip.text(aceopts.langmap.findmissingerror).addClass('ui-state-highlight');
							setTimeout(function() { searchtip.removeClass('ui-state-highlight', 1500); }, 500);
						}
						else
						{
							searchfind.removeClass('ui-state-error');
							if (lastsearch != searchfind.val())
							{
								aceeditor.find(searchfind.val(), {
									'backwards' : $('#' + eid + '_search_backwards').is(':checked'),
									'wrap' : $('#' + eid + '_search_wrap').is(':checked'),
									'caseSensitive' : $('#' + eid + '_search_casesensitive').is(':checked'),
									'wholeWord' : $('#' + eid + '_search_wholeword').is(':checked'),
									'regExp' : $('#' + eid + '_search_regexp').is(':checked')
								});

								lastsearch = searchfind.val();
							}

							if (aceeditor.getSelectionRange())
							{
								var replacewith = $('#' + eid + '_search_replacewith').val();
								searchtip.text(aceopts.langmap.searchtip);
								aceeditor.replaceAll(replacewith);
							}
							else
							{
								searchtip.text(aceopts.langmap.finderror).addClass('ui-state-highlight');
								setTimeout(function() { searchtip.removeClass('ui-state-highlight', 1500); }, 500);
							}
						}
					};

					buttons[aceopts.langmap.cancel] = function() {
						$(this).dialog('close');
					};

					$('#' + eid + '_dialog').attr('title', aceopts.langmap.search).html(html).dialog({
						'height' : 305,
						'width' : 520,
						'minWidth' : 520,
						'minHeight' : 305,
						'dialogClass' : 'barebones_single_eauieditor_dialog',
						'modal' : true,
						'buttons' : buttons
					});

					return false;
				});

				break;
			}
			case 'goto':
			{
				imgtag = $('<img src="' + Gx__RootURL + '/' + Gx__SupportPath + '/ajaxorg_ace/images/go_to_line.gif" alt="' + aceopts.langmap.goto + '" />');
				tag = $('<a href="#" title="' + aceopts.langmap.goto + '"></a>').click(function(e) {
					var linenum = prompt('Goto Line:');
					if (linenum)  aceeditor.gotoLine(linenum);

					return false;
				});

				break;
			}
			case 'wrap':
			{
				imgtag = $('<img src="' + Gx__RootURL + '/' + Gx__SupportPath + '/ajaxorg_ace/images/word_wrap.gif" alt="' + aceopts.langmap.wrap + '" />');
				if (aceopts.wrap)  imgtag.addClass('barebones_single_eauieditor_toolbar_selected');

				tag = $('<a href="#" title="' + aceopts.langmap.wrap + '"></a>').click(function(e) {
					if (acesession.getUseWrapMode())
					{
						acesession.setUseWrapMode(false);
						aceeditor.renderer.setPrintMarginColumn(80);
						$(this).find('img').removeClass('barebones_single_eauieditor_toolbar_selected');
					}
					else
					{
						acesession.setUseWrapMode(true);
						acesession.setWrapLimitRange(null, null);
						aceeditor.renderer.setPrintMarginColumn(80);
						$(this).find('img').addClass('barebones_single_eauieditor_toolbar_selected');
					}

					return false;
				});

				break;
			}
			case 'syntaxselect':
			{
				var output = [];
				var list2 = aceopts.syntaxselect.toLowerCase().split(",");
				for (var x2 in list2)
				{
					x2 = $.trim(list2[x2]);
					if (Gx__EditAreaInstances[eid].editor[tempid].acemodes[x2])  output.push('<option value="' + x2 + '"' + (aceopts.defaultsyntax == x2 ? ' selected="selected"' : '') + '>' + aceopts.langmap['mode_' + x2] + '</option>');
				}

				tag = $('<select title="' + aceopts.langmap.mode + '" />').html(output.join(''));
				Gx__EditAreaInstances[eid].editor[tempid].syntaxselecttag = tag;
				selectfunc = function(e) {
					acesession.setMode(Gx__EditAreaInstances[eid].editor[tempid].acemodes[$(this).val()]);
				};

				break;
			}
			case 'fontsize':
			{
				var output = [];
				var list2 = aceopts.fontsizeselect.toLowerCase().split(",");
				for (var x2 in list2)
				{
					x2 = $.trim(list2[x2]);
					output.push('<option value="' + x2 + '"' + (aceopts.fontsize == x2 ? ' selected="selected"' : '') + '>' + x2 + '</option>');
				}

				tag = $('<select title="' + aceopts.langmap.fontsize + '" />').html(output.join(''));
				selectfunc = function(e) {
					aceeditor.setFontSize($(this).val());
				};

				break;
			}
			case 'keybinding':
			{
				var output = [];
				var list2 = aceopts.keybindingselect.toLowerCase().split(",");
				for (var x2 in list2)
				{
					x2 = $.trim(list2[x2]);
					if (typeof(Gx__EditAreaInstances[eid].editor[tempid].acekeybindings[x2]) != 'undefined')  output.push('<option value="' + x2 + '"' + (aceopts.keybinding == x2 ? ' selected="selected"' : '') + '>' + aceopts.langmap['keybinding_' + x2] + '</option>');
				}

				tag = $('<select title="' + aceopts.langmap.keybinding + '" />').html(output.join(''));
				selectfunc = function(e) {
					aceeditor.setKeyboardHandler(Gx__EditAreaInstances[eid].editor[tempid].acekeybindings[$(this).val()]);
				};

				break;
			}
			case 'help':
			{
				imgtag = $('<img src="' + Gx__RootURL + '/' + Gx__SupportPath + '/ajaxorg_ace/images/help.gif" alt="' + aceopts.langmap.help + '" />');
				tag = $('<a href="#" title="' + aceopts.langmap.help + '"></a>').click(function(e) {
					alert(aceopts.langmap.helpbox);

					return false;
				});

				break;
			}
			case '|':
			{
				tag = $('<img class="barebones_single_eauieditor_toolbar_spacer" src="' + Gx__RootURL + '/' + Gx__SupportPath + '/ajaxorg_ace/images/spacer.gif" />');

				break;
			}
		}

		if (imgtag != null)
		{
			imgtag.hover(
				function() {
					$(this).addClass('barebones_single_eauieditor_toolbar_hover');
				},
				function() {
					$(this).removeClass('barebones_single_eauieditor_toolbar_hover');
				}
			);
			if (tag != null)  tag.append(imgtag);
		}
		if (selectfunc != null)  tag.change(selectfunc).keyup(selectfunc);
		if (tag != null)  html.append(tag);
	}

	// Create the resizeable (if set).
	if (aceopts.resize)
	{
		$('#' + eid + '_edit_real' + (editopts.ismulti ? '_' + Gx__EditAreaInstances[eid].editor[tempid].tabnum : '')).resizable({
			handles : 's',
			resize: function(e, ui) {
				aceeditor.resize();
				aceeditor.focus();
			}
		});
	}

	// Apply an asterisk if the content area has changed ('ismulti' only).  Remove it if the content exactly matches the original.  Also sets the edited flag.
	if (editopts.ismulti)
	{
		acesession.on('change', function() {
			var curreditor = Gx__EditAreaInstances[eid].editor[tempid];

			if (curreditor.orignumlines > -1 && curreditor.orignumlines != curreditor.aceeditor.session.getLength())
			{
				if (!curreditor.edited)
				{
					$('a[href=\'#' + eid + '_tabs_' + curreditor.tabnum + '\']').html('* ' + Gx__EditAreaInstances[eid].optmap[tempid].display);
					Gx__EditAreaInstances[eid].editor[tempid].edited = true;
				}

				return;
			}

			var temp = acesession.getValue();
			if (curreditor.origcontent == temp && curreditor.edited)
			{
				$('a[href=\'#' + eid + '_tabs_' + curreditor.tabnum + '\']').html(Gx__EditAreaInstances[eid].optmap[tempid].display);
				Gx__EditAreaInstances[eid].editor[tempid].edited = false;
			}
			else if (curreditor.origcontent != temp && !curreditor.edited)
			{
				$('a[href=\'#' + eid + '_tabs_' + curreditor.tabnum + '\']').html('* ' + Gx__EditAreaInstances[eid].optmap[tempid].display);
				Gx__EditAreaInstances[eid].editor[tempid].edited = true;
			}
		});
	}

	// Set the focus.
	if (aceopts.focus)  aceeditor.focus();

	// Load the content.
	EAUI_LoadContent(eid, tempid);

	if (typeof(Gx__EditAreaInstances[eid].initoptid) != 'undefined')  delete Gx__EditAreaInstances[eid].initoptid;
}

function EAUI_ContentLoaded(eid, id, content)
{
	var tempsyntax = Gx__EditAreaInstances[eid].optmap[id].syntax;
	if (Gx__EditAreaInstances_CanvasSupported)
	{
		if (Gx__EditAreaInstances[eid].ismulti)  $('a[href=\'#' + eid + '_tabs_' + Gx__EditAreaInstances[eid].editor[id].tabnum + '\']').html(Gx__EditAreaInstances[eid].optmap[id].display);

		// Maps EditArea syntax options to ACE.
		var altmap = {
			'c' : 'c_cpp',
			'cpp' : 'c_cpp',
			'js' : 'javascript',
			'rss' : 'xml',
			'vb' : 'vbscript'
		};
		if (altmap[tempsyntax])  tempsyntax = altmap[tempsyntax];
		if (!Gx__EditAreaInstances[eid].editor[id].acemodes[tempsyntax])  tempsyntax = 'text';

		Gx__EditAreaInstances[eid].editor[id].acesession.setMode(Gx__EditAreaInstances[eid].editor[id].acemodes[tempsyntax]);
		if (Gx__EditAreaInstances[eid].editor[id].syntaxselecttag)  Gx__EditAreaInstances[eid].editor[id].syntaxselecttag.val(tempsyntax);
		Gx__EditAreaInstances[eid].editor[id].origcontent = content;
		Gx__EditAreaInstances[eid].editor[id].acesession.setValue(content);
		Gx__EditAreaInstances[eid].editor[id].orignumlines = Gx__EditAreaInstances[eid].editor[id].aceeditor.session.getLength();
		Gx__EditAreaInstances[eid].editor[id].edited = false;
	}
	else
	{
		// No plain-text syntax, so HTML fallback.
		var altmap = {
			'php' : true,
			'css' : true,
			'js' : true,
			'html' : true,
			'xml' : true,
			'robotstxt' : true,
			'coldfusion' : true,
			'java' : true,
			'python' : true,
			'ruby' : true,
			'perl' : true,
			'sql' : true,
			'tsql' : true,
			'cpp' : true,
			'c' : true,
			'vb' : true,
			'basic' : true,
			'pas' : true,
		};
		if (!altmap[tempsyntax])  tempsyntax = 'html';

		if (Gx__EditAreaInstances[eid].ismulti)  editAreaLoader.openFile(eid + '_edit', { id : id, title : Gx__EditAreaInstances[eid].optmap[id].display, text : content, syntax : tempsyntax });
		else  editAreaLoader.setValue(eid + '_edit', content);
	}

	if (!Gx__EditAreaInstances[eid].optmap[id].isopen)
	{
		Gx__EditAreaInstances[eid].numopen++;
		Gx__EditAreaInstances[eid].optmap[id].isopen = true;
	}
}

var Gx__EditAreaInstances_Loader = [];

function EAUI_URLContentLoaded(responsetext, requeststatus, request)
{
	var data;

	data = Gx__EditAreaInstances_Loader.shift();
	if (typeof(Gx__EditAreaInstances[data.eid]) == 'object' && typeof(Gx__EditAreaInstances[data.eid].optmap[data.id]) == 'object')  EAUI_ContentLoaded(data.eid, data.id, unescape(responsetext));
	$('#' + data.eid + '_loader').html('');

	if (Gx__EditAreaInstances_Loader.length)  EAUI_URLContentLoad();
}

function EAUI_URLContentLoad()
{
	$('#' + Gx__EditAreaInstances_Loader[0].eid + '_loader').load(Gx__EditAreaInstances_Loader[0].loadurl, Gx__EditAreaInstances_Loader[0].loadparams, EAUI_URLContentLoaded);
}

function EAUI_LoadContent(eid, id)
{
	var opts;

	if (typeof(Gx__EditAreaInstances[eid]) == 'object' && typeof(Gx__EditAreaInstances[eid].optmap[id]) == 'object')
	{
		opts = Gx__EditAreaInstances[eid].optmap[id];
		if (typeof(opts.loadurl) == 'string')
		{
			Gx__EditAreaInstances_Loader.push({ eid : eid, id : id, loadurl : opts.loadurl, loadparams : opts.loadparams });
			if (Gx__EditAreaInstances_Loader.length == 1)  EAUI_URLContentLoad();
		}
		else if (typeof(opts.loadcontent) == 'string')
		{
			EAUI_ContentLoaded(eid, id, opts.loadcontent);
		}
		else if (Gx__EditAreaInstances[eid].ismulti)
		{
			alert('EAUI_LoadContent():  CreateEditAreaInstance() called without a content loading option.');
		}
		else
		{
			if (!opts.isopen)
			{
				Gx__EditAreaInstances[eid].numopen++;
				Gx__EditAreaInstances[eid].optmap[id].isopen = true;
			}
		}
	}
}

// Only used for EditArea.
function EAUI_Loaded(eid)
{
	eid = eid.substring(0, eid.length - 5);

	if (typeof(Gx__EditAreaInstances[eid]) == 'object' && typeof(Gx__EditAreaInstances[eid].initoptid) == 'string')
	{
		EAUI_LoadContent(eid, Gx__EditAreaInstances[eid].initoptid);
		delete Gx__EditAreaInstances[eid].initoptid;
	}
}

var Gx__EditAreaInstances_Saver = [];

function EAUI_URLContentSaved(responsetext, requeststatus, request)
{
	var data;

	data = Gx__EditAreaInstances_Saver.shift();
	if (typeof(Gx__EditAreaInstances[data.eid]) == 'object' && typeof(Gx__EditAreaInstances[data.eid].optmap[data.id]) == 'object')
	{
		if ($.trim(responsetext).substring(0, 2) != 'OK')  alert('File \'' + Gx__EditAreaInstances[data.eid].optmap[data.id].display + '\' not saved.  Try again later.\n\n' + responsetext);
		else if (!Gx__EditAreaInstances_CanvasSupported)  editAreaLoader.setFileEditedMode(data.eid + '_edit', data.id, false);
		else
		{
			Gx__EditAreaInstances[data.eid].editor[data.id].origcontent = data.params.content;
			Gx__EditAreaInstances[data.eid].editor[data.id].orignumlines = Gx__EditAreaInstances[data.eid].editor[data.id].aceeditor.session.getLength();
			$('a[href=\'#' + data.eid + '_tabs_' + Gx__EditAreaInstances[data.eid].editor[data.id].tabnum + '\']').html(Gx__EditAreaInstances[data.eid].optmap[data.id].display);
			Gx__EditAreaInstances[data.eid].editor[data.id].edited = false;
		}
	}
	$('#' + data.eid + '_loader').html('');

	if (Gx__EditAreaInstances_Saver.length)  EAUI_URLContentSave();
}

function EAUI_URLContentSave()
{
	$('#' + Gx__EditAreaInstances_Saver[0].eid + '_loader').load(Gx__EditAreaInstances_Saver[0].url, Gx__EditAreaInstances_Saver[0].params, EAUI_URLContentSaved);
}

function EAUI_Save_ACE(eid, id)
{
	var params, file;

	if (typeof(Gx__EditAreaInstances[eid]) == 'object' && Gx__EditAreaInstances[eid].ismulti && typeof(Gx__EditAreaInstances[eid].optmap[id]) == 'object')
	{
		if (Gx__EditAreaInstances[eid].editor[id].edited && typeof(Gx__EditAreaInstances[eid].optmap[id].saveurl) != 'undefined')
		{
			params = Gx__EditAreaInstances[eid].optmap[id].saveparams;
			if (typeof(params) == 'undefined')  params = {};
			params.content = Gx__EditAreaInstances[eid].editor[id].acesession.getValue();
			Gx__EditAreaInstances_Saver.push({ eid : eid, id : id, url : Gx__EditAreaInstances[eid].optmap[id].saveurl, params : params });
			if (Gx__EditAreaInstances_Saver.length == 1)  EAUI_URLContentSave();
		}
	}
}

function EAUI_Save_EditArea(eid, content)
{
	var params, file;

	eid = eid.substring(0, eid.length - 5);

	if (typeof(Gx__EditAreaInstances[eid]) == 'object' && Gx__EditAreaInstances[eid].ismulti)
	{
		file = editAreaLoader.getCurrentFile(eid + '_edit');
		if (typeof(file) == 'object' && file.edited && typeof(Gx__EditAreaInstances[eid].optmap[file.id]) == 'object' && typeof(Gx__EditAreaInstances[eid].optmap[file.id].saveurl) != 'undefined')
		{
			params = Gx__EditAreaInstances[eid].optmap[file.id].saveparams;
			if (typeof(params) == 'undefined')  params = {};
			params.content = content;
			Gx__EditAreaInstances_Saver.push({ eid : eid, id : file.id, url : Gx__EditAreaInstances[eid].optmap[file.id].saveurl, params : params });
			if (Gx__EditAreaInstances_Saver.length == 1)  EAUI_URLContentSave();
		}
	}
}

function EAUI_FileClose_ACE(eid, id)
{
	var retval;

	if (typeof(Gx__EditAreaInstances[eid]) == 'object' && Gx__EditAreaInstances[eid].ismulti && typeof(Gx__EditAreaInstances[eid].optmap[id]) == 'object')
	{
		retval = true;
		if (Gx__EditAreaInstances[eid].editor[id].edited && typeof(Gx__EditAreaInstances[eid].optmap[id].saveurl) != 'undefined')  retval = confirm('The file \'' + Gx__EditAreaInstances[eid].optmap[id].display + '\' has not been saved.  Close anyway?');

		if (retval)
		{
			EAUI_CloseTab(eid, id);
			if (Gx__EditAreaInstances[eid].numopen == 0 && typeof(Gx__EditAreaInstances[eid].closelast) == 'function')  Gx__EditAreaInstances[eid].closelast(eid);
		}
	}

	return false;
}

function EAUI_CloseTab(eid, id)
{
	if (typeof(Gx__EditAreaInstances[eid]) == 'object' && typeof(Gx__EditAreaInstances[eid].editor[id]) == 'object')
	{
		if (typeof(Gx__EditAreaInstances[eid].optmap[id]) == 'object')
		{
			Gx__EditAreaInstances[eid].editor[id].aceeditor.destroy();

			// Can't just simply do a '.tabs('remove', '#id');'
			// http://dev.jqueryui.com/ticket/3171  (feature was slated for jQuery UI 1.8)
			$('li[aria-controls=' + eid + '_tabs_' + Gx__EditAreaInstances[eid].editor[id].tabnum + ']').remove();
			$('#' + eid + '_tabs_' + Gx__EditAreaInstances[eid].editor[id].tabnum).remove();
			$('#' + eid + '_tabs').tabs('refresh');

			delete Gx__EditAreaInstances[eid].editor[id];
			delete Gx__EditAreaInstances[eid].optmap[id];
			if (Gx__EditAreaInstances[eid].numopen > 0)  Gx__EditAreaInstances[eid].numopen--;
		}
	}

	return true;
}

function EAUI_FileClose_EditArea(file)
{
	var retval;

	retval = true;
	for (var eid in Gx__EditAreaInstances)
	{
		if (typeof(Gx__EditAreaInstances[eid]) == 'object' && Gx__EditAreaInstances[eid].ismulti && typeof(Gx__EditAreaInstances[eid].optmap[file.id]) == 'object')
		{
			if (typeof(file) == 'object' && file.edited && typeof(Gx__EditAreaInstances[eid].optmap[file.id].saveurl) != 'undefined')  retval = confirm('The file \'' + file.title + '\' has not been saved.  Close anyway?');

			if (retval)
			{
				delete Gx__EditAreaInstances[eid].optmap[file.id];
				if (Gx__EditAreaInstances[eid].numopen > 0)  Gx__EditAreaInstances[eid].numopen--;
				if (Gx__EditAreaInstances[eid].numopen == 0 && typeof(Gx__EditAreaInstances[eid].closelast) == 'function')  Gx__EditAreaInstances[eid].closelast(eid);
			}

			break;
		}
	}

	return retval;
}

function EAUI_OnBeforeUnload()
{
	var unsavedfiles = [];

	for (var eid in Gx__EditAreaInstances)
	{
		if (typeof(Gx__EditAreaInstances[eid]) == 'object' && Gx__EditAreaInstances[eid].ismulti)
		{
			for (var id in Gx__EditAreaInstances[eid].optmap)
			{
				if (Gx__EditAreaInstances_CanvasSupported)
				{
					if (typeof(Gx__EditAreaInstances[eid].editor[id]) == 'object' && Gx__EditAreaInstances[eid].editor[id].edited)  unsavedfiles.push(Gx__EditAreaInstances[eid].optmap[id].display);
				}
				else
				{
					var fileinfo = editAreaLoader.getFile(eid + '_edit', id);
					if (typeof(fileinfo) == 'object' && fileinfo.edited)  unsavedfiles.push(Gx__EditAreaInstances[eid].optmap[id].display);
				}
			}
		}
	}

	if (unsavedfiles.length)  return 'The following file' + (unsavedfiles.length == 1 ? ' is' : 's are') + ' not saved:\n\n\t\'' + unsavedfiles.join('\',\n\t\'') + '\'';
}

if (typeof(window.AddOnBeforeUnload) == 'function')  AddOnBeforeUnload(EAUI_OnBeforeUnload);
else  window.onbeforeunload = EAUI_OnBeforeUnload;

if (Gx__EditAreaInstances_CanvasSupported)
{
	if (typeof(window.LoadConditionalScript) == 'function')
	{
		LoadCSS(Gx__RootURL + '/' + Gx__SupportPath + '/jquery_ui_themes/smoothness/jquery-ui-1.10.4.css?_=20140418', 'all');
		LoadCSS(Gx__RootURL + '/' + Gx__SupportPath + '/css/aceeditor.css?_=20140418', 'all');
		LoadConditionalScript(Gx__RootURL + '/' + Gx__SupportPath + '/jquery-ui-1.10.4' + (Gx__SupportDebug ? '' : '.min') + '.js?_=20140418', true, function(loaded) { return (loaded && !IsConditionalScriptLoading()); }, function() {
			InjectScriptTag(Gx__RootURL + '/' + Gx__SupportPath + '/ajaxorg_ace/ace.js');
			LoadConditionalScript(Gx__RootURL + '/' + Gx__SupportPath + '/ajaxorg_ace/ace.js?_=20150110', true, function(loaded) { return (loaded && !IsConditionalScriptLoading()); }, function() {
				LoadConditionalScript(Gx__RootURL + '/' + Gx__SupportPath + '/ajaxorg_ace/mode-web.js?_=20150110', true, function(loaded) { return (loaded && !IsConditionalScriptLoading()); }, function() {
					LoadConditionalScript(Gx__RootURL + '/' + Gx__SupportPath + '/ajaxorg_ace/keybinding-all.js?_=20150110', true, function(loaded) { return (loaded && !IsConditionalScriptLoading()); }, function() {
						LoadConditionalScript(Gx__RootURL + '/' + Gx__SupportPath + '/ajaxorg_ace/theme-crimson_editor.js?_=20150110', true, function(loaded) { return (loaded && !IsConditionalScriptLoading()); }, function() {
						});
					});
				});
			});
		});
	}
	else
	{
		document.write('<link rel="stylesheet" href="' + Gx__RootURL + '/' + Gx__SupportPath + '/jquery_ui_themes/smoothness/jquery-ui-1.10.4.css" type="text/css" media="all" />');
		document.write('<link rel="stylesheet" href="' + Gx__RootURL + '/' + Gx__SupportPath + '/css/aceeditor.css" type="text/css" media="all" />');
		document.write('<script type="text/javascript" src="' + Gx__RootURL + '/' + Gx__SupportPath + '/jquery-ui-1.10.4' + (Gx__SupportDebug ? '' : '.min') + '.js"></script>');
		document.write('<script type="text/javascript" src="' + Gx__RootURL + '/' + Gx__SupportPath + '/ajaxorg_ace/ace.js"></script>');
		document.write('<script type="text/javascript" src="' + Gx__RootURL + '/' + Gx__SupportPath + '/ajaxorg_ace/mode-web.js"></script>');
		document.write('<script type="text/javascript" src="' + Gx__RootURL + '/' + Gx__SupportPath + '/ajaxorg_ace/keybinding-all.js"></script>');
		document.write('<script type="text/javascript" src="' + Gx__RootURL + '/' + Gx__SupportPath + '/ajaxorg_ace/theme-crimson_editor.js"></script>');
	}
}
else
{
	if (typeof(window.LoadConditionalScript) == 'function')
	{
		InjectScriptTag(Gx__RootURL + '/' + Gx__SupportPath + '/edit_area/edit_area_full.js');
		LoadConditionalScript(Gx__RootURL + '/' + Gx__SupportPath + '/edit_area/edit_area_full.js?_=20090725', true, function(loaded) { return (loaded && !IsConditionalScriptLoading()); }, function() {
			setTimeout(function() { editAreaLoader.window_loaded(); }, 250);
		});
	}
	else
	{
		document.write('<script type="text/javascript" src="' + Gx__RootURL + '/' + Gx__SupportPath + '/edit_area/edit_area_full.js"></script>');
	}
}
