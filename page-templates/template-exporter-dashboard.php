<?php
/* Template Name: Exporter Dashboard */
if (!is_user_logged_in() || !in_array('supplier',(array)wp_get_current_user()->roles)) { wp_redirect(wp_login_url()); exit; }
get_header();
include get_theme_file_path('views/suppliers/dashboard/index.php');
get_footer();
