<?php
/**
 * Comment Moderation Administration Screen.
 *
 * Redirects to edit-comments.php?comment_status=moderated.
 *
 * @package MandarinCMS
 * @subpackage Administration
 */
require_once( dirname( dirname( __FILE__ ) ) . '/bootstrap.php' );
mcms_redirect( admin_url('edit-comments.php?comment_status=moderated') );
exit;
