<?php
namespace AWPS\Suppliers;

class Roles {
  public function register() {
    add_action('init', function () {
      add_role('supplier', 'Supplier', [
        'read' => true,
        'upload_files' => true,
      ]);
      // Ensure suppliers can manage their own products
      $role = get_role('supplier');
      foreach (['edit_products','edit_published_products','publish_products','delete_products','upload_files'] as $cap) {
        $role && $role->add_cap($cap);
      }
    });
  }
}
