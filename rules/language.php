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

$bad_a = array(
    '/(^|[^{]){1}(\$a){1}(->|\[){0,}(.*?)(?=\z|\s|[\x80-\xFF]|[^a-zA-Z0-9_\[])(\]){0,}/'
);




//$deprecated = calculate_megarule($deprecated);
//$other = calculate_megarule($other);
//$unsupported = calculate_megarule($unsupported);
$excludes = '/(function |^\s*\*|^\s*\/\/)/i';

$rules['LANGUAGE'] = array(
        'BAD_A' => $bad_a,
        'EXCLUDES' => $excludes
);

unset($bad_a, $excludes);