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

$root_path= isset($_POST['root_path']) ? $_POST['root_path'] : die('bad form - root_path');
$scan_path= isset($_POST['scan_path']) ? $_POST['scan_path'] : die('bad form - scan_path');
$output_format= isset($_POST['output_format']) ? $_POST['output_format'] : die('bad form - output_format');
$oldfile= isset($_POST['oldfile']) ? $_POST['oldfile'] : die('bad form - oldfile');
$newfile= isset($_POST['newfile']) ? $_POST['newfile'] : die('bad form - newfile');
$newfile = base64_decode($newfile);

$dir=$oldfile;
include_once('web_styles.php');

$newfile=str_replace("*M2SCAN","",$newfile);
$newfile=str_replace("M2SCAN*","",$newfile);
//collect all manual overrides
$overrides = array();
$newlines = array();
$removelines = array();
if (isset($_POST['removelines'])) {
    $arr = explode(",", $_POST['removelines']);
    foreach ($arr as $key => $val) {
        $removelines[$val] = $val;
    }
}

foreach ($_POST as $key => $val) {
    if (substr($key,0,7)=="manual_") {
        list($junk, $line, $junk2) = explode("_",$key);
        $overrides[$line] = base64_decode($val);
    }
    if (substr($key,0,8)=="newline_") {
        list($junk, $line, $junk2) = explode("_",$key);
        $newlines[$line] = base64_decode($val);
    }
}

//build the final version of the file
$final_file="";
$contents = explode("\n",$newfile);
$line=0;
foreach ($contents as $key => $buffer) {
    $line++;
    if (count($removelines) > 0 && isset($removelines[$line])) {
        continue;
    }
    if (strlen($buffer) > 0 && strlen(trim($buffer)) == 0) {
        //whitespace only...ignore
        continue;
    }
    if (isset($overrides[$line])) {
        $newline = $overrides[$line];
    } else {
        $newline = $buffer;
    }
    if (isset($newlines[$line])) {
        $newline .= "\n" . $newlines[$line];
    }
    $final_file .= $newline . "\n";
}
$final_file = substr($final_file, 0, -1);
$final_file = (mb_detect_encoding($final_file, "UTF-8") == "UTF-8") ? $final_file : utf8_encode($final_file);
//do filesystem operations
$fh=fopen($oldfile . ".m2scan","w");
fwrite($fh, $final_file);
fclose($fh);
//file_put_contents($oldfile . ".m2scan", $final_file);
unlink($oldfile);
rename($oldfile . ".m2scan", $oldfile);
chmod($oldfile,0666);
//return to scan
$dir=$oldfile;
if ($errors>0) {
    $strMsg="File Saved - if there are no PHP errors displayed then you can return to the scan to try another file";
} else {
    $strMsg="";
}

?>
<div style="margin-top:35px;"></div>
<form name="frmScan" method="POST" action="m2check.php">
<input type="hidden" name="root_path" value="<?php echo $root_path; ?>" />
<input type="hidden" name="scan_path" value="<?php echo $scan_path; ?>" />
<input type="hidden" name="output_format" value="<?php echo $output_format; ?>" />
<div class="centered"><?php echo $strMsg; ?></div>
<div class="centered"><input type="submit" value="Back to Scan"></div>
</form>
<?php
if ($errors==0) {
    //autosubmit form
    echo "<script>";
    echo "    document.forms['frmScan'].submit();";
    echo "</script>";
}
?>
</body></html>
