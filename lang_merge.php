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

set_time_limit(0);
error_reporting(E_ALL);

$errors = 0;

function handleError($errno, $errstr,$error_file,$error_line)
{
    global $errors;
    echo "<b>Error:</b> [$errno] $errstr - $error_file:$error_line";
    echo "<br />";
    $errors++;
}
set_error_handler("handleError");

function copyr($source, $dest)
{
    // Check for symlinks
    if (is_link($source)) {
        return symlink(readlink($source), $dest);
    }

    // Simple copy for a file
    if (is_file($source)) {
        return copy($source, $dest);
    }

    // Make destination directory
    if (!is_dir($dest)) {
        mkdir($dest);
    }

    // Loop through the folder
    $dir = dir($source);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        // Deep copy directories
        copyr("$source/$entry", "$dest/$entry");
    }

    // Clean up
    $dir->close();
    return true;
}

function unlinkRecursive($dir, $deleteRootToo)
{
    if(!$dh = @opendir($dir))
    {
        return;
    }
    while (false !== ($obj = readdir($dh)))
    {
        if($obj == '.' || $obj == '..')
        {
            continue;
        }

        if (is_dir($dir . '/' . $obj) || !@unlink($dir . '/' . $obj))
        {
            unlinkRecursive($dir.'/'.$obj, true);
        }
    }

    closedir($dh);
     
    if ($deleteRootToo)
    {
        @rmdir($dir);
    }
     
    return;
}

$root_path = isset($_POST['root_path']) ? $_POST['root_path'] : dirname(__FILE__);
if (substr($root_path, -1) == '/') {
    $root_path = substr($root_path,0,strlen($dir)-1);
}
$scan_path = isset($_POST['scan_path']) ? $_POST['scan_path'] : null;
if (!empty($scanpath) && substr($scan_path, -1) == '/') {
    $scan_path = substr($scan_path,0,strlen($scan_path)-1);
}

$dir = (empty($scan_path))? $root_path : $root_path . '/' . $scan_path;
//setup summary obj from m2check.php to avoid errors
$summary = new stdClass();
$summary->lang_dirs = 0;
$summary->lang_files = 0;
$summary->lang_errors = 0;
$summary->code_dirs = 0;
$summary->code_files = 0;
$summary->code_errors = 0;
$summary->help_dirs = 0;
$summary->help_files = 0;
require_once('functions.php');
include_once('web_styles.php');

$langdirs = lang_dirs_to_check($dir);
foreach ($langdirs as $langdir) {
    $langs = langs_in_dir($langdir);
    foreach ($langs as $lang) {
        if (substr($lang,-5) == '_utf8') {
            $lang2 = str_replace("_utf8", "", $lang);
            //create the non-utf8 directory if it does not exist
            if (!file_exists($langdir . '/' . $lang2)) {
                mkdir($langdir . '/' . $lang2);
            }
            //copy all help files if they exist
            if (is_dir($langdir . '/' . $lang . '/help')) {
                copyr($langdir . '/' . $lang . '/help', $langdir . '/' . $lang2 . '/help');
            }
            //get the files in this dir
            $langfiles = get_files_by_ext($langdir . '/' . $lang);
            foreach ($langfiles as $langfile) {
                //does one already exist?
                if (file_exists($langdir . '/' . $lang2 . '/' . $langfile)) {
                    $lastline = -1;
                    $val = $langdir . '/' . $lang2 . '/' . $langfile;
                    //use similar to helpfiles fixing code
                    //first get destination file into $string
                    include $val;
                    //then get $contents and $lastline
                    //open file, find last non-$string[] line
                    $raw = file_get_contents($val);
                    $contents = explode("\n",$raw);
                    unset($raw);
                    $lines = count($contents); $lastline = -1;
                    for ($x=$lines-1; $x>=0; $x--) {
                        $line = $contents[$x];
                        if (substr($line,0,7) == '$string') {
                            $lastline = $x;
                            break;
                        }
                    }
                    if ($lastline == -1) {
                        //default to second line and overwrite
                        $lastline = 1;
                    }
                    //now open our source file into $newbuffer
                    $raw = file_get_contents($langdir . '/' . $lang . '/' . $langfile);
                    $newbuffer = explode("\n",$raw);
                    unset($raw);
                    //regex it as per scanner language-error checking
                    $multi = false; $linestr=""; $line=0; $added = 0;
                    foreach ($newbuffer as $buffer) {
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
                        $matches=array();
                        preg_match('/\$string\[\'(?P<stringvar>[^\']*)\'\]\s=\s(?P<singlequoted>\'|")(?P<text>.*?)(?=($|\';|";))/s', $linestr, $matches);
                        if (!empty($matches)) {
                            $parsestring = trim($matches['text']);
                            $stringvar = $matches['stringvar'];
                            if (!isset($string[$stringvar])) {
                                $contents[$lastline + ($added+1)] = '$string[\'' . $stringvar . '\'] = \'' . str_replace("'","\'",$parsestring) . '\';';
                                echo "<br />adding string['" . $stringvar . "'] to $val";
                                $added++;
                            } else {
                                echo "<br />string['" . $stringvar . "'] already exists in $val";
                            }
                        }
                        if(!$multi){
                            $linestr="";
                        }
                    }
                    if ($added > 0) {
                        $contents[] = "\n";
                        file_put_contents($val, implode("\n",$contents));
                        $added = 0;
                    }
                } else {
                    //straightforward copy
                    echo "<br />copy $langfile to " . $langdir . '/' . $lang2;
                    copy($langdir . '/' . $lang . '/' . $langfile, $langdir . '/' . $lang2 . '/' . $langfile);
                }
            }
            //now kill the old utf8 if no errors
            if ($errors==0) {
                unlinkRecursive($langdir . '/' . $lang, true);
            }
        }
    }
}