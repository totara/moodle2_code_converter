<?php
/*
* This file is part of the Moodle 2 Code Converter Tool
*
* Copyright (C) 2012 Totara Learning Solutions LTD
*
* Originally based off and inspired by Moodle's check_db_syntax database API script found here:
* http://cvs.moodle.org/contrib/tools/check_db_syntax/
* Copyright (C) 1999 onwards  Martin Dougiamas  http://moodle.com
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program. If not, see <http://www.gnu.org/licenses/>.
*
* @author Ciaran Irvine <ciaran.irvine@totaralms.com>
* @author Simon Coggins <simon.coggins@totaralms.com>
* @author Alastair Munro <alastair.munro@totaralms.com>
*/

global $rules;

$display = array(
    '/(\$PAGE->)?(?<!_)print_header(_simple)?\(/',
    '/\bprint_heading\(.+\)/',
    '/print_box(_(start|end))?\(/',
    '/(?<![_>])print_footer\(.*\)/',
    '/\$THEME->(l|r)arrow/',
    '/\{?\$CFG->(mod)?pixpath\}?/',
    '/print_table/',
    '/\$PAGE->get_type\(\)/',
    '/\$PAGE->get_format_name/',
    '/current_theme\(\)/',
    '/\/(pix|images|theme)\//',
    '/\{?\$CFG->theme(www)?\}?/',
    '/get_string(.*?)local_/',
    '/admin_externalpage_print_(header|footer)/',
    '/get_string(.*?),\'local\'/',
    '/require_js/',
    '/\/local\/icon\/icon.php/',
    '/style=(\'|")(.*?);(\'|")/'
);

$forms = array(
    '/setHelpButton/',
    '/PARAM_CLEAN[^HTML]/',
    '/addElement[(]+\'htmleditor\'/',
    '/createElement[(]+\'htmleditor\'/',
    '/addElement[(]+\'file\'/',
    '/createElement[(]+\'file\'/',
    '/<input type="file"/'
    
);
//$display = calculate_megarule($display);
//$forms = calculate_megarule($forms);
$excludes = '/(function |^\s*\*|^\s*\/\/)/i';

$rules['OUTPUT'] = array(
        'DISPLAY' => $display,
        'FORMS' => $forms,
        'EXCLUDES' => $excludes
    );

unset($display, $forms, $excludes);
?>
