<?php
$GLOBALS['csstidy']['templates']['barebones'] = array();
$GLOBALS['csstidy']['templates']['barebones'][0]  = '<span class="at">';  // String before @rule.
$GLOBALS['csstidy']['templates']['barebones'][1]  = '</span> <span class="format">{</span>' . "\n";  // Bracket after @-rule.
$GLOBALS['csstidy']['templates']['barebones'][2]  = '<span class="selector">';  // String before selector.
$GLOBALS['csstidy']['templates']['barebones'][3]  = '</span> <span class="format">{</span>' . "\n";  // Bracket after selector.
$GLOBALS['csstidy']['templates']['barebones'][4]  = "\t" . '<span class="property">';  // String before property.
$GLOBALS['csstidy']['templates']['barebones'][5]  = '</span> <span class="value">';  // String after property, before value.
$GLOBALS['csstidy']['templates']['barebones'][6]  = '</span><span class="format">;</span>' . "\n";  // Value terminator.
$GLOBALS['csstidy']['templates']['barebones'][7]  = '<span class="format">}</span>';  // Closing bracket for selector.
$GLOBALS['csstidy']['templates']['barebones'][8]  = "\n\n";  // Space between blocks {...}.
$GLOBALS['csstidy']['templates']['barebones'][9]  = "\n" . '<span class="format">}</span>' . "\n\n";  // Closing bracket for @-rule.
$GLOBALS['csstidy']['templates']['barebones'][10] = "\t";  // Indent in @-rule.
$GLOBALS['csstidy']['templates']['barebones'][11] = '<span class="comment">';  // Before comment.
$GLOBALS['csstidy']['templates']['barebones'][12] = '</span>' . "\n";  // After comment.
$GLOBALS['csstidy']['templates']['barebones'][13] = "\n";  // After last line of last @-rule.
$GLOBALS['csstidy']['templates']['barebones'][14] = '</span> <span class="value">';  // Before @-rule value.
$GLOBALS['csstidy']['templates']['barebones'][15] = '</span><span class="format">,</span> <span class="selector">';  // Multiple selector separator.
?>