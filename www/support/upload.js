// CubicleSoft Barebones CMS AJAX Uploader functions.
// (C) 2014 CubicleSoft.  All Rights Reserved.

function CreateUploadInterface(id, params, callback, callbackparams, filetypes, filetypedescs, uploadlimit)
{
	CreateStatusUpdater(id, {
		progressbars : [
			{
				first : [1, 100, 60],
				last : [83, 100, 84],
				ranges : {
					10 : [1, 100, 84],
					35 : [25, 100, 84],
					60 : [57, 100, 84],
					90 : [83, 100, 84]
				}
			},
			{
				first : [1, 100, 60],
				last : [83, 100, 84],
				ranges : {
					10 : [1, 100, 84],
					35 : [25, 100, 84],
					60 : [57, 100, 84],
					90 : [83, 100, 84]
				}
			}
		],
		useinfo : true,
		usemessages : true
	});

	SetStatusUpdaterInfo(id, '<div id="' + id + '_uploadbuttoninject"><span class="uploadbuttonwrap"><span class="uploadbuttonwrapinner"><span>Upload</span></span><input type="file" id="' + id + '_filedata" name="Filedata"' + (typeof(uploadlimit) == 'undefined' || parseInt(uploadlimit, 10) > 1 ? ' multiple' : '') + ' /></span></div>');

	var settings = {
		m_numfiles : 0,
		m_currfile : 0,
		m_totalbytes : 0,
		m_currbytes : 0,
		m_errors : 0,
		m_id : id,
		m_callback : callback,
		m_callbackparams : callbackparams,
		m_file_types : (typeof(filetypes) == 'string' ? filetypes : "*.*"),
		m_file_types_description : (typeof(filetypedescs) == 'string' ? filetypedescs : "All Files"),
		m_file_upload_limit : (typeof(uploadlimit) != 'undefined' ? parseInt(uploadlimit, 10) : -1)
	};

	$('#' + id + '_filedata').fileupload({
		// Backend settings.
		url : Gx__URLBase,
		formData : (typeof(params) != 'undefined' ? params : {}),
		sequentialUploads : true,
		dataType : 'html',
		pasteZone : null,

		// File upload callbacks.
		submit : function(e, data) {
			data.context = settings;

			var msg = '';
			var file = data.files[0];

			if (data.context.m_file_upload_limit > -1 && data.context.m_numfiles >= data.context.m_file_upload_limit)
			{
				msg = 'Queue limit reached.  Try uploading \'' + file.name + '\' later.';
			}
			else if (file.size > Gx__MaxSendSize)
			{
				msg = 'The file \'' + file.name + '\' (' + ConvertBytesToFriendlyLimit(file.size) + ') is too big to upload.  The server upload limit is ' + ConvertBytesToFriendlyLimit(Gx__MaxSendSize) + '.';
			}
			else if (file.size == 0)
			{
				msg = 'The file \'' + file.name + '\' is zero bytes.  The uploader is unable to handle zero byte files.';
			}
			else
			{
				var types = data.context.m_file_types.split(';');

				var y = file.name.match(/([^\.]+)$/);
				var fileext = (y && y[1] !== file.name ? y[1] : '');

				var x;
				for (x = 0; x < types.length; x++)
				{
					if (types[x] == '*.*')  break;

					y = types[x].match(/([^\.]+)$/);
					var fileext2 = (y && y[1] !== types[x] ? y[1] : '');

					if (fileext === fileext2)  break;
				}

				if (x == types.length)  msg = 'The file \'' + file.name + '\' does not have a valid matching file extension.';
			}

			if (msg != '')
			{
				data.context.m_errors++;
				AddStatusUpdaterMessage(data.context.m_id, msg, 'Error', 'Errors');

				return false;
			}

			data.context.m_numfiles++;
			data.context.m_totalbytes += file.size;

			if (data.context.m_numfiles > 1)  ShowStatusUpdaterProgressBar(data.context.m_id, 0, true, true);
			ShowStatusUpdaterProgressBar(data.context.m_id, 1, true, true);
		},

		progress : function(e, data) {
			AJAXUpload_UpdateProgress(data, true);
		},

		done : function(e, data) {
			data.context.m_currfile++;

			var file = data.files[0];

			data.context.m_currbytes += data.loaded;
			AJAXUpload_UpdateProgress(data, false);

			if (typeof(data.result) == 'undefined' || data.result == "" || data.result == "OK")  AddStatusUpdaterMessage(data.context.m_id, file.name, 'Successful Upload', 'Successful Uploads');
			else
			{
				data.context.m_errors++;
				AddStatusUpdaterMessage(data.context.m_id, '\'' + file.name + '\' uploaded but a server error occurred:  ' + data.result, 'Error', 'Errors');
			}

			AJAXUpload_UploadCompleted(data);
		},

		fail : function(e, data) {
			data.context.m_currfile++;

			var notice = false;
			var file = data.files[0];
			var msg = 'Upload of \'' + file.name + '\' failed.  Reason:  ';

			data.context.m_currbytes += data.loaded;
			AJAXUpload_UpdateProgress(data, false);

			if (data.jqXHR.status != 200)  msg += 'HTTP error.  HTTP 200 OK status code expected.';
			else
			{
				var errors = [];
				$.each(data.messages, function (index, error) {
					errors[errors.length] = error;
				});

				msg += errors.join('  ');
			}

			if (notice)  AddStatusUpdaterMessage(data.context.m_id, msg, 'Notice', 'Notices');
			else
			{
				data.context.m_errors++;
				AddStatusUpdaterMessage(data.context.m_id, msg, 'Error', 'Errors');
			}

			AJAXUpload_UploadCompleted(data);
		}

	});

	return id;
}

function DestroyUploadInstance(id)
{
	$('#' + id + '_filedata').fileupload('destroy');
	DestroyStatusUpdater(id);
}

function AJAXUpload_UpdateProgress(data, useloaded)
{
	var TempHTML, BytesUploaded, Percent, CurrFile, TimeLeft;

	BytesUploaded = (useloaded ? data.loaded : 0);
	Percent = Math.floor((data.context.m_currbytes + BytesUploaded) * 100 / data.context.m_totalbytes);
	if (Percent > 100)  Percent = 100;
	CurrFile = data.context.m_currfile + 1;
	if (CurrFile > data.context.m_numfiles)  CurrFile = data.context.m_numfiles;
	TempHTML = 'Uploading ' + CurrFile + ' of ' + data.context.m_numfiles;
	TempHTML += ' | ' + Percent + '%';
	TempHTML += ' | ' + ConvertBytesToFriendlyLimit(data.context.m_currbytes + BytesUploaded) + ' of ' + ConvertBytesToFriendlyLimit(data.context.m_totalbytes);
	SetStatusUpdaterProgressBar(data.context.m_id, 0, Percent, TempHTML);

	Percent = Math.floor(data.loaded * 100 / data.total);
	if (Percent > 100)  Percent = 100;
	TempHTML = Percent + '%';
	TempHTML += ' | ' + ConvertBytesToFriendlyLimit(data.loaded) + ' of ' + ConvertBytesToFriendlyLimit(data.total);
	TimeLeft = (data.bitrate > 0 ? Math.floor((data.total - data.loaded) * 8 / data.bitrate) : 0);
	TempHTML += ' | ' + ConvertSecondsElapsedToFriendlyLimit(TimeLeft, false);
	TempHTML += ' | ' + ConvertBytesToFriendlyLimit(data.bitrate / 8) + '/sec';
	TempHTML += '<br />' + data.files[0].name;
	SetStatusUpdaterProgressBar(data.context.m_id, 1, Percent, TempHTML);
}

function AJAXUpload_UploadCompleted(data)
{
	if (data.context.m_currfile >= data.context.m_numfiles)
	{
		if (typeof(data.context.m_callback) === "function")  data.context.m_callback(data, data.context.m_callbackparams);

		// Hide the progress bars and reset custom variables.
		if (data.context)
		{
			ShowStatusUpdaterProgressBar(data.context.m_id, 0, false, false);
			ShowStatusUpdaterProgressBar(data.context.m_id, 1, false, false);
			data.context.m_numfiles = 0;
			data.context.m_currfile = 0;
			data.context.m_totalbytes = 0;
			data.context.m_currbytes = 0;
			data.context.m_errors = 0;
		}
	}
}


if (typeof(window.LoadConditionalScript) == 'function')
{
	LoadCSS(Gx__RootURL + '/' + Gx__SupportPath + '/css/upload.css?_=20140419', 'all');

	LoadConditionalScript(Gx__RootURL + '/' + Gx__SupportPath + '/status.js?_=20090725', true, function(loaded) {
		return ((!loaded && typeof(window.CreateStatusUpdater) == 'function') || (loaded && !IsConditionalScriptLoading()));
	}, function() {});

	// Minified version created with 'vendor/jquery.ui.widget.js', 'jquery.iframe-transport.js', and 'jquery.fileupload.js' (in that order).
	LoadConditionalScript(Gx__RootURL + '/' + Gx__SupportPath + '/jquery.fileupload-9.9.1' + (Gx__SupportDebug ? '' : '.min') + '.js', true, function(loaded) { return (loaded && !IsConditionalScriptLoading()); }, function() {});
}
else
{
	document.write('<link rel="stylesheet" href="' + Gx__RootURL + '/' + Gx__SupportPath + '/css/upload.css" type="text/css" media="all" />');
	document.write('<script type="text/javascript" src="' + Gx__RootURL + '/' + Gx__SupportPath + '/status.js"></script>');
	document.write('<script type="text/javascript" src="' + Gx__RootURL + '/' + Gx__SupportPath + '/jquery.fileupload-9.9.1' + (Gx__SupportDebug ? '' : '.min') + '.js"></script>');
}
