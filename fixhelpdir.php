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

include_once 'lib/markdownify/markdownify_extra.php';
$helpdir= isset($_POST['helpdir']) ? $_POST['helpdir'] : die('bad form - helpdir');
$root_path= isset($_POST['root_path']) ? $_POST['root_path'] : die('bad form - root_path');
$scan_path= isset($_POST['scan_path']) ? $_POST['scan_path'] : die('bad form - scan_path');
echo "<br />Fixing $helpdir";

//sort the POST by value so we don't have to constantly include the same langfile for checking
asort($_POST);
reset($_POST);
$cur_langfile = null; $lastline = -1;
$deletedfiles = array();
foreach ($_POST as $key => $val) {
    if ($key == 'helpdir' || $key == 'root_path' || $key == 'scan_path') {continue;}
    if ($val == 'IGNORE') {continue;}
    if ($val == 'DELETE') {
        echo '<br />Delete ' . $helpdir . '/' . $key . '.html';
        $deletedfiles[] = $helpdir . '/' . $key . '.html';
        continue;
    }

    if (empty($cur_langfile) || $cur_langfile!=$val) {
        //new langfile - first add to old
        if (!empty($cur_langfile) && count($newbuffer) > 0) {
            echo "<br />add buffer to $cur_langfile";
            foreach ($newbuffer as $buffkey => $newline) {
                $contents[$lastline + ($buffkey+1)] = $newline;
            }
            $contents[] = "\n";
            file_put_contents($cur_langfile, implode("\n",$contents));
            unset($contents);
            unset($newbuffer);
            if (isset($string)) {
                unset($string);
            }
        }
        echo "<br /><br />new langfile $val";
        include $val;
        $cur_langfile = $val;
        //open file, find last non-$string[] line
        $raw = file_get_contents($val);
        $contents = explode("\n",$raw);
        $lines = count($contents); $lastline = -1;
        for ($x=$lines-1; $x>=0; $x--) {
            $line = $contents[$x];
            if (substr($line,0,7) == '$string') {
                $lastline = $x;
                break;
            }
        }
        echo " lastline: $lastline";
        $newbuffer = array();
    }
    echo '<br />Transfer ' . $helpdir . '/' . $key . '.html to ' . $val;
    if (isset($string[$key . '_help'])) {
        echo "<br /><b>WARNING</b> string['" . $key . "_help'] already exists in " . $val . ", ignoring";
    } else {
        //markdownify
        $help = file_get_contents($helpdir . '/' . $key . '.html');
        $md = new Markdownify_Extra();
        $output = $md->parseString($help);
        $newbuffer[] = '$string[\'' . $key . '_help\'] = \'' . str_replace("'","\'",$output) . '\';';
        $deletedfiles[] = $helpdir . '/' . $key . '.html';
    }
}
//clean up
if (isset($newbuffer) && !empty($newbuffer)) {
    foreach ($newbuffer as $buffkey => $newline) {
        $contents[$lastline + ($buffkey+1)] = $newline;
    }
    $contents[] = "\n";
    file_put_contents($cur_langfile, implode("\n",$contents));
    unset($contents);
    unset($newbuffer);
    if (isset($string)) {
        unset($string);
    }
}

if (isset($string)) {
    unset($string);
}

echo "<br /><br />Deleting files...";
foreach ($deletedfiles as $file) {
    echo "<br />$file";
    unlink($file);
}
echo "<br />DONE<br />";
echo '<form action="m2check.php" method="POST">';
echo "<input type=\"hidden\" name=\"root_path\" value=\"{$root_path}\" />";
echo "<input type=\"hidden\" name=\"scan_path\" value=\"{$scan_path}\" />";
echo "<input type='submit' value='Back To Scan' />";
echo '</form>';
?>