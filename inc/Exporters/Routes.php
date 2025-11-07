<?php
namespace AWPS\Exporters;

class Routes {
  public function register() {
    add_action('init', function(){
      add_rewrite_rule('^exporters/?$', 'index.php?exporters=1', 'top');
      add_rewrite_rule('^exporter/([^/]+)/?$', 'index.php?exporter_slug=$matches[1]', 'top');
    });
    add_filter('query_vars', function($vars){
      $vars[] = 'exporters';
      $vars[] = 'exporter_slug';
      return $vars;
    });
    add_action('template_redirect', [$this,'load_templates']);
    // Flush once on theme switch
    add_action('after_switch_theme', 'flush_rewrite_rules');
  }
  public function load_templates() {
    if (get_query_var('exporters')) {
      include get_theme_file_path('page-templates/template-exporters-directory.php'); exit;
    }
    if ($slug = get_query_var('exporter_slug')) {
      $user = get_user_by('slug', $slug);
      if ($user && in_array('exporter', (array)$user->roles)) {
        $GLOBALS['awps_current_exporter'] = $user;
        include get_theme_file_path('views/exporters/profile.php'); exit;
      }
      wp_redirect(home_url('/exporters/')); exit;
    }
  }
}
