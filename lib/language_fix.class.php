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

class language_fix extends moodle2_fixer {

    function __construct(){

    }
    
    public function bad_a($line, $error){
        $replace = '$1*M2SCAN{$2$3$4$5}M2SCAN*';
        $line = preg_replace($error['regex'],$replace,$line);
        return $line;
    }

    public function escaped_double_quote($line, $error){
        //$replace = '*M2SCAN"M2SCAN*';
        $line = str_replace('\"','*M2SCAN"M2SCAN*',$line);
        return $line;
    }

    public function double_quoted_line($line, $error){
        $text = str_replace("'","\'",$error['text']);
        $text = str_replace('\"','"',$text);
        $line = '$string[\'' . $error['stringvar'] . '\'] = *M2SCAN\'M2SCAN*' . $text . '*M2SCAN\'M2SCAN*;';
        return $line;
    }

}
