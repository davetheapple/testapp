<?php
/**
 * Prebuilt Templates for CSSTidy
 *
 * The CSSTidy built-in templates:
 *   'default',
 *   'altdefault',
 *   'high_compression',
 *   'high',
 *   'highest_compression',
 *   'highest',
 *   'low_compression',
 *   'low'
 *
 * Copyright 2005, 2006, 2007 Florian Schmitz
 *
 * This file is part of CSSTidy.
 *
 *   CSSTidy is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Lesser General Public License as published by
 *   the Free Software Foundation; either version 2.1 of the License, or
 *   (at your option) any later version.
 *
 *   CSSTidy is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Lesser General Public License for more details.
 *
 *   You should have received a copy of the GNU Lesser General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @license http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
 * @package csstidy
 * @author Florian Schmitz (floele at gmail dot com) 2005
 * @author Nikolay Matsievsky (speed at webo dot name) 2010
 * @author Thomas Hruska 2010
 */

$GLOBALS['csstidy']['templates']['default'] = array();
$GLOBALS['csstidy']['templates']['default'][0]  = '<span class="at">';  // String before @rule.
$GLOBALS['csstidy']['templates']['default'][1]  = '</span> <span class="format">{</span>' . "\n";  // Bracket after @-rule.
$GLOBALS['csstidy']['templates']['default'][2]  = '<span class="selector">';  // String before selector.
$GLOBALS['csstidy']['templates']['default'][3]  = '</span> <span class="format">{</span>' . "\n";  // Bracket after selector.
$GLOBALS['csstidy']['templates']['default'][4]  = '<span class="property">';  // String before property.
$GLOBALS['csstidy']['templates']['default'][5]  = '</span><span class="value">';  // String after property, before value.
$GLOBALS['csstidy']['templates']['default'][6]  = '</span><span class="format">;</span>' . "\n";  // Value terminator.
$GLOBALS['csstidy']['templates']['default'][7]  = '<span class="format">}</span>';  // Closing bracket for selector.
$GLOBALS['csstidy']['templates']['default'][8]  = "\n\n";  // Space between blocks {...}.
$GLOBALS['csstidy']['templates']['default'][9]  = "\n" . '<span class="format">}</span>' . "\n\n";  // Closing bracket for @-rule.
$GLOBALS['csstidy']['templates']['default'][10] = '';  // Indent in @-rule.
$GLOBALS['csstidy']['templates']['default'][11] = '<span class="comment">';  // Before comment.
$GLOBALS['csstidy']['templates']['default'][12] = '</span>' . "\n";  // After comment.
$GLOBALS['csstidy']['templates']['default'][13] = "\n";  // After last line of last @-rule.
$GLOBALS['csstidy']['templates']['default'][14] = '</span> <span class="value">';  // Before @-rule value.
$GLOBALS['csstidy']['templates']['default'][15] = '</span><span class="format">,</span><span class="selector">';  // Multiple selector separator.

$GLOBALS['csstidy']['templates']['altdefault'] = array();
$GLOBALS['csstidy']['templates']['altdefault'][0]  = '<span class="at">';  // String before @rule.
$GLOBALS['csstidy']['templates']['altdefault'][1]  = '</span> <span class="format">{</span>' . "\n";  // Bracket after @-rule.
$GLOBALS['csstidy']['templates']['altdefault'][2]  = '<span class="selector">';  // String before selector.
$GLOBALS['csstidy']['templates']['altdefault'][3]  = '</span> <span class="format">{</span>' . "\n";  // Bracket after selector.
$GLOBALS['csstidy']['templates']['altdefault'][4]  = "\t" . '<span class="property">';  // String before property.
$GLOBALS['csstidy']['templates']['altdefault'][5]  = '</span> <span class="value">';  // String after property, before value.
$GLOBALS['csstidy']['templates']['altdefault'][6]  = '</span><span class="format">;</span>' . "\n";  // Value terminator.
$GLOBALS['csstidy']['templates']['altdefault'][7]  = '<span class="format">}</span>';  // Closing bracket for selector.
$GLOBALS['csstidy']['templates']['altdefault'][8]  = "\n\n";  // Space between blocks {...}.
$GLOBALS['csstidy']['templates']['altdefault'][9]  = "\n" . '<span class="format">}</span>' . "\n\n";  // Closing bracket for @-rule.
$GLOBALS['csstidy']['templates']['altdefault'][10] = "\t";  // Indent in @-rule.
$GLOBALS['csstidy']['templates']['altdefault'][11] = '<span class="comment">';  // Before comment.
$GLOBALS['csstidy']['templates']['altdefault'][12] = '</span>' . "\n";  // After comment.
$GLOBALS['csstidy']['templates']['altdefault'][13] = "\n";  // After last line of last @-rule.
$GLOBALS['csstidy']['templates']['altdefault'][14] = '</span> <span class="value">';  // Before @-rule value.
$GLOBALS['csstidy']['templates']['altdefault'][15] = '</span><span class="format">,</span> <span class="selector">';  // Multiple selector separator.

$GLOBALS['csstidy']['templates']['high_compression'] = array();
$GLOBALS['csstidy']['templates']['high_compression'][0]  = '<span class="at">';  // String before @rule.
$GLOBALS['csstidy']['templates']['high_compression'][1]  = '</span> <span class="format">{</span>' . "\n";  // Bracket after @-rule.
$GLOBALS['csstidy']['templates']['high_compression'][2]  = '<span class="selector">';  // String before selector.
$GLOBALS['csstidy']['templates']['high_compression'][3]  = '</span><span class="format">{</span>';  // Bracket after selector.
$GLOBALS['csstidy']['templates']['high_compression'][4]  = '<span class="property">';  // String before property.
$GLOBALS['csstidy']['templates']['high_compression'][5]  = '</span><span class="value">';  // String after property, before value.
$GLOBALS['csstidy']['templates']['high_compression'][6]  = '</span><span class="format">;</span>';  // Value terminator.
$GLOBALS['csstidy']['templates']['high_compression'][7]  = '<span class="format">}</span>';  // Closing bracket for selector.
$GLOBALS['csstidy']['templates']['high_compression'][8]  = "\n";  // Space between blocks {...}.
$GLOBALS['csstidy']['templates']['high_compression'][9]  = "\n" . '<span class="format">}</span>' . "\n";  // Closing bracket for @-rule.
$GLOBALS['csstidy']['templates']['high_compression'][10] = '';  // Indent in @-rule.
$GLOBALS['csstidy']['templates']['high_compression'][11] = '<span class="comment">';  // Before comment.
$GLOBALS['csstidy']['templates']['high_compression'][12] = '</span>';  // After comment.
$GLOBALS['csstidy']['templates']['high_compression'][13] = "\n";  // After last line of last @-rule.
$GLOBALS['csstidy']['templates']['high_compression'][14] = '</span> <span class="value">';  // Before @-rule value.
$GLOBALS['csstidy']['templates']['high_compression'][15] = '</span><span class="format">,</span><span class="selector">';  // Multiple selector separator.
$GLOBALS['csstidy']['templates']['high'] = $GLOBALS['csstidy']['templates']['high_compression'];

$GLOBALS['csstidy']['templates']['highest_compression'] = array();
$GLOBALS['csstidy']['templates']['highest_compression'][0]  = '<span class="at">';  // String before @rule.
$GLOBALS['csstidy']['templates']['highest_compression'][1]  = '</span> <span class="format">{</span>';  // Bracket after @-rule.
$GLOBALS['csstidy']['templates']['highest_compression'][2]  = '<span class="selector">';  // String before selector.
$GLOBALS['csstidy']['templates']['highest_compression'][3]  = '</span><span class="format">{</span>';  // Bracket after selector.
$GLOBALS['csstidy']['templates']['highest_compression'][4]  = '<span class="property">';  // String before property.
$GLOBALS['csstidy']['templates']['highest_compression'][5]  = '</span><span class="value">';  // String after property, before value.
$GLOBALS['csstidy']['templates']['highest_compression'][6]  = '</span><span class="format">;</span>';  // Value terminator.
$GLOBALS['csstidy']['templates']['highest_compression'][7]  = '<span class="format">}</span>';  // Closing bracket for selector.
$GLOBALS['csstidy']['templates']['highest_compression'][8]  = '';  // Space between blocks {...}.
$GLOBALS['csstidy']['templates']['highest_compression'][9]  = '<span class="format">}</span>';  // Closing bracket for @-rule.
$GLOBALS['csstidy']['templates']['highest_compression'][10] = '';  // Indent in @-rule.
$GLOBALS['csstidy']['templates']['highest_compression'][11] = '<span class="comment">';  // Before comment.
$GLOBALS['csstidy']['templates']['highest_compression'][12] = '</span>';  // After comment.
$GLOBALS['csstidy']['templates']['highest_compression'][13] = "\n";  // After last line of last @-rule.
$GLOBALS['csstidy']['templates']['highest_compression'][14] = '</span> <span class="value">';  // Before @-rule value.
$GLOBALS['csstidy']['templates']['highest_compression'][15] = '</span><span class="format">,</span><span class="selector">';  // Multiple selector separator.
$GLOBALS['csstidy']['templates']['highest'] = $GLOBALS['csstidy']['templates']['highest_compression'];

$GLOBALS['csstidy']['templates']['low_compression'] = array();
$GLOBALS['csstidy']['templates']['low_compression'][0]  = '<span class="at">';  // String before @rule.
$GLOBALS['csstidy']['templates']['low_compression'][1]  = '</span> <span class="format">{</span>' . "\n";  // Bracket after @-rule.
$GLOBALS['csstidy']['templates']['low_compression'][2]  = '<span class="selector">';  // String before selector.
$GLOBALS['csstidy']['templates']['low_compression'][3]  = '</span>' . "\n" . '<span class="format">{</span>' . "\n";  // Bracket after selector.
$GLOBALS['csstidy']['templates']['low_compression'][4]  = "\t" . '<span class="property">';  // String before property.
$GLOBALS['csstidy']['templates']['low_compression'][5]  = '</span><span class="value">';  // String after property, before value.
$GLOBALS['csstidy']['templates']['low_compression'][6]  = '</span><span class="format">;</span>' . "\n";  // Value terminator.
$GLOBALS['csstidy']['templates']['low_compression'][7]  = '<span class="format">}</span>';  // Closing bracket for selector.
$GLOBALS['csstidy']['templates']['low_compression'][8]  = "\n\n";  // Space between blocks {...}.
$GLOBALS['csstidy']['templates']['low_compression'][9]  = "\n" . '<span class="format">}</span>' . "\n\n";  // Closing bracket for @-rule.
$GLOBALS['csstidy']['templates']['low_compression'][10] = "\t";  // Indent in @-rule.
$GLOBALS['csstidy']['templates']['low_compression'][11] = '<span class="comment">';  // Before comment.
$GLOBALS['csstidy']['templates']['low_compression'][12] = '</span>' . "\n";  // After comment.
$GLOBALS['csstidy']['templates']['low_compression'][13] = "\n";  // After last line of last @-rule.
$GLOBALS['csstidy']['templates']['low_compression'][14] = '</span> <span class="value">';  // Before @-rule value.
$GLOBALS['csstidy']['templates']['low_compression'][15] = '</span><span class="format">,</span><span class="selector">';  // Multiple selector separator.
$GLOBALS['csstidy']['templates']['low'] = $GLOBALS['csstidy']['templates']['low_compression'];
?>