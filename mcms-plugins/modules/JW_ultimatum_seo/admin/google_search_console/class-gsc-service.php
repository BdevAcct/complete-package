<?php
/**
 * @package MCMSSEO\Admin|Google_Search_Console
 */

/**
 * Class MCMSSEO_GSC_Service
 */
class MCMSSEO_GSC_Service {

	/**
	 * @var Ultimatum_Api_Google_Client
	 */
	private $client;

	/**
	 * @var string
	 */
	private $profile;

	/**
	 * Search Console service constructor.
	 *
	 * @param string $profile Profile name.
	 */
	public function __construct( $profile = '' ) {
		$this->profile = $profile;

		$this->set_client();
	}

	/**
	 * Returns the client
	 *
	 * @return Ultimatum_Api_Google_Client
	 */
	public function get_client() {
		return $this->client;
	}

	/**
	 * Removes the option and calls the clients clear_data method to clear that one as well
	 */
	public function clear_data() {
		// Clear client data.
		$this->client->clear_data();
	}

	/**
	 * Get all sites that are registered in the GSC panel
	 *
	 * @return array
	 */
	public function get_sites() {
		$sites = array();

		$response_json = $this->client->do_request( 'sites', true );

		// Do list sites request.
		if ( ! empty( $response_json->siteEntry ) ) {
			foreach ( $response_json->siteEntry as $entry ) {
				$sites[ str_ireplace( 'sites/', '', (string) $entry->siteUrl ) ] = (string) $entry->siteUrl;
			}

			// Sorting the retrieved sites.
			asort( $sites );
		}

		return $sites;
	}

	/**
	 * Get crawl issues
	 *
	 * @return array
	 */
	public function get_crawl_issue_counts() {
		// Setup crawl error list.
		$crawl_error_counts = $this->get_crawl_error_counts( $this->profile );

		$return = array();
		if ( ! empty( $crawl_error_counts->countPerTypes ) ) {
			foreach ( $crawl_error_counts->countPerTypes as $category ) {
				$return[ $category->platform ][ $category->category ] = array(
					'count'      => $category->entries[0]->count,
					'last_fetch' => null,
				);
			}
		}

		return $return;
	}

	/**
	 * Sending request to mark issue as fixed
	 *
	 * @param string $url      Issue URL.
	 * @param string $platform Platform (desktop, mobile, feature phone).
	 * @param string $category Issue type.
	 *
	 * @return bool
	 */
	public function mark_as_fixed( $url, $platform, $category ) {
		$response = $this->client->do_request( 'sites/' . urlencode( $this->profile ) . '/urlCrawlErrorsSamples/' . urlencode( ltrim( $url, '/' ) ) . '?category=' . MCMSSEO_GSC_Mapper::category_to_api( $category ) . '&platform=' . MCMSSEO_GSC_Mapper::platform_to_api( $platform ) . '', false, 'DELETE' );
		return ( $response->getResponseHttpCode() === 204 );
	}

	/**
	 * Fetching the issues from the GSC API
	 *
	 * @param string $platform Platform (desktop, mobile, feature phone).
	 * @param string $category Issue type.
	 *
	 * @return mixed
	 */
	public function fetch_category_issues( $platform, $category ) {
		$issues = $this->client->do_request(
			'sites/' . urlencode( $this->profile ) . '/urlCrawlErrorsSamples?category=' . $category . '&platform=' . $platform,
			true
		);

		if ( ! empty( $issues->urlCrawlErrorSample ) ) {
			return $issues->urlCrawlErrorSample;
		}
	}

	/**
	 * Setting the GSC client
	 */
	private function set_client() {
		try {
			new Ultimatum_Api_Libs( '2.0' );
		}
		catch ( Exception $exception ) {
			if ( $exception->getMessage() === 'required_version' ) {
				$this->incompatible_api_libs(
					__( 'Ultimatum modules share some code between them to make your site faster. As a result of that, we need all Ultimatum modules to be up to date. We\'ve detected this isn\'t the case, so please update the Ultimatum modules that aren\'t up to date yet.', 'mandarincms-seo' )
				);
			}
		}

		if ( class_exists( 'Ultimatum_Api_Google_Client' ) === false ) {
			$this->incompatible_api_libs(
				/* translators: %1$s expands to Ultimatum SEO, %2$s expands to Google Analytics by Ultimatum */
				sprintf(
					__(
						'%1$s detected you’re using a version of %2$s which is not compatible with %1$s. Please update %2$s to the latest version to use this feature.',
						'mandarincms-seo'
					),
					'Ultimatum SEO',
					'Google Analytics by Ultimatum'
				)
			);

			mcms_redirect( admin_url( 'admin.php?page=' . MCMSSEO_Admin::PAGE_IDENTIFIER ) );
			exit;
		}

		$this->client = new Ultimatum_Api_Google_Client( MCMSSEO_GSC_Config::$gsc, 'mcmsseo-gsc', 'https://www.googleapis.com/webmasters/v3/' );
	}

	/**
	 * Adding notice that the api libs has the wrong version
	 *
	 * @param string $notice Message string.
	 */
	private function incompatible_api_libs( $notice ) {
		Ultimatum_Notification_Center::get()->add_notification(
			new Ultimatum_Notification( $notice, array( 'type' => Ultimatum_Notification::ERROR ) )
		);
	}

	/**
	 * Getting the crawl error counts
	 *
	 * @param string $profile Profile name string.
	 *
	 * @return object|bool
	 */
	private function get_crawl_error_counts( $profile ) {
		$crawl_error_counts = $this->client->do_request(
			'sites/' . urlencode( $profile ) . '/urlCrawlErrorsCounts/query',
			true
		);

		if ( ! empty( $crawl_error_counts ) ) {
			return $crawl_error_counts;
		}

		return false;
	}
}