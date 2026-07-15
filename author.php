<?php
/**
 * Author Archive Template - SEO Optimized
 */
get_header();

$author = get_queried_object();
$author_id = $author->ID;
$author_name = get_the_author_meta('display_name', $author_id);
$author_bio = get_the_author_meta('description', $author_id);
$author_avatar = get_avatar($author_id, 96, '', '', ['class' => 'rounded-full']);
$author_posts_count = count_user_posts($author_id);

// Get social links (add these fields to user profile via functions.php or plugin)
$social = [
    'linkedin' => get_the_author_meta('linkedin', $author_id),
    'twitter' => get_the_author_meta('twitter', $author_id),
    'website' => get_the_author_meta('user_url', $author_id),
];
?>

<!-- JSON-LD Schema (add to head via wp_head hook or inline) -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": <?php echo json_encode($author_name); ?>,
  "url": <?php echo json_encode(get_author_posts_url($author_id)); ?>,
  "image": <?php echo json_encode(get_avatar_url($author_id, ['size' => 96])); ?>,
  "description": <?php echo json_encode(wp_strip_all_tags($author_bio)); ?>,
  "worksFor": {
    "@type": "Organization",
    "name": "<?php bloginfo('name'); ?>",
    "url": "<?php echo home_url(); ?>"
  }
}
</script>

<div class="author-archive" style="padding:40px 20px; max-width:1200px; margin:0 auto;">
    
    <!-- Author Header Card -->
    <header class="author-header" style="text-align:center; margin-bottom:40px; padding:30px; background:#f9f9f9; border-radius:12px; border:1px solid #eee;">
        
        <!-- Avatar -->
        <div style="margin-bottom:20px;">
            <?php echo $author_avatar; ?>
        </div>
        
        <!-- Name + Post Count -->
        <h1 class="author-name" style="font-size:2rem; margin:0 0 5px 0; color:#333;">
            <?php echo esc_html($author_name); ?>
        </h1>
        <p class="author-meta" style="color:#666; margin:0 0 15px 0;">
            <?php echo intval($author_posts_count); ?> <?php echo _n('post', 'posts', $author_posts_count, 'awps'); ?>
        </p>
        
        <!-- Bio -->
        <?php if ($author_bio) : ?>
            <p class="author-bio" style="color:#555; max-width:700px; margin:0 auto 20px; line-height:1.6;">
                <?php echo wp_kses_post(wpautop($author_bio)); ?>
            </p>
        <?php endif; ?>
        
        <!-- Social Links -->
        <?php if (array_filter($social)) : ?>
            <div class="author-social" style="margin-top:15px;">
                <?php if ($social['website']) : ?>
                    <a href="<?php echo esc_url($social['website']); ?>" target="_blank" rel="noopener noreferrer" 
                       style="display:inline-block; margin:0 8px; color:#007cba; text-decoration:none;">🌐 Website</a>
                <?php endif; ?>
                <?php if ($social['linkedin']) : ?>
                    <a href="<?php echo esc_url($social['linkedin']); ?>" target="_blank" rel="noopener noreferrer" 
                       style="display:inline-block; margin:0 8px; color:#0077B5; text-decoration:none;">💼 LinkedIn</a>
                <?php endif; ?>
                <?php if ($social['twitter']) : ?>
                    <a href="<?php echo esc_url($social['twitter']); ?>" target="_blank" rel="noopener noreferrer" 
                       style="display:inline-block; margin:0 8px; color:#1DA1F2; text-decoration:none;">🐦 Twitter</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
    </header>
    
    <!-- Posts Section -->
    <?php if (have_posts()) : ?>
        
        <h2 style="font-size:1.5rem; margin:0 0 20px 0; color:#333;">Latest Articles</h2>
        
        <div class="posts-list" style="display:grid; gap:20px;">
            <?php while (have_posts()) : the_post(); ?>
                
                <article style="background:#fff; padding:25px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.08); transition:transform 0.2s;">
                    <h3 style="margin:0 0 10px 0; font-size:1.3rem;">
                        <a href="<?php the_permalink(); ?>" style="color:#007cba; text-decoration:none;">
                            <?php the_title(); ?>
                        </a>
                    </h3>
                    <div style="color:#666; font-size:14px; margin-bottom:12px;">
                        📅 <?php echo get_the_date('F j, Y'); ?> 
                        <?php 
                        $categories = get_the_category();
                        if ($categories) {
                            echo ' | 📂 ' . esc_html($categories[0]->name);
                        }
                        ?>
                    </div>
                    <div style="color:#333; line-height:1.6;">
                        <?php the_excerpt(); ?>
                    </div>
                    <a href="<?php the_permalink(); ?>" style="display:inline-block; margin-top:15px; color:#007cba; text-decoration:none; font-weight:500;">
                        Read article →
                    </a>
                </article>
                
            <?php endwhile; ?>
        </div>
        
        <!-- Pagination -->
        <div style="margin-top:40px; text-align:center;">
            <?php 
            echo paginate_links([
                'prev_text' => '← Previous',
                'next_text' => 'Next →',
                'mid_size' => 2,
            ]); 
            ?>
        </div>
        
    <?php else : ?>
        
        <!-- No Posts State -->
        <div style="text-align:center; padding:60px 20px; background:#f9f9f9; border-radius:12px;">
            <div style="font-size:48px; margin-bottom:20px;">📭</div>
            <h2 style="color:#333; margin:0 0 10px 0;">No Articles Yet</h2>
            <p style="color:#666; max-width:500px; margin:0 auto;">
                <?php echo esc_html($author_name); ?> hasn't published any articles yet. Check back soon!
            </p>
            <p style="margin-top:20px;">
                <a href="<?php echo home_url('/blog/'); ?>" style="color:#007cba; text-decoration:none; font-weight:500;">
                    ← Browse all articles
                </a>
            </p>
        </div>
        
    <?php endif; ?>
    
</div>

<?php get_footer(); ?>