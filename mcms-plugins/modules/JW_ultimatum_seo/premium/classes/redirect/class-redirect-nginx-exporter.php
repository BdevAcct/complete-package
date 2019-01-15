<?php
/**
 * @package MCMSSEO\Premium\Classes
 */

/**
 * Exporter for Nginx, only declares the two formats
 */
class MCMSSEO_Redirect_Nginx_Exporter extends MCMSSEO_Redirect_File_Exporter {

	/**
	 * %1$s is the origin
	 * %2$s is the target
	 * %3$s is the redirect type
	 * %4$s is the optional x-redirect-by filter.
	 *
	 * @var string
	 */
	protected $url_format   = 'location /%1$s { %4$s return %3$s %2$s; }';

	/**
	 * %1$s is the origin
	 * %2$s is the target
	 * %3$s is the redirect type
	 * %4$s is the optional x-redirect-by filter.
	 *
	 * @var string
	 */
	protected $regex_format = 'location ~ %1$s { %4$s return %3$s %2$s; }';

	/**
	 * Formats a redirect for use in the export.
	 *
	 * @param MCMSSEO_Redirect $redirect The redirect to format.
	 *
	 * @return string
	 */
	public function format( MCMSSEO_Redirect $redirect ) {
		return sprintf(
			$this->get_format( $redirect->get_format() ),
			$redirect->get_origin(),
			$redirect->get_target(),
			$redirect->get_type(),
			$this->add_x_redirect_header()
		);
	}

	/**
	 * Adds an X-Redirect-By header if allowed by the filter.
	 *
	 * @return string
	 */
	private function add_x_redirect_header() {
		/**
		 * Filter: 'mcmsseo_add_x_redirect' - can be used to remove the X-Redirect-By header Ultimatum SEO creates
		 * (only available in Ultimatum SEO, defaults to true, which is adding it)
		 *
		 * @api bool
		 */
		if ( apply_filters( 'mcmsseo_add_x_redirect', true ) === true ) {
			return 'add_header X-Redirect-By "Ultimatum SEO";';
		}

		return '';
	}
}
