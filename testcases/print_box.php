<?php

    // echoing
    print_box_start();

    // returning
    $out = print_box_start('classes', 'idbase', true);

    // returning with defaults
    $out = print_box_start('generalbox', '', true);

    // returning
    $out = print_box_end(true);

    // explicit default
    print_box_end(false);

    // no arguments
    print_box_end();

// simple no indent
print_box('this is a box');

    $out = print_box($message, 'classes', 'id', true);

    $out = print_box($message, '', '', true);

    print_box(get_string('test', 'file'), 'generalbox', '', false)   ;

    echo 'string ' . print_box($message, '', 'someid', true) . 'another string';


