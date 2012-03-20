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

class general_fix extends moodle2_fixer {

    function __construct(){

    }

    public function deprecated($line, $error){
        switch($error['ruleindex']){
            case 0: //'/button_to_popup_window/',
                break;
            case 1: //'/choose_from_menu(_nested|_yesno)+/',
                break;
            case 2: //'/choose_from_radio/',
                break;
            case 3: //'/close_window_button/',
                break;
            case 4: //'/doc_link/',
                break;
            case 5: //'/formerr/',
                break;
            case 6: //'/helpbutton/',
                $func = $this->get_function_definition($line, 'helpbutton');
                if ($func) {
                    $identifier=null;$component=null;$linktext=null;
                    $params = $this->parse_for_params($func, 'helpbutton');
                    if(isset($params[0])) {
                        $identifier = $params[0];
                    }
                    if(isset($params[2])) {
                        $component = $params[2];
                    }
                    if(isset($params[4])) {
                        $linktext = $params[4];
                    }
                    $component = str_replace("local_","totara_",$component);
                    $newline = '$OUTPUT->help_icon(' . $identifier . ', ' . $component;
                    if ($linktext) {
                        $newline .= ', ' . $linktext;
                    }
                    $newline .= ')';
                    $line = str_replace($func, "*M2SCAN{$newline}M2SCAN*", $line);
                }
                break;
            case 7: //'/link_to_popup_window/',
                break;
            case 8: //'/notice_yesno/',
                $func = $this->get_function_definition($line, 'notice_yesno');
                $done = false;
                if ($func) {
                    $newline = '';
                    $params = $this->parse_for_params($func, 'notice_yesno');

                    if (isset($params[5]) && isset($params[6])) {
                        echo 'using correct layout';
                        $newline .= "*M2SCAN\$formcontinue = html_form::make_button({$params[1]}, {$params[3]}, get_string('yes'), {$params[5]});M2SCAN* \n";
                        $newline .= "*M2SCAN\$formcancel = html_form::make_button({$params[2]}, {$params[4]}, get_string('no'), {$params[6]});M2SCAN* \n";
                        $newline .= '*M2SCANecho $OUTPUT->confirm(' . $params[0] . ', ' . "$formcontinue, $formcancel)";
                        $done = true;
                    }

                    if (!$done && isset($params[3]) && isset($params[4])) {
                        $newline .= 'echo $OUTPUT->confirm(';
                        $newline .= "$params[0], ";
                        $newline .= "new moodle_url({$params[1]}, {$params[3]}), ";
                        $newline .= "new moodle_url({$params[2]}, {$params[4]}))";
                        $done = true;
                    }

                    if (!$done && isset($params[1]) && isset($params[2])) {
                        $newline .= 'echo $OUTPUT->confirm(';
                        $newline .= "{$params[0]}, {$params[1]}, {$params[2]}";
                        $newline .= ')';
                        $done = true;
                    }

                    $line = str_replace($func, "*M2SCAN{$newline}M2SCAN*", $line);
                }
                break;
            case 9: //'/notify\(.*\)/',
                $func = $this->get_function_definition($line, 'notify');
                if ($func) {
                    $newline = '$OUTPUT->notification(';
                    $params = $this->parse_for_params($func, 'notify');
                    if (isset($params[0])) {
                        $newline .= $params[0];
                    }

                    if (isset($params[1])) {
                        $classes = $params[1];
                        $newline .= ", $classes";
                    }

                    if (!isset($params[3]) || ($params[3] !== true)) {
                        $newline = "echo $newline";
                    }

                    $newline .= ')';
                    $line = str_replace($func, "*M2SCAN{$newline}M2SCAN*", $line);
                }
                break;
            case 10: //'/popup_form/',
                break;
            case 11: //'/print_(arrow|box|checkbox|container|continue|date_selector|headline|paging_bar|scale_menu_helpbutton|side_block|single_button|spacer|textarea|textfield|time_selector|user_picture)+/',
                //get_record
                $func = $this->get_function_definition($line, 'print_continue');
                if ($func) {
                    $newline = '$OUTPUT->continue_button(';
                    $params = $this->parse_for_params($func, 'print_continue');
                    if (isset($params[0])) {
                        $newline .= $params[0];
                    }
                    if (!isset($params[1]) || ($params[1] !== true)) {
                        $newline = "echo $newline";
                    }

                    $newline .= ')';
                    $line = str_replace($func, "*M2SCAN{$newline}M2SCAN*", $line);
                }

                $func = $this->get_function_definition($line, 'print_container');
                if ($func) {
                    $params = $this->parse_for_params($func, 'print_container');
                    $return = ($params[4] == 'true');
                    $message = $params[0];
                    $newline = "\$OUTPUT->container({$message}";

                    $new_params = @array(1 => $params[2], 2 => $params[3]);
                    $optional_defaults = array(1 => "''", 2 => "''");
                    $new_opt_str = $this->manage_optional_params($new_params, $optional_defaults);

                    if ($new_opt_str) {
                        $newline .= ', ' . $new_opt_str;
                    }
                    $newline .= ')';

                    if (!$return) {
                        $newline = "echo $newline";
                    }

                    $line = str_replace($func, "*M2SCAN{$newline}M2SCAN*", $line);
                }

                $func = $this->get_function_definition($line, 'print_container_start');
                if ($func) {
                    $newline = '$OUTPUT->container_start(';
                    $params = $this->parse_for_params($func, 'print_container_start');
                    $return = (isset($params[3]) && $params[3] == 'true');

                    $new_params = @array(0 => $params[1], 1 => $params[2]);
                    $optional_defaults = array(0 => "''", 1 => "''");
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

                $func = $this->get_function_definition($line, 'print_container_end');
                if ($func) {
                    $newline = '$OUTPUT->container_end(';
                    $params = $this->parse_for_params($func, 'print_container_end');
                    if (!isset($params[0]) || ($params[0] != 'true')) {
                        $newline = "echo $newline";
                    }

                    $newline .= ')';
                    $line = str_replace($func, "*M2SCAN{$newline}M2SCAN*", $line);
                }

                $func = $this->get_function_definition($line, 'print_user_picture');
                if ($func) {
                    $params = $this->parse_for_params($func, 'print_user_picture');
                    $user = $params[0];
                    $courseid = $params[1];
                    unset($params[0]);
                    unset($params[1]);

                    $optional_defaults = array(2 => 'null', 3 => 0, 4 => 'false', 5 => 'true', 6 => "''", 7 => 'true');
                    $new_opt_arr = $this->manage_optional_params($params, $optional_defaults, true);
                    $return = (trim($new_opt_arr[4]) == 'true');
                    $simple = empty($new_opt_arr);
                    // get any code before the function is called
                    $before = substr($line, 0, strpos($line, 'print_user_picture'));

                    $whitespace = $this->get_whitespace($line);

                    if ($simple) {
                        $newline = "*M2SCAN{$whitespace}\$userpic = new moodle_user_picture();M2SCAN*\n";
                        $newline .= "*M2SCAN{$whitespace}\$userpic->user = {$user};M2SCAN*\n";
                        $newline .= "*M2SCAN{$whitespace}\$userpic->courseid = {$courseid};M2SCAN*\n";
                    } else {
                        $newline = "*M2SCAN{$whitespace}\$userpic = new user_picture();M2SCAN*\n";
                        $newline .= "*M2SCAN{$whitespace}\$userpic->user = {$user};M2SCAN*\n";
                        $newline .= "*M2SCAN{$whitespace}\$userpic->courseid = {$courseid};M2SCAN*\n";

                        if (isset($new_opt_arr[2]) && $new_opt_arr[2] != $optional_defaults[2]) {
                            $newline .= "*M2SCAN{$whitespace}\$userpic->image->src = {$new_opt_arr[2]};M2SCAN*\n";
                        }
                        if (isset($new_opt_arr[3]) && $new_opt_arr[3] != $optional_defaults[3]) {
                            $newline .= "*M2SCAN{$whitespace}\$userpic->size = {$new_opt_arr[3]};M2SCAN*\n";
                        }
                        if (isset($new_opt_arr[5]) && $new_opt_arr[5] != $optional_defaults[5]) {
                            $newline .= "*M2SCAN{$whitespace}\$userpic->link = {$new_opt_arr[5]};M2SCAN*\n";
                        }
                        if (isset($new_opt_arr[6]) && $new_opt_arr[6] != $optional_defaults[6] && $new_opt_arr[6] != "''") {
                            $newline .= "*M2SCAN{$whitespace}\$userpic->add_action(new popup_action('click', new moodle_url({$new_opt_arr[6]})));M2SCAN*\n";
                        }
                        if (isset($new_opt_arr[7]) && $new_opt_arr[7] != $optional_defaults[7]) {
                            $newline .= "*M2SCAN{$whitespace}\$userpic->alttext = {$new_opt_arr[7]};M2SCAN*\n";
                        }
                    }

                    $echo = ($return) ? '' : 'echo ';
                    // only replace first instance
                    $line = preg_replace('/' . preg_quote($before, '/') . '/', "{$newline}{$before}", $line, 1);
                    $line = str_replace($func, "*M2SCAN{$echo}\$OUTPUT->user_picture(\$userpic)M2SCAN*", $line);
                }

                $func = $this->get_function_definition($line, 'print_single_button');
                if ($func) {
                    $params = $this->parse_for_params($func, 'print_single_button');

                    // required params
                    $link = $params[0];
                    $options = $params[1];
                    // if any options are provided pass them through in a moodle_url
                    // instead of just giving the link as a string
                    if (isset($options) && !empty($options) && $options != 'array()' && strtolower($options) != 'null') {
                        $link = "new moodle_url($link, $options)";
                    }
                    unset($params[0]);
                    unset($params[1]);

                    $optional_defaults = array(2 => 'OK', 3 => 'post', 4 => '_self', 5 => 'false', 6 => "''", 7 => 'false', 8 => "''");
                    $new_opt_arr = $this->manage_optional_params($params, $optional_defaults, true);

                    // override label parameter to always include it as it is required in new version
                    $new_opt_arr[2] = isset($params[2]) ? $params[2] : "'OK'";
                    // override method parameter to always include it as the default changed to post
                    $new_opt_arr[3] = isset($params[3]) ? $params[3] : "'get'";

                    $return = (isset($new_opt_arr[5]) && trim($new_opt_arr[5]) == 'true');

                    // classed as 'simple' if it doesn't use the jsmessage option as we can do everything
                    // else via the helper function
                    $simple = !isset($new_opt_arr[8]);

                    // get any code before the function is called
                    $before = substr($line, 0, strpos($line, 'print_single_button'));
                    $label = $new_opt_arr[2];
                    $method = $new_opt_arr[3];

                    $whitespace = $this->get_whitespace($line);
                    $echo = ($return) ? '' : 'echo ';

                    if ($simple) {
                        $simpleoptions = array();
                        if (isset($new_opt_arr[6]) && $new_opt_arr[6] != $optional_defaults[6] && $new_opt_arr[6] != "''") {
                            $simpleoptions[] = "'tooltip' => {$new_opt_arr[6]}";
                        }
                        if (isset($new_opt_arr[7]) && $new_opt_arr[7] != $optional_defaults[7] && $new_opt_arr[7] != "''") {
                            $simpleoptions[] = "'disabled' => {$new_opt_arr[7]}";
                        }
                        // print as a one-liner
                        $newline = "{$echo}\$OUTPUT->single_button({$link}, {$label}, {$method}";
                        if (count($simpleoptions) > 0) {
                            $newline .= ', array(' . implode(', ', $simpleoptions) . ')';
                        }
                        $newline .= ")";
                        $line = str_replace($func, "*M2SCAN{$newline}M2SCAN*", $line);

                    } else {
                        // need to build full object
                        $newline = "*M2SCAN{$whitespace}\$button = new single_button({$link}, {$label}, {$method});M2SCAN*\n";

                        if (isset($new_opt_arr[6]) && $new_opt_arr[6] != $optional_defaults[6] && $new_opt_arr[6] != "''") {
                            $newline .= "*M2SCAN{$whitespace}\$button->tooltip = {$new_opt_arr[6]};M2SCAN*\n";
                        }
                        if (isset($new_opt_arr[7]) && $new_opt_arr[7] != $optional_defaults[7]) {
                            $newline .= "*M2SCAN{$whitespace}\$button->disabled = {$new_opt_arr[7]};M2SCAN*\n";
                        }
                        if (isset($new_opt_arr[8]) && $new_opt_arr[7] != $optional_defaults[8]) {
                            $newline .= "*M2SCAN{$whitespace}\$button->add_confirm_action({$new_opt_arr[8]});M2SCAN*\n";
                        }

                        // only replace first instance
                        $line = preg_replace('/' . preg_quote($before, '/') . '/', "{$newline}{$before}", $line, 1);
                        $line = str_replace($func, "*M2SCAN{$echo}\$OUTPUT->render(\$button)M2SCAN*", $line);
                    }

                }
                break;
            case 12: //'/update_(course|module|tag)+_button/',
                break;
            case 13: //'/print_heading(_block|_with_help)+/',
                break;
            case 14: //'/isguest[(]+/'
                $func = $this->get_function_definition($line, 'isguest');
                if ($func) {
                    $params = $this->parse_for_params($func, 'isguest');
                    $userid = isset($params[0]) ? $params[0] : '';
                    $newline = "isguestuser({$userid})";
                    $line = str_replace($func, "*M2SCAN{$newline}M2SCAN*", $line);
                }
                break;
            case 15: // '/get_context_instance\(CONTEXT_/'
                $func = $this->get_function_definition($line, 'get_context_instance');
                if ($func) {
                    $params = $this->parse_for_params($func, 'get_context_instance');
                    if (isset($params[0])) {
                        $class = strtolower(str_replace('CONTEXT_', '', $params[0]));
                        $newline = 'context_' . $class . '::instance()';
                        $line = str_replace($func, "*M2SCAN{$newline}M2SCAN*", $line);
                    }
                }
                break;
        }
        return $line;
    }

    public function unsupported($line, $error){
        switch($error['ruleindex']){
            case 0: //'/blocks_print_group/',
                break;
            case 1: //'/print_(file_picture|png|scale_menu|side_block_(start|end)+|timer_selector|user)/',
                break;
            case 2: //'/update_categories_search_button/',
                break;
            case 3: //'/update_mymoodle_icon/'
                break;
        }
        return $line;
    }

    public function other($line, $error){
        $newline = '';
        switch($error['ruleindex']){
            case 0: //'/[\s]error[(]+/'
                $func = $this->get_function_definition($line, 'error');
                if ($func) {
                    $params = $this->parse_for_params($func, 'error');
                    // don't try and fix references to error() that don't contain
                    // get_string(). Need to manually move to lang string
                    if (isset($params[0]) && strpos($params[0], 'get_string(') === false) {
                        break;
                    }

                    $stringparams = $this->parse_for_params($params[0], 'get_string');
                    if (count($stringparams>0)) {
                        $key = (isset($stringparams[0]))?$stringparams[0]:null;
                        $file = (isset($stringparams[1]))?$stringparams[1]:null;
                        $var = (isset($stringparams[2]))?$stringparams[2]:null;
                        $extra = (isset($stringparams[3]))?$stringparams[3]:null;
                        $link = (isset($params[4]))?$params[4]:null;

                        // update to use print_error syntax
                        $replace = "print_error($key";

                        if (!empty($file) || !empty($var) || !empty($extra) || !empty($link)) {
                            $file = empty($file) ? "''" : $file;
                            $replace .= ", $file";
                        }

                        if (!empty($link) || !empty($var) || !empty($extra)) {
                            $link = empty($link) ? "''" : $link;
                            $replace .= ", $link";
                        }

                        if (!empty($var) || !empty($extra)) {
                            $var = empty($var) ? "null" : $var;
                            $replace .= ", $var";
                        }

                        if (!empty($extra)) {
                            $extra = empty($extra) ? "" : ", $extra";
                            $replace .= $extra;
                        }

                        $replace .= ")";
                        $line = str_replace($func, "*M2SCAN{$replace}M2SCAN*", $line);
                    }
                }
                break;
        }
        return $line;
    }

    public function capability($line, $error){
        $newline = '';
        switch($error['ruleindex']){
            case 0: //require_capability
                //this can get complicated with the hierarchy prefix stuff!
                $capsmap = $this->totara_get_capability_upgrade_map();
                $func = $this->get_function_definition($line, 'require_capability');
                if ($func) {
                    $params = $this->parse_for_params($func, 'require_capability');
                    $oldcap = $params[0];
                    if (strpos($oldcap, '$prefix')!==false) {
                        $newcap = str_replace('moodle/local', 'totara/hierarchy', $oldcap);
                    } else {
                        //check for a match
                        $capkey = str_replace("'","",$oldcap);
                        if (isset($capsmap[$capkey])) {
                            $newcap = $capsmap[$capkey]['newcap'];
                        } else {
                            $newcap = $oldcap;
                        }
                    }
                    $newline = 'require_capability(' . $newcap . '';
                    if (isset($params[1])) {
                        $newline .= ', ' . $params[1];
                    }
                    $newline .= ')';
                    $line = str_replace($func, "*M2SCAN{$newline}M2SCAN*", $line);
                }
                break;
        }

        return $line;
    }
    
    public function totara_get_capability_upgrade_map() {
        $upgrade_caps = array (
        'moodle/local:markcomplete' => array('newcap'=>'moodle/course:markcomplete', 'component' => 'moodle'),
        'local/comment:delete' => array('newcap'=>'moodle/comment:delete', 'component' => 'moodle'),
        'local/comment:post' => array('newcap'=>'moodle/comment:post', 'component' => 'moodle'),
        'local/comment:view' => array('newcap'=>'moodle/comment:view', 'component' => 'moodle'),
        'moodle/local:createcoursecustomfield' => array('newcap'=>'totara/core:createcoursecustomfield', 'component' => 'totara/core'),
        'moodle/local:deletecoursecustomfield' => array('newcap'=>'totara/core:deletecoursecustomfield', 'component' => 'totara/core'),
        'moodle/local:updatecoursecustomfield' => array('newcap'=>'totara/core:updatecoursecustomfield', 'component' => 'totara/core'),
        'local/cohort:assign' => array('newcap'=>'totara/cohort:assign', 'component' => 'totara/cohort'),
        'local/cohort:manage' => array('newcap'=>'totara/cohort:manage', 'component' => 'totara/cohort'),
        'local/cohort:view' => array('newcap'=>'totara/cohort:view', 'component' => 'totara/cohort'),
        'local/dashboard:admin' => array('newcap'=>'totara/dashboard:admin', 'component' => 'totara/dashboard'),
        'local/dashboard:edit' => array('newcap'=>'totara/dashboard:edit', 'component' => 'totara/dashboard'),
        'local/dashboard:view' => array('newcap'=>'totara/dashboard:view', 'component' => 'totara/dashboard'),
        'local/oauth:negotiate' => array('newcap'=>'totara/oauth:negotiate', 'component' => 'totara/oauth'),
        'local/plan:accessanyplan' => array('newcap'=>'totara/plan:accessanyplan', 'component' => 'totara/plan'),
        'local/plan:accessplan' => array('newcap'=>'totara/plan:accessplan', 'component' => 'totara/plan'),
        'local/plan:configureplans' => array('newcap'=>'totara/plan:configureplans', 'component' => 'totara/plan'),
        'local/plan:manageobjectivescales' => array('newcap'=>'totara/plan:manageobjectivescales', 'component' => 'totara/plan'),
        'local/plan:managepriorityscales' => array('newcap'=>'totara/plan:managepriorityscales', 'component' => 'totara/plan'),
        'local/program:accessanyprogram' => array('newcap'=>'totara/program:accessanyprogram', 'component' => 'totara/program'),
        'local/program:configureassignments' => array('newcap'=>'totara/program:configureassignments', 'component' => 'totara/program'),
        'local/program:configurecontent' => array('newcap'=>'totara/program:configurecontent', 'component' => 'totara/program'),
        'local/program:configuremessages' => array('newcap'=>'totara/program:configuremessages', 'component' => 'totara/program'),
        'local/program:configureprogram' => array('newcap'=>'totara/program:configureprogram', 'component' => 'totara/program'),
        'local/program:createprogram' => array('newcap'=>'totara/program:createprogram', 'component' => 'totara/program'),
        'local/program:handleexceptions' => array('newcap'=>'totara/program:handleexception', 'component' => 'totara/program'),
        'local/program:viewhiddenprograms' => array('newcap'=>'totara/program:viewhiddenprograms', 'component' => 'totara/program'),
        'local/program:viewprogram' => array('newcap'=>'totara/program:viewprogram', 'component' => 'totara/program'),
        'local/reportbuilder:managereports' => array('newcap'=>'totara/reportbuilder:managereports', 'component' => 'totara/reportbuilder'),
        'moodle/local:assignselfposition' => array('newcap'=>'totara/hierarchy:assignselfposition', 'component' => 'totara/hierarchy'),
        'moodle/local:assignuserposition' => array('newcap'=>'totara/hierarchy:assignuserposition', 'component' => 'totara/hierarchy'),
        'moodle/local:createcompetency' => array('newcap'=>'totara/hierarchy:createcompetency', 'component' => 'totara/hierarchy'),
        'moodle/local:createcompetencycustomfield' => array('newcap'=>'totara/hierarchy:createcompetencycustomfield', 'component' => 'totara/hierarchy'),
        'moodle/local:createcompetencyframeworks' => array('newcap'=>'totara/hierarchy:createcompetencyframeworks', 'component' => 'totara/hierarchy'),
        'moodle/local:createcompetencytemplate' => array('newcap'=>'totara/hierarchy:createcompetencytemplate', 'component' => 'totara/hierarchy'),
        'moodle/local:createcompetencytype' => array('newcap'=>'totara/hierarchy:createcompetencytype', 'component' => 'totara/hierarchy'),
        'moodle/local:createorganisation' => array('newcap'=>'totara/hierarchy:createorganisation', 'component' => 'totara/hierarchy'),
        'moodle/local:createorganisationcustomfield' => array('newcap'=>'totara/hierarchy:createorganisationcustomfield', 'component' => 'totara/hierarchy'),
        'moodle/local:createorganisationframeworks' => array('newcap'=>'totara/hierarchy:createorganisationframeworks', 'component' => 'totara/hierarchy'),
        'moodle/local:createorganisationtype' => array('newcap'=>'totara/hierarchy:createorganisationtype', 'component' => 'totara/hierarchy'),
        'moodle/local:createposition' => array('newcap'=>'totara/hierarchy:createposition', 'component' => 'totara/hierarchy'),
        'moodle/local:createpositioncustomfield' => array('newcap'=>'totara/hierarchy:createpositioncustomfield', 'component' => 'totara/hierarchy'),
        'moodle/local:createpositionframeworks' => array('newcap'=>'totara/hierarchy:createpositionframeworks', 'component' => 'totara/hierarchy'),
        'moodle/local:createpositiontype' => array('newcap'=>'totara/hierarchy:createpositiontype', 'component' => 'totara/hierarchy'),
        'moodle/local:deletecompetency' => array('newcap'=>'totara/hierarchy:deletecompetency', 'component' => 'totara/hierarchy'),
        'moodle/local:deletecompetencycustomfield' => array('newcap'=>'totara/hierarchy:deletecompetencycustomfield', 'component' => 'totara/hierarchy'),
        'moodle/local:deletecompetencyframeworks' => array('newcap'=>'totara/hierarchy:deletecompetencyframeworks', 'component' => 'totara/hierarchy'),
        'moodle/local:deletecompetencytemplate' => array('newcap'=>'totara/hierarchy:deletecompetencytemplate', 'component' => 'totara/hierarchy'),
        'moodle/local:deletecompetencytype' => array('newcap'=>'totara/hierarchy:deletecompetencytype', 'component' => 'totara/hierarchy'),
        'moodle/local:deleteorganisation' => array('newcap'=>'totara/hierarchy:deleteorganisation', 'component' => 'totara/hierarchy'),
        'moodle/local:deleteorganisationcustomfield' => array('newcap'=>'totara/hierarchy:deleteorganisationcustomfield', 'component' => 'totara/hierarchy'),
        'moodle/local:deleteorganisationframeworks' => array('newcap'=>'totara/hierarchy:deleteorganisationframeworks', 'component' => 'totara/hierarchy'),
        'moodle/local:deleteorganisationtype' => array('newcap'=>'totara/hierarchy:deleteorganisationtype', 'component' => 'totara/hierarchy'),
        'moodle/local:deleteposition' => array('newcap'=>'totara/hierarchy:deleteposition', 'component' => 'totara/hierarchy'),
        'moodle/local:deletepositioncustomfield' => array('newcap'=>'totara/hierarchy:deletepositioncustomfield', 'component' => 'totara/hierarchy'),
        'moodle/local:deletepositionframeworks' => array('newcap'=>'totara/hierarchy:deletepositionframeworks', 'component' => 'totara/hierarchy'),
        'moodle/local:deletepositiontype' => array('newcap'=>'totara/hierarchy:deletepositiontyp', 'component' => 'totara/hierarchy'),
        'moodle/local:updatecompetency' => array('newcap'=>'totara/hierarchy:updatecompetency', 'component' => 'totara/hierarchy'),
        'moodle/local:updatecompetencycustomfield' => array('newcap'=>'totara/hierarchy:updatecompetencycustomfield', 'component' => 'totara/hierarchy'),
        'moodle/local:updatecompetencyframeworks' => array('newcap'=>'totara/hierarchy:updatecompetencyframeworks', 'component' => 'totara/hierarchy'),
        'moodle/local:updatecompetencytemplate' => array('newcap'=>'totara/hierarchy:updatecompetencytemplate', 'component' => 'totara/hierarchy'),
        'moodle/local:updatecompetencytype' => array('newcap'=>'totara/hierarchy:updatecompetencytype', 'component' => 'totara/hierarchy'),
        'moodle/local:updateorganisation' => array('newcap'=>'totara/hierarchy:updateorganisation', 'component' => 'totara/hierarchy'),
        'moodle/local:updateorganisationcustomfield' => array('newcap'=>'totara/hierarchy:updateorganisationcustomfield', 'component' => 'totara/hierarchy'),
        'moodle/local:updateorganisationframeworks' => array('newcap'=>'totara/hierarchy:updateorganisationframeworks', 'component' => 'totara/hierarchy'),
        'moodle/local:updateorganisationtype' => array('newcap'=>'totara/hierarchy:updateorganisationtype', 'component' => 'totara/hierarchy'),
        'moodle/local:updateposition' => array('newcap'=>'totara/hierarchy:updateposition', 'component' => 'totara/hierarchy'),
        'moodle/local:updatepositioncustomfield' => array('newcap'=>'totara/hierarchy:updatepositioncustomfield', 'component' => 'totara/hierarchy'),
        'moodle/local:updatepositionframeworks' => array('newcap'=>'totara/hierarchy:updatepositionframeworks', 'component' => 'totara/hierarchy'),
        'moodle/local:updatepositiontype' => array('newcap'=>'totara/hierarchy:updatepositiontype', 'component' => 'totara/hierarchy'),
        'moodle/local:viewcompetency' => array('newcap'=>'totara/hierarchy:viewcompetency', 'component' => 'totara/hierarchy'),
        'moodle/local:vieworganisation' => array('newcap'=>'totara/hierarchy:vieworganisation', 'component' => 'totara/hierarchy'),
        'moodle/local:viewposition' => array('newcap'=>'totara/hierarchy:viewposition', 'component' => 'totara/hierarchy'));

        return $upgrade_caps;
    }
}
