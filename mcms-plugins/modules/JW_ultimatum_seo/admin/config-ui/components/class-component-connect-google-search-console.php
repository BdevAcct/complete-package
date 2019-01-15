<?php
/**
 * @package MCMSSEO\Admin\ConfigurationUI
 */

/**
 * Class MCMSSEO_Config_Component_Connect_Google_Search_Console
 */
class MCMSSEO_Config_Component_Connect_Google_Search_Console implements MCMSSEO_Config_Component {

	const OPTION_ACCESS_TOKEN = 'mcmsseo-gsc-access_token';
	const OPTION_REFRESH_TOKEN = 'mcmsseo-gsc-refresh_token';


	/** @var MCMSSEO_GSC_Service Service to use */
	protected $gsc_service;

	/**
	 * MCMSSEO_Config_Component_Connect_Google_Search_Console constructor.
	 */
	public function __construct() {
		$this->gsc_service = new MCMSSEO_GSC_Service( $this->get_profile() );
	}

	/**
	 * Set the Google Search Console service.
	 *
	 * @param MCMSSEO_GSC_Service $service Set service to use.
	 */
	public function set_gsc_service( MCMSSEO_GSC_Service $service ) {
		$this->gsc_service = $service;
	}

	/**
	 * Gets the component identifier.
	 *
	 * @return string
	 */
	public function get_identifier() {
		return 'ConnectGoogleSearchConsole';
	}

	/**
	 * Gets the field.
	 *
	 * @return MCMSSEO_Config_Field
	 */
	public function get_field() {
		return new MCMSSEO_Config_Field_Connect_Google_Search_Console();
	}

	/**
	 * Get the data for the field.
	 *
	 * @return mixed
	 */
	public function get_data() {

		$data = array(
			'profileList'    => $this->get_profilelist(),
			'profile'        => $this->get_profile(),
			'hasAccessToken' => $this->hasAccessToken(),
		);

		return $data;
	}

	/**
	 * Save data
	 *
	 * @param array $data Data containing changes.
	 *
	 * @return mixed
	 */
	public function set_data( $data ) {

		$current_data = $this->get_data();

		$this->handle_profile_change( $current_data, $data );

		// Save profile.
		$has_saved = update_option(
			MCMSSEO_GSC::OPTION_MCMSSEO_GSC,
			array( 'profile' => $data['profile'] )
		);

		// Collect results to return to the configurator.
		$results = array(
			'profile' => $has_saved,
		);

		return $results;
	}

	/**
	 * Remove issues when the profile has changed
	 *
	 * @param array $current_data Saved data before changes.
	 * @param array $data         Data after changes.
	 */
	protected function handle_profile_change( $current_data, $data ) {
		// If the profile has been changed, remove issues.
		if ( $current_data['profile'] === $data['profile'] ) {
			return;
		}

		$this->reload_issues();
	}

	/**
	 * Get the current GSC profile
	 *
	 * @return string
	 */
	protected function get_profile() {
		return MCMSSEO_GSC_Settings::get_profile();
	}

	/**
	 * Reload GSC issues
	 */
	protected function reload_issues() {
		MCMSSEO_GSC_Settings::reload_issues();
	}

	/**
	 * Gets a list with the profiles.
	 *
	 * @return array
	 */
	protected function get_profilelist() {
		$profiles = array();
		$sites    = $this->gsc_service->get_sites();
		foreach ( $sites as $siteKey => $siteValue ) {
			$profiles[ untrailingslashit( $siteKey )  ] = untrailingslashit( $siteValue );
		}

		return $profiles;
	}

	/**
	 * Checks if there is an access token. If so, there is a connection.
	 *
	 * @return bool
	 */
	private function hasAccessToken() {
		return ( null !== $this->gsc_service->get_client()->getAccessToken() );
	}
}
