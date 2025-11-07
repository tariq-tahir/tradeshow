<article class="exporter-card">
  <h3><a href="<?= esc_url(home_url('/exporter/'.$u->user_nicename)); ?>">
    <?= esc_html(get_user_meta($u->ID,'company_name',true) ?: $u->display_name); ?>
  </a></h3>
  <p><?= esc_html(get_user_meta($u->ID,'country',true)); ?></p>
</article>
