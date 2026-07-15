<?php

namespace Awps\Api\Widgets;

use WP_Widget;

/**
 * Latest Products Widget with Carousel
 */
class LatestProductsWidget extends WP_Widget {

	public $widget_ID;
	public $widget_name;
	public $widget_options;
	public $control_options;

	public function __construct() {
		$this->widget_ID = 'awps_latest_products';
		$this->widget_name = 'AWPS Latest Products';

		$this->widget_options = array(
			'classname' => $this->widget_ID,
			'description' => 'Shows latest WooCommerce products in a carousel.',
			'customize_selective_refresh' => true,
		);

		$this->control_options = array(
			'width' => 400,
			'height' => 350,
		);

		parent::__construct(
			$this->widget_ID,
			$this->widget_name,
			$this->widget_options,
			$this->control_options
		);
	}

	public function register() {
		add_action( 'widgets_init', array( $this, 'widgetsInit' ) );
	}

	public function widgetsInit() {
		register_widget( $this );
	}

	public function widget( $args, $instance ) {
		if ( ! function_exists( 'wc_get_products' ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				echo $args['before_widget'];
				echo '<p style="color:red; padding:10px; background:#fff3f3;">⚠️ WooCommerce not active.</p>';
				echo $args['after_widget'];
			}
			return;
		}

		$title  = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 6;
		$number = max( 1, min( 20, $number ) );

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . esc_html( apply_filters( 'widget_title', $title ) ) . $args['after_title'];
		}

		$product_ids = wc_get_products( [
			'status'  => 'publish',
			'limit'   => $number,
			'orderby' => 'date',
			'order'   => 'DESC',
			'return'  => 'ids',
		] );

		if ( ! $product_ids ) {
			echo '<p>' . esc_html__( 'No products found.', 'awps' ) . '</p>';
			echo $args['after_widget'];
			return;
		}

		// Enqueue Slick only once per page
		if ( ! wp_script_is( 'slick', 'enqueued' ) ) {
			wp_enqueue_style( 'slick', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css', [], '1.8.1' );
			wp_enqueue_style( 'slick-theme', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css', ['slick'], '1.8.1' );
			wp_enqueue_script( 'slick', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js', ['jquery'], '1.8.1', true );

			// Add custom init script
			wp_add_inline_script( 'slick', "
				jQuery(document).ready(function($) {
                    $('.awps-products-carousel').slick({
                        dots: true,
                        infinite: true,
                        speed: 300,
                        slidesToShow: 6,
                        slidesToScroll: 6,
                        prevArrow: '<button type=\"button\" class=\"slick-prev\">‹</button>',
                        nextArrow: '<button type=\"button\" class=\"slick-next\">›</button>',
                        responsive: [
                            {
                                breakpoint: 1024,
                                settings: {
                                    slidesToShow: 3,
                                    slidesToScroll: 3
                                }
                            },
                            {
                                breakpoint: 768,
                                settings: {
                                    slidesToShow: 2,
                                    slidesToScroll: 2
                                }
                            },
                            {
                                breakpoint: 480,
                                settings: {
                                    slidesToShow: 2,
                                    slidesToScroll: 2,
                                    dots: false,            // optional: hide dots on small screens
                                    arrows: true           // optional: hide arrows on mobile
                                }
                            }
                        ]
                    });
				});
			", 'after' );
		}

		// Output carousel HTML
		echo '<div class="awps-products-carousel">';

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) continue;

			$image = $product->get_image( 'woocommerce_thumbnail' ) ?: wc_placeholder_img( 'woocommerce_thumbnail' );
			$name  = esc_html( $product->get_name() );
			$url   = esc_url( $product->get_permalink() );
			

            // Get exporter via post_author (like your working code)
            $exporter_id = get_post_field( 'post_author', $product->get_id() );
            $exporter_name = 'Exporter';

            if ( $exporter_id ) {
                $exporter = get_user_by( 'id', $exporter_id );
                if ( $exporter ) {
                    // Try company_name meta, fallback to display_name
                    $company_name = get_user_meta( $exporter_id, 'company_name', true );
                    $exporter_name = $company_name ?: $exporter->display_name;

                    $exporter_slug = $exporter->user_nicename;

                }

            }
            
            


            $exporter_profile_url = $exporter ? home_url('/exporter/' . sanitize_title($exporter_slug)) : '#';

            



			echo '<div class="awps-product-slide">';
			echo '<a href="' . $url . '" class="awps-product-link">';
			echo '<div class="awps-product-image-wrapper">';
			echo $image;
			echo '</div>';
            echo '</a>';
			echo '<div class="awps-product-info">';
            echo '<a href="' . $exporter_profile_url . '" class="awps-product-link">';
			echo '<div class="awps-company">' . esc_html( $company_name ) . '</div>';
            echo '</a>';
            echo '<a href="' . $url . '" class="awps-product-link">';
			echo '<div class="awps-product-name">' . $name . '</div>';
            echo '</a>';
			echo '</div>';
			
			echo '</div>';
		}

		echo '</div>';

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title  = ! empty( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 4;

		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				Title:
			</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
			       type="text"
			       value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>">
				Number of products:
			</label>
			<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>"
			       type="number"
			       step="1"
			       min="1"
			       max="20"
			       value="<?php echo esc_attr( $number ); ?>"
			       size="3">
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title']  = sanitize_text_field( $new_instance['title'] );
		$instance['number'] = absint( $new_instance['number'] );
		return $instance;
	}
}