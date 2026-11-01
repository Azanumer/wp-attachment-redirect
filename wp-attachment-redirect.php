<?php
/**
 * Plugin Name:       Attachment Redirect
 * Plugin URI:        https://github.com/Azanumer/wp-attachment-redirect
 * Description:       Redirects thin WordPress attachment pages to the file itself or the parent post — kills a common source of low-value indexed pages.
 * Version:           1.0.0
 * Author:            Azan Umer
 * License:           MIT
 * Text Domain:       wp-attachment-redirect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WAR_VERSION', '1.0.0' );
define( 'WAR_OPTION', 'war_settings' );

/**
 * Default settings.
 *
 * @return array
 */
function war_get_settings() {
	$defaults = array(
		'mode'       => 'file', // file | parent | 404
		'apply_to'   => 'images', // images | all
		'status'     => 301,
	);
	$saved = get_option( WAR_OPTION, array() );
	return is_array( $saved ) ? array_merge( $defaults, $saved ) : $defaults;
}

/**
 * Redirect attachment pages before the template loads.
 */
function war_redirect_attachment_pages() {
	if ( ! is_attachment() ) {
		return;
	}

	$settings = war_get_settings();
	$attachment_id = get_queried_object_id();

	if ( 'images' === $settings['apply_to'] && ! wp_attachment_is_image( $attachment_id ) ) {
		return;
	}

	// Let site owners opt individual attachments out.
	if ( apply_filters( 'war_skip_redirect', false, $attachment_id ) ) {
		return;
	}

	$status = in_array( (int) $settings['status'], array( 301, 302 ), true ) ? (int) $settings['status'] : 301;

	if ( '404' === $settings['mode'] ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		require get_query_template( '404' );
		exit;
	}

	if ( 'parent' === $settings['mode'] ) {
		$parent_id = wp_get_post_parent_id( $attachment_id );
		$target = $parent_id ? get_permalink( $parent_id ) : wp_get_attachment_url( $attachment_id );
	} else {
		$target = wp_get_attachment_url( $attachment_id );
	}

	if ( ! $target ) {
		return; // Nothing sensible to redirect to — leave the page alone.
	}

	wp_redirect( esc_url_raw( $target ), $status );
	exit;
}
add_action( 'template_redirect', 'war_redirect_attachment_pages', 1 );

/**
 * Settings page under Settings → Attachment Redirect.
 */
function war_register_settings_page() {
	add_options_page(
		__( 'Attachment Redirect', 'wp-attachment-redirect' ),
		__( 'Attachment Redirect', 'wp-attachment-redirect' ),
		'manage_options',
		'war-settings',
		'war_render_settings_page'
	);
}
add_action( 'admin_menu', 'war_register_settings_page' );

/**
 * Register the option with sanitisation.
 */
function war_register_settings() {
	register_setting(
		'war_settings_group',
		WAR_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'war_sanitize_settings',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'war_register_settings' );

/**
 * Sanitise settings input.
 *
 * @param array $input Raw input.
 * @return array
 */
function war_sanitize_settings( $input ) {
	$clean = array();
	$clean['mode'] = isset( $input['mode'] ) && in_array( $input['mode'], array( 'file', 'parent', '404' ), true )
		? $input['mode'] : 'file';
	$clean['apply_to'] = isset( $input['apply_to'] ) && 'all' === $input['apply_to'] ? 'all' : 'images';
	$clean['status'] = isset( $input['status'] ) && 302 === (int) $input['status'] ? 302 : 301;
	return $clean;
}

/**
 * Render the settings page.
 */
function war_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-attachment-redirect' ) );
	}
	$settings = war_get_settings();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Attachment Redirect', 'wp-attachment-redirect' ); ?></h1>
		<p><?php esc_html_e( 'Attachment pages are thin pages WordPress creates for every upload. Redirecting them keeps Google from indexing them and sends visitors straight to something useful.', 'wp-attachment-redirect' ); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'war_settings_group' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Redirect target', 'wp-attachment-redirect' ); ?></th>
					<td>
						<label><input type="radio" name="<?php echo esc_attr( WAR_OPTION ); ?>[mode]" value="file" <?php checked( $settings['mode'], 'file' ); ?>> <?php esc_html_e( 'The media file itself (recommended)', 'wp-attachment-redirect' ); ?></label><br>
						<label><input type="radio" name="<?php echo esc_attr( WAR_OPTION ); ?>[mode]" value="parent" <?php checked( $settings['mode'], 'parent' ); ?>> <?php esc_html_e( 'The parent post (falls back to the file when unattached)', 'wp-attachment-redirect' ); ?></label><br>
						<label><input type="radio" name="<?php echo esc_attr( WAR_OPTION ); ?>[mode]" value="404" <?php checked( $settings['mode'], '404' ); ?>> <?php esc_html_e( 'Show a 404 page', 'wp-attachment-redirect' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Apply to', 'wp-attachment-redirect' ); ?></th>
					<td>
						<label><input type="radio" name="<?php echo esc_attr( WAR_OPTION ); ?>[apply_to]" value="images" <?php checked( $settings['apply_to'], 'images' ); ?>> <?php esc_html_e( 'Images only', 'wp-attachment-redirect' ); ?></label><br>
						<label><input type="radio" name="<?php echo esc_attr( WAR_OPTION ); ?>[apply_to]" value="all" <?php checked( $settings['apply_to'], 'all' ); ?>> <?php esc_html_e( 'All attachment types', 'wp-attachment-redirect' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Redirect status', 'wp-attachment-redirect' ); ?></th>
					<td>
						<select name="<?php echo esc_attr( WAR_OPTION ); ?>[status]">
							<option value="301" <?php selected( $settings['status'], 301 ); ?>>301 <?php esc_html_e( 'Permanent', 'wp-attachment-redirect' ); ?></option>
							<option value="302" <?php selected( $settings['status'], 302 ); ?>>302 <?php esc_html_e( 'Temporary', 'wp-attachment-redirect' ); ?></option>
						</select>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
