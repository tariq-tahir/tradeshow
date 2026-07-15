<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package awps
 */

?>

</main><!-- #content -->

<?php
if ( is_customize_preview() ) {
    echo '<div id="awps-footer-control" style="margin-top:-30px;position:absolute;"></div>';
}
?>

<footer class="site-footer">
    <div class="footer-content">
        <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
            <?php if ( is_active_sidebar( "footer-column-$i" ) ) : ?>
                <div class="footer-column">
                    <?php dynamic_sidebar( "footer-column-$i" ); ?>
                </div>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    
    <div class="footer-bottom">
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> <a href="<?php echo home_url(); ?>">TradeShow</a>. All Rights Reserved
        </div>
        <a href="#" class="back-to-top" title="Back to top">
            <i class="fas fa-chevron-up"></i>
        </a>
    </div>
</footer>


<?php wp_footer(); ?>

</body>
</html>
