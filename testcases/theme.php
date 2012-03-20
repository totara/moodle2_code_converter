<?php

// test some stuff related to themes:

echo current_theme();

// find pixpath
echo $CFG->pixpath . '/i/course.gif">something';

// find modpixpath
echo "{$CFG->modpixpath}/i/course.gif\" alt=\"test\" />";
echo $CFG->modpixpath."/i/course.gif\" />more tags";

// find references to the various directories
echo $CFG->wwwroot . '/images/icon.png';
echo $CFG->wwwroot . '/theme/icon.png';
echo $CFG->wwwroot . '/pix/icon.png';

// any reference to $CFG->themewww variable or $CFG->theme
echo 'test' . $CFG->themewww . 'something else';
echo "test{$CFG->theme}something else";


// trying to replace pixpaths
// without curly brackets - outside of quotes
echo 'something' . $CFG->pixpath . '/dir/subdir/image-with_others_chars1234.pnganything here';
echo '<img src="'.$CFG->pixpath.'/t/image.png" alt="something" />';
echo 'something'.$CFG->pixpath."/i/course.gif\" more really important stuff>end tag";

// this isn't handled perfectly but will have to do
echo 'something'.$CFG->pixpath.'/i/course.gif';

// we don't try and handle these:

// without curly brackets - inside of double quotes
echo "something $CFG->pixpath/i/course.gif";

// with curly brackets - inside of double quotes
echo "something{$CFG->pixpath}/i/course.gif";
