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
$root_path = isset($_POST['root_path']) ? $_POST['root_path'] : dirname(__FILE__);
if (substr($root_path, -1) == '/') {
    $root_path = substr($root_path,0,strlen($dir)-1);
}
$scan_path = isset($_POST['scan_path']) ? $_POST['scan_path'] : null;
if (!empty($scanpath) && substr($scan_path, -1) == '/') {
    $scan_path = substr($scan_path,0,strlen($scan_path)-1);
}

$dir = (empty($scan_path))? $root_path : $root_path . '/' . $scan_path;

require_once('functions.php');
$rules = array();
/// List of patterns to search
require_once('rules.php');

$errors = array('CODE'=>array());
$codedirs = code_dirs_to_check($dir);
foreach ($codedirs as $codedir) {
    if (!isset($errors['CODE'][$codedir])) {
        $obj = new stdClass();
        $obj->files = array();
    } else{
        $obj = $errors['CODE'][$codedir];
    }
    $files = code_files_to_check($codedir);
    foreach($files as $file){
        check_code_file($file, $obj);
    }
    if(count($obj->files)>0){
        $summary->code_dirs++;
    }
    $errors['CODE'][$codedir] = $obj;
    unset($obj);
}

$billboard = array();
foreach ($errors['CODE'] as $codedir => $obj) {
    $files = $obj->files;
    foreach ($files as $file => $errs) {
        foreach ($errs as $err) {
            $key = $err['category'] . "_" . $err['type']  . "_" . $err['ruleindex'];
            if (!isset($billboard[$key])) {
                $billboard[$key] = 1;
            } else {
                $billboard[$key]++;
            }
        }
    }
}

echo "<p align=\"center\"><b>scanning $dir</b></p>";
echo "<p align=\"center\"><b>Top 20 Errors</b></p>";
arsort($billboard);
$total = array_sum($billboard);
$i = 0; $top20 = 0;
foreach ($billboard as $single => $sales) {
    //if ($i >= 20) { break; }
    list($cat,$typ,$idx) = explode("_", $single);
    echo "<br />" . ($i+1) . " - $single " .  $rules[$cat][$typ][intval($idx)]  . " - $sales";
    if ($i<20) {$top20 += $sales;}
    $i++;
}
echo "<br /><br />Top 20 are $top20 out of $total - " . number_format( ($top20/$total)*100,2,".",",") . "%";
?>