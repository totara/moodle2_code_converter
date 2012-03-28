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

global $rules;

$dml = array (
        '/begin_sql/',
        '/(?<!\$DB\->)count_records(_select|_sql)?/',
        '/(?<!\$DB\->)delete_records(_select)?/',
        '/(?<!\$DB\->)(get_field(set)?(_select|sql)?)/',
        '/(?<!\$DB\->)(get_record(set|s)?(_list|_menu|_select|_sql)?(_menu)?)+/',
        '/(?<!\$DB\->)insert_record\(.+\)/',
        '/(?<!\$DB\->)record_exists(_select|_sql)?/',
        '/(?<!\$DB\->)records_to_menu/',
        '/(?<!\$DB\->)recordset_to_(array|menu)/',
        '/rs_(EOF|close|fetch_record|next_record)/',
        '/(?<!\$DB\->)set_field(_select)?/',
        '/(?<!\$DB\->)update_record/',
        '/rs_fetch_next_record/',
);

$helper = array (
    '/db_(lowercase|uppercase)/',
    '/(?<!\$DB\->)sql_(as|bitand|bitnot|bitor|bitxor|cast_char2int|ceil|compare_text|concat|concat_join|empty|fullname|isempty|isnotempty|length|max|null_from_clause|order_by_text|paging_limit|position)/'
);

$deprecated = array (
    '/sql_(ilike)/'

);

$ddl = array (
        '/(?<!\->)add_(field|index|key)\(/',
        '/(?<!\->)change_field_(default|enum|notnull|precision|type|unsigned)/',
        '/(?<!\->)create_(table|temp_table)/',
        '/(?<!\->)drop_(field|index|key|table)/',
        '/(?<!\->)find_(check_constraint_name|index_name|key_name|sequence_name)/',
        '/(?<!\->)rename_(field|index|key|table)/',
        '/(?<!\->)(check_constraint|field|index|table)_exists/'
);

$coreonly = array (
        '/delete_tables_from_xmldb_file/',
        '/drop_plugin_tables/',
        '/get_db_directories/',
        '/get_used_table_names/',
        '/install_from_xmldb_file/'
);

$enum = array (
        '/ENUM(VALUES)?=".*?" /',
        '/>getEnum\(/',
        '/new xmldb_field\((((\'[^\']*?\')|[^\',]+?|array\(.*)[,\)]\s?){9,20}/',
        '/>add_field\((((\'[^\']*?\')|[^\',]+?|array\(.*)[,\)]\s?){9,20}/',
        '/>set_attributes\((((\'[^\']*?\')|[^\',]+?|array\(.*)[,\)]\s?){8,20}/',
        '/change_field_enum/'
);

$internal = array (
        '/change_db_encoding/',
        '/configure_dbconnection/',
        '/db_(detect_lobs|update_lobs)/',
        '/execute_sql(_arr)?/',
        '/onespace2empty/',
        '/oracle_dirty_hack/',
        '/rcache_(get|getforfill|releaseforfill|set|unset|unset_table)/',
        '/where_clause/'
);

$unsupported = array (
        '/column_type/',
        '/table_column/',
        '/modify_database/',
        '/(Execute|Connect|PConnect|ErrorMsg)/',
        '/(MetaTables|MetaColumns|MetaColumnNames|MetaPrimaryKeys|MetaIndexes)/'
);

$other = array (
        '/\$db[,; -]/',
        "/[^\$_'\"\.-]dbfamily/",
        "/[^\$_'\"\.-]dblibrary/",
        "/[^\$_'\"\.-]dbtype[^s]/",
        '/(?<!\$DB\->)sql_substr\(\)/',
        '/{\$CFG->prefix}(.*?)(\'|\s|"|$)/',
        '/(\'|")+\s?(\.)+\s?(?<!{)\$CFG->prefix(?!})\s?(\.)+\s?(\'|")+(.*?)($|\\\\n|\.|\)|\'|\s|")/',
        '/(?<!{)\$CFG->prefix(?!})\s?\.\s?(\'|")(.*?)(\'|\s|"|$)/',
        '/NEWNAMEGOESHERE/',
        '/new\s(XMLDBTable|XMLDBField|XMLDBIndex|XMLDBKey)/',
        '/>(addFieldInfo|addIndexInfo|addKeyInfo|setAttributes)/',
        '/(if|while|for|return).*>(?<!\->)get_recordset(_list|_select|_sql)?/',
        '/SELECT DISTINCT.*\.\*/',
        "/get_in_or_equal\(.*SQL_PARAMS_NAMED\s*,\s*'.*\d'/",
        "/addslashes\(/",
);

/// List of reserved words
/// 1. default (common) ones
/*$reservedlist = array(
        'user', 'group', 'order', 'select', 'from', 'where',
        'role', 'null', 'start', 'end', 'date', 'match',
        'mod', 'new', 'old');

foreach ($reservedlist as $key => $word) {
    $reservedlist[$key] = '/(?: AS\s+|:)+' . trim($word) .'/';
}*/

/// List of exceptions that aren't errors (function declarations, comments, adodb usage from adodb drivers and harcoded strings). Non reportable false positives
$excludes = '/(function |^\s*\*|^\s*\/\/|\$this-\>adodb-\>(Execute|Connect|PConnect|ErrorMsg|MetaTables|MetaIndexes|MetaColumns|MetaColumnNames|MetaPrimaryKeys|)|protected \$[a-zA-Z]*db|Incorrect |check find_index_name|not available anymore|output|Replace it with the correct use of|where order of parameters is|_moodle_database|invaliddbtype|has been deprecated in Moodle 2\.0\. Will be out in Moodle 2\.1|Potential SQL injection detected|requires at least two parameters|hint_database = install_db_val|Current database \(|admin_setting_configselect|(if|while|for|return).*\>get_recordset(_list|_select|_sql)?.*\>valid\(\)|NEWNAMEGOESHERE.*XMLDB_LINEFEED|has_capability\(.*:view.*context)|die(.*result.*:null.*errstr)|CAST\(.+AS\s+(INT|FLOAT|DECIMAL|NUM|REAL)/';

/*
$dml        = calculate_megarule($dml,array('[ =@.]'), array('( )?\('), 'i');
$helper     = calculate_megarule($helper,array('[ =@.]'), array('( )?\('), 'i');
$ddl        = calculate_megarule($ddl,array('[ =@.]'), array('( )?\('), 'i');
$coreonly   = calculate_megarule($coreonly,array('[ =@.]'), array('( )?\('), 'i');
$enum       = calculate_megarule($enum);
$internal   = calculate_megarule($internal,array('[ =@.]'), array('( )?\('), 'i');
$unsupported= calculate_megarule($unsupported,array('[ \>=@,.]'), array('( )?\('));
$other      = calculate_megarule($other);
$reserved   = calculate_megarule($reservedlist, array("[ =('\"]"), array("[ ,)'\"]"), 'i');
*/
$rules['DATABASE'] = array(
        'DML' => $dml,
        'HELPER' => $helper,
        'DEPRECATED' => $deprecated,
        'DDL' => $ddl,
        'COREONLY' => $coreonly,
        'ENUM' => $enum,
        'INTERNAL' => $internal,
        'UNSUPPORTED' => $unsupported,
        'OTHER' => $other,
        //'RESERVEDWORD' => $reservedlist,
        'EXCLUDES' => $excludes
);

//clean up temp vars
unset($dml ,$helper, $deprecated, $ddl, $coreonly, $enum, $internal, $unsupported, $other, $reserved, $excludes);
?>
