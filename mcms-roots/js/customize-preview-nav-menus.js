/* global _mcmsCustomizePreviewNavMenusExports */

/** @namespace mcms.customize.navMenusPreview */
mcms.customize.navMenusPreview = mcms.customize.MenusCustomizerPreview = ( function( $, _, mcms, api ) {
	'use strict';

	var self = {
		data: {
			navMenuInstanceArgs: {}
		}
	};
	if ( 'undefined' !== typeof _mcmsCustomizePreviewNavMenusExports ) {
		_.extend( self.data, _mcmsCustomizePreviewNavMenusExports );
	}

	/**
	 * Initialize nav menus preview.
	 */
	self.init = function() {
		var self = this, synced = false;

		/*
		 * Keep track of whether we synced to determine whether or not bindSettingListener
		 * should also initially fire the listener. This initial firing needs to wait until
		 * after all of the settings have been synced from the pane in order to prevent
		 * an infinite selective fallback-refresh. Note that this sync handler will be
		 * added after the sync handler in customize-preview.js, so it will be triggered
		 * after all of the settings are added.
		 */
		api.preview.bind( 'sync', function() {
			synced = true;
		} );

		if ( api.selectiveRefresh ) {
			// Listen for changes to settings related to nav menus.
			api.each( function( setting ) {
				self.bindSettingListener( setting );
			} );
			api.bind( 'add', function( setting ) {

				/*
				 * Handle case where an invalid nav menu item (one for which its associated object has been deleted)
				 * is synced from the controls into the preview. Since invalid nav menu items are filtered out from
				 * being exported to the frontend by the _is_valid_nav_menu_item filter in mcms_get_nav_menu_items(),
				 * the customizer controls will have a nav_menu_item setting where the preview will have none, and
				 * this can trigger an infinite fallback refresh when the nav menu item lacks any valid items.
				 */
				if ( setting.get() && ! setting.get()._invalid ) {
					self.bindSettingListener( setting, { fire: synced } );
				}
			} );
			api.bind( 'remove', function( setting ) {
				self.unbindSettingListener( setting );
			} );

			/*
			 * Ensure that mcms_nav_menu() instances nested inside of other partials
			 * will be recognized as being present on the page.
			 */
			api.selectiveRefresh.bind( 'render-partials-response', function( response ) {
				if ( response.nav_menu_instance_args ) {
					_.extend( self.data.navMenuInstanceArgs, response.nav_menu_instance_args );
				}
			} );
		}

		api.preview.bind( 'active', function() {
			self.highlightControls();
		} );
	};

	if ( api.selectiveRefresh ) {

		/**
		 * Partial representing an invocation of mcms_nav_menu().
		 *
		 * @memberOf mcms.customize.navMenusPreview
		 * @alias mcms.customize.navMenusPreview.NavMenuInstancePartial
		 *
		 * @class
		 * @augments mcms.customize.selectiveRefresh.Partial
		 * @since 4.5.0
		 */
		self.NavMenuInstancePartial = api.selectiveRefresh.Partial.extend(/** @lends mcms.customize.navMenusPreview.NavMenuInstancePartial.prototype */{

			/**
			 * Constructor.
			 *
			 * @since 4.5.0
			 * @param {string} id - Partial ID.
			 * @param {Object} options
			 * @param {Object} options.params
			 * @param {Object} options.params.navMenuArgs
			 * @param {string} options.params.navMenuArgs.args_hmac
			 * @param {string} [options.params.navMenuArgs.myskin_location]
			 * @param {number} [options.params.navMenuArgs.menu]
			 * @param {object} [options.constructingContainerContext]
			 */
			initialize: function( id, options ) {
				var partial = this, matches, argsHmac;
				matches = id.match( /^nav_menu_instance\[([0-9a-f]{32})]$/ );
				if ( ! matches ) {
					throw new Error( 'Illegal id for nav_menu_instance partial. The key corresponds with the args HMAC.' );
				}
				argsHmac = matches[1];

				options = options || {};
				options.params = _.extend(
					{
						selector: '[data-customize-partial-id="' + id + '"]',
						navMenuArgs: options.constructingContainerContext || {},
						containerInclusive: true
					},
					options.params || {}
				);
				api.selectiveRefresh.Partial.prototype.initialize.call( partial, id, options );

				if ( ! _.isObject( partial.params.navMenuArgs ) ) {
					throw new Error( 'Missing navMenuArgs' );
				}
				if ( partial.params.navMenuArgs.args_hmac !== argsHmac ) {
					throw new Error( 'args_hmac mismatch with id' );
				}
			},

			/**
			 * Return whether the setting is related to this partial.
			 *
			 * @since 4.5.0
			 * @param {mcms.customize.Value|string} setting  - Object or ID.
			 * @param {number|object|false|null}  newValue - New value, or null if the setting was just removed.
			 * @param {number|object|false|null}  oldValue - Old value, or null if the setting was just added.
			 * @returns {boolean}
			 */
			isRelatedSetting: function( setting, newValue, oldValue ) {
				var partial = this, navMenuLocationSetting, navMenuId, isNavMenuItemSetting, _newValue, _oldValue, urlParser;
				if ( _.isString( setting ) ) {
					setting = api( setting );
				}

				/*
				 * Prevent nav_menu_item changes only containing type_label differences triggering a refresh.
				 * These settings in the preview do not include type_label property, and so if one of these
				 * nav_menu_item settings is dirty, after a refresh the nav menu instance would do a selective
				 * refresh immediately because the setting from the pane would have the type_label whereas
				 * the setting in the preview would not, thus triggering a change event. The following
				 * condition short-circuits this unnecessary selective refresh and also prevents an infinite
				 * loop in the case where a nav_menu_instance partial had done a fallback refresh.
				 * @todo Nav menu item settings should not include a type_label property to begin with.
				 */
				isNavMenuItemSetting = /^nav_menu_item\[/.test( setting.id );
				if ( isNavMenuItemSetting && _.isObject( newValue ) && _.isObject( oldValue ) ) {
					_newValue = _.clone( newValue );
					_oldValue = _.clone( oldValue );
					delete _newValue.type_label;
					delete _oldValue.type_label;

					// Normalize URL scheme when parent frame is HTTPS to prevent selective refresh upon initial page load.
					if ( 'https' === api.preview.scheme.get() ) {
						urlParser = document.createElement( 'a' );
						urlParser.href = _newValue.url;
						urlParser.protocol = 'https:';
						_newValue.url = urlParser.href;
						urlParser.href = _oldValue.url;
						urlParser.protocol = 'https:';
						_oldValue.url = urlParser.href;
					}

					// Prevent original_title differences from causing refreshes if title is present.
					if ( newValue.title ) {
						delete _oldValue.original_title;
						delete _newValue.original_title;
					}

					if ( _.isEqual( _oldValue, _newValue ) ) {
						return false;
					}
				}

				if ( partial.params.navMenuArgs.myskin_location ) {
					if ( 'nav_menu_locations[' + partial.params.navMenuArgs.myskin_location + ']' === setting.id ) {
						return true;
					}
					navMenuLocationSetting = api( 'nav_menu_locations[' + partial.params.navMenuArgs.myskin_location + ']' );
				}

				navMenuId = partial.params.navMenuArgs.menu;
				if ( ! navMenuId && navMenuLocationSetting ) {
					navMenuId = navMenuLocationSetting();
				}

				if ( ! navMenuId ) {
					return false;
				}
				return (
					( 'nav_menu[' + navMenuId + ']' === setting.id ) ||
					( isNavMenuItemSetting && (
						( newValue && newValue.nav_menu_term_id === navMenuId ) ||
						( oldValue && oldValue.nav_menu_term_id === navMenuId )
					) )
				);
			},

			/**
			 * Make sure that partial fallback behavior is invoked if there is no associated menu.
			 *
			 * @since 4.5.0
			 *
			 * @returns {Promise}
			 */
			refresh: function() {
				var partial = this, menuId, deferred = $.Deferred();

				// Make sure the fallback behavior is invoked when the partial is no longer associated with a menu.
				if ( _.isNumber( partial.params.navMenuArgs.menu ) ) {
					menuId = partial.params.navMenuArgs.menu;
				} else if ( partial.params.navMenuArgs.myskin_location && api.has( 'nav_menu_locations[' + partial.params.navMenuArgs.myskin_location + ']' ) ) {
					menuId = api( 'nav_menu_locations[' + partial.params.navMenuArgs.myskin_location + ']' ).get();
				}
				if ( ! menuId ) {
					partial.fallback();
					deferred.reject();
					return deferred.promise();
				}

				return api.selectiveRefresh.Partial.prototype.refresh.call( partial );
			},

			/**
			 * Render content.
			 *
			 * @inheritdoc
			 * @param {mcms.customize.selectiveRefresh.Placement} placement
			 */
			renderContent: function( placement ) {
				var partial = this, previousContainer = placement.container;

				// Do fallback behavior to refresh preview if menu is now empty.
				if ( '' === placement.addedContent ) {
					placement.partial.fallback();
				}

				if ( api.selectiveRefresh.Partial.prototype.renderContent.call( partial, placement ) ) {

					// Trigger deprecated event.
					$( document ).trigger( 'customize-preview-menu-refreshed', [ {
						instanceNumber: null, // @deprecated
						mcmsNavArgs: placement.context, // @deprecated
						mcmsNavMenuArgs: placement.context,
						oldContainer: previousContainer,
						newContainer: placement.container
					} ] );
				}
			}
		});

		api.selectiveRefresh.partialConstructor.nav_menu_instance = self.NavMenuInstancePartial;

		/**
		 * Request full refresh if there are nav menu instances that lack partials which also match the supplied args.
		 *
		 * @param {object} navMenuInstanceArgs
		 */
		self.handleUnplacedNavMenuInstances = function( navMenuInstanceArgs ) {
			var unplacedNavMenuInstances;
			unplacedNavMenuInstances = _.filter( _.values( self.data.navMenuInstanceArgs ), function( args ) {
				return ! api.selectiveRefresh.partial.has( 'nav_menu_instance[' + args.args_hmac + ']' );
			} );
			if ( _.findWhere( unplacedNavMenuInstances, navMenuInstanceArgs ) ) {
				api.selectiveRefresh.requestFullRefresh();
				return true;
			}
			return false;
		};

		/**
		 * Add change listener for a nav_menu[], nav_menu_item[], or nav_menu_locations[] setting.
		 *
		 * @since 4.5.0
		 *
		 * @param {mcms.customize.Value} setting
		 * @param {object}             [options]
		 * @param {boolean}            options.fire Whether to invoke the callback after binding.
		 *                                          This is used when a dynamic setting is added.
		 * @return {boolean} Whether the setting was bound.
		 */
		self.bindSettingListener = function( setting, options ) {
			var matches;
			options = options || {};

			matches = setting.id.match( /^nav_menu\[(-?\d+)]$/ );
			if ( matches ) {
				setting._navMenuId = parseInt( matches[1], 10 );
				setting.bind( this.onChangeNavMenuSetting );
				if ( options.fire ) {
					this.onChangeNavMenuSetting.call( setting, setting(), false );
				}
				return true;
			}

			matches = setting.id.match( /^nav_menu_item\[(-?\d+)]$/ );
			if ( matches ) {
				setting._navMenuItemId = parseInt( matches[1], 10 );
				setting.bind( this.onChangeNavMenuItemSetting );
				if ( options.fire ) {
					this.onChangeNavMenuItemSetting.call( setting, setting(), false );
				}
				return true;
			}

			matches = setting.id.match( /^nav_menu_locations\[(.+?)]/ );
			if ( matches ) {
				setting._navMenuMySkinLocation = matches[1];
				setting.bind( this.onChangeNavMenuLocationsSetting );
				if ( options.fire ) {
					this.onChangeNavMenuLocationsSetting.call( setting, setting(), false );
				}
				return true;
			}

			return false;
		};

		/**
		 * Remove change listeners for nav_menu[], nav_menu_item[], or nav_menu_locations[] setting.
		 *
		 * @since 4.5.0
		 *
		 * @param {mcms.customize.Value} setting
		 */
		self.unbindSettingListener = function( setting ) {
			setting.unbind( this.onChangeNavMenuSetting );
			setting.unbind( this.onChangeNavMenuItemSetting );
			setting.unbind( this.onChangeNavMenuLocationsSetting );
		};

		/**
		 * Handle change for nav_menu[] setting for nav menu instances lacking partials.
		 *
		 * @since 4.5.0
		 *
		 * @this {mcms.customize.Value}
		 */
		self.onChangeNavMenuSetting = function() {
			var setting = this;

			self.handleUnplacedNavMenuInstances( {
				menu: setting._navMenuId
			} );

			// Ensure all nav menu instances with a myskin_location assigned to this menu are handled.
			api.each( function( otherSetting ) {
				if ( ! otherSetting._navMenuMySkinLocation ) {
					return;
				}
				if ( setting._navMenuId === otherSetting() ) {
					self.handleUnplacedNavMenuInstances( {
						myskin_location: otherSetting._navMenuMySkinLocation
					} );
				}
			} );
		};

		/**
		 * Handle change for nav_menu_item[] setting for nav menu instances lacking partials.
		 *
		 * @since 4.5.0
		 *
		 * @param {object} newItem New value for nav_menu_item[] setting.
		 * @param {object} oldItem Old value for nav_menu_item[] setting.
		 * @this {mcms.customize.Value}
		 */
		self.onChangeNavMenuItemSetting = function( newItem, oldItem ) {
			var item = newItem || oldItem, navMenuSetting;
			navMenuSetting = api( 'nav_menu[' + String( item.nav_menu_term_id ) + ']' );
			if ( navMenuSetting ) {
				self.onChangeNavMenuSetting.call( navMenuSetting );
			}
		};

		/**
		 * Handle change for nav_menu_locations[] setting for nav menu instances lacking partials.
		 *
		 * @since 4.5.0
		 *
		 * @this {mcms.customize.Value}
		 */
		self.onChangeNavMenuLocationsSetting = function() {
			var setting = this, hasNavMenuInstance;
			self.handleUnplacedNavMenuInstances( {
				myskin_location: setting._navMenuMySkinLocation
			} );

			// If there are no mcms_nav_menu() instances that refer to the myskin location, do full refresh.
			hasNavMenuInstance = !! _.findWhere( _.values( self.data.navMenuInstanceArgs ), {
				myskin_location: setting._navMenuMySkinLocation
			} );
			if ( ! hasNavMenuInstance ) {
				api.selectiveRefresh.requestFullRefresh();
			}
		};
	}

	/**
	 * Connect nav menu items with their corresponding controls in the pane.
	 *
	 * Setup shift-click on nav menu items which are more granular than the nav menu partial itself.
	 * Also this applies even if a nav menu is not partial-refreshable.
	 *
	 * @since 4.5.0
	 */
	self.highlightControls = function() {
		var selector = '.menu-item';

		// Skip adding highlights if not in the customizer preview iframe.
		if ( ! api.settings.channel ) {
			return;
		}

		// Focus on the menu item control when shift+clicking the menu item.
		$( document ).on( 'click', selector, function( e ) {
			var navMenuItemParts;
			if ( ! e.shiftKey ) {
				return;
			}

			navMenuItemParts = $( this ).attr( 'class' ).match( /(?:^|\s)menu-item-(-?\d+)(?:\s|$)/ );
			if ( navMenuItemParts ) {
				e.preventDefault();
				e.stopPropagation(); // Make sure a sub-nav menu item will get focused instead of parent items.
				api.preview.send( 'focus-nav-menu-item-control', parseInt( navMenuItemParts[1], 10 ) );
			}
		});
	};

	api.bind( 'preview-ready', function() {
		self.init();
	} );

	return self;

}( jQuery, _, mcms, mcms.customize ) );
