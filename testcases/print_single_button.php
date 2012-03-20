<?php
// simplest
print_single_button($link, $options);

// simple
print_single_button($link, $options, 'Continue', 'post');

// simple null options
print_single_button($link, null, 'Continue', 'post');

// simple returning
echo print_single_button($link, array(), 'ok', 'get', '_self', true);

    $text = " this is something else";
    // complex
    print_single_button($link, $options, 'OK', 'get', 'newtarget', false, 'tip', true);

    $text = " this is something else";

        // simple with options returning indented
        $out = print_single_button($link, $options, 'OK', 'get', '_self', true, 'tooltip');


        // complex non-returning indented
        print_single_button($link, $options, 'OK', 'get', '_self', false, 'tooltip', true, get_string('jsconfirmmsg', 'filename'));


