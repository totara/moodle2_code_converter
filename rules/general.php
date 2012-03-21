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

$deprecated = array(
    '/button_to_popup_window/',
    '/choose_from_menu(_nested|_yesno)?/',
    '/choose_from_radio/',
    '/close_window_button/',
    '/doc_link/',
    '/formerr/',
    '/helpbutton\(/',
    '/link_to_popup_window/',
    '/notice_yesno/',
    '/notify\(.*\)/',
    '/popup_form/',
    '/print_(arrow|checkbox|container|continue|date_selector|headline|paging_bar|scale_menu_helpbutton|side_block|single_button|spacer|textarea|textfield|time_selector|user_picture)+/',
    '/update_(course|module|tag)+_button/',
    '/print_heading(_block|_with_help)+/',
    '/isguest[(]+/',
    '/get_context_instance\(CONTEXT_/',
    '/new object()/'
);

$unsupported = array(
    '/blocks_print_group/',
    '/print_(file_picture|png|scale_menu|side_block_(start|end)+|timer_selector|user)/',
    '/update_categories_search_button/',
    '/update_mymoodle_icon/'
);

$other = array(
    '/\berror\(.+\)/'
);

$capability = array(
    '/require_capability\((\'|")(moodle\/local|local\/)/'
);
//$deprecated = calculate_megarule($deprecated);
//$other = calculate_megarule($other);
//$unsupported = calculate_megarule($unsupported);
$excludes = '/(function |^\s*\*|^\s*\/\/)/i';

$rules['GENERAL'] = array(
        'DEPRECATED' => $deprecated,
        'UNSUPPORTED' => $unsupported,
        'OTHER' => $other,
        'CAPABILITY' => $capability,
        'EXCLUDES' => $excludes
    );

unset($deprecated, $unsupported, $other, $excludes);
?>
