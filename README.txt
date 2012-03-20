Moodle 2 code converter
=======================

This tool is a stand-alone web application that can be used to help while
porting Moodle 1.9 code to the new Moodle 2.x coding standards.

It will automatically find references to deprecated functions and in some
cases automatically convert the code to the new syntax.

To use:

1. Unpack the code in a directory and make it available via a local
   webserver (either by putting it in your webroot or with a symlink).

2. View the directory using a web browser

The scanner is designed to be portable and standalone and does not have
any links to databases etc - a web server that runs PHP, on the same machine
as the code you want to change, it all you need.

You'll see an interface for scanning a code directory and performing various
actions:

  Root directory: is the full path to the directory containing the moodle 1.9
  code

  Scan path: is an optional path to a subdirectory (in case you don't want to
  do a full scan of everything)

  Type: Type of scan. Normally you want a full scan to see all the things to
  fix. Top 20 just shows which type of syntax issues are the most common.
  Language Merge will try to combine language strings in lang/en/ and
  lang/en_utf8/ into a single set of strings in lang/en/. Language Merge
  requires the correct permissions (see below).

  Output: How to display the results (usually screen is fine but there is an
  export option).

If you want the auto-fix stuff to work you'll need to give the web server's
user write permissions with something like this:

chown -R www-data /path/to/moodle/code/directory

where www-data is the owner of your web server's process. Remember to change
permissions back to the original owner before commiting changes to your source
code repository!

The 'rules' that are used to find code that needs fixing are located in the
rules/ directory. Rules take the form of standard preg PHP regular expressions.
The code that fixes existing references is in lib/. Currently this covers
the main cases, but it can be extended to catch more - please send us any
additions you make via pull requests to github.

Results of a scan are divided into three areas: Help, Language and Code

Help
=======
In Moodle 2 all the help HTML files should be moved into language files. For
each directory containing help files, the scanner will present a list of help
files and a "best guess" as to which language file the text should end up in.
Other language files in various standard locations are also detected and
available in the dropdown. Simply set the destination for each individual file
(including IGNORE and DELETE options) and hit Do Stuff! - the scanner will take
the contents of each HTML file, run it through Markdownify, add it to the
destination language pack, and tidy up the html files.

Language
=========
For every language file detected in your scan path, the scanner tries to find
all instances of unbraced (i.e. where the enclosing { } is missing) $a in all
formats like $a, $a->property, $a[1] etc. Also detected are strings enclosed
in double quotes, and escaped double quotes in single-quote strings, all of
which need to be tidied up in Moodle 2.

For each language file where errors have been detected, click on Do Stuff to
proceed to the CodeFixUI (see below) to make the changes.

Code
======
We've tried to detect and provide fixes for the bulk of the most common changes
involved in porting code to Moodle 2 but there are literally hundreds of minor
and more obscure changes and many may be missing. Feel free to extend the
scanner with new rule/fix sets if necessary. Also there are rules in the current
version where there is no corresponding fix so the scanner can tell you there's
a problem but not automatically repair it.

Fixing Code files works in exactly the same way as Language files: just click Do
Stuff to move to the CodeFixUI.

CodeFixUI
==========
This is a relatively simple web-based Diff/IDE tool. On the left is the existing
contents of a file, with detected errors highlighted in red. On the right, the
file after the scanner has run through the fixer classes in lib/ and tried to
apply autofixes, highlighted in green. If a fix is not quite right, or you have
noticed other areas of the file that you want to modify, you have the following
options.

1. On the right-hand pane, click on the Line Number to open a text area to MODIFY
   that line.
2. On the far right of the screen click on the + to open a text area to ADD a new
   line immediately following the line you clicked on
3. On the far right of the screen click the - to REMOVE an entire line

In some circumstances you may want to make changes that are so extensive that
the simple CodeFixUI will not be flexible enough and you will have to drop out to
your favourite IDE, make your changes, then rescan.

Rules And Fixes
================

Most of this section will only be of interest to developers who may want to add
new rules and fixes, or who are curious as to how the internals work. Casual
users can skip this section.

In the rules directory there are currently files categorising errors into
various categories - database, language, output and general. You can add an
entirely new category if you wish simply by creating a new file in a similar
format to the existing files in the rules directory. Not you should also create
a corresponding base fixer class if you do this (see below). Rule categories
are then subdivided into various types e.g database code errors are subdivided
into DML, DDL, Deprecated and so on. Each Type is then further subdivided into
an array of all the individual rules. Rules take the form of regular
expressions - the scanner examines each file line by line and uses the standard
preg_match PHP function to detect matches. So for example the rule for the
get_records() function, a very common code error to be changed for Moodle 2, is
in the database category, type dml, in position 4 of the dml array (arrays in
PHP are zero-based).

New rules added to a type should ALWAYS be added to the end of the array, for
reasons which will become clear below.

For each category there should also be a fixer class in the lib/ directory.
These should be in files with the naming format [category]_fix.class.php.
Fixer classes should extend the base fixer class as follows:

class database_fix extends moodle2_fixer {

For each type of rule in the category there should exist a fixer function for
the rule type. The function will be passed the line of code from the file,
plus the details of the error which includes the array index. So for example in
our get_records example in database_fix.class.php there is a database_fix class,
containing a public dml function, which then has a switch statement
switch($error['ruleindex']){ and we want to look for case 4: in that switch
statement to see the actual fixing code.

CodeFixUI does its best to identify multiline blocks of code e.g. a function
call spread over multiple lines in the source code for readability, and will
try to pass the entire block to the fixer class. A fix simply modifies this
$line variable then sends it back to be displayed.

The base moodle2_fixer class contains a variety of helper functions. The most
commonly used are

get_function_definition($line, functionname) : scans the source code for the
specified function and returns the entire function call including all
parameters, up to and including the final closing parenthesis, extracted from
the $line.

get_whitespace($line): returns the chunk of whitespace if any at the very
beginning of the line. Useful in some cases for maintaining indentation.

parse_for_params($text, functionname) : parses $text to find the full function
call and then extracts each individual parameter into an array. Note that some
parameters may themselves be a function call with multiple parameters e.g. the
second parameter may be a call to get_string with two parameters of its own -
in this case the entire get_string function call will be in the $params[1]
array returned by parse_for_params, and you can pass this again to
parse_for_params to get a second array of all the get_string parameters


params_to_array($params,start, end) : many database functions in Moodle 2 now
take an array as a parameter in the form array(field=>value, field2=>value2...).
Given an array of values (usually extracted from a Moodle 1.9 function call
via parse_for_params, but can be an array you build yourself if you wish) this
function will try to build the array from each successive pair of values between
$start and $end indexes of the passed array.

There are plenty of examples in the existing fixer classes to help you get started.

What it will do
===============

- Find and optionally replace a number of old coding practices with the new
  versions, including output functions, DML functions, etc.
- Move language files from [LANG-CODE]_utf8/ to [LANG-CODE], merging keys if
  multiple directories already existed
- Move help files into lang files (if you specify the lang file for each help
  file via the interface)


What it won't do
================

- Fix all issues. We've currently only written rules for the most common
  problems, and avoided some common ones that can't be easily automated
- Insert "global $DB;" in functions that now use it (due to DML lib replacements
  made by the script).
- Remove "global $CFG;" in functions that no longer use it.
- Parameterise the *_select() or *_sql() DML functions

Gotchas
=======

If you change the rules or the file being scanned you may need to rescan to
pick up the new changes. That's because the results are cached when you first
do the scan, so if in doubt, re-run the whole scan.

