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
?>
<html>
<head>
<title>Moodle 2 Code Change Scanner</title>
<script>

function validate() {
    var frm = document.forms['frmData'];
    var elem = frm.scan_type;
    for(i=0;i<elem.length;i++) {
        if (elem[i].checked==true){
            frm.action = elem[i].value;
            break;
        }
    }
    frm.submit();
}
</script>
</head>
<body>
Let the scanner know the root folder of your moodle2 installation and (optionally) a particular sub-path you want to scan<br />
e.g. Root: /var/www/moodle2 Scan: totara/reportbuilder will scan<br />
/var/www/moodle2/totara/reportbuilder and all subfolders. Leave "Scan path" blank to scan everything
<form name="frmData" action="m2check.php" method="post">
<br /><br />
Root directory:<input type="text" size="40" name="root_path">
<br />
Scan path:<input type="text" size="40" name="scan_path">
<br /><br />Type:<br />
<input type="radio" name="scan_type" value="lang_merge.php">Language Merge<br />
<input type="radio" name="scan_type" checked value="m2check.php">Full Scan<br />
<input type="radio" name="scan_type" value="top20.php">Top 20<br />
<br />Output:<br />
<input type="radio" name="output_format" checked value="screen">Screen<br />
<input type="radio" name="output_format" value="csv">CSV (comma delimited, pipe | text delimiter<br />
<br />
<input type="button" value="Scan" onclick="validate();"/>
</form>
</body>
</html>
