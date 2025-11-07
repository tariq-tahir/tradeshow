<?php
namespace AWPS\Exporters;

class Roles {
  public function register() {
    add_action('init', function () {
      add_role('exporter', 'Exporter', [
        'read' => true,
        'upload_files' => true,
      ]);
      // Ensure exporters can manage their own products
      $role = get_role('exporter');
      foreach (['edit_products','edit_published_products','publish_products','delete_products','upload_files'] as $cap) {
        $role && $role->add_cap($cap);
      }
    });
  }
}
