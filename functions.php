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

/// INTERNAL FUNCIONS
function help_dirs_to_check($path) {
    global $summary;
    if(!is_dir($path)) {
        die("$path is not a directory!");
    }
    $results = array();
    $pending = array();
    $dir = opendir($path);
    while (false !== ($file=readdir($dir))) {
        if (substr($file, 0, 1)=='.'){
            continue;
        }
        $fullpath = $path . '/' . $file;
        if (is_dir($fullpath)) {
            if($file=='help'){
                $results[]=$fullpath;
            } else {
                $pending[] = $fullpath;
            }
        }
    }
    closedir($dir);
    foreach ($pending as $pend) {
        $results = array_unique(array_merge($results, help_dirs_to_check($pend)));
    }
    $summary->help_dirs = count($results);
    return $results;
}

function help_dir_scan($path){
    global $summary;
    $obj = new stdClass();
    $obj->root = $path;
    $obj->rootfiles=array();
    $obj->subdirs=array();
    if(!is_dir($path)) {
        die("$path is not a directory!");
    }
    $dir = opendir($path);
    while (false !== ($file=readdir($dir))) {
        if (substr($file, 0, 1)=='.'){
            continue;
        }
        $fullpath = $path . '/' . $file;
        if (is_dir($fullpath)) {
            $summary->help_dirs++;
            $obj->subdirs[$fullpath] = help_dir_scan($fullpath);
        }
        if(is_file($fullpath) && strpos($file, '.htm')!==false){
            $summary->help_files++;
            $obj->rootfiles[] = $fullpath;
        }
    }
    sort($obj->rootfiles);
    closedir($dir);
    return $obj;
}

function build_select($id, $options, $sel=0){
    $html = "<select name=\"$id\">";
    foreach ($options as $key => $val) {
        if ($key==$sel) {
            $html .= "\n" . "<option selected value=\"$key\">$val</option>";
        } else {
            $html .= "\n" . "<option value=\"$key\">$val</option>";
        }
    }
    $html .= "\n" . "</select>";
    return $html;
}

function print_help_dir($obj) {
    global $root_path, $scan_path;
    if(count($obj->rootfiles > 0)) {
        echo "<form name=\"frm_{$obj->root}\" id=\"frm_{$obj->root}\" action=\"fixhelpdir.php\" method=\"POST\">";
        echo "<input type=\"hidden\" name=\"helpdir\" value=\"{$obj->root}\" />";
        echo "<input type=\"hidden\" name=\"root_path\" value=\"{$root_path}\" />";
        echo "<input type=\"hidden\" name=\"scan_path\" value=\"{$scan_path}\" />";
        echo "<table class='dirsummary'><tr><th colspan='4'>{$obj->root}</th></tr>";
        echo "<tr><td colspan=4 align=center><input type=\"button\" name=\"btnDelete\" value=\"Delete All\" onclick=\"switchAllHelp('frm_{$obj->root}', 'DELETE');\" />";
        echo "&nbsp;&nbsp;&nbsp;<input type=\"button\" name=\"btnIgnore\" value=\"Ignore All\" onclick=\"switchAllHelp('frm_{$obj->root}', 'IGNORE');\" />";
        echo "&nbsp;&nbsp;&nbsp;<input type='submit' name=\"btnSubmit\" value='Do Stuff!' /</td></tr>";
        $chunks = explode('/',$obj->root);
        $numchunks = count($chunks);
        $langfolder = ""; $lang2folder = "";
        for ($x = 0; $x < $numchunks; $x++) {
            $langfolder .= $chunks[$x] . '/';
            $lang2folder .= $chunks[$x] . '/';
            if ($chunks[$x] == 'lang') {
                $lang = $chunks[$x+1];
                $lang2 = (strpos($lang,"_utf8")!==false)?str_replace("_utf8", "", $lang):null;
                $langfolder .= $lang;
                $lang2folder .= $lang;
                break;
            }
        }
        if ($lang2 && !is_dir($lang2folder)) {
            $lang2folder = null;
        }
        //get all lang files in langfolder
        $defaultsel = null; $langfiles = array();
        $langfiles['IGNORE'] = "-- IGNORE --";
        $langfiles['DELETE'] = "-- DELETE --";
        $modfiles = get_files_by_ext($langfolder,'.php',true);
        if(count($modfiles) > 0) {
            reset($modfiles); $defaultsel = key($modfiles);
            $langfiles = array_merge($langfiles,$modfiles);
        }
        if ($lang2folder) {
            $modfiles = get_files_by_ext($lang2folder,'.php',true);
            if(count($modfiles) > 0) {
                reset($modfiles); $defaultsel = key($modfiles);
                $langfiles = array_merge($langfiles,$modfiles);
            }
        }
        $langfiles['DNU1'] = "-- Other Totara --";
        $coremodules = array('core', 'customfield', 'dashboard' . 'hierarchy', 'oauth', 'plan', 'program', 'reportbuilder', 'reportheading');
        foreach ( $coremodules as $core) {
            $mod = $root_path . '/totara/' . $core . '/lang/' . $lang . '/totara_' . $core . '.php';
            if (!in_array($mod, $langfiles) && is_file($mod)) {
                $langfiles[$mod] = $mod;
            }
            $mod = $root_path . '/totara/' . $core . '/lang/' . $lang2 . '/totara_' . $core . '.php';
            if (!in_array($mod, $langfiles) && is_file($mod)) {
                $langfiles[$mod] = $mod;
            }
        }
        if (empty($defaultsel)) {$defaultsel = 'DNU1';}

        $langfiles['DNU2'] = "-- Moodle Core --";
        $langfiles = array_merge($langfiles, get_files_by_ext($root_path . '/lang/' . $lang,'.php',true));
        $langfiles = array_merge($langfiles, get_files_by_ext($root_path . '/lang/' . $lang2,'.php',true));
        foreach ($obj->rootfiles as $file) {
            $fn = str_replace($obj->root . "/","",$file);
            $fnkey = str_replace(".html","",$fn);
            $thissel = ($fnkey == 'index')?'DELETE':$defaultsel;
            echo "<tr><td width=\"30%\">" . $fn . "</td><td width=\"15%\">$lang</td>";
            echo "<td>" . build_select($fnkey, $langfiles,$thissel) . "</td><td width=\"10%\">&nbsp;</td>";
            echo "</tr>";
        }
        echo "</table></form>";
    }
    if(count($obj->subdirs > 0)) {
        foreach ($obj->subdirs as $subdir => $rec) {
            print_help_dir($rec);
        }
    }
}
/**
 * Given one full path, return one array with all the lang dirs to check
 */
function lang_dirs_to_check($path) {
    if(!is_dir($path)) {
        die("$path is not a directory!");
    }
    $results = array();
    $pending = array();
    if (basename($path) == 'lang') {
        $results[] = $path;
    }
    $dir = opendir($path);
    while (false !== ($file=readdir($dir))) {
        if (substr($file, 0, 1)=='.') {continue;}
        $fullpath = $path . '/' . $file;
        if (is_dir($fullpath)) {
            if ($file == 'lang') {
                $results[] = $fullpath;
            } else {
                $pending[] = $fullpath;
            }
        }
    }
    closedir($dir);
    foreach ($pending as $pend) {
        $results = array_unique(array_merge($results, lang_dirs_to_check($pend)));
    }
    return $results;
}
/**
* Given a lang dir, identify all language packs
*/
function langs_in_dir($path) {
    global $summary;
    if(!is_dir($path)) {
        die("$path is not a directory!");
    }
    $results = array();
    $dir = opendir($path);
    while (false !== ($file=readdir($dir))) {
        if (substr($file, 0, 1)=='.'){
            continue;
        }
        $fullpath = $path . '/' . $file;
        if (is_dir($fullpath)) {
            $results[]=$file;
        }
    }
    closedir($dir);
    $summary->lang_dirs += count($results);
    return $results;
}
/**
* Given a lang dir, identify all known problems
*/

function get_files_by_ext($path, $ext='.php', $fullpath=false){
    $files = array();
    if(!is_dir($path)) {
        echo("<br /><b>$path</b> is not a directory!");
        return $files;
    }
    $dir = opendir($path);
    while (false !== ($file=readdir($dir))) {
        if (substr($file, 0, 1)=='.') {
            continue;
        }
        if (is_dir($path . '/' . $file)) {
            continue;
        }
        if (is_file($path . '/' . $file) && strpos($file, $ext)===false) {
            continue;
        } else {
            $idx = ($fullpath)?($path . '/' . $file):$file;
            $files[$idx] = $idx;
        }
    }
    asort($files);
    return $files;
}

function check_lang_dir ($langdir, $lang) {
    global $errors, $summary, $rules;
    $path = $langdir . "/" . $lang;
    if(!is_dir($path)) {
        die("$path is not a directory!");
    }
    if (!isset($errors['LANG'][$langdir][$lang])) {
        $obj = new stdClass();
        $obj->files = array();
    } else {
        $obj = $errors['LANG'][$langdir][$lang];
    }
    //check dir for all languages
    $files = get_files_by_ext($path);
    foreach ($files as $file) {
            $raw = file_get_contents($path . '/' . $file);
            $contents = explode("\n",$raw);
            $multi = false; $linestr=""; $line=0;
            foreach ($contents as $buffer) {
                $buffer = trim($buffer);
                $bads = array();
                if(!$multi && strlen($buffer)==0 || preg_match('/^(\<\?php|\/\/|\?\>)/i',$buffer,$bads)){
                    $line++; $linestr="";
                    continue;
                }
                $last_two = substr($buffer, -2);
                if($multi){
                    $buffer="\n" . $buffer;
                    $linestr .= $buffer;
                    $line++;
                    
                    if($last_two==="\";" || $last_two==="';"){
                        $multi=false;
                    }else{
                        continue;
                    }
                }else{
                    $linestr .= $buffer;
                    $line++;
                    if($last_two!=="\";" && $last_two!=="';"){
                        $multi=true;
                        continue;
                    }
                }

                //echo "<br /><textarea rows='4' cols='80'>" . $linestr . "</textarea>";
                $matches=array();
                preg_match('/\$string\[\'(?P<stringvar>[^\']*)\'\]\s=\s(?P<singlequoted>\'|")(?P<text>.*?)(?=($|\';|";))/s', $linestr, $matches);
                if (!empty($matches)) {
                    $parsestring = trim($matches['text']);
                    //if (substr($parsestring,strlen($parsestring)-2) != ($matches['singlequoted'].';')) {
                    //    die('arrrrrgh ' . $file . "line $line $linestr");
                    //} else {
                        //now to get the correct linenumber for multiline strings we need to explode and loop
                        $chunks = explode("\n",$parsestring);
                        $chunkcount=count($chunks);
                        //double quoted lines are handled slightly differently
                        if ($matches['singlequoted'] == '"') {
                            $type = 'DOUBLE_QUOTED_LINE';
                            if (!isset($obj->files[$file])) {
                                $obj->files[$file] = array(); $summary->lang_files++;
                            }
                            $trueline = $line - ($chunkcount);
                            $err = array('line'=>$trueline,'category'=>'LANGUAGE','type'=>$type, 'ruleindex'=>0, 'regex'=>'', 'match' => '"', 'stringvar'=>$matches['stringvar'],'text'=>$matches['text']);
                            $obj->files[$file][] = $err;
                            $summary->lang_errors++;
                        }
                        for ($offset=0; $offset<$chunkcount; $offset++) {
                            $chunk = $chunks[$offset];
                            $trueline = $line - ($chunkcount-($offset+1));
                            //parse rules
                            $excludes = $rules['LANGUAGE']['EXCLUDES'];
                            foreach ($rules['LANGUAGE'] as $type => $regex) {
                                if ($type == 'EXCLUDES') {
                                    continue;
                                }
                                foreach ($regex as $key => $regex) {
                                    $match = array();
                                    if (!empty($regex) && preg_match_all($regex, $chunk, $match) && !preg_match($excludes, $chunk)) {
                                        if (!isset($obj->files[$file])) {
                                            $obj->files[$file] = array(); $summary->lang_files++;
                                        }
                                        for ($x=0; $x<count($match[0]); $x++) {
                                            $matchstr='';
                                            if ($type == 'BAD_A') {
                                                if (isset($match[2]) && isset($match[2][$x]) && !empty($match[2][$x])) {
                                                    $matchstr .= $match[2][$x];
                                                }
                                                if (isset($match[3][$x]) && !empty($match[3][$x])) {
                                                    $matchstr .= $match[3][$x];
                                                }
                                                if (isset($match[4][$x])) { //internal bit - can be an number (and hence zero) if form $a[x]
                                                    $matchstr .= '' . $match[4][$x];
                                                }
                                                if (isset($match[5][$x]) && !empty($match[5][$x])) {
                                                    $matchstr .= $match[5][$x];
                                                }
                                                //if($file=='auth_mnet.php'){echo "<br />$file $trueline $matchstr " . print_r($match,true);}

                                            } else {
                                                $matchstr = $match[0][$x];
                                            }
                                            $obj->files[$file][] = array('line'=>$trueline,'category'=>'LANGUAGE','type'=>$type, 'ruleindex'=>$key, 'regex'=>$regex, 'match' => $matchstr, 'stringvar'=>$matches['stringvar'],'text'=>$chunk);
                                            $summary->lang_errors++;

                                        }
                                    }
                                }
                            }
                            //specials
                            $regex = '/.\\\"/';
                            if ($matches['singlequoted'] == "'" && preg_match($regex, $chunk, $match)) {
                                $type = 'ESCAPED_DOUBLE_QUOTE';
                                if (!isset($obj->files[$file])) { $obj->files[$file] = array(); $summary->lang_files++; }
                                $obj->files[$file][] = array('line'=>$trueline,'category'=>'LANGUAGE','type'=>$type, 'ruleindex'=>0, 'regex'=>$regex, 'match' => $match[0], 'stringvar'=>$matches['stringvar'],'text'=>$chunk);
                                $summary->lang_errors++;
                            }
                        }
                    //}
                }
                if(!$multi){
                    $linestr="";
                }
            }
        }
    $errors['LANG'][$langdir][$lang] = $obj;
}
/**
* Given one full path, return one array with all the non-lang dirs to check
*/
function code_dirs_to_check($path) {

    if(!is_dir($path)) {
        die("$path is not a directory!");
    }
    $results = array();
    $pending = array();
    $dir = opendir($path);
    while (false !== ($file=readdir($dir))) {
        if (substr($file, 0, 1)=='.'){
            continue;
        }
        $fullpath = $path . '/' . $file;
        //ignore lang & help dirs
        $fullpath = $path . '/' . $file;
        if (is_dir($fullpath) && $file!='lang' && $file!='help') {
            $pending[] = $fullpath;
        }
        if (is_file($fullpath) && strpos($file, basename(__FILE__))!==false) {
            /// Exclude me
            continue;
        }
        if (is_file($fullpath) && (strpos($file, '.php')===false && strpos($file, '.html')===false &&strpos($file,'.xml')===false))  {
            /// Exclude some files
            
            continue;
        }
        //at least one php file in this dir, add it
        if (!in_array($path, $results)) {
            /// Add dir if doesn't exist
            $results[] = $path;
        }
    }
    closedir($dir);
    foreach ($pending as $pend) {
        $results = array_unique(array_merge($results, code_dirs_to_check($pend)));
    }
    return $results;
}
/**
* Given one full path, return one array with all the code files to check
*/
function code_files_to_check($path) {

    $results = array();
    $pending = array();
    if(!is_dir($path)) {
        die("$path is not a directory!");
    }
    $dir = opendir($path);
    while (false !== ($file=readdir($dir))) {

        $fullpath = $path . '/' . $file;

        if (substr($file, 0, 1)=='.' || $file=='CVS') {
            /// Exclude some dirs
            continue;
        }

        if (is_dir($fullpath)) {
            /// ignore dirs
            continue;
        }

        if (is_file($fullpath) && strpos($file, basename(__FILE__))!==false) {
            /// Exclude me
            continue;
        }

        if (is_file($fullpath) && (strpos($fullpath, 'lib/adodb')!==false ||
        strpos($fullpath, 'lib/pear')!==false ||
        strpos($fullpath, 'lib/simpletest')!==false ||
        strpos($fullpath, 'lib/htmlpurifier')!==false ||
        strpos($fullpath, 'lib/memcached.class.php')!==false ||
        strpos($fullpath, 'lib/eaccelerator.class.php')!==false ||
        strpos($fullpath, 'lib/phpmailer')!==false ||
        strpos($fullpath, 'lib/simplepie/simplepie.class.php')!==false ||
        strpos($fullpath, 'lib/soap')!==false ||
        strpos($fullpath, 'lib/zend/Zend/Amf/Adobe/DbInspector.php')!==false ||
        strpos($fullpath, 'search/Zend/Search')!==false ||
        strpos($fullpath, 'lang/')!==false ||
        strpos($fullpath, 'config.php')!==false ||
        strpos($fullpath, 'config-dist.php')!=false)) {
            /// Exclude adodb, pear, simpletest, htmlpurifier, memcached, phpmailer, soap and lucene libs, lang and config files
            continue;
        }

        if (is_file($fullpath) && strpos($file, '.php')===false && strpos($file, '.html')===false && strpos($file,'.xml')===false) {
            /// Exclude some files
            continue;
        }

        if (!in_array($fullpath, $results)) {
            /// Add file if doesn't exists
            $results[] = $fullpath;
        }
    }
    sort($results);
    closedir($dir);

    return $results;
}

function check_code_file($file, &$obj) {
    global $rules, $summary;
    $raw = file_get_contents($file);
    $contents = explode("\n",$raw);
    $line=0;
    foreach ($contents as $buffer) {
        $line++;
        /// Search for regex rules
        foreach ($rules as $rulecategory => $ruletypes) {
            if($rulecategory=='LANGUAGE') {
                continue;
            }
            $excludes = $ruletypes['EXCLUDES'];
            foreach ($ruletypes as $type=>$rule) {
                if($type=='EXCLUDES') {
                    continue;
                }
                $matches = array();
                foreach($rule as $index => $regex) {
                    if (!empty($regex) && preg_match($regex, $buffer, $matches) && !preg_match($excludes, $buffer)) {
                        // Error found, add to errors
                        if (!isset($obj->files[$file])) {
                            $obj->files[$file] = array();
                            $summary->code_files++;
                        }
                        $err = array();
                        $err['line'] = $line;
                        $err['category'] = $rulecategory;
                        $err['type'] = $type;
                        $err['ruleindex'] = $index;
                        $err['regex'] = $regex;
                        $err['match'] = $matches[0];
                        //echo "<br />add err $line to $file";
                        $obj->files[$file][] = $err;
                        $summary->code_errors++;
                    }
                }
                
            }
        }
    }
}
/**
 * DEPRECATED in favour of more clarity on the actual regex that triggered the error
* Given an array of search patterns, create one "megarule", with the specified prefixes and suffixes
*/
function calculate_megarule($patterns, $prefixes=array(), $suffixes=array(), $modifiers='') {

    $megarule  = '';
    $totalrule = '';

    if (empty($patterns)) {
        return false;
    }

    foreach ($patterns as $pattern) {
        $megarule .= '|(?:' . $pattern . ')';
    }
    $megarule = trim($megarule, '|');

    /// Add all the prefix/suffix combinations
    foreach ($prefixes as $prefix) {
        foreach ($suffixes as $suffix) {
            $totalrule .= '|(?:' . $prefix . '(?:' . $megarule . ')' . $suffix . ')';
        }
    }
    $totalrule = trim($totalrule, '|');

    return '/' . (empty($totalrule) ? $megarule : $totalrule) . '/' . $modifiers;
}

/// Function used to discard some well known false positives ($fp) when
/// some $text in $file has been detected as error. Only processed if
/// we detect the script is being executed from moodle root directory.
/// Simply returns true/false
function is_known_false_positive($fp, $file, $text, $is_moodle_root = false) {

    if (!$is_moodle_root) {
        return false;
    }

    /// Take out dirroot from $file
    $file = trim(str_replace(dirname(__FILE__), '', $file), '/');

    /// Look for $file in array of known false positives
    if (array_key_exists($file, $fp)) {
        foreach ($fp[$file] as $fprule) {
            if (preg_match('/' . $fprule . '/i', $text)) {
                return true;
            }
        }
    }

    /// Arrived here, no false positives found for that file/$text
    return false;
}

?>