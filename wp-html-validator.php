<?php

/**
 * Plugin Name: HTML Validator
 * Description: Validates your site's HTML and reports any errors in the footer. Original code based on the DeBogger plugin by Simon Prosser.
 * Author: J.D. Grimes
 * Version: 1.0.0
 * Author URI: http://codesymphony.co/
 *
 * @package WP_HTML_Validator
 * @version 1.0.0
 */

/**
 * Display the HTML errors on the page.
 *
 * @since 1.0.0
 */
function wp_html_validator_footer() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_GET['wp_html_validator'] ) ) {
		return;
	}

	$w3c = wp_html_validator_check_url( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

	if ( is_wp_error( $w3c ) || $w3c['status'] !== 'valid' ) {
		$color = '#FF9999';
	} else {
		$color = '#99FF99';
	}

	?>

	<div style="background-color: <?php echo esc_attr( $color ); ?>; text-align: left; display: block; clear: both; margin-left: auto; margin-right: auto; border: 1px dashed red; width: 70%; color: #000; padding: 10px;">
		<?php if ( is_wp_error( $w3c ) ) : ?>
			<?php echo esc_html( 'There was an error: ' . $w3c->get_error_message() ); ?>
		<?php else : ?>
			<?php $cached = ( $w3c['cached'] ) ? '(cached)' : '(not cached)'; ?>
			<?php if ( $w3c['status'] !== 'valid' ) : ?>
				<?php echo 'Not W3C valid! (<a href="http://validator.w3.org/#validate_by_input"> ' , (int) $w3c['error_count'] , ' errors</a>) ', $cached; ?>
				<pre><?php echo esc_html( $w3c['errors'] ); ?></pre>
			<?php else : ?>
				<?php echo 'W3C Valid! ', $cached; ?>
			<?php endif; ?>
		<?php endif; ?>

		<span style="text-align: right; float: right; color: #000;">
			<small>
				<a href="http://codesymphony.co/">HTML Validator by J.D. Grimes</a>
			</small>
		</span>
	</div>

	<?php
}
add_action( 'admin_footer', 'wp_html_validator_footer', 9999 );
add_action( 'wp_footer', 'wp_html_validator_footer', 9999 );

/**
 * Check the validity of a webpage's HTML.
 *
 * @since 1.0.0
 *
 * @param string $url The URL of a webpage to check.
 *
 * @return WP_Error|array {
 *         The result of validation.
 *
 *         @type string $status      Validation status from W3C: valid, invalid, or abort.
 *         @type int    $error_count The number of errors reported.
 *         @type bool   $cached      Whether the cache was used.
 * }
 */
function wp_html_validator_check_url( $url ) {

	$url_md5 = md5( $url );

	$transient = get_transient( "wp_html_validator-{$url_md5}" );

	if ( false === $transient ) {

		$response = wp_remote_get(
			add_query_arg( 'wp_html_validator', 1, $url )
			, array(
				'timeout' => 10,
				'cookies' => $_COOKIE,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_body = wp_remote_retrieve_body( $response );

		$w3c_response = wp_remote_post(
			'http://validator.w3.org/check'
			, array(
				'body' => array( 'fragment' => $response_body ),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $w3c_response ) ) {
			return $w3c_response;
		}

		$result = array();
		$result['status'] = strtolower( wp_remote_retrieve_header( $w3c_response, 'x-w3c-validator-status' ) );
		$result['error_count'] = (int) wp_remote_retrieve_header( $w3c_response, 'x-w3c-validator-errors' );

		if ( $result['error_count'] !== 0 ) {

			$dom = new DOMDocument;
			$dom->loadHTML( wp_remote_retrieve_body( $w3c_response ) );
			$result['errors']  = $dom->getElementById( 'error_loop' )->textContent;
		}

		$expiration = 2 * MINUTE_IN_SECONDS;

		if ( defined( 'WP_HTML_VALIDATOR_CACHE_EXPIRATION' ) ) {
			$expiration = WP_HTML_VALIDATOR_CACHE_EXPIRATION;
		}

		set_transient( "wp_html_validator-{$url_md5}", $result, $expiration );

		$result['cached'] = false;

	} else {

		$result = $transient;
		$result['cached'] = true;
	}

	return $result;
}

// EOF
