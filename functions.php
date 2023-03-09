<?php

$files = array(
    'plugins/acf-gallery/acf-gallery.php',
    'plugins/acf-options-page/acf-options-page.php',
    'plugins/acf-repeater/acf-repeater.php',
    'setup/assets.php',
    'setup/acf.php',
    'setup/core.php',
    'setup/helper.php',
    'setup/post-types.php',
    'setup/render.php',
);

foreach ($files as $file) {
    if (!$filePath = locate_template($file)) {
        error_log(sprintf(__('Error locating %s for inclusion'), $file), E_USER_ERROR);
    }
    include_once $filePath;
}
