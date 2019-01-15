<?php
/**
 * @package MCMSSEO\Premium\Classes
 */

/**
 * Class MCMSSEO_Watcher
 */
abstract class MCMSSEO_Watcher {

	/**
	 * @var string The type to watch for.
	 */
	protected $watch_type;

	/**
	 * The hooks being set for the given watcher
	 */
	abstract protected function set_hooks();

	/**
	 * Returns the undo notification text for the given watcher
	 *
	 * @return string
	 */
	abstract protected function get_undo_slug_notification();

	/**
	 * Returns the undo notification text for the given watcher
	 *
	 * @return string
	 */
	abstract protected function get_delete_notification();

	/**
	 * Parses the hidden field with the old URL to show in the form
	 *
	 * @param string $url  The old URL.
	 * @param string $type The type of the URL.
	 *
	 * @return string
	 */
	protected function parse_url_field( $url, $type ) {

		// Output the hidden field.
		return '<input type="hidden" name="mcmsseo_old_' . $type . '_url" value="' . esc_attr( $url ) . '"/>';

	}

	/**
	 * This method checks if it's desirable to create a redirect
	 *
	 * @param string $old_url The old URL.
	 * @param string $new_url The entered new URL.
	 *
	 * @return bool
	 */
	protected function should_create_redirect( $old_url, $new_url ) {

		// Get the site URL.
		$site = parse_url( get_site_url() );

		if ( $old_url !== $new_url && $old_url !== '/' && ( ! isset( $site['path'] ) || ( isset( $site['path'] ) && $old_url !== $site['path'] . '/' ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Creates notification with given $message
	 *
	 * This method will also apply filter for $notification_type to determine if notification has to be shown
	 *
	 * @param string $message           The message that will be added to the notification.
	 * @param string $notification_type The type of the notification.
	 * @param string $id 				ID that will be given to the notice.
	 */
	protected function create_notification( $message, $notification_type, $id = null ) {
		$show_notification = true;
		$show_notification = apply_filters( 'mcmsseo_enable_notification_' . $this->watch_type . '_' . $notification_type, $show_notification );

		if ( $show_notification ) {
			// Add the message to the notifications center.
			$arguments = array( 'type' => 'updated' );
			if ( ! empty( $id ) ) {
				$arguments['id'] = $id;
			}

			Ultimatum_Notification_Center::get()->add_notification( new Ultimatum_Notification( $message, $arguments ) );
		}

	}

	/**
	 * Display the delete notification.
	 *
	 * @param string $url The redirect that will be deleted.
	 */
	protected function set_delete_notification( $url ) {
		$id = 'mcmsseo_delete_redirect_' . md5( $url );

		// Format the message.
		$message = sprintf(
			$this->get_delete_notification(),
			'Ultimatum SEO',
			$this->get_delete_action_list( $url, $id ),
			'<a target="_blank" href="https://jiiworks.net/deleting-pages-from-your-site/#utm_source=mandarincms-seo-premium-' . $this->watch_type . '-watcher&amp;utm_medium=dialog&amp;utm_campaign=410-redirect">',
			'</a>'
		);

		$this->create_notification( $message, 'delete' );
	}

	/**
	 * Returns the string to the javascript method from where the added redirect can be undone
	 *
	 * @param MCMSSEO_Redirect $redirect The redirect that will be deleted.
	 * @param string         $id       ID of the notice that is displayed.
	 *
	 * @return string
	 */
	protected function javascript_undo_redirect( MCMSSEO_Redirect $redirect, $id ) {
		return sprintf(
			'mcmsseo_undo_redirect( "%1$s", "%2$s", "%3$s", "%4$s", this );',
			esc_js( $redirect->get_origin() ),
			esc_js( $redirect->get_target() ),
			esc_js( $redirect->get_type() ),
			mcms_create_nonce( 'mcmsseo-redirects-ajax-security' )
		);
	}

	/**
	 * Opens the redirect manager and create the redirect
	 *
	 * @param string $old_url     The URL that will be redirected.
	 * @param string $new_url     The URL where the old_url redirects to.
	 * @param int    $header_code The redirect type.
	 *
	 * @return MCMSSEO_Redirect
	 */
	protected function create_redirect( $old_url, $new_url, $header_code = 301 ) {
		// The URL redirect manager.
		$redirect_manager = new MCMSSEO_Redirect_Manager();
		$redirect         = new MCMSSEO_Redirect( $old_url, $new_url, $header_code );

		// Create the redirect.
		$redirect_manager->create_redirect( $redirect );

		return $redirect;
	}

	/**
	 * Returns the string to the javascript method from where a new redirect can be added
	 *
	 * @param string $url  The URL that can be redirected.
	 * @param string $id   ID of the notice that is displayed.
	 * @param int    $type The redirect type. Default is 301.
	 *
	 * @return string
	 */
	protected function javascript_create_redirect( $url, $id, $type = MCMSSEO_Redirect::PERMANENT ) {
		return sprintf(
			'mcmsseo_create_redirect( "%1$s", "%2$s", "%3$s", this );',
			esc_js( $url ),
			$type,
			mcms_create_nonce( 'mcmsseo-redirects-ajax-security' )
		);
	}

	/**
	 * Return the URL to the admin page where the just added redirect can be found
	 *
	 * @param string $old_url String that filters the mcmsseo_redirect table to the just added redirect.
	 *
	 * @return string
	 */
	protected function admin_redirect_url( $old_url ) {
		return admin_url( 'admin.php?page=mcmsseo_redirects&s=' . urlencode( $old_url ) );
	}

	/**
	 * There might be the possibility to undo the redirect, if it is so, we have to notify the user.
	 *
	 * @param string $old_url The origin URL.
	 * @param string $new_url The target URL.
	 */
	protected function notify_undo_slug_redirect( $old_url, $new_url ) {
		// Check if we should create a redirect.
		if ( $this->should_create_redirect( $old_url, $new_url ) ) {
			$redirect = $this->create_redirect( $old_url, $new_url );

			$this->set_undo_slug_notification( $redirect );
		}
	}

	/**
	 * Display the undo notification
	 *
	 * @param MCMSSEO_Redirect $redirect The old URL to the post.
	 */
	protected function set_undo_slug_notification( MCMSSEO_Redirect $redirect ) {
		$id = 'mcmsseo_undo_redirect_' . md5( $redirect->get_origin() );
		$old_url = $this->create_hyperlink_from_url( home_url() . '/' . $redirect->get_origin() );
		$new_url = $this->create_hyperlink_from_url( home_url() . '/' . $redirect->get_target() );

		// Format the message.
		$message = sprintf(
			$this->get_undo_slug_notification(),
			'Ultimatum SEO',
			'<a target="_blank" href="' . $this->admin_redirect_url( $redirect->get_origin() ) . '">',
			'</a>',
			$old_url,
			$new_url,
			'<button type="button" class="button" onclick=\'' . $this->javascript_undo_redirect( $redirect, $id ). '\'>',
			'</button>'
		);

		// Only set notification when the slug change was not saved through quick edit.
		$this->create_notification( $message, 'slug_change' );
	}

	/**
	 * Returns a list with the actions that the user can do on deleting a post/term
	 *
	 * @param string $url The URL that will be redirected.
	 * @param string $id  The ID of the element.
	 *
	 * @return string.
	 */
	protected function get_delete_action_list( $url, $id ) {
		return sprintf(
			'<ul>%1$s %2$s</ul>',
			'<li><button type="button" class="button" onclick=\'' . $this->javascript_create_redirect( $url, $id, MCMSSEO_Redirect::PERMANENT ) . '\'>' . __( 'Redirect it to another URL', 'mandarincms-seo-premium' ) . '</button></li>',
			'<li><button type="button" class="button" onclick=\'' . $this->javascript_create_redirect( $url, $id, MCMSSEO_Redirect::DELETED ) . '\'>' . __( 'Make it serve a 410 Content Deleted header', 'mandarincms-seo-premium' ) . '</button></li>'
		);
	}

	/**
	 * Returns the passed url in hyperlink form. Both the target and the text of the hyperlink is the passed url.
	 *
	 * @param string $url The url in string form to convert to a hyperlink.
	 *
	 * @return string
	 */
	protected function create_hyperlink_from_url( $url ) {
		return '<a target="_blank" href=' . $url . '>' . $url . '</a>';
	}
}
