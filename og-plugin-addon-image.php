<?php
/**
 * OG Plugin Addon: image
 *
 * @package           OG Plugin Addon: og:image
 * @author            Marcin Pietrzak
 * @copyright         2023-2025 Marcin Pietrzak
 * @license           GPL-3.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       OG — Addon: og:image
 * Plugin URI:        https://github.com/iworks/og-plugin-addon-image
 * Description:       Extension for OG WordPress plugin allows you to add a og:image.
 * Version:           1.0.2
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Marcin Pietrzak
 * Author URI:        http://iworks.pl/
 * Text Domain:       og-plugin-addon-image
 * License:           GPL v3 or later
 * License URI:       http://www.gnu.org/licenses/gpl-1.0.txt
 * Requires Plugins:  og
 */

class iworks_og_plugin_addon_image {

	private $post_types = array(
		'post',
		'page',
	);

	private $post_meta_name_og_image = '_og_attachment_id';

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'init', array( $this, 'action_load_plugin_textdomain' ), 0 );
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_filter( 'og_array', array( $this, 'filter_og_og_image' ) );
	}

	public function filter_og_og_image( $og ) {
		if ( ! is_singular( $this->post_types ) ) {
			return $og;
		}
		$attachment_id = get_post_meta( get_the_ID(), $this->post_meta_name_og_image, true );
		if ( empty( $attachment_id ) ) {
			return $og;
		}
		$attachment = wp_get_attachment_image_src( $attachment_id, 'full' );
		if ( empty( $attachment ) ) {
			return $og;
		}
		$image = array(
			'url' => $attachment[0],
		);
		if ( preg_match( '/^https/', $attachment[0] ) ) {
			$image['secure_url'] = $attachment[0];
		}
		if ( $attachment[1] ) {
			$image['width'] = $attachment[1];
		}
		if ( $attachment[2] ) {
			$image['height'] = $attachment[2];
		}
		$image['type'] = get_post_mime_type( $attachment_id );
		if ( is_array( $og['og']['image'] ) ) {
			array_unshift( $og['og']['image'], $image );
		} else {
			$og['og']['image'] = array( $image );
		}
		return $og;
	}

	public function add_meta_boxes() {
		foreach ( $this->post_types as $screen ) {
			add_meta_box(
				esc_html__( 'OG Plugin Addons', 'og-plugin-addon-image' ),
				esc_html__( 'OG Plugin Addons', 'og-plugin-addon-image' ),
				array( $this, 'add_meta_box_callback' ),
				$screen,
				'side',
				'low'
			);
		}
	}

	public function admin_enqueue_scripts() {
		global $typenow;
		if ( in_array( $typenow, $this->post_types ) ) {
			wp_enqueue_media();
		}
	}

	public function admin_head() {
		global $typenow;
		if ( in_array( $typenow, $this->post_types ) ) {
			?><script>
				jQuery.noConflict();
				(function($) {
					$(function() {
						$('body').on('click', '.editor-og-image', function(e) {
							e.preventDefault();
							let button = $(this);
							let rwpMediaUploader = null;
							rwpMediaUploader = wp.media({
								title: button.data('modal-title'),
								button: {
									text: button.data('modal-button')
								},
								multiple: true
							}).on('select', function() {
								let attachment = rwpMediaUploader.state().get('selection').first().toJSON();
								button.prev().val(attachment[button.data('return')]);
								l(attachment.id);
								l(attachment.url);
								$('input[name=<?php echo esc_attr( $this->post_meta_name_og_image ); ?>]', button.closest('.postbox') ).val( attachment.id );
								button.html(
									'<span class="components-responsive-wrapper"><div><img src="'+attachment.url+'" alt="" class="components-responsive-wrapper__content"></div></span>'
									);
							}).open();
						});
					});
				})(jQuery);
			</script>
			<?php
		}
	}

	public function save_post( $post_id ) {
		if ( isset( $_POST[ $this->post_meta_name_og_image ] ) ) {
			$value = intval( $_POST[ $this->post_meta_name_og_image ] );
			update_post_meta( $post_id, $this->post_meta_name_og_image, $value );
		} else {
			delete_post_meta( $post_id, $this->post_meta_name_og_image );
		}
	}

	public function add_meta_box_callback() {
		global $post;
		$value = get_post_meta( $post->ID, $this->post_meta_name_og_image, true );
		$url   = null;
		if ( ! empty( $value ) ) {
			$url = wp_get_attachment_url( $value );
		}
		?>
<div class="editor-post-featured-image">
	<div class="editor-post-featured-image__container">
	<button type="button" class="components-button editor-post-featured-image__toggle editor-og-image">
		<?php
		if ( empty( $url ) ) {
			esc_html_e( 'Set og:image', 'og-plugin-addon-image' );
		} else {
			printf(
				'<span class="components-responsive-wrapper"><div><img src="%s" alt="" class="components-responsive-wrapper__content"></div></span>',
				esc_url( $url )
			);
		}
		?>
 </button>
		<div class="components-drop-zone" data-is-drop-zone="true"></div>
	</div>
</div>
<input type="hidden" name="<?php echo esc_attr( $this->post_meta_name_og_image ); ?>" value="<?php echo intval( $value ); ?>">
		<?php
	}

	/**
	 * i18n
	 *
	 * @since 1.0.1
	 */
	public function action_load_plugin_textdomain() {
		return;
		load_plugin_textdomain(
			'og-plugin-addon-image',
			false,
			plugin_basename( $this->root ) . '/languages'
		);
	}

}

new iworks_og_plugin_addon_image();
