<?php
defined('ABSPATH') || exit;

if (file_exists(PB_LTI_PATH . 'vendor/autoload.php')) {
    require_once PB_LTI_PATH . 'vendor/autoload.php';
}

require_once PB_LTI_PATH . 'db/schema.php';
require_once PB_LTI_PATH . 'db/migrate.php';
require_once PB_LTI_PATH . 'routes/rest.php';
require_once PB_LTI_PATH . 'admin/menu.php';

register_activation_hook(__FILE__, 'pb_lti_activate');
function pb_lti_activate() {
    pb_lti_run_migrations();
}
