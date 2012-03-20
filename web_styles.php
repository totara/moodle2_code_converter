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
<title>Result of scan for <?php echo $dir; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
html {
    height:100%; /* fix height to 100% for IE */
    width:100%;
    padding:0; /*remove padding */
    margin:0; /* remove margins */
    border:0; /* remove borders */
    background:#fff; /*color background - only works in IE */
    font-family:"trebuchet ms", tahoma, verdana, arial, sans-serif; /* set default font */
}
body {
    height:100%; /* fix height to 100% for IE */
    width:100%;
    padding:0; /*remove padding */
    margin:0; /* remove margins */
    border:0; /* remove borders */
    font-family: "Lucida Sans Unicode","Lucida Grande",sans-serif;
    font-size: 13px;
    font-weight: 400;
    font-style: normal;
    color: #444444;
}

table {
    width: 100%;
    border: 0px;
    padding:0; /*remove padding */
    margin:0; /* remove margins */
}

.summarytitle {
    
    background-color: #ffffff;
    border: 1px solid black;
    font-size: 1.5em;
    text-align: center;
}
.summaryhead {
    width: 23%;
    background-color: #FFF1D0;
    border-bottom: 1px solid #DA9329;
    float:left;
}
.summarycell {
    width: 23%;
    background-color: #ffffff;
    float:left;
}

table.langdir th {
    background-color: #ffffff;
    border: 1px solid black;
    font-size: 1.5em;
    text-align: center;
}
table.dirsummary th {
    text-align:left;
    background-color: #00cccc;
    text-align: center;
}
table.summary th {
    text-align:left;
    background-color: #DA9329;
    text-align: center;
}

table.scanresult th {
    background-color: #FFF1D0;
    border-bottom: 1px solid #DA9329;
    text-align: left;
}

table.scanresult tr.r1 td {
    background-color: #FAFAFA;
}

.codeproblem {
    background: none repeat scroll 0 0 #FFAAAA !important;
}

.codefix {
    background: none repeat scroll 0 0 #99FF99;
}

div.clear {
    clear: both;
}
div.content {
	width:100%;
}

div.centered {
	width:100%;
	text-align:center;
	margin-left:auto;
	margin-right:auto;
}
div.leftpane {
	width:48%;
	float:left;
	border: 1px solid black;
	text-overflow:clip;
	clip:auto;
}
div.rightpane {
	width:48%;
	float:right;
	border: 1px solid black;
	font-family: "Lucida Console","Lucida Sans Typewriter",Monaco,monospace;
    font-size: 8pt;
}
div.line {
	width:100%;
}

div.r0 {
    background-color:#FFFFFF;
}
div.r1 {
    background-color:#FAFAFA;
}

div.addline {
    background-color:#aaaaaa;
}
div.linenumber {
    width:20px;
    display:inline;
	position:relative;
    font-family: "Lucida Console","Lucida Sans Typewriter",Monaco,monospace;
    font-size: 8pt;
}
div.plusone {
	width:40px;
	position:relative;
	display:inline;
	float:right;
	vertical-align:middle;
    font-family: "Lucida Console","Lucida Sans Typewriter",Monaco,monospace;
    font-size: 8pt;
}
input.comment {
    font-family: "Lucida Console","Lucida Sans Typewriter",Monaco,monospace;
    font-size: 8pt;
}
input.newline {
    font-family: "Lucida Console","Lucida Sans Typewriter",Monaco,monospace;
    font-size: 8pt;
}
div.codeline {
    width: 700px;
    position:relative;
    display:inline;
    text-align:start;
    font-family: "Lucida Console","Lucida Sans Typewriter",Monaco,monospace;
    font-size: 8pt;
    white-space:normal;
    word-wrap: break-word;
}
a.linenum, a.linenum:visited, a.linenum:hover {
    color: #0000AA;
}

a.plusone, a.plusone:visited, a.plusone:hover {
    color: #0000AA;
    text-decoration:none;
}


</style>
</head>
<body>