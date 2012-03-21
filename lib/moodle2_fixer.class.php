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

class moodle2_fixer {
    
    //obtain the parameters from a function call
    public function extract_params($call){
        $params = array();
        $openpar=0; $infunc=false; $pos=1;
        for ($x=1;$x<strlen($call)-1;$x++) {
            $rem=$call[$x];
            if ($rem=='(') {
                $openpar++; $infunc=true;
            }
            if ($rem==')' && $infunc) {
                $openpar--;
                if($openpar==0){
                    $infunc=false;
                }
            }
            if ($rem==',' && !$infunc) {
                //check that what we are not in the middle of a string param with commas
                $temp = trim(substr($call,$pos,($x-$pos)));
                $firstchar = substr($temp,0,1);
                if ($firstchar == "'" || $firstchar == '"') {
                    if (substr_count($temp, $firstchar)%2 == 1){
                        continue;
                    }
                }
                $params[] = $temp;
                $pos = $x+1;
            }
        }
        if ($pos<strlen($call)) {
            $params[] = trim(substr($call,$pos,($x-$pos)));
        }
        return $params;
    }

    //takes params array and turns params in a key=>value pairs form into an array call
    public function params_to_array($params, $start, $end=null) {

        if (is_null($end)) {
            $end = count($params)-1;
        } else {
            if ($start<0 || ($start > count($params)-1) || $end<$start || ($end-$start)%2!=1) {
                echo "<b>bad call to params_to_array ($start, $end, param array of size " . count($params) . ")</b>";
                return false;
            }
        }
        $arrstring = "array(";
        for ($x=$start;$x<=$end;$x=$x+2) {
            if (isset($params[$x]) && isset($params[$x+1]) && $params[$x] != 'null' && trim($params[$x]) != '' && trim($params[$x]) != "''") {
                if (strlen($arrstring)>6){
                    $arrstring .= ", ";
                }
                $arrstring .= $params[$x] . " => ";
                $arrstring .= $params[$x+1];
            } else {
                break;
            }
        }
        $arrstring .= ")";
        if ($arrstring == "array()" || $arrstring == "array(null => null)") { $arrstring = "null"; }
        return $arrstring;
    }

    // returns the full definition of a function (name, bracket, args, close bracket) within a specific line
    public function get_function_definition($line, $funcname, $ignoreprefix="", &$ignore=null) {
        if (($loc = strpos($line, $funcname . '(')) === false) {
            // function name not found in string
            return false;
        } else {
            if ($ignoreprefix != "") {
                $loc2 = strpos($line, $ignoreprefix . $funcname . '(');
                if ($loc2 !== false && $loc2==($loc - strlen($ignoreprefix))) {
                    $ignore = true;
                    return false;
                }
            }
        }
        $num_params = 0;
        $infunc = false;
        for ($i = $loc; $i<strlen($line); $i++) {
            $char = $line[$i];
            if ($char == '(') {
                $num_params++;
                $infunc = true;
            }
            if ($char == ')') {
                $num_params--;
            }
            if ($num_params == 0 && $infunc) {
                return substr($line, $loc, $i - $loc + 1);
            }
        }
        // did not find end of function
        return false;
    }

    //find first instance of a function and extract its params
    public function parse_for_params($line, $funcname) {
        $matches=array(); $params = array();
        if (preg_match('/.*?\s*?'.$funcname.'\s*?\((.*?)(?=\))(\).*)/s',$line,$matches)) {
            if (!empty($matches[1])) {
                $pos = strpos($line,$funcname);
                $pos += strlen($funcname);
                $remainder = substr($line,$pos);
                $openpar=0; $infunc=false; $call="";
                for ($x=0;$x<strlen($remainder);$x++) {
                    $rem=$remainder[$x];
                    if ($rem=='(') { $openpar++; $infunc=true;}
                    if ($rem==')' && $infunc) {
                        $openpar--;
                        if ($openpar==0) {
                            $infunc=false;
                            $call = substr($remainder,0,$x+1);
                            break;
                        }
                    }
                }
                $params = $this->extract_params($call);
            }
        }
        return $params;
    }

    // Manages a series of optional parameters and adds placeholder defaults
    // for optional parameters that are missing
    public function manage_optional_params($new_params, $optionaldefaults, $returnarray = false) {
        $out = array();
        $showopt = false;

        foreach (array_reverse($new_params, true) as $key => $value) {
            if (isset($value) && $value != $optionaldefaults[$key]) {
                $showopt = true;
            }

            if ($showopt) {
                if (isset($value)) {
                    $data = $value;
                } else {
                    $data = $optionaldefaults[$key];
                }
                $out[$key] = $data;
            }
        }

        if ($returnarray) {
            return array_reverse($out, true);
        } else {
            return implode(', ', array_reverse($out, true));
        }
    }

    // given a code line, return any whitespace at the start. This is useful for maintaining indentation
    public function get_whitespace($line) {
        // find first non-whitespace character
        preg_match('/[^\s]/', $line, $matches, PREG_OFFSET_CAPTURE);
        if ($matches) {
            // this gives the position of the first non-whitespace character
            $pos = $matches[0][1];
            // just return the whitespace
            return substr($line, 0, $pos);
        } else {
            return '';
        }
    }

}
