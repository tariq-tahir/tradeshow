<?php
/* Template Name: Exporters Directory */
get_header();
$country = sanitize_text_field($_GET['country'] ?? '');
$args = [
    'role' => 'exporter',
    'number' => 20,
    'paged' => max(1, (int)($_GET['paged'] ?? 1)),
    'meta_query' => [
        'relation' => 'AND',
        [
            'key'     => 'company_status',
            'value'   => 'enabled',
            'compare' => '='
        ]
    ]
];

// Add country filter if specified
if ($country) {
    $args['meta_query'][] = [
        'key'     => 'exports_to', // Note: Changed from 'country' to 'exports_to' to match your field name
        'value'   => $country,
        'compare' => 'LIKE'
    ];
}

$users = new WP_User_Query($args);
?>
<form method="get" class="filters">
  <input name="country" placeholder="Country" value="<?= esc_attr($country) ?>">
  <button type="submit">Filter</button>
</form>
<section class="grid">
<?php foreach ($users->get_results() as $u) include get_theme_file_path('views/exporters/card.php'); ?>
</section>
<?php get_footer();