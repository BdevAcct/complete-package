/* global tinymce, switchEditors */
/* eslint consistent-this: [ "error", "control" ] */
mcms.textWidgets = ( function( $ ) {
	'use strict';

	var component = {
		dismissedPointers: [],
		idBases: [ 'text' ]
	};

	/**
	 * Text widget control.
	 *
	 * @class TextWidgetControl
	 * @constructor
	 * @abstract
	 */
	component.TextWidgetControl = Backbone.View.extend({

		/**
		 * View events.
		 *
		 * @type {Object}
		 */
		events: {},

		/**
		 * Initialize.
		 *
		 * @param {Object} options - Options.
		 * @param {jQuery} options.el - Control field container element.
		 * @param {jQuery} options.syncContainer - Container element where fields are synced for the server.
		 * @returns {void}
		 */
		initialize: function initialize( options ) {
			var control = this;

			if ( ! options.el ) {
				throw new Error( 'Missing options.el' );
			}
			if ( ! options.syncContainer ) {
				throw new Error( 'Missing options.syncContainer' );
			}

			Backbone.View.prototype.initialize.call( control, options );
			control.syncContainer = options.syncContainer;

			control.$el.addClass( 'text-widget-fields' );
			control.$el.html( mcms.template( 'widget-text-control-fields' ) );

			control.customHtmlWidgetPointer = control.$el.find( '.mcms-pointer.custom-html-widget-pointer' );
			if ( control.customHtmlWidgetPointer.length ) {
				control.customHtmlWidgetPointer.find( '.close' ).on( 'click', function( event ) {
					event.preventDefault();
					control.customHtmlWidgetPointer.hide();
					$( '#' + control.fields.text.attr( 'id' ) + '-html' ).focus();
					control.dismissPointers( [ 'text_widget_custom_html' ] );
				});
				control.customHtmlWidgetPointer.find( '.add-widget' ).on( 'click', function( event ) {
					event.preventDefault();
					control.customHtmlWidgetPointer.hide();
					control.openAvailableWidgetsPanel();
				});
			}

			control.pasteHtmlPointer = control.$el.find( '.mcms-pointer.paste-html-pointer' );
			if ( control.pasteHtmlPointer.length ) {
				control.pasteHtmlPointer.find( '.close' ).on( 'click', function( event ) {
					event.preventDefault();
					control.pasteHtmlPointer.hide();
					control.editor.focus();
					control.dismissPointers( [ 'text_widget_custom_html', 'text_widget_paste_html' ] );
				});
			}

			control.fields = {
				title: control.$el.find( '.title' ),
				text: control.$el.find( '.text' )
			};

			// Sync input fields to hidden sync fields which actually get sent to the server.
			_.each( control.fields, function( fieldInput, fieldName ) {
				fieldInput.on( 'input change', function updateSyncField() {
					var syncInput = control.syncContainer.find( '.sync-input.' + fieldName );
					if ( syncInput.val() !== fieldInput.val() ) {
						syncInput.val( fieldInput.val() );
						syncInput.trigger( 'change' );
					}
				});

				// Note that syncInput cannot be re-used because it will be destroyed with each widget-updated event.
				fieldInput.val( control.syncContainer.find( '.sync-input.' + fieldName ).val() );
			});
		},

		/**
		 * Dismiss pointers for Custom HTML widget.
		 *
		 * @since 4.8.1
		 *
		 * @param {Array} pointers Pointer IDs to dismiss.
		 * @returns {void}
		 */
		dismissPointers: function dismissPointers( pointers ) {
			_.each( pointers, function( pointer ) {
				mcms.ajax.post( 'dismiss-mcms-pointer', {
					pointer: pointer
				});
				component.dismissedPointers.push( pointer );
			});
		},

		/**
		 * Open available widgets panel.
		 *
		 * @since 4.8.1
		 * @returns {void}
		 */
		openAvailableWidgetsPanel: function openAvailableWidgetsPanel() {
			var sidebarControl;
			mcms.customize.section.each( function( section ) {
				if ( section.extended( mcms.customize.Widgets.SidebarSection ) && section.expanded() ) {
					sidebarControl = mcms.customize.control( 'sidebars_widgets[' + section.params.sidebarId + ']' );
				}
			});
			if ( ! sidebarControl ) {
				return;
			}
			setTimeout( function() { // Timeout to prevent click event from causing panel to immediately collapse.
				mcms.customize.Widgets.availableWidgetsPanel.open( sidebarControl );
				mcms.customize.Widgets.availableWidgetsPanel.$search.val( 'HTML' ).trigger( 'keyup' );
			});
		},

		/**
		 * Update input fields from the sync fields.
		 *
		 * This function is called at the widget-updated and widget-synced events.
		 * A field will only be updated if it is not currently focused, to avoid
		 * overwriting content that the user is entering.
		 *
		 * @returns {void}
		 */
		updateFields: function updateFields() {
			var control = this, syncInput;

			if ( ! control.fields.title.is( document.activeElement ) ) {
				syncInput = control.syncContainer.find( '.sync-input.title' );
				control.fields.title.val( syncInput.val() );
			}

			syncInput = control.syncContainer.find( '.sync-input.text' );
			if ( control.fields.text.is( ':visible' ) ) {
				if ( ! control.fields.text.is( document.activeElement ) ) {
					control.fields.text.val( syncInput.val() );
				}
			} else if ( control.editor && ! control.editorFocused && syncInput.val() !== control.fields.text.val() ) {
				control.editor.setContent( mcms.editor.autop( syncInput.val() ) );
			}
		},

		/**
		 * Initialize editor.
		 *
		 * @returns {void}
		 */
		initializeEditor: function initializeEditor() {
			var control = this, changeDebounceDelay = 1000, id, textarea, triggerChangeIfDirty, restoreTextMode = false, needsTextareaChangeTrigger = false, previousValue;
			textarea = control.fields.text;
			id = textarea.attr( 'id' );
			previousValue = textarea.val();

			/**
			 * Trigger change if dirty.
			 *
			 * @returns {void}
			 */
			triggerChangeIfDirty = function() {
				var updateWidgetBuffer = 300; // See mcms.customize.Widgets.WidgetControl._setupUpdateUI() which uses 250ms for updateWidgetDebounced.
				if ( control.editor.isDirty() ) {

					/*
					 * Account for race condition in customizer where user clicks Save & Publish while
					 * focus was just previously given to the editor. Since updates to the editor
					 * are debounced at 1 second and since widget input changes are only synced to
					 * settings after 250ms, the customizer needs to be put into the processing
					 * state during the time between the change event is triggered and updateWidget
					 * logic starts. Note that the debounced update-widget request should be able
					 * to be removed with the removal of the update-widget request entirely once
					 * widgets are able to mutate their own instance props directly in JS without
					 * having to make server round-trips to call the respective MCMS_Widget::update()
					 * callbacks. See <https://core.trac.mandarincms.com/ticket/33507>.
					 */
					if ( mcms.customize && mcms.customize.state ) {
						mcms.customize.state( 'processing' ).set( mcms.customize.state( 'processing' ).get() + 1 );
						_.delay( function() {
							mcms.customize.state( 'processing' ).set( mcms.customize.state( 'processing' ).get() - 1 );
						}, updateWidgetBuffer );
					}

					if ( ! control.editor.isHidden() ) {
						control.editor.save();
					}
				}

				// Trigger change on textarea when it has changed so the widget can enter a dirty state.
				if ( needsTextareaChangeTrigger && previousValue !== textarea.val() ) {
					textarea.trigger( 'change' );
					needsTextareaChangeTrigger = false;
					previousValue = textarea.val();
				}
			};

			// Just-in-time force-update the hidden input fields.
			control.syncContainer.closest( '.widget' ).find( '[name=savewidget]:first' ).on( 'click', function onClickSaveButton() {
				triggerChangeIfDirty();
			});

			/**
			 * Build (or re-build) the visual editor.
			 *
			 * @returns {void}
			 */
			function buildEditor() {
				var editor, onInit, showPointerElement;

				// Abort building if the textarea is gone, likely due to the widget having been deleted entirely.
				if ( ! document.getElementById( id ) ) {
					return;
				}

				// The user has disabled TinyMCE.
				if ( typeof window.tinymce === 'undefined' ) {
					mcms.editor.initialize( id, {
						quicktags: true,
						mediaButtons: true
					});

					return;
				}

				// Destroy any existing editor so that it can be re-initialized after a widget-updated event.
				if ( tinymce.get( id ) ) {
					restoreTextMode = tinymce.get( id ).isHidden();
					mcms.editor.remove( id );
				}

				// Add or enable the `mcmsview` module.
				$( document ).one( 'mcms-before-tinymce-init.text-widget-init', function( event, init ) {
					// If somebody has removed all modules, they must have a good reason.
					// Keep it that way.
					if ( ! init.modules ) {
						return;
					} else if ( ! /\bmcmsview\b/.test( init.modules ) ) {
						init.modules += ',mcmsview';
					}
				} );

				mcms.editor.initialize( id, {
					tinymce: {
						mcmsautop: true
					},
					quicktags: true,
					mediaButtons: true
				});

				/**
				 * Show a pointer, focus on dismiss, and speak the contents for a11y.
				 *
				 * @param {jQuery} pointerElement Pointer element.
				 * @returns {void}
				 */
				showPointerElement = function( pointerElement ) {
					pointerElement.show();
					pointerElement.find( '.close' ).focus();
					mcms.a11y.speak( pointerElement.find( 'h3, p' ).map( function() {
						return $( this ).text();
					} ).get().join( '\n\n' ) );
				};

				editor = window.tinymce.get( id );
				if ( ! editor ) {
					throw new Error( 'Failed to initialize editor' );
				}
				onInit = function() {

					// When a widget is moved in the DOM the dynamically-created TinyMCE iframe will be destroyed and has to be re-built.
					$( editor.getWin() ).on( 'unload', function() {
						_.defer( buildEditor );
					});

					// If a prior mce instance was replaced, and it was in text mode, toggle to text mode.
					if ( restoreTextMode ) {
						switchEditors.go( id, 'html' );
					}

					// Show the pointer.
					$( '#' + id + '-html' ).on( 'click', function() {
						control.pasteHtmlPointer.hide(); // Hide the HTML pasting pointer.

						if ( -1 !== component.dismissedPointers.indexOf( 'text_widget_custom_html' ) ) {
							return;
						}
						showPointerElement( control.customHtmlWidgetPointer );
					});

					// Hide the pointer when switching tabs.
					$( '#' + id + '-tmce' ).on( 'click', function() {
						control.customHtmlWidgetPointer.hide();
					});

					// Show pointer when pasting HTML.
					editor.on( 'pastepreprocess', function( event ) {
						var content = event.content;
						if ( -1 !== component.dismissedPointers.indexOf( 'text_widget_paste_html' ) || ! content || ! /&lt;\w+.*?&gt;/.test( content ) ) {
							return;
						}

						// Show the pointer after a slight delay so the user sees what they pasted.
						_.delay( function() {
							showPointerElement( control.pasteHtmlPointer );
						}, 250 );
					});
				};

				if ( editor.initialized ) {
					onInit();
				} else {
					editor.on( 'init', onInit );
				}

				control.editorFocused = false;

				editor.on( 'focus', function onEditorFocus() {
					control.editorFocused = true;
				});
				editor.on( 'paste', function onEditorPaste() {
					editor.setDirty( true ); // Because pasting doesn't currently set the dirty state.
					triggerChangeIfDirty();
				});
				editor.on( 'NodeChange', function onNodeChange() {
					needsTextareaChangeTrigger = true;
				});
				editor.on( 'NodeChange', _.debounce( triggerChangeIfDirty, changeDebounceDelay ) );
				editor.on( 'blur hide', function onEditorBlur() {
					control.editorFocused = false;
					triggerChangeIfDirty();
				});

				control.editor = editor;
			}

			buildEditor();
		}
	});

	/**
	 * Mapping of widget ID to instances of TextWidgetControl subclasses.
	 *
	 * @type {Object.<string, mcms.textWidgets.TextWidgetControl>}
	 */
	component.widgetControls = {};

	/**
	 * Handle widget being added or initialized for the first time at the widget-added event.
	 *
	 * @param {jQuery.Event} event - Event.
	 * @param {jQuery}       widgetContainer - Widget container element.
	 * @returns {void}
	 */
	component.handleWidgetAdded = function handleWidgetAdded( event, widgetContainer ) {
		var widgetForm, idBase, widgetControl, widgetId, animatedCheckDelay = 50, renderWhenAnimationDone, fieldContainer, syncContainer;
		widgetForm = widgetContainer.find( '> .widget-inside > .form, > .widget-inside > form' ); // Note: '.form' appears in the customizer, whereas 'form' on the widgets admin screen.

		idBase = widgetForm.find( '> .id_base' ).val();
		if ( -1 === component.idBases.indexOf( idBase ) ) {
			return;
		}

		// Prevent initializing already-added widgets.
		widgetId = widgetForm.find( '.widget-id' ).val();
		if ( component.widgetControls[ widgetId ] ) {
			return;
		}

		// Bypass using TinyMCE when widget is in legacy mode.
		if ( ! widgetForm.find( '.visual' ).val() ) {
			return;
		}

		/*
		 * Create a container element for the widget control fields.
		 * This is inserted into the DOM immediately before the .widget-content
		 * element because the contents of this element are essentially "managed"
		 * by PHP, where each widget update cause the entire element to be emptied
		 * and replaced with the rendered output of MCMS_Widget::form() which is
		 * sent back in Ajax request made to save/update the widget instance.
		 * To prevent a "flash of replaced DOM elements and re-initialized JS
		 * components", the JS template is rendered outside of the normal form
		 * container.
		 */
		fieldContainer = $( '<div></div>' );
		syncContainer = widgetContainer.find( '.widget-content:first' );
		syncContainer.before( fieldContainer );

		widgetControl = new component.TextWidgetControl({
			el: fieldContainer,
			syncContainer: syncContainer
		});

		component.widgetControls[ widgetId ] = widgetControl;

		/*
		 * Render the widget once the widget parent's container finishes animating,
		 * as the widget-added event fires with a slideDown of the container.
		 * This ensures that the textarea is visible and an iframe can be embedded
		 * with TinyMCE being able to set contenteditable on it.
		 */
		renderWhenAnimationDone = function() {
			if ( ! widgetContainer.hasClass( 'open' ) ) {
				setTimeout( renderWhenAnimationDone, animatedCheckDelay );
			} else {
				widgetControl.initializeEditor();
			}
		};
		renderWhenAnimationDone();
	};

	/**
	 * Setup widget in accessibility mode.
	 *
	 * @returns {void}
	 */
	component.setupAccessibleMode = function setupAccessibleMode() {
		var widgetForm, idBase, widgetControl, fieldContainer, syncContainer;
		widgetForm = $( '.editwidget > form' );
		if ( 0 === widgetForm.length ) {
			return;
		}

		idBase = widgetForm.find( '> .widget-control-actions > .id_base' ).val();
		if ( -1 === component.idBases.indexOf( idBase ) ) {
			return;
		}

		// Bypass using TinyMCE when widget is in legacy mode.
		if ( ! widgetForm.find( '.visual' ).val() ) {
			return;
		}

		fieldContainer = $( '<div></div>' );
		syncContainer = widgetForm.find( '> .widget-inside' );
		syncContainer.before( fieldContainer );

		widgetControl = new component.TextWidgetControl({
			el: fieldContainer,
			syncContainer: syncContainer
		});

		widgetControl.initializeEditor();
	};

	/**
	 * Sync widget instance data sanitized from server back onto widget model.
	 *
	 * This gets called via the 'widget-updated' event when saving a widget from
	 * the widgets admin screen and also via the 'widget-synced' event when making
	 * a change to a widget in the customizer.
	 *
	 * @param {jQuery.Event} event - Event.
	 * @param {jQuery}       widgetContainer - Widget container element.
	 * @returns {void}
	 */
	component.handleWidgetUpdated = function handleWidgetUpdated( event, widgetContainer ) {
		var widgetForm, widgetId, widgetControl, idBase;
		widgetForm = widgetContainer.find( '> .widget-inside > .form, > .widget-inside > form' );

		idBase = widgetForm.find( '> .id_base' ).val();
		if ( -1 === component.idBases.indexOf( idBase ) ) {
			return;
		}

		widgetId = widgetForm.find( '> .widget-id' ).val();
		widgetControl = component.widgetControls[ widgetId ];
		if ( ! widgetControl ) {
			return;
		}

		widgetControl.updateFields();
	};

	/**
	 * Initialize functionality.
	 *
	 * This function exists to prevent the JS file from having to boot itself.
	 * When MandarinCMS enqueues this script, it should have an inline script
	 * attached which calls mcms.textWidgets.init().
	 *
	 * @returns {void}
	 */
	component.init = function init() {
		var $document = $( document );
		$document.on( 'widget-added', component.handleWidgetAdded );
		$document.on( 'widget-synced widget-updated', component.handleWidgetUpdated );

		/*
		 * Manually trigger widget-added events for media widgets on the admin
		 * screen once they are expanded. The widget-added event is not triggered
		 * for each pre-existing widget on the widgets admin screen like it is
		 * on the customizer. Likewise, the customizer only triggers widget-added
		 * when the widget is expanded to just-in-time construct the widget form
		 * when it is actually going to be displayed. So the following implements
		 * the same for the widgets admin screen, to invoke the widget-added
		 * handler when a pre-existing media widget is expanded.
		 */
		$( function initializeExistingWidgetContainers() {
			var widgetContainers;
			if ( 'widgets' !== window.pagenow ) {
				return;
			}
			widgetContainers = $( '.widgets-holder-wrap:not(#available-widgets)' ).find( 'div.widget' );
			widgetContainers.one( 'click.toggle-widget-expanded', function toggleWidgetExpanded() {
				var widgetContainer = $( this );
				component.handleWidgetAdded( new jQuery.Event( 'widget-added' ), widgetContainer );
			});

			// Accessibility mode.
			$( window ).on( 'load', function() {
				component.setupAccessibleMode();
			});
		});
	};

	return component;
})( jQuery );
