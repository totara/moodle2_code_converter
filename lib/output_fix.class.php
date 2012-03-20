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

class output_fix extends moodle2_fixer {

    function __construct(){

    }

    public function display($line, $error){
        $newline = '';
        switch($error['ruleindex']){
            case 0: //'/(\$PAGE->)?(?<!_)(print_header(?_simple))?\((.*?)(\);)/',
                //both functions have the same arguments in the same order
                if (strpos($line,"print_header_simple")!==false){
                    $func = $this->get_function_definition($line, 'print_header_simple');
                    $params = $this->parse_for_params($func, 'print_header_simple');
                } else {
                    $func = $this->get_function_definition($line, 'print_header');
                    $params = $this->parse_for_params($func, 'print_header');
                }
                $white = $this->get_whitespace($line);
                if (isset($params[0])){
                    $newline .= '*M2SCAN$PAGE->set_title(' . $params[0] . ');M2SCAN*' . "\n";
                }
                if (isset($params[1])){
                    $newline .= $white . '*M2SCAN$PAGE->set_heading(' . $params[1] . ');M2SCAN*' . "\n";
                }
                if (isset($params[2])){
                    //nav is tricksy
                    if (substr($params[2],0,1)=='$'){
                        //may give errors on runtime if not a good navobject
                        $newline .= $white . '*M2SCAN/* SCANMSG: may be additional work required for ' . $params[2] . ' variable */M2SCAN*' . "\n";
                    }
                }
                if (isset($params[3])){
                    $newline .= $white . '*M2SCAN$PAGE->set_focuscontrol(' . $params[3] . ');M2SCAN*' . "\n";
                }
                if (isset($params[5])){
                    $newline .= $white . '*M2SCAN$PAGE->set_cacheable(' . $params[5] . ');M2SCAN*' . "\n";
                }
                if (isset($params[6])){
                    $newline .= $white . '*M2SCAN$PAGE->set_button(' . $params[6] . ');M2SCAN*' . "\n";
                }
                if (isset($params[7])){
                    $newline .= $white . '*M2SCAN$PAGE->set_headingmenu(' . $params[7] . ');M2SCAN*' . "\n";
                }
                $newline .= $white . '*M2SCANecho $OUTPUT->header()M2SCAN*';
                $line = str_replace($func, $newline, $line);
                break;
            case 1: //'/print_heading/',
                $func = $this->get_function_definition($line, 'print_heading');
                if ($func) {
                    $params = $this->parse_for_params($func, 'print_heading');
                    $text = isset($params[0])?$params[0]:null;
                    $align = isset($params[1])?$params[1]:null;
                    $size = isset($params[2])?$params[2]:null;
                    $class = isset($params[3])?$params[3]:null;
                    $return = isset($params[4])?$params[4]:null;
                    $id = isset($params[5])?$params[5]:null;
                    if ($return == 'true') {
                        $newline = '$OUTPUT->heading(';
                    } else {
                        $newline = 'echo $OUTPUT->heading(';
                    }

                    if (!empty($text) || !empty($size) || !empty($class) || !empty($id)) {
                        $newline .= empty($text) ? "''" : $text;
                    }
                    if (!empty($size) || !empty($class) || !empty($id)) {
                        $size = empty($size) ? '2' : $size;
                        $newline .= ", $size";
                    }

                    if (!empty($class) || !empty($id)) {
                        $class = empty($class) ? 'main' : $class;
                        $newline .= ", $class";
                    }

                    if (!empty($id)) {
                        $id = empty($id) ? 'null' : $id;
                        $newline .= ", $id";
                    }

                    $newline .= ')';
                    $line = str_replace($func, "*M2SCAN{$newline}M2SCAN*", $line);
                }
                break;
            case 2: //'/print_box_(start|end)?\((.*?)(\);)/',

                $func = $this->get_function_definition($line, 'print_box_start');
                if ($func) {
                    $newline = '$OUTPUT->box_start(';
                    $params = $this->parse_for_params($func, 'print_box_start');
                    $new_params = @array(0 => $params[0], 1 => $params[1]);
                    $optional_defaults = array(0 => "'generalbox'", 1 => "''");
                    $return = ($params[2] == 'true');
                    $new_opt_str = $this->manage_optional_params($new_params, $optional_defaults);

                    if ($new_opt_str) {
                        $newline .= $new_opt_str;
                    }
                    $newline .= ')';

                    if (!$return) {
                        $newline = "echo $newline";
                    }

                    $line = str_replace($func, "*M2SCAN{$newline}M2SCAN*", $line);

                }


                $func = $this->get_function_definition($line, 'print_box_end');
                if ($func) {
                    $newline = '$OUTPUT->box_end(';
                    $params = $this->parse_for_params($func, 'print_box_end');
                    if (!isset($params[0]) || ($params[0] != 'true')) {
                        $newline = "echo $newline";
                    }

                    $newline .= ')';
                    $line = str_replace($func, "*M2SCAN{$newline}M2SCAN*", $line);
                }

                $func = $this->get_function_definition($line, 'print_box');
                if ($func) {
                    $params = $this->parse_for_params($func, 'print_box');
                    $message = isset($params[0]) ? $params[0] : null;
                    $new_params = @array(1 => $params[1], 2 => $params[2]);
                    $optional_defaults = array(1 => "'generalbox'", 2 => "''");
                    $return = ($params[3] == 'true');
                    $new_opt_str = $this->manage_optional_params($new_params, $optional_defaults);
                    $newline = "\$OUTPUT->box($message";

                    if ($new_opt_str) {
                        $newline .= ', ' . $new_opt_str;
                    }
                    $newline .= ')';

                    if (!$return) {
                        $newline = "echo $newline";
                    }

                    $line = str_replace($func, "*M2SCAN{$newline}M2SCAN*", $line);
                }

                break;
            case 3: //'/print_footer/',
                $func = $this->get_function_definition($line, 'print_footer');
                if ($func) {
                    $params = $this->parse_for_params($func, 'print_footer');
                    $course = isset($params[0])?$params[0]:null;
                    $usercourse = isset($params[1])?$params[1]:null;
                    $return = isset($params[2])?$params[2]:null;

                    if ($return == 'true') {
                        $newline = '$OUTPUT->footer(';
                    } else {
                        $newline = 'echo $OUTPUT->footer(';
                    }

                    if (!empty($course) || !empty($usercourse) || !empty($return)) {
                        $newline .= empty($course) ? 'null' : $course;
                    }

                    if (!empty($usercourse) || !empty($return)) {
                        $usercourse = empty($usercourse) ? 'null' : $usercourse;
                        $newline .= ", $usercourse";
                    }
                    $newline .= ')';
                    $line = str_replace($func, "*M2SCAN{$newline}M2SCAN*", $line);
                }
                break;
            case 4: //'/\$THEME->(l|r)arrow/',
                break;
            case 5: //'/\$->CFG->pixpath/',
                // only try and fix pixpath if it's outside of a quoted string - this covers most cases
                // others e.g. instead double quoted string are still highlighted but not fixed
                // here we match:
                // - $CFG->pixpath (without brackets)
                // - optional whitespace, then dot, then more optional whitespace
                // - a single or double quote
                // - any character that could be part of an image file path
                // - a dot, followed by any image extension
                $regexp = '@(?<!\{)\$CFG->(mod)?pixpath(?!\})\s*\.\s*(["\'])([a-zA-Z0-9/\._-]*)\.(gif|png|jpg|jpeg)@';
                if (preg_match($regexp, $line, $matches)) {
                    // matching modpixpath or pixpath?
                    $mod = $matches[1] == 'mod' ? 'mod' : '';
                    // this tells us if the filename is inside a single or double quoted string
                    $quotetype = $matches[2];
                    // we need this for the new function argument
                    $filepath = $matches[3];
                    $new = "\$OUTPUT->{$mod}pix_url({$quotetype}{$filepath}{$quotetype}) . {$quotetype}";
                    $line = preg_replace($regexp, "*M2SCAN{$new}M2SCAN*", $line);
                }
                break;
            case 6: //'/print_table/'
                $newline = '';
                $func = $this->get_function_definition($line, 'print_table');
                if ($func) {
                    $params = $this->parse_for_params($func, 'print_table');

                    if (!isset($params[1]) || $params[1] === false) {
                        $newline = 'echo ';
                    }

                    $newline .= 'html_writer::table';
                }

                $line = preg_replace($error['regex'], "*M2SCAN{$newline}M2SCAN*", $line);
                break;
            case 7: //'/\$PAGE->get_type\(\)/'
                $line = preg_replace($error['regex'], '*M2SCAN$PAGE->pagetypeM2SCAN*', $line);
                break;
            case 8: //'/\$PAGE->get_format_name\(\)/'
                $line = preg_replace($error['regex'], '*M2SCAN$PAGE->pagetypeM2SCAN*', $line);
                break;
            case 9: // '/current_theme\(\)/'
                $line = preg_replace($error['regex'], '*M2SCAN$PAGE->theme->nameM2SCAN*', $line);
                break;
            case 10: ///\/(pix|images|theme)\//
                break;
            case 11:///\{?\$CFG->theme(www)?\}?/
                break;
            case 12:///get_string(.*?)local_/
                $func = $this->get_function_definition($line, 'get_string');
                if ($func) {
                    $params = $this->parse_for_params($func, 'get_string');
                    $newline = 'get_string(';
                    for ($x=0; $x<count($params); $x++) {
                        if(isset($params[$x])) {
                            $params[$x] = str_replace('local_', 'totara_', $params[$x]);
                            $newline .= $params[$x] . ', ';
                        }
                    }
                    $newline .= ')';
                    $newline = str_replace(', )', ')', $newline);
                    $line = str_replace($func,"*M2SCAN{$newline}M2SCAN*",$line);
                }
                break;
            case 13: // admin_externalpage_print_(header|footer)
                $func = $this->get_function_definition($line, 'admin_externalpage_print_header');
                if ($func) {
                    $newline = 'echo $OUTPUT->header()';
                    $line = str_replace($func,"*M2SCAN{$newline}M2SCAN*",$line);
                    break;
                }
                $func = $this->get_function_definition($line, 'admin_externalpage_print_footer');
                if ($func) {
                    $newline = 'echo $OUTPUT->footer()';
                    $line = str_replace($func,"*M2SCAN{$newline}M2SCAN*",$line);
                    break;
                }
                break;
            case 14: ///get_string(.*?),\'local\'/
                $func = $this->get_function_definition($line, 'get_string');
                if ($func) {
                    $params = $this->parse_for_params($func, 'get_string');
                    $newline = 'get_string(';
                    for ($x=0; $x<count($params); $x++) {
                        if(isset($params[$x])) {
                            if ($params[$x] == "'local'") {
                                $params[$x] = "'totara_core'";
                            }
                            $newline .= $params[$x] . ', ';
                        }
                    }
                    $newline .= ')';
                    $newline = str_replace(', )', ')', $newline);
                    $line = str_replace($func,"*M2SCAN{$newline}M2SCAN*",$line);
                }
                break;
            case 15 : //require_js
                $func = $this->get_function_definition($line, 'require_js');
                $white = $this->get_whitespace($line);
                $newline = "";
                if ($func) {
                    $params = $this->parse_for_params($func, 'require_js');
                    if (isset($params[0])) {
                        $arr = $this->get_function_definition($params[0], 'array');
                        if ($arr) {
                            $arrparams = $this->parse_for_params($arr, 'array');
                            foreach ($arrparams as $lib) {
                                if(empty($lib)) {continue;}
                                if(substr($lib,0,2) == '//') {continue;}
                                $delim = (substr($lib,0,1) == '$')?'':'\'';
                                if (strpos($lib,"yui_")===0) {
                                    $lib = substr($lib,4);
                                    $newline .= $white . '*M2SCAN$PAGE->requires->yui2_lib(' . $delim . $lib . $delim . ');' . "M2SCAN*\n";
                                } else {
                                    $newline .= $white . '*M2SCAN$PAGE->requires->js(' . $delim . $lib . $delim . ');' . "M2SCAN*\n";
                                }
                            }
                            $line = $newline;
                        }
                    }
                }
                break;
            case 16: //local/icon/icon.php
                $line = '*M2SCAN' . str_replace("/local/icon/icon.php", "/totara/core/icon/icon.php", $line) . 'M2SCAN*';
                break;
        }
        return $line;
    }

    public function forms($line, $error){
        switch($error['ruleindex']){
            case 0: //'/setHelpButton/',
                //two forms, one using $form-> or $mform-> and one directly on an element using $grp
                $matches = array();
                $elem = null; $component = 'null'; $helpidentifier = null;
                $suppresscheck = null; $linktext=null;
                $reg = '/(\$.*?)->setHelpButton/';
                if (preg_match($reg, $line, $matches)) {
                    $white = $white = $this->get_whitespace($line);
                    if ($matches[1] == '$form' || $matches[1] == '$mform') {
                        $func = $this->get_function_definition($line, 'setHelpButton');
                        if ($func) {
                            $params = $this->parse_for_params($func, 'setHelpButton');
                            if (isset($params[0])) {
                                $elem = trim($params[0]);
                            }
                            if (isset($params[1])) {
                                $arrayparams = $this->parse_for_params($params[1], 'array');
                                if (isset($arrayparams[2])) {
                                    $component = trim ($arrayparams[2]);
                                }
                                if (isset($arrayparams[0])) {
                                    $helpidentifier = $arrayparams[0];
                                }
                            }
                            if (isset($params[2])) {
                                $suppresscheck = $params[2];
                            }
                            if (isset($params[4])) {
                                $linktext = $params[4];
                            }
                        }
                    }
                    if ($matches[1] == '$grp') {
                        $func = $this->get_function_definition($line, 'setHelpButton');
                        if ($func) {
                            $params = $this->parse_for_params($func, 'setHelpButton');
                            $elem = '$grp->_name';
                            if (isset($params[0])) {
                                $arrayparams = $this->parse_for_params($params[0], 'array');
                                if (isset($arrayparams[2])) {
                                    $component = trim ($arrayparams[2]);
                                }
                                if (isset($arrayparams[0])) {
                                    $helpidentifier = $arrayparams[0];
                                }
                            }
                        }
                    }
                    if ($elem && $helpidentifier) {
                        $component = str_replace("local_","totara_",$component);
                        $newline = $white . '*M2SCAN$mform->addHelpButton(' . $elem . ', ' . $helpidentifier . ', ' . $component;
                        if ($linktext) {
                            $newline .= ', ' . $linktext;
                        }
                        if ($suppresscheck) {
                            $newline .= ', ' . $suppresscheck;
                        }
                        $newline .= ');M2SCAN*';
                        $line = $newline;
                    }
                }
                break;
            case 1: //'/PARAM_CLEAN[^HTML]/',
                break;
            case 2: //'/addElement[(]+\'htmleditor\'/',
                $line = preg_replace($error['regex'], '*M2SCANaddElement(\'editor\'M2SCAN*', $line);
                break;
            case 3: //'/createElement[(]+\'htmleditor\'/',
                $line = preg_replace($error['regex'], '*M2SCANcreateElement(\'editor\'M2SCAN*', $line);
                break;
            case 4: //'/addElement[(]+\'file\'/',
                $line = preg_replace($error['regex'], '*M2SCANaddElement(\'filepicker\'M2SCAN*', $line);
                break;
            case 5: //'/createElement[(]+\'file\'/',
                $line = preg_replace($error['regex'], '*M2SCANcreateElement(\'filepicker\'M2SCAN*', $line);
                break;
            case 6: //'/<input type="file"/'
                break;
        }
        return $line;
    }

}
