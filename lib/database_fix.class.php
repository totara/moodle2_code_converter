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
class database_fix extends moodle2_fixer {

    function __construct(){

    }
    public function dml($line, $error){
        $match = array(); $newline = '';
        switch($error['ruleindex']){
            case 0: //'/(begin_sql/'
                
                $lines = explode("\n",$line);
                $linecount = 0; $white = '';
                foreach ($lines as $l) {
                    $func = $this->get_function_definition($l, 'begin_sql');
                    if ($func) {
                        $white = $this->get_whitespace($l);
                        $newline .= $white . '*M2SCAN //SCANMSG: transactions may need additional fixingM2SCAN*' . "\n";
                        $newline .= $white . '*M2SCAN    $transaction = $DB->start_delegated_transaction();*M2SCAN' . "\n";
                        continue;
                    }
                    $func = $this->get_function_definition($l, 'rollback_sql');
                    if ($func) {
                        continue;
                    }
                    $func = $this->get_function_definition($l, 'commit_sql');
                    if ($func) {
                        $newline .= "\n" . $white . '    *M2SCAN$transaction->allow_commit();M2SCAN*';
                        continue;
                    }
                    $newline .= "\n" . "    " . $l;
                    $linecount++;
                }
                $line = $newline;
                break;
            case 1: //'/count_records(_select|_sql)?/'
                //special handling for each one that we can fix
                $done = false; $ignore=false;
                //get_record
                $func = $this->get_function_definition($line, 'count_records', '$DB->', $ignore);
                if ($func) {
                    $newline = '$DB->count_records(';
                    $params = $this->parse_for_params($func,'count_records');
                    if (isset($params[0])) {
                        $newline .= $params[0];
                    }
                    if (count($params) > 1) {
                        $arrstring = $this->params_to_array($params,1,6);
                        if (!empty($arrstring)) {
                            $newline .= ", " . $arrstring;
                        }
                    }
                    $newline .= ')';
                    $line = str_replace($func, "*M2SCAN{$newline}M2SCAN*", $line);
                    $done = true;
                }
                if (!$done && !$ignore) {
                    //just add a $DB for all the others
                    $line = str_replace($error['match'],'*M2SCAN$DB->' . $error['match'] . 'M2SCAN*', $line);
                }
                break;
            case 2: //'/delete_records(_select)?/'
                $done = false; $ignore=false;
                //delete_records
                $func = $this->get_function_definition($line, 'delete_records', '$DB->', $ignore);
                if ($func) {
                    $newline = '$DB->delete_records(';
                    $params = $this->parse_for_params($func,'delete_records');
                    if (isset($params[0])) {
                        $newline .= $params[0];
                    }
                    if (count($params) > 1) {
                        $arrstring = $this->params_to_array($params,1,6);
                        if (!empty($arrstring)) {
                            $newline .= ", " . $arrstring;
                        }
                    }
                    if (isset($params[7])) {
                        $newline .= ", " . $params[7];
                    }
                    $newline .= ')';
                    $line = str_replace($func, "*M2SCAN{$newline}M2SCAN*", $line);
                    $done = true;
                }

                if (!$done && !$ignore) {
                    //just add a $DB for all the others
                    $line = str_replace($error['match'],'*M2SCAN$DB->' . $error['match'] . 'M2SCAN*', $line);
                }
                break;
            case 3: //'/get_field(set)?(_select|sql)?/'
                $done = false; $ignore=false;
                //special handling for each one that we can fix
                //get_field
                $func = $this->get_function_definition($line, 'get_field', '$DB->', $ignore);
                if ($func) {
                    $newline = '$DB->get_field(';
                    $params = $this->parse_for_params($func,'get_field');
                    if (isset($params[0]) && isset($params[1])) {
                        $newline .= $params[0] . ', ' . $params[1];
                    }
                    if (count($params) > 2) {
                        $arrstring = $this->params_to_array($params,2);
                        if (!empty($arrstring)) {
                            $newline .= ", " . $arrstring;
                        }
                    }
                    $newline .= ')';
                    $line = str_replace($func, "*M2SCAN{$newline}M2SCAN*", $line);
                    $done = true;
                }
                if (!$done && !$ignore) {
                    //just add a $DB for all the others
                    $line = str_replace($error['match'],'*M2SCAN$DB->' . $error['match'] . 'M2SCAN*', $line);
                }
                break;
            case 4: //'/get_record(s|set)?(_list|_menu|_select|_sql)?(_menu)?/'
                //special handling for each one that we can fix
                $done = false; $ignore=false;
                //get_record
                $func = $this->get_function_definition($line, 'get_record', '$DB->', $ignore);
                if ($func) {
                    $newline = '$DB->get_record(';
                    $params = $this->parse_for_params($func,'get_record');
                    if (isset($params[0])) {
                        $newline .= $params[0];
                    }
                    if (count($params) > 1) {
                        $arrstring = $this->params_to_array($params,1,6);
                        if (!empty($arrstring)) {
                            $newline .= ", " . $arrstring;
                        }
                    }
                    if (isset($params[7])) {
                        $newline .= ", " . $params[7];
                    }
                    $newline .= ')';
                    $line = str_replace($func, "*M2SCAN{$newline}M2SCAN*", $line);
                    $done = true;
                }

                if ($func = $this->get_function_definition($line, 'get_records', '$DB->', $ignore)) {
                    $funcname = 'get_records';
                } elseif ($func = $this->get_function_definition($line, 'get_records_menu', '$DB->', $ignore)) {
                    $funcname = 'get_records_menu';
                } elseif ($func = $this->get_function_definition($line, 'get_records_list', '$DB->', $ignore)) {
                    $funcname = 'get_records_list';
                } elseif ($func = $this->get_function_definition($line, 'get_recordset_list', '$DB->', $ignore)) {
                    $funcname = 'get_recordset_list';
                } elseif ($func = $this->get_function_definition($line, 'get_recordset', '$DB->', $ignore)) {
                    $funcname = 'get_recordset';
                } else {
                    $funcname = '';
                }

                if ($funcname) {
                    $newline = "\$DB->{$funcname}(";
                    $params = $this->parse_for_params($func, $funcname);
                    if (isset($params[0])) {
                        $newline .= $params[0];
                    }
                    if (count($params) > 1) {
                        $arrstring = $this->params_to_array($params,1,2);
                        if (!empty($arrstring)) {
                            $newline .= ", " . $arrstring;
                        }
                    }

                    // suppress notices if some elements in $params aren't set (will default to null)
                    $new_params = @array(
                        2 => $params[3],
                        3 => $params[4],
                        4 => $params[5],
                        5 => $params[6],
                    );
                    $optional_defaults = array(2 => '', 3 => '*', 4 => '0', 5 => '0');
                    $new_opt_str = $this->manage_optional_params($new_params, $optional_defaults);
                    if ($new_opt_str) {
                        $newline .= ', ' . $new_opt_str;
                    }

                    $newline .= ')';
                    $line = str_replace($func, "*M2SCAN{$newline}M2SCAN*", $line);
                    $done = true;
                }

                if (!$done && !$ignore) {
                    //just add a $DB for all the others
                    $line = str_replace($error['match'],'*M2SCAN$DB->' . $error['match'] . 'M2SCAN*', $line);
                }
                break;
            case 5: //'/insert_record/'
                $func = $this->get_function_definition($line, 'insert_record', '$DB->', $ignore);
                if ($func) {
                    $newline = '$DB->insert_record(';
                    $params = $this->parse_for_params($func,'insert_record');
                    if (isset($params[0]) && isset($params[1])) {
                        $newline .= $params[0] . ', ' . $params[1];
                    }

                    // suppress notices if some elements in $params aren't set (will default to null)
                    $new_params = @array(
                        2 => $params[2],
                        3 => $params[3],
                    );
                    $optional_defaults = array(2 => true, 3 => 'id');
                    $new_opt_str = $this->manage_optional_params($new_params, $optional_defaults);
                    if ($new_opt_str) {
                        $newline .= ', ' . $new_opt_str;
                    }

                    $newline .= ')';
                    $line = str_replace($func, "*M2SCAN{$newline}M2SCAN*", $line);
                }
                break;
            case 6: //'/record_exists(_select|_sql)?/'
                //special handling for each one that we can fix
                $done = false; $ignore=false;
                //get_record
                $func = $this->get_function_definition($line, 'record_exists', '$DB->', $ignore);
                if ($func) {
                    $newline = '$DB->record_exists(';
                    $params = $this->parse_for_params($func,'record_exists');
                    if (isset($params[0])) {
                        $newline .= $params[0];
                    }
                    if (count($params) > 1) {
                        $arrstring = $this->params_to_array($params,1,6);
                        if (!empty($arrstring)) {
                            $newline .= ", " . $arrstring;
                        }
                    }
                    $newline .= ')';
                    $line = str_replace($func, "*M2SCAN{$newline}M2SCAN*", $line);
                    $done = true;
                }
                if (!$done && !$ignore) {
                    //just add a $DB for all the others
                    $line = preg_replace($error['regex'],'*M2SCAN$DB->$0M2SCAN*', $line);
                }
                break;
            case 7: //'/records_to_menu/'
                break;
            case 8: //'/recordset_to_(array|menu)/'
                break;
            case 9: //'/rs_(EOF|close|fetch_record|next_record)/'
                break;
            case 10: //'/set_field(_select)?/'
                $done = false; $ignore=false;
                //special handling for each one that we can fix
                //set_field
                $func = $this->get_function_definition($line, 'set_field', '$DB->', $ignore);
                if ($func) {
                    $newline = '$DB->set_field(';
                    $params = $this->parse_for_params($func,'set_field');
                    if (count($params) < 5) {
                        // invalid set_field definition
                        echo "<b>invalid set_field definition</b>";
                    }
                    if (isset($params[0]) && isset($params[1]) && isset($params[2])) {
                        $newline .= $params[0] . ', ' . $params[1] . ', ' . $params[2];
                    }
                    if (count($params) > 3) {
                        $arrstring = $this->params_to_array($params,3);
                        if (!empty($arrstring)) {
                            $newline .= ", " . $arrstring;
                        }
                    }
                    $newline .= ')';
                    $line = str_replace($func, "*M2SCAN{$newline}M2SCAN*", $line);
                    $done = true;
                }
                if (!$done && !$ignore) {
                    //just add a $DB for all the others
                    $line = str_replace($error['match'],'*M2SCAN$DB->' . $error['match'] . 'M2SCAN*', $line);
                }
                break;
            case 11: //'/update_record/'
                $func = $this->get_function_definition($line, 'update_record', '$DB->', $ignore);
                if ($func) {
                    $line = str_replace($error['match'], '*M2SCAN$DB->update_recordM2SCAN*', $line);
                }
                break;
            case 12: ///rs_fetch_next_record/
                $find = '/while[\s]?\([\s]?(\$.*?)[\s]?=[\s]?rs_fetch_next_record[\s]?\([\s]?(\$.*?)[\s]?\)[\s]?\)[\s]?\{/';
                $replace = '*M2SCANforeach ($2 as $1) {M2SCAN*';
                $line = preg_replace($find, $replace, $line);
                break;
            default:

        }
        return $line;
    }
    public function helper($line, $error){
        switch($error['ruleindex']){
            case 0 : //'/db_(lowercase|uppercase)/'
                break;
            case 1 : //'/sql_(as|bitand|bitnot|bitor|bitxor|cast_char2int|ceil|compare_text|concat|concat_join|empty|fullname|isempty|isnotempty|length|max|null_from_clause|order_by_text|paging_limit|position|substr)/'
                break;
        }
        return $line;
    }
    public function ddl($line, $error){
        switch($error['ruleindex']){
            case 0 : //'/add_(field|index|key)/'
                if ($func = $this->get_function_definition($line, 'add_field')) {
                    $funcname = 'add_field';
                } elseif ($func = $this->get_function_definition($line, 'add_index')) {
                    $funcname = 'add_index';
                } elseif ($func = $this->get_function_definition($line, 'add_key')) {
                    $funcname = 'add_key';
                }
                if ($func) {
                    $params = $this->parse_for_params($func, $funcname);
                    //first two are compulsory
                    if (isset($params[0]) && isset($params[1])) {
                        $newline = '$dbman->' . $funcname . '(';
                        $newline .= $params[0] . ', ' . $params[1] ;
                        if (isset($params[2])) {
                            $newline .= ", " . $params[2];
                        }
                        if (isset($params[3])) {
                            $newline .= ", " . $params[3];
                        }
                        $newline .= ')';
                        $line = str_replace($func,"*M2SCAN{$newline}M2SCAN*",$line);
                    }
                }
                break;
            case 1 : //'/change_field_(default|enum|notnull|precision|type|unsigned)/'
                $line = str_replace($error['match'], '*M2SCAN$dbman->' . $error['match'] . 'M2SCAN*', $line);
                break;
            case 2 : //'/create_(table|temp_table)/'
                $line = str_replace($error['match'], '*M2SCAN$dbman->' . $error['match'] . 'M2SCAN*', $line);
                break;
            case 3 : //'/drop_(field|index|key|table)/'
                $line = str_replace($error['match'], '*M2SCAN$dbman->' . $error['match'] . 'M2SCAN*', $line);
                break;
            case 4 : //'/find_(check_constraint_name|index_name|key_name|sequence_name)/'
                break;
            case 5 : //'/rename_(field|index|key|table)/'
                $line = str_replace($error['match'], '*M2SCAN$dbman->' . $error['match'] . 'M2SCAN*', $line);
                break;
            case 6 : //'/(check_constraint|field|index|table)_exists/'
                $line = str_replace($error['match'], '*M2SCAN$dbman->' . $error['match'] . 'M2SCAN*', $line);
                break;
        }
        return $line;
    }
    public function coreonly($line, $error){
        switch($error['ruleindex']){
            case 0 : //'/delete_tables_from_xmldb_file/'
                break;
            case 1 : //'/drop_plugin_tables/'
                break;
            case 2 : //'/get_db_directories/'
                break;
            case 3 : //'/get_used_table_names/'
                break;
            case 4 : //'/install_from_xmldb_file/'
                break;
        }
        return $line;
    }
    public function enum($line, $error){
        switch($error['ruleindex']){
            case 0 : //'/ENUM(VALUES)?=".*?" /'
                // these should all be removed
                $line = preg_replace($error['regex'], '', $line);
                break;
            case 1 : //'/>getEnum\(/'
                break;
            case 2 : //'/new xmldb_field\((((\'[^\']*?\')|[^\',]+?|array\(.*)[,\)]\s?){9,20}/'
                break;
            case 3 : //'/>add_field\((((\'[^\']*?\')|[^\',]+?|array\(.*)[,\)]\s?){9,20}/'
                break;
            case 4 : //'/>set_attributes\((((\'[^\']*?\')|[^\',]+?|array\(.*)[,\)]\s?){8,20}/'
                break;
            case 5 : //'/change_field_enum/'
                break;
        }
        return $line;
    }
    public function internal($line, $error){
        switch($error['ruleindex']){
            case 0 : //'/change_db_encoding/'
                break;
            case 1 : //'/configure_dbconnection/'
                break;
            case 2 : //'/db_(detect_lobs|update_lobs)/'
                break;
            case 3 : //'/execute_sql(_arr)?/'
                $func = $this->get_function_definition($line, 'execute_sql');
                if ($func) {
                    $params = $this->parse_for_params($func, 'execute_sql');
                    $newline = '$DB->execute(' . $params[0] . ')';
                    $line = str_replace($func,"*M2SCAN{$newline}M2SCAN*",$line);
                }
                break;
            case 4 : //'/onespace2empty/'
                break;
            case 5 : //'/oracle_dirty_hack/'
                break;
            case 6 : //'/rcache_(get|getforfill|releaseforfill|set|unset|unset_table)/'
                break;
            case 7 : //'/where_clause/'
                break;
        }
        return $line;
    }
    public function unsupported($line, $error){
        switch($error['ruleindex']){
            case 0 : //'/column_type/'
                break;
            case 1 : //'/table_column/'
                break;
            case 2 : //'/modify_database/'
                break;
            case 3 : //'/(Execute|Connect|PConnect|ErrorMsg)/'
                break;
            case 4 : //'/(MetaTables|MetaColumns|MetaColumnNames|MetaPrimaryKeys|MetaIndexes)/'
                break;
        }
        return $line;
    }
    public function other($line, $error){
        $matches=array();
        switch($error['ruleindex']){
            case 0 : //'/\$db[,; -]/'
                break;
            case 1 : //"/[^\$_'\"\.-]dbfamily/"
                break;
            case 2 : //"/[^\$_'\"\.-]dblibrary/"
                break;
            case 3 : //"/[^\$_'\"\.-]dbtype[^s]/"
                break;
            case 4 : //'/sql_substr\(\)/'
                break;
            case 5 : //'/{\$CFG->prefix}(.*?)(\'|\s|")/'
                $line = preg_replace($error['regex'], '*M2SCAN{$1}$2M2SCAN*', $line);
                break;
            case 6: //'/(\'|")+\s?(\.)+\s?(?<!{)\$CFG->prefix(?!})\s?(\.)+\s?(\'|")+(.*?)($|\'|\s|")/'
                $line = preg_replace($error['regex'] , '*M2SCAN{$5}$6M2SCAN*', $line);
                break;
            case 7 : //'/(?<!{)\$CFG->prefix(?!}) . (\'|")(.*?)(\'|\s|")/'
                $line = preg_replace($error['regex'] , '*M2SCAN$1{$2}$3M2SCAN*', $line);
                break;
            case 8 : //'/NEWNAMEGOESHERE/'
                break;
            case 9 : //'/new\s(XMLDBTable|XMLDBField|XMLDBIndex|XMLDBKey)/'
                if ($func = $this->get_function_definition($line, 'XMLDBTable')) {
                    $funcname = 'XMLDBTable';
                } elseif ($func = $this->get_function_definition($line, 'XMLDBField')) {
                    $funcname = 'XMLDBField';
                } elseif ($func = $this->get_function_definition($line, 'XMLDBIndex')) {
                    $funcname = 'XMLDBIndex';
                } elseif ($func = $this->get_function_definition($line, 'XMLDBKey')) {
                    $funcname = 'XMLDBKey';
                }
                if ($func) {
                    $newfunc = strtolower(str_replace("XMLDB","",$funcname));
                    $newfunc = "xmldb_" . $newfunc;
                    $line = str_replace($funcname,"*M2SCAN{$newfunc}M2SCAN*",$line);
                }
                break;
            case 10 : //'/>(addFieldInfo|addIndexInfo|addKeyInfo|setAttributes)/'
                if ($func = $this->get_function_definition($line, 'setAttributes')) {
                    $params = $this->parse_for_params($func, 'setAttributes');
                    $newline = 'set_attributes(';
                    for ($x=0; $x<9; $x++) {
                        if($x!=5 && $x!=6 && isset($params[$x])) {
                            $newline .= $params[$x] . ', ';
                        }
                    }
                    $newline .= ')';
                    $newline = str_replace(', )', ')', $newline);
                    $line = str_replace($func,"*M2SCAN{$newline}M2SCAN*",$line);
                    break;
                }
                if ($func = $this->get_function_definition($line, 'addFieldInfo')) {
                    $params = $this->parse_for_params($func, 'addFieldInfo');
                    $newline = 'add_field(';
                    for ($x=0; $x<10; $x++) {
                        if($x!=6 && $x!=7 && isset($params[$x])) {
                            $newline .= $params[$x] . ', ';
                        }
                    }
                    $newline .= ')';
                    $newline = str_replace(', )', ')', $newline);
                    $line = str_replace($func,"*M2SCAN{$newline}M2SCAN*",$line);
                    break;
                }
                if ($func = $this->get_function_definition($line, 'addIndexInfo')) {
                    $params = $this->parse_for_params($func, 'addIndexInfo');
                    $newline = 'add_index(';
                    for ($x=0; $x<count($params); $x++) {
                        if(isset($params[$x])) {
                            $newline .= $params[$x] . ', ';
                        }
                    }
                    $newline .= ')';
                    $newline = str_replace(', )', ')', $newline);
                    $line = str_replace($func,"*M2SCAN{$newline}M2SCAN*",$line);
                    break;
                }
                if ($func = $this->get_function_definition($line, 'addKeyInfo')) {
                    $params = $this->parse_for_params($func, 'addKeyInfo');
                    $newline = 'add_key(';
                    for ($x=0; $x<count($params); $x++) {
                        if(isset($params[$x])) {
                            $newline .= $params[$x] . ', ';
                        }
                    }
                    $newline .= ')';
                    $newline = str_replace(', )', ')', $newline);
                    $line = str_replace($func,"*M2SCAN{$newline}M2SCAN*",$line);
                    break;
                }
                break;
            case 11 : //'/(if|while|for|return).*>get_recordset(_list|_select|_sql)?/'
                break;
            case 12 : //'/SELECT DISTINCT.*\.\*/'
                break;
            case 13 : //"/get_in_or_equal\(.*SQL_PARAMS_NAMED\s*,\s*'.*\d'/"
                break;
            
        }
        return $line;
    }

    public function reservedword($line, $error) {
        switch($error['ruleindex']) {
            case 0 : //'/(?: AS\s+|:)user/'
                break;
            case 1 : //'/(?: AS\s+|:)group/'
                break;
            case 2 : //'/(?: AS\s+|:)order/'
                break;
            case 3 : //'/(?: AS\s+|:)select/'
                break;
            case 4 : //'/(?: AS\s+|:)from/'
                break;
            case 5 : //'/(?: AS\s+|:)where/'
                break;
            case 6 : //'/(?: AS\s+|:)role/'
                break;
            case 7 : //'/(?: AS\s+|:)null/'
                break;
            case 8 : //'/(?: AS\s+|:)start/'
                break;
            case 9 : //'/(?: AS\s+|:)end/'
                break;
            case 10 : //'/(?: AS\s+|:)date/'
                break;
            case 11 : //'/(?: AS\s+|:)match/'
                break;
            case 12 : //'/(?: AS\s+|:)mod/'
                break;
            case 13 : //'/(?: AS\s+|:)new/'
                break;
            case 14 : //'/(?: AS\s+|:)old/'
                break;
        }
        return $line;
    }

    public function deprecated($line, $error) {
        switch ($error['ruleindex']) {
            case 0 : //'/sql_(ilike)/'
                break;
        }
        return $line;
    }
}
