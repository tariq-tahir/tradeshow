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
  <div class="container">
    <div class="footer-grid">
      <div class="footer-col">
        <h4>TradeShow.pk</h4>
        <p>Connect. Export. Grow.</p>
        <p>📞 +92-300-121-1566<br>
           💬 <a href="https://wa.me/923001211566">WhatsApp</a><br>
           📧 support@tradeshow.pk</p>
        <div class="social-icons">[LinkedIn] [Facebook] [Instagram]</div>
      </div>
      <div class="footer-col">
        <h4>For Buyers</h4>
        <ul>
          <li><a href="/how-it-works">How It Works</a></li>
          <li><a href="/products">Browse Products</a></li>
          <li><a href="/categories">Categories</a></li>
          <li><a href="/rfq-process">RFQ Process</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>For Exporters</h4>
        <ul>
          <li><a href="/join-exporter">Join as Exporter</a></li>
          <li><a href="/list-product">List Your Products</a></li>
          <li><a href="/exporter-dashboard">Dashboard</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Legal & Support</h4>
        <ul>
          <li><a href="/privacy-policy">Privacy Policy</a></li>
          <li><a href="/terms">Terms of Use</a></li>
          <li><a href="/contact">Contact Us</a></li>
          <li><a href="/help">Help Center</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© 2025 TradeShow.pk — Pakistan’s First Digital Trade Show</p>
      <p>Made with ❤️ in Pakistan</p>
    </div>
  </div>
</footer>

	<footer id="colophon" class="site-footer container-fluid" role="contentinfo">

		<div class="site-info">
			<?php
				printf(
					'<a %s href="%s">%s</a>',
					is_customize_preview() ? 'id="awps-footer-copy-control"' : '',
					esc_url( __( 'https://github.com/Alecaddd/awps', 'awps' ) ),
					esc_html( Awps\Api\Customizer::text( 'awps_footer_copy_text' ) )
				);
			?>
		</div><!-- .site-info -->
	</footer><!-- #colophon -->


<?php wp_footer(); ?>

</body>
</html>
