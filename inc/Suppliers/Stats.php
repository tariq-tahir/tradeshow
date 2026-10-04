<?php
namespace AWPS\Suppliers;
/**
 * Dashboard statistics queries for suppliers/exporters.
 */
class Stats {
  /**
   * Register.
   */
  public function register(){
    add_action('wp', function(){
      if (is_singular('product')) {
        $id = get_the_ID();
        $v = (int)get_post_meta($id,'views',true);
        update_post_meta($id,'views',$v+1);
      }
    });
  }
}
