<?php

    // echoing
    print_container_start();

    // returning
    $out = print_container_start(true, 'classes', 'idbase', true);

    // returning with defaults
    $out = print_container_start(false, '', '', true);

    // returning
    $out = print_container_end(true);

    // explicit default
    print_container_end(false);

    // no arguments
    print_container_end();

// simple no indent
print_container('this is a container');

    $out = print_container($message, true, 'classes', 'id', true);

    $out = print_container($message, false, '', '', true);

    print_container(get_string('test', 'file'), 'anything', '', '', false);

    echo 'string ' . print_container($message, false, '', '', true) . 'another string';


