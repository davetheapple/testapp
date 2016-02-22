document.onkeyup = function(e) {
	window.parent.WYMUI_ResetTimeoutTracker('resize', window.parent.WYMUI_UpdateWYMEditorStatus_Resize, 250);
	window.parent.WYMUI_ResetTimeoutTracker('content', window.parent.WYMUI_UpdateWYMEditorStatus_Content, 250);
};
