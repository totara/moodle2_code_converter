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
    $root_path = substr($root_path,0,strlen($root_path)-1);
}
$scan_path = isset($_POST['scan_path']) ? $_POST['scan_path'] : null;
if (!empty($scanpath) && substr($scan_path, -1) == '/') {
    $scan_path = substr($scan_path,0,strlen($scan_path)-1);
}

$dir = (empty($scan_path))? $root_path : $root_path . '/' . $scan_path;

$output_format = isset($_POST['output_format']) ? $_POST['output_format'] : 'screen';

require_once('functions.php');
$rules = array();
/// List of patterns to search
require_once('rules.php');

$errors = array('LANG'=>array(), 'CODE'=>array(), 'HELP'=>array());
$summary = new stdClass();
$summary->lang_dirs = 0;
$summary->lang_files = 0;
$summary->lang_errors = 0;
$summary->code_dirs = 0;
$summary->code_files = 0;
$summary->code_errors = 0;
$summary->help_dirs = 0;
$summary->help_files = 0;

$langdirs = lang_dirs_to_check($dir);
foreach ($langdirs as $langdir) {
    if (!isset($errors['LANG'][$langdir])) {
        $errors['LANG'][$langdir]=array();
    }
    $langs = langs_in_dir($langdir);
    foreach($langs as $lang){
        //if(substr($lang,-5)=='_utf8'){
        //    die('Moodle 1.9 ' . $lang . ' language directory detected: run a language merge before proceeding with scan');
        //}
        check_lang_dir($langdir,$lang);
    }
}

$helpdirs = help_dirs_to_check($dir);
foreach ($helpdirs as $helpdir) {
    $errors['HELP'][] = help_dir_scan($helpdir);
}

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
    if(count($obj->files)>0){$summary->code_dirs++;}
    $errors['CODE'][$codedir] = $obj;
    unset($obj);
}

if ($output_format == 'csv') {
    $fp = fopen('php://output', 'w');
    // send response headers to the browser
    header( 'Content-Type: text/csv' );
    header( 'Content-Disposition: attachment;filename='. substr($dir,strrpos($dir,"/")+1) . '.csv');
    fputs($fp,"|SUMMARY FOR $dir|\n");
    fputs($fp,"|TYPE|,|DIRECTORIES|,|FILES|,|ERRORS|\n");
    fputs($fp,"|Language|,{$summary->lang_dirs},{$summary->lang_files},{$summary->lang_errors}\n");
    fputs($fp,"|Code|,{$summary->code_dirs},{$summary->code_files},{$summary->code_errors}\n\n");
    
    foreach ($errors['LANG'] as $langdir => $langs) {
        fputs($fp, "|LANGUAGE ERRORS: $langdir|\n");
        foreach ($langs as $lang => $obj) {
            fputs($fp, "$lang\n");
            $files = $obj->files;
            foreach($files as $file => $errs){
                if(count($errs) == 0){
                    continue;
                }
               fputs($fp, "$file\n");
               fputs($fp,"|Line #|,|Category|,|Type|,|String Var|,|Text|\n");
               foreach ($errs as $err) {
                   fputcsv($fp, $err, ",", '"');
               }
               fputs($fp, "\n");
            }
        }
    }
    foreach ($errors['CODE'] as $codedir => $obj) {
        fputs($fp, "|CODE ERRORS: $codedir|\n");
            $files = $obj->files;
            foreach($files as $file => $errs){
                if(count($errs) == 0){
                    continue;
                }
                fputs($fp, "$file\n");
                fputs($fp,"|Line #|,|Category|,|Type|,|Text|\n");
                foreach ($errs as $err) {
                    fputcsv($fp, $err, ",", '|');
                }
                fputs($fp, "\n");
            }
    }
    fclose($fp);
} //end csv output

if ($output_format == 'screen') {
    include_once('web_styles.php');
    echo '
        <script>
        function switchAllHelp(frm, val) {
            var thefrm = document.getElementById(frm);
            var elems = thefrm.elements;
            for (var i = 0; i < elems.length; i++) {
                if (elems[i].type == \'select-one\' ) {
                    for (var y = 0; y < elems[i].length; y++) {
                        if(elems[i].options[y].value == val) {
                            elems[i].selectedIndex = y;
                            break;
                        }
                    }
                }
            }
        }
        </script>
    ';
    echo '<div>';
    echo "<div class='summarytitle'>SUMMARY FOR $dir</div>";
    echo "<div><div class='summaryhead'>TYPE</div><div class='summaryhead'>DIRECTORIES</div><div class='summaryhead'>FILES</div><div class='summaryhead'>ERRORS</div></div>";
    echo "<div><div class='summarycell'><a href='#HELP'>Help</a></div>";
    echo "<div class='summarycell'>{$summary->help_dirs}</div>";
    echo "<div class='summarycell'>{$summary->help_files}</div>";
    echo "<div class='summarycell'>&nbsp;</div></div>";
    echo "<div><div class='summarycell'><a href='#LANG'>Language</a></div>";
    echo "<div class='summarycell'>{$summary->lang_dirs}</div>";
    echo "<div class='summarycell'>{$summary->lang_files}</div>";
    echo "<div class='summarycell'>{$summary->lang_errors}</div></div>";
    echo "<div><div class='summarycell'><a href='#CODE'>Code</a></div>";
    echo "<div class='summarycell'>{$summary->code_dirs}</div>";
    echo "<div class='summarycell'>{$summary->code_files}</div>";
    echo "<div class='summarycell'>{$summary->code_errors}</div></div>";
    echo "</div>"; //end #head div
    echo '<div style="clear:both;"></div><br /<a name="HELP"></a>';
    echo "<div style='clear:both;'></div><br /><table class='langdir'><tr><th>HELP FILES</th></tr></table>";
    foreach ($errors['HELP'] as $helpdir) {
        print_help_dir($helpdir);
    }
    echo '</div>';
    echo '<div ><a name="LANG"></a>';
    foreach ($errors['LANG'] as $langdir => $langs) {
        echo "<div style='clear:both;'></div><br /><table class='langdir'><tr><th>LANGUAGE ERRORS: $langdir</th></tr></table>";
        foreach ($langs as $lang => $obj) {
            echo "<table class='dirsummary'><tr><th colspan='5'>$lang</th></tr></table>";
            $files = $obj->files;
            foreach($files as $file => $errs){
                if(count($errs) == 0){
                    continue;
                }
                echo '<form method="POST" action="codefix_ui.php">
                            <input type="hidden" name="filename" value="' . $langdir . '/' . $lang . '/' . $file . '" />
                            <input type="hidden" name="root_path" value="' . $root_path . '" />
                            <input type="hidden" name="scan_path" value="' . $scan_path . '" />
                            <input type="hidden" name="code_errors" value="' . base64_encode(serialize($obj->files[$file])) . '" />
                            ';
                echo "<table class='summary'><tr><th width='80%'>$file</th><th><input type='submit' value='Do Stuff!' /></th></tr></table>";
                echo "</form>";
                $rowcount = 0;
                echo '<table class="scanresult">
                                <tr><th width="10%">Line</th>
                                <th width="15%">Category</th>
                                <th width="15%">Type</th>
                                <th width="25%">String</th>
                                <th width="35%">Text</th></tr>';
                foreach ($errs as $err) {
                    echo '<tr class="r' . ($rowcount%2) . '">
                        <td>' . $err['line'] . '</td>
                        <td>' . $err['category'] . '</td>
                        <td>' . $err['type'] . '</td>
                        <td>' . $err['stringvar'] . '</td>
                        <td>' . $err['text'] . '</td></tr>';
                    $rowcount++;
                }
                echo "</table>\n<br />";
            }
        }
    }
    echo '<a name="CODE"></a>';
    foreach ($errors['CODE'] as $codedir => $obj) {
        if (count($obj->files) == 0) {continue;}
        echo "<table class='dirsummary'><tr><th>CODE ERRORS: $codedir FILES:"  . count($errors['CODE'][$codedir]->files) . "</th></tr></table>";
        foreach ($errors['CODE'][$codedir]->files as $file=>$errs) {

            if (count($errs) == 0) {
                continue;
            }
            
            echo '<form method="POST" action="codefix_ui.php">
            <input type="hidden" name="filename" value="' . $file . '" />
            <input type="hidden" name="root_path" value="' . $root_path . '" />
            <input type="hidden" name="scan_path" value="' . $scan_path . '" />
            <input type="hidden" name="code_errors" value="' . base64_encode(serialize($obj->files[$file])) . '" />
            ';
            echo "<table class='summary'><tr><th width='80%'>$file</th><th><input type='submit' value='Do Stuff!' /></th></tr></table>";
            echo "</form>";
            $rowcount = 0;
            echo '<table class="scanresult">
                    <tr><th width="10%">Line</th>
                    <th width="15%">Category</th>
                    <th width="15%">Type</th>
                    <th width="25%">Match</th></tr>';
            foreach ($errs as $key=>$err) {
                echo '<tr class="r' . ($rowcount%2) . '">
                    <td>' . $err['line'] . '</td>
                    <td>' . $err['category'] . '</td>
                    <td>' . $err['type'] . '</td>
                    <td>' . $err['match'] . '</td>
                    </tr>';
                $rowcount++;
            }
            echo "</table>\n<br />";
        }
    }
    echo "</div></body></html>";
} //end screen output

?>
