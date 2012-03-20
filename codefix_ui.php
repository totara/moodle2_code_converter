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

$root_path= isset($_POST['root_path']) ? $_POST['root_path'] : die('bad form - root_path');
$scan_path= isset($_POST['scan_path']) ? $_POST['scan_path'] : die('bad form - scan_path');
$filename = isset($_POST['filename']) ? $_POST['filename'] : die('bad form - filename');
$code_errors = isset($_POST['code_errors']) ? $_POST['code_errors'] : die('bad form - data');
$code_errors = unserialize(base64_decode($code_errors));

function search($array, $key, $values){
    $results = array();
    if (is_array($array)){
        foreach ($array as $subarray) {
            if (isset($subarray[$key])) {
                foreach ($values as $value) {
                    if ($subarray[$key] == $value){
                        $results[] = $subarray;
                    }
                }
            }
        }
    }
    return $results;
}

$in_transaction = false;

function is_multiline($text) {
    global $in_transaction;
    $line = trim($text);
    if ($line == "begin_sql();") {
        $in_transaction = true;
        return true;
    }
    if ($in_transaction && $line != "commit_sql();") {
        return true;
    }
    if ($in_transaction && $line == "commit_sql();") {
        $in_transaction = false;
        return false;
    }
    $lastchar = substr($text,-1);
    return ($lastchar==';' || $lastchar=='{') ? false :true;
}

function get_multiline(&$file, $startline) {
    $obj = new stdClass();
    $obj->startline = $startline;
    $obj->linearray = array();
    for ($x=$startline; $x < count($file); $x++) {
        if (!is_multiline($file[$x])) {
            $obj->endline = $x + 1;
            break;
        }
    }
    if (isset($obj->endline)) {
        for ($x=$obj->startline; $x<=$obj->endline; $x++) {
            $obj->linearray[] = $x;
        }
        $obj->multiline = '';
        $multiline = array_slice($file,$obj->startline-1,($obj->endline-$obj->startline)+1);
        $obj->multiline = implode("\n", $multiline);
        return $obj;
    } else {
        return false;
    }
}


//load the libraries we need for this file
//base class
require_once('lib/moodle2_fixer.class.php');
foreach ($code_errors as $error) {
    $cat = strtolower($error['category']);
    $file = 'lib/' . $cat . '_fix.class.php';
    if(file_exists($file)){
        require_once($file);
        $fixobj='fix_' .$cat; $fixclass=$cat.'_fix';
        $$fixobj = new $fixclass();
    }
}
$dir = $filename;
include_once('web_styles.php');
$origfile = file_get_contents($filename);
$newfile = "";


?>
<script src="js_base_64.js" /></script>
<script>
var removelines = [];

function saveChanges(){
    var frm = document.forms['frmSave'];
    var elems = document.getElementsByClassName("comment");
    var newelem = null;
    var elem=null;
    for (var i = 0; i < elems.length; i++) {
        elem = elems[i];
        codestr = elem.value;
        if(codestr==''){
            if(!confirm('Manual change ' + elem.id + ' is empty! Select OK to remove this line from the file, or Cancel to skip this manual change')){
                continue;
            }
        }

        codestr= base64.encode(codestr);
        newelem = document.createElement('input');
        newelem.setAttribute("id", elem.id);
        newelem.setAttribute("name", elem.id);
        newelem.setAttribute("type","hidden");
        newelem.setAttribute("value",codestr);
        frm.appendChild(newelem);
    }
    var elems = document.getElementsByClassName("newline");
    var elem=null;
    for (var i = 0; i < elems.length; i++) {
        elem = elems[i];
        codestr = elem.value;
        if(codestr==''){
            if(!confirm('Newline ' + elem.id + ' is empty! Select OK to keep this blank newline in the file, or Cancel to ignore it')){
                continue;
            }
        }
        codestr= base64.encode(codestr);
        newelem = document.createElement('input');
        newelem.setAttribute("id", elem.id);
        newelem.setAttribute("name", elem.id);
        newelem.setAttribute("type","hidden");
        newelem.setAttribute("value",codestr);
        frm.appendChild(newelem);
    }
    if (removelines.length>0) {
        arrstr = removelines.join(',');
        newelem = document.createElement('input');
        newelem.setAttribute("id", 'removelines');
        newelem.setAttribute("name", 'removelines');
        newelem.setAttribute("type","hidden");
        newelem.setAttribute("value",arrstr);
        frm.appendChild(newelem);
    }
    //console.log(frm);
    frm.submit();
}

function abandonChanges(){
    var frm = document.forms['frmAbandon'];
    frm.submit();
}

function removeElement(divNum, ctrl1) {
    var d = document.getElementById(ctrl1);
    var olddiv = document.getElementById(divNum);
    d.removeChild(olddiv);
}

function addManualChange(line) {
    if(document.getElementById('manual_' + line)){
        //already exists
        return false;
    }
    elem = document.getElementById('line_' + line);
    code = document.getElementById('code_' + line);
    codestr = code.innerHTML.replace(/&lt;/g,"<").replace(/&gt;/g,">");
    codestr = code.innerHTML.replace(/&nbsp;/g," ");
    //look for codefix span and remove
    var pos = codestr.indexOf('<span class="codefix">');
    if(pos != -1) {
        var pos2 = codestr.indexOf('</span>',pos+1);
        codestr = codestr.substring(0,pos) + codestr.substring(pos+22,pos2) + codestr.substr(pos2+7);
    }
    var newdiv = document.createElement('div');
    newdiv.setAttribute("id",'manual_' + line);
    newdiv.setAttribute("width","100%");
    newdiv.innerHTML = "<b>Manual Change Line " + line + ":</b><br /><textarea spellcheck=\"false\" rows=\"3\" cols=\"100\" class=\"comment\" name=\""+ 'manual_' + line + '_text' +"\" id=\"manual_" + line + "_text\" >" + codestr + "</textarea> [<b><a href=\"javascript:void(0);return false;\" onclick=\"removeElement(\'"+'manual_' + line +"\', \'"+'line_' + line+"\')\">x</a></b>]<br />";
    elem.appendChild(newdiv);
    return false;
}

function addLine(line) {
    if(document.getElementById('newline_' + line)){
        //already exists
        return false;
    }
    elem = document.getElementById('line_' + line);
    var newdiv = document.createElement('div');
    newdiv.setAttribute("id",'newline_' + line);
    newdiv.setAttribute("width","100%");
    newdiv.innerHTML = "<b>New Line after Line " + line + ":</b><br /><textarea spellcheck=\"false\" rows=\"3\" cols=\"100\" class=\"newline\" name=\""+ 'newline_' + line + '_text' +"\" id=\"newline_" + line + "_text\" ></textarea> [<b><a href=\"javascript:void(0);return false;\" onclick=\"removeElement(\'"+'newline_' + line +"\', \'"+'line_' + line+"\')\">x</a></b>]<br />";
    elem.appendChild(newdiv);
    return false;
}

function removeLine(line) {
    elem = document.getElementById('code_' + line);
    if(elem.className.indexOf('codeproblem') != -1) {
        var reg = new RegExp('(\\s|^)codeproblem(\\s|$)');
        elem.className=elem.className.replace(reg,' ');
        //remove from array
        pos = removelines.indexOf(line);
        if(pos!=-1){
            removelines.splice(pos,1);
        }
    } else {
        elem.className += elem.className ? ' codeproblem' : 'codeproblem';
        removelines.push(line);
    }
    return false;
}
</script>

<div class="content">
<div class="summarytitle">Autofixing <?php echo $filename; ?></div>
<div class="clear"></div>
    <div style="width:100%; text-align:center;">
        <input type="button" value="Save Changes" onclick="saveChanges();">
        &nbsp;&nbsp;&nbsp;
        <input type="button" value="Abandon Changes" onclick="abandonChanges();">
    </div>
<div class="leftpane">
<?php
    $contents = explode("\n",$origfile);
    $line=0;
    $numlines = count($contents);
    $newfile = "";
    for ($line=1; $line<=$numlines; $line++) {
        $multiobj = null;
        $chunks = null;
        $buffer = $contents[$line-1];
        $line_errors=search($code_errors,'line', array($line));

        if(count($line_errors)>0){
            //determine if this is a multiline instance, extract and pass the entire block to fixer class
            $newline = $buffer;
            if (is_multiline($buffer)) {
                $multiobj = get_multiline($contents,$line);
                if ($multiobj) {
                    //recalculate line_errors to include all errors for all these lines
                    $line_errors=search($code_errors,'line', $multiobj->linearray);
                    $newline = $multiobj->multiline;
                    $buffer = $newline;
                } else {
                    //something has gone wrong - process as a normal line
                }
            }
            $chunks = array();
            foreach($line_errors as $err){
                //call lib to get new line
                $cat = strtolower($err['category']);
                $func = strtolower($err['type']);
                $newline = ${'fix_' .$cat}->$func($newline,$err);
                //format for display
                $chunks[] = $err['match'];
            }
        } else {
            //just add to newfile (avoid doubling up on trailing newlines)
            $newline = $buffer;
        }

        //handle display - markup errors
        $buffer = htmlspecialchars($buffer);
        $buffer = str_replace(" ","&nbsp;", $buffer);
        if ($chunks) {
            foreach ($chunks as $chunk) {
                $chunk = htmlspecialchars($chunk);
                $chunk = str_replace(" ", "&nbsp;", $chunk);
                $buffer = str_replace($chunk, '<span class="codeproblem">' . $chunk . '</span>', $buffer);
            }
        }

        $bufferlines = explode("\n",$buffer);
        $buffercount = count($bufferlines);
        $newlines = explode("\n",$newline);
        $newcount = count($newlines);
        $loopcounter = ($newcount>=$buffercount)?$newcount:$buffercount;
        $curline = $line;
        for ($x=0; $x<$loopcounter; $x++) {
            $rowclass = $curline%2;
            if (isset($bufferlines[$x])) {
                echo "<div class=\"line r$rowclass\" ><div class=\"linenumber\"><a class=\"linenum\" href=\"javascript:void(0); return false;\">$curline</a></div>";
                echo "<div class=\"codeline\">" . $bufferlines[$x] . "</div>";
                echo "</div>";
                echo "<div class=\"clear\"></div>";
            } else {
                echo "<div class=\"line r$rowclass addline\" ><div class=\"linenumber\">&nbsp;</div>";
                echo "<div class=\"codeline\">&nbsp;</div></div><div class=\"clear\"></div>";
            }
            if (!isset($newlines[$x])) {
                $newlines[$x] = "*M2SCANREMOVED";
            }
            $curline ++;
        }
        $newline = implode("\n", $newlines);
        //add to newfile (avoid doubling up on trailing newlines)
        $newline .= ($line!=$numlines)?"\n":'';
        $newfile .= $newline;
        //if we now need to leap past the multiline block
        if ($multiobj) {
            $line = $multiobj->endline;
        }
    }
?>
    </div>

    <div class="rightpane">
<?php
    $contents = explode("\n",$newfile);
    $finalfile = ""; //needed so we do not include M2SCANREMOVED lines
    $line=0; $multifix=false;
    foreach ($contents as $buffer) {
        $line++;
        $rowclass = $line%2; 
        $cleanline = $buffer;
        if(strpos($cleanline,'*M2SCANREMOVED')===false){
            //only add non-removed lines
            $cleanline = str_replace('*M2SCAN','',$cleanline);
            $cleanline = str_replace('M2SCAN*','',$cleanline);
            $finalfile .= $cleanline . "\n";
        }
        $buffer = str_replace(" ","&nbsp;",htmlspecialchars($buffer));
        $lineclass = '';
        if(strpos($buffer,'*M2SCANREMOVED')!==false){
            $lineclass = ' codeproblem';
            $buffer = str_replace('*M2SCANREMOVED','',$buffer);
            $line--;
        } else {
            if(strpos($buffer,'M2SCAN')!==false){
                $buffer = str_replace('*M2SCAN','<span class="codefix">',$buffer);
                $buffer = str_replace('M2SCAN*','</span>',$buffer);
            }
        }
        echo "<div class=\"line r$rowclass $lineclass\" id=\"line_$line\">";
        if ($lineclass=='') {
            echo "<div class=\"linenumber\"><a class=\"linenum\" href=\"javascript:void(0); return false;\" onclick=\"return addManualChange($line);\">$line</a></div>";
        } else {
            echo "<div class=\"linenumber\">&nbsp;</div>";
        }
        echo "<div id=\"code_$line\" class=\"codeline\">" . $buffer . "</div>";
        echo "<div class=\"plusone\"><a class=\"plusone\" href=\"javascript:void(0); return false;\" onclick=\"return addLine($line);\">+</a> <a class=\"plusone\" href=\"javascript:void(0); return false;\" onclick=\"return removeLine($line);\">-</a></div>";
        echo "</div>";
        echo "<div class=\"clear\"></div>";
    }
    $finalfile = substr($finalfile, 0, -1);
?>
    </div>
    <div class="clear"></div>
    <div style="width:100%; text-align:center;">
        <input type="button" value="Save Changes" onclick="saveChanges();">
        &nbsp;&nbsp;&nbsp;
        <input type="button" value="Abandon Changes" onclick="abandonChanges();">
    </div>
</div>

<form name="frmAbandon" id="frmAbandon" method="POST" action="m2check.php">
<input type="hidden" name="root_path" value="<?php echo $root_path; ?>" />
<input type="hidden" name="scan_path" value="<?php echo $scan_path; ?>" />
<input type="hidden" name="output_format" value="screen" />
</form>

<form name="frmSave" id="frmSave" method="POST" action="save_code_file.php">
<input type="hidden" name="root_path" value="<?php echo $root_path; ?>" />
<input type="hidden" name="scan_path" value="<?php echo $scan_path; ?>" />
<input type="hidden" name="output_format" value="screen" />
<input type="hidden" name="oldfile" value="<?php echo $filename; ?>" />
<input type="hidden" name="newfile" value="<?php echo base64_encode($finalfile); ?>" />

</form>