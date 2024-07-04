<?php
spl_autoload_register(
    function ($class_name) {
        // Strip off the non-directory part of the class name and get the
        // class name as individual pieces.
        $class_name = str_ireplace('Wpmtg\\', '', $class_name);
        $class_name_pieces = explode('\\', $class_name);

        // The default file is directly in the classes folder.
        $class_file = __DIR__ . '/classes/' . $class_name . '.php';

        // Add additional depth to the file name.
        if (count($class_name_pieces) > 1) {
            $class_file = __DIR__ . '/classes/' . implode('/', array_slice($class_name_pieces, 1)) . '.php';
        }

        // Only load the file if it exists.
        if (file_exists($class_file)) {
            include_once $class_file;
        }
    }
);
