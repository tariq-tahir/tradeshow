<?php
/**
 * Date Archive Template - WITH DEBUG
 */
get_header();
?>

<div class="archive-container" style="padding:40px 20px; max-width:1200px; margin:0 auto;">
    
    <!-- Archive Header -->
    <header class="page-header" style="margin-bottom:30px; padding-bottom:20px; border-bottom:1px solid #eee;">
        <h1 class="page-title" style="font-size:2rem; margin:0;">
            Articles from <?php echo get_the_date(); ?>
        </h1>
    </header>

    
    <!-- THE LOOP -->
    <?php if ( have_posts() ) : ?>
        
        <div class="posts-list" style="display:grid; gap:20px;">
            <?php while ( have_posts() ) : the_post(); ?>
                
                <article style="background:#f9f9f9; padding:20px; border-radius:8px;">
                    <h2 style="margin:0 0 10px 0;">
                        <a href="<?php the_permalink(); ?>" style="color:#007cba; text-decoration:none;">
                            <?php the_title(); ?>
                        </a>
                    </h2>
                    <div style="color:#666; font-size:14px; margin-bottom:10px;">
                        📅 <?php echo get_the_date(); ?> | 
                        👤 <?php the_author(); ?>
                    </div>
                    <div style="color:#333;">
                        <?php the_excerpt(); ?>
                    </div>
                </article>
                
            <?php endwhile; ?>
        </div>
        
        <!-- Pagination -->
        <div style="margin-top:30px;">
            <?php the_posts_pagination(); ?>
        </div>
        
    <?php else : ?>
        
        <!-- NO POSTS FOUND -->
        <div style="text-align:center; padding:60px 20px; background:#f9f9f9; border-radius:8px;">
            <div style="font-size:48px; margin-bottom:20px;">📭</div>
            <h2 style="color:#333; margin:0 0 10px 0;">No Posts Found</h2>
            <p style="color:#666; margin:0;">
                There are no posts published on <strong><?php echo get_the_date(); ?></strong>.
            </p>
            <p style="margin-top:20px;">
                <a href="<?php echo home_url('/blog/'); ?>" style="color:#007cba;">
                    ← Back to all posts
                </a>
            </p>
        </div>
        
    <?php endif; ?>
    
</div>

<?php get_footer(); ?>