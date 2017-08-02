(function($, document, window) {
	var media;

	GF = {
		// Save a reference for the window object
		window: $( window ),

		// Strings that might get overwritten for non-english sites
		Strings: {
			sure: "Are you sure?",
			saveError: "There was an error while trying to save your data. Please try again.",
			emptyInput: "Please enter search text first!",
			noGoogleResults: "Google Maps was unable to find the requested address!",
			selectImage: "Select Image",
			selectMedia: "Select Media",
			saveAndUse: "Save & Use",
			deleteSidebarConfirmation: "This sidebar might be in use somewhere in the site. If you delete it, a default sidebar will be displayed where needed. Are you sure you want to delete this sidebar?",
			noSidebarName: "Please enter a title for the new sidebar!",
			duplicateSidebar: "A there is a sidebar with that name already!"
		},

		// Trangform every word to uppercase
		ucwords: function( string ) {
			return (string + '').replace(/^([a-z])|[\s_]+([a-z])/g, function ($1) {
				return $1.toUpperCase();
			})
		},

		// Check if the argument is set
		isset: function(item) {
			return typeof(item) != 'undefined';
		},

		// Get the escaped format of a value
		escape: function( text ) {
			var escaped = $( '<div />' ).text( text ).html();
			return escaped;
		},

		// Main field class
		Field: function( $item ) {
			/**
			 * Call the construct method.
			 * The construct method should be called in each sub-class
			 * After the construct method is done, initialize() will be called
			 */
			this.construct( $item );
		},

		// Main container class
		Container: function( $container ) {
			/**
			 * Call the construct method. Similar to GF.Field above
			 */
			this.constructContainer( $container );
		},

		// Intialize options panels
		initOptions: function() {
			// Init options pages
			$( '.gf-options' ).each(function() {
				new GF.ContainerOptions( $( this ) )
			});
		},

		// Initialize postmeta containers
		initPostmeta: function() {
			var id;

			if( typeof( GF_Postmeta ) != 'undefined' ) {
				for( id in GF_Postmeta ) {
					$( '.postbox#' + id ).each(function() {
						new GF.ContainerPostMeta( id, GF_Postmeta[ id ] );
					});
				}
			}

			GF.ContainerPostMeta.listen();
		},

		// Initialize widgets
		initWidgets: function() {
			// Start with added widgets
			$( '#widgets-right .gf-widget' ).each(function() {
				new GF.ContainerWidget( $( this ) );
			});

			// Start listening for new widgets
			GF.ContainerWidget.listen();
		},

		// Initialize terms meta
		initTermsMeta: function() {
			$( '.gf-form-table' ).each(function() {
				new GF.ContainerTermsMeta( $( this ) );
			});
		},

		// Initiator - detect containers and start
		init: function() {
			// Add dependencies if available
			if( typeof( GF_Dependencies ) != 'undefined' ) {
				GF.addDependencies( GF_Dependencies );
			}

			GF.initOptions();
			GF.initPostmeta();
			GF.initWidgets();
			GF.initTermsMeta();
		},

		// Hold the height of the admin bar. Always visible, no need to check
		adminBarHeight: 28,

		/* Dependencies holder. Format:
		{
			containerId: {
				 fieldId: {
				 	relation: 'AND|OR',
				 	targets: {
				 		fieldId: {
				 			value: '',
				 			compare: '=|==|>=|<=|<|>|!=|NOT_NULL|NULL|IN|NOT_IN'
				 		},
				 		fieldId: {...}
				 	}
			 	},
			 	fieldId: { ... },
			 	fieldId__inner: { // For repeaters
			 		same-as-container
			 	}
			},
			containerId: { ... }
		}
		*/
		dependencies: {},

		// Since this script should be in the header, use this within the HTML
		addDependencies: function( deps ) {
			$.extend( this.dependencies, deps );
		}
	};

	// Init GF on document ready
	$( document ).ready( GF.init );

	/**
	 * When needed, creates a new media uploader popup
	 */
	GF.Media = media = {
		// The callback that will be called when a file is selected
		callback: function( attachment ) {},

		/**
		 * Default settings for the popup
		 */
		defaults: {
			// The type of needed files. Can be all/image/video/audio.
			type: 'all',

			// The ID of the selected file. Can be empty
			selected: null,

			// The title of the whole popup
			title: GF.Strings.selectMedia,

			// The text of the button.
			buttonText: GF.Strings.saveAndUse,

			// Enables multiple items
			multiple: false
		},

		/**
		 * Similar to the defaults, holds the current options
		 */
		options: {},

		/**
		 * The active frame.
		 *
		 * A frame will be created upon each file request and after selecting/closing
		 * that frame will be destroyed. This way, multiple uploads can request different
		 * settings and not get messed up.
		 */
		_frame: null,

		/**
		 * Opens a window with particular settings.
		 *
		 * The options object should either be empty or follow the defaults from above
		 */
		requestFile: function( options, callback ) {
			var args, frame;

			media.options = options = $.extend( media.defaults, options ? options : {} );
			media.callback = callback;

			// Prepare the args for the dialog
			args = {
				title: options.title,
				button: {
					text: options.buttonText
				},
				multiple: options.multiple
			};

			if( options.type != 'all' ) {
				args.library = {
					type: options.type
				}
			}

			// Creat the frame
			frame = media.frame = wp.media( args );

			// Modify it once open
			frame.on( 'ready', media.modifyPopup );

			// Add a local callback
			frame.state( 'library' ).on( 'select', media.onSelect );

			// Pre-select file if needed
			if( options.selected ) {
				frame.on( 'open', media.selectFile );
			}

			// Open the frame
			frame.open();
		},

		/**
		 * This is triggered once the popup is being closed and has files to send.
		 *
		 * It will either sent the first selection to onSelect or call it once
		 * for each file that has been selected in the popup.
		 */
		onSelect: function() {
			if( media.options.multiple ) {
				// Call onSelect once for each image
				this.get( 'selection' ).map( media.attachment );
			} else {
				// Only use the first item
				media.attachment( this.get( 'selection' ).first() );
			}

			// Destroy the frame
			delete media.frame;
		},

		// Handles selecting of items
		attachment: function( attachment ) {
			// Actually call the callback
			media.callback( attachment.attributes );
		},

		// Modifies the already rendered popup
		modifyPopup: function() {
			// Only hide the sidebar
			$( '.media-modal' ).addClass( 'no-sidebar' );
		},

		// Selects an existing file by ID
		selectFile: function() {
			var selection = media.frame.state().get( 'selection' );			
			attachment = wp.media.attachment( media.options.selected );
			attachment.fetch();
			selection.add( attachment ? [ attachment ] : [] );
		}
	}

	/**
	* Adding static stuff to the base field class
	*/
	_.extend( GF.Field, {
		// An array that will contain most fields
		fields: [],

		// Add field proxy, might be used for dependencies, etc.
		addField: function( field ) {
			this.fields.push( field );
		},

		// Factory-like method
		initField: function( node ) {
			var $node = $( node ), theClass, words, field;

			// Get class words
			words = node.className.replace( /^.*gf-field-([^ ]+).*$/i, '$1' ).split( '-' );
			
			// Join uppercase words to form the class
			theClass = '';
			for( i in words ) {
				theClass += GF.ucwords( words[i] );
			}

			// Check if there's a specific class for this field
			if( typeof( GF.Field[ theClass ] ) != 'function' ) {
				console.log('Please implement ' + theClass);
				field = new GF.Field( $node );
			} else {
				// Create the field
				field = new GF.Field[ theClass ]( $node );
			}

			// Push it to the globally available fields
			this.addField( field );

			return field;
		},

		// Add a specific field class
		extend: function( type, proto, staticProps ) {
			var base = this, i, type;

			// There could be multiple types for a single field
			types = type.split(',');

			for( i in types ) {
				type = types [ i ];

				// Create the class
				GF.Field[ type ] = function( $item ) {
					this.construct( $item );
				}

				// Inherit base
				_.extend( GF.Field[ type ].prototype, base.prototype );

				// Add new prototype methods and properties
				_.extend( GF.Field[ type ].prototype, proto );

				// Add static methods and properties
				if( GF.isset( staticProps ) ) {
					_.extend( GF.Field[ type ], staticProps );
				} 

				// Add the ability for the field to be inherited
				GF.Field[ type ].extend = function( subType, subProto, subStatic ) {
					base.extend.call( GF.Field[ type ], subType, subProto, subStatic );
				}
			}
		}
	});

	/**
	 * Add common methods and properties to the base field class
	 */
	_.extend( GF.Field.prototype, {
		// Main constructor, called for each field
		construct: function( $item ) {
			var field = this;

			// Everybody is innocent before proving guilty :)
			this.valid = true;

			// Hold a representation of the input's value
			this.value = null;

			// External event subscribers
			this.subscribers = {};

			// Double-check to make sure the item's a jQuery object
			if( ! GF.isset( $item.size ) || ! $item.size() ) {
				return;
			}

			// Hold the ID of the field in it's container
			this.id = $item.data( 'id' );

			// The row property is linked to the .gf-field element
			this.row = $( $item );

			// The input holds either the <input /> or the corresponding div(s)
			// One row might contain multiple inputs, so make sure to work with .each()
			if( this.row.is( '.multilingual' ) ) {
				this.input = this.row.find( '.lang-input' ).children();
			} else {
				this.input = this.row.find( '.field-wrap:eq(0)' ).children();
			}

			// Get the data of the field
			this.data = this.row.data();

			// Add the 'this' handle to the DOM element for easy access
			this.row.data( 'gf', this );

			// Check if field is required and fetch the RegEx
			this.initRequired();

			// Show/hide multilinual fields and make controls work
			this.initMultilingual();

			// Add help icon helper
			this.helpIcon();

			// Listen to value changes
			this.bindChange();

			// Collect initial values
			this.triggerChange();

			// Call sub-class initialize method
			if( this.initialize ) {
				this.initialize();
			}
		},

		// Binds events for input changes. Could be inherited later, along with triggerChange
		bindChange: function() {
			var field = this;

			this.row.on( 'change', 'input,select,textarea', function() {
				var $el = $(this);
				field.setValueFromInput( $el.val(), $el );
			} );
		},

		// Trigger change to the input
		triggerChange: function() {
			this.row.find( 'input,select,textarea' ).trigger( 'change' );
		},

		// Set value, called from bindChange
		setValue: function( value, language ) {
			if( typeof( language ) != 'undefined' ) {
				// Store a multilingual value
				this.value[ language ] = value;
			} else {
				// Store a single value for language
				this.value = value;
			}

			// Trigger an event
			this.trigger( 'valueChanged', this.value );
		},

		// Set a value by detecting the input language
		setValueFromInput: function( value, $node ) {
			var language, $input;

			if( this.multilingual ) {
				// for multilingual fields, detect the language first
				$input = $node.is( '.lang-input' ) ? $node : $node.closest( '.lang-input' );
				language = $input.attr( 'class' ).replace(/^.*lang-input-(\w\w).*$/i, '$1');
				this.setValue( value, language );
			} else {
				// the field isn't multilingual, simply store the value
				this.setValue( value );
			}
		},

		// Collect required field data
		initRequired: function() {
			var modifier, expression;

			if( GF.isset( this.data.regex ) ) {
				this.isRequired = true;

				// Extract the regular expression and convert it to a JS one
				modifier   = this.data.regex.replace( /^.*\/(\w*)$/, '$1' );
				expression = this.data.regex.replace( /^\/\^?([^\$]*)\$*\/\w*$/, '^$1$' );
				this.validationRule = new RegExp( expression, modifier );
			} else {
				this.isRequired = false;
			}
		},

		// Bring the multilingual controls to life
		initMultilingual: function() {
			var field = this, $wrap, $buttons, $inputs;

			// Don't do anything on non-multilingual fields
			if( ! this.row.is( '.multilingual' ) ) {
				this.multilingual = false;
				return;
			}

			// Since the field is multilingual, we'll be storing multiple values
			this.value = {};

			// Set as multilingual
			this.multilingual = true;
			$wrap             = this.row.find( '.gf-lang-wrap:eq(0)' );
			$buttons          = $wrap.children( '.gf-lang-switch' );
			$inputs           = $wrap.children( '.lang-input' );

			// Button events
			$buttons.on( 'click', 'a', function( e ) {
				var $this = $( this ),
					lang  = $this.data( 'language' );

				e.preventDefault();

				$this
					.addClass( 'active' )
					.parent()
					.siblings()
					.children()
					.removeClass( 'active' );

				$inputs
					.hide()
					.filter( '.lang-input-' + lang )
					.show();

				// Trigger a window resize so things go in place
				$( window ).resize();

				// Save the language in case it's needed
				field.activeLanguage = lang;
			});

			// Trigger initial change
			$buttons.find( 'a' ).eq( 0 ).click();
		},

		// Add actions to the help icon
		helpIcon: function() {
			// Since we're using the default browser tooltip,
			// just prevent the page from jumping
			this.row.on( 'click', '.label .help', function() {
				return false;
			} );
		},

		// Checks if the field's value is valid for required fields
		// false - no errors
		// true - error text
		check: function() {
			var valid;

			if( ! this.isRequired ) {
				return false;
			}

			if( valid = this.checkValue() ) {
				this.row.removeClass( 'invalid' );
			} else {
				this.row.addClass( 'invalid' );
			}

			return valid !== true;
		},

		// Check the field's value - this might differ in successors
		checkValue: function() {
			var valid = true;

			if( this.multilingual ) {
				for( i in this.value ) {
					if( ! this.validationRule.test( this.value[ i ] ) ) {
						valid = false;
					}
				}
			} else {
				valid = this.validationRule.test( this.value );
			}

			return valid; 
		},

		// Add an event listener
		bind: function( eventName, callback, context ) {
			// Check if there's an array of subscribers for this event, create if missing
			if( !GF.isset( this.subscribers[ eventName ]) ) {
				this.subscribers[ eventName ] = [];
			}

			// Add the listener
			this.subscribers[ eventName ].push({
				callback: callback,
				context: context
			});
		},

		// Add the ability to remove a callback or all callbacks if using with a single parameter
		unbind: function( eventName, callback ) {
			var i;

			// Nothing to do here without subscribers
			if( !GF.isset( this.subscribers[ eventName ]) ) {
				return;
			}

			if( GF.isset( callback ) ) {
				// Delete the specific event
				clear = [];
				for( i in this.subscribers[ eventName ] ) {
					if( this.subscribers[ eventName ][ i ].callback != callback ) {
						clear.add( this.subscribers[ eventName ][ i ] );
					}
				}
				this.subscribers[ eventName ] = clear;
			} else {
				// Delete all events
				delete this.subscribers[ eventName ]
			}
		},

		// Trigger an event
		trigger: function( eventName, data ) {
			var subscriber, i;

			if( !GF.isset( this.subscribers[ eventName ] ) ) {
				return;
			}

			for( i in this.subscribers[ eventName ] ) {
				subscriber = this.subscribers[ eventName ][ i ];

				if( GF.isset( subscriber.context) ) {
					subscriber.callback.call( context );
				} else {
					subscriber.callback( data );
				}
			}
		}
	});

	/**
	 * Add common methods and properties to the base container class
	 */
	_.extend( GF.Container.prototype, {
		// Main constructor, called for each panel
		constructContainer: function( $container ) {
			// The ID of the container, used primarily for dependencies
			this.id = null;

			// The element property will contain the main DOM element
			this.element = null;

			// All fields' DOM elements
			this.$fields = null;

			// All fields' GF.Field objects
			this.fields = {};

			// Hold all field's value for dependencies
			this.values = {};

			// Hold the ID of the visible tab
			this.activeTab = null;

			// Strore the element
			this.element = $container;

			// Get the ID of the container
			this.id = $container.attr( 'id' );

			// Save an instance of this class
			this.element.data( 'gf', this );

			// Find the hidden error message div
			this.errorMessage = this.element.find( '.error-msg' );

			// Find the succesgfull saved div and hide it after a few seconds
			this.successMessage = this.element.find( '.updated' );
			this.successMessage.each(function() {
				var msg = this;
				setTimeout( function(){
					$( msg ).fadeOut();
				}, 3000 );
			});

			// Attach fields
			this.initFields();

			// Bind validation for the container
			this.bindValidation();

			// Add tabs functionality
			this.initTabs();

			// Jump to a child class constructor if one is defined
			if( typeof( this.initializeContainer ) == 'function' ) {
				this.initializeContainer();
			}
		},

		// Get the fields' DOM elements
		getFields: function() {
			// Get all fields except separator
			return this.$fields = this.element.find( '.gf-field:not(.gf-field .gf-field,.gf-separator)' );
		},

		// Add a field to the fields[] array. If needed,
		// additional actions might be performed
		addField: function( field ) {
			this.fields[ field.id ] = field;
		},

		// Find container fields and create them
		initFields: function() {
			var container = this, deps, i, $fields;

			// Make sure it's known which are the fields
			$fields = this.getFields();

			// For each field, call GF.Field.initField which will route
			// to the right class for the particular field
			$fields.each(function() {
				// Initialize the new field
				var field = GF.Field.initField( this );

				// Push the new field to the container
				container.addField( field );
			});

			_.each( this.fields, function( field ) {
				// Bind a change event to the field
				field.bind( 'valueChanged', function( value ) {
					container.valueChanged( field, value )
				});

				// Collect the field value initially
				container.values[ field.id ] = field.value;
			});

			// Collect dependencies
			this.dependencies = this.getDependencies();

			// After initialization of all fields, which includes collecting their values, check everything
			this.dependencies = GF.dependencies[ this.id ];

			// Turn off jQuery effects during this step
			$.fx.off = true;

			// Check each field
			for( i in this.dependencies ) {
				if( ! (/__inner$/i).test( i ) ) {
					// Check the field's dependencies
					this.checkDependency( this.fields[ i ], this.dependencies[i] );
				}
			}

			// Send the dependencies to fields
			_.each( this.fields, function( field ) {
				if( GF.isset( container.dependencies ) ) {
					if( GF.isset( field.setInnerDependencies ) && GF.isset( container.dependencies[ field.id + '__inner' ] ) ) {
						field.setInnerDependencies( container.dependencies[ field.id + '__inner' ] );
					}
				}
			});

			// Restore jQuery animations
			$.fx.off = false;
		},

		// Get the dependencies for the particular container
		getDependencies: function() {
			var deps = GF.isset( GF.dependencies[ this.id ]) ? GF.dependencies[ this.id ] : {};
			return this.dependencies = deps;
		},

		// Bind the validation of the container to an event.
		bindValidation: function() {
			// This function should be overwritten in typical containers.
			// This does not apply to the repater though.
		},

		// Check if there are errors in the fields
		validate: function() {
			this.status = {
				valid: true,
				errors: []
			};

			// Check each fields's status
			_.each( this.fields, this.validateField , this );

			return this.status.valid;
		},

		// Validate a single field
		validateField: function( field ) {
			var error = field.check();

			// If there's an error, add it to the container's status
			if( error ) {
				// Scroll to the beginning of the page to make the user see the message
				if( this.status.valid ) {
					this.errorMessage.fadeIn();

					// Hide success message if there is one
					this.errorMessage.siblings( '#message' ).remove();

					$( 'html,body' ).stop(true).animate({
						scrollTop: 0
					});
				}

				this.status.valid = false;
				this.status.errors.push( error );
			}
		},

		// Initialize tabs in the container
		initTabs: function() {
			var container = this;

			// Get links and tabs
			this.tabs   = this.element.find( '.tabs' );
			this.tabNav = this.tabs.find( '.tabs-nav a' );
			this.tabCnt = this.tabs.find( '.tab' );

			if( ! this.tabs.size() ) {
				// Nothing to do here
				return;
			}

			// Bind link click
			this.tabNav.on( 'click', function(e) {
				e.preventDefault();

				container.showTab( $( this ).attr( 'href' ).replace('#', '') );
			});

			// For top tabs, enable fixed positioning
			if( this.tabs.is( '.top-tabs' ) && GF.isset( this.initFixedTabs ) ) {
				this.initFixedTabs();
			}

			// Show the first tab
			if( hash = location.hash.replace(/^#\//, '') ) {
				if( this.tabCnt.filter( '#' + hash ).size() ) {
					// Show the tab from the hash
					this.showTab( hash, false );
				}
			} else {
				// Show the first tab
				this.showTab( this.tabCnt[0].id, false );
			}
		},

		// Switch to a certain tab
		showTab: function( id, animate ) {
			if( id == this.activeTab ) {
				// Nothing to do here too
				return;
			}

			// Check if there are any animations to be done upon change
			if( !GF.isset( animate ) ) {
				animate = true;
			}

			// Add active/inactive classes
			this.tabNav.removeClass( 'nav-tab-active' ).filter( '[href=#' + id + ']' ).addClass( 'nav-tab-active' );
			this.tabCnt.addClass( 'inactive-tab' ).filter( '#' + id ).removeClass( 'inactive-tab' );

			// Save the tab ID
			this.activeTab = id;

			// Add the ID of the tab to the hash
			location.hash = '/' + id;

			// Scroll the window to the tab
			if( animate )
				$( 'html,body' ).animate({
					scrollTop: this.tabs.offset().top - GF.adminBarHeight
				});
		},

		// This is triggered when a field changes it's value
		valueChanged: function( field, value ) {
			var deps, i;

			// Save the value internally
			this.values[ field.id ] = value;
			
			// For sub-classes do something based on this value
			if( typeof( this.afterValueChanged ) == 'function' ) {
				this.afterValueChanged( field, value );
			}

			// Check each field
			for( i in this.dependencies ) {
				// Don't bother with fields that don't depend on the changed one or have inner dependencies
				if( ! (/__inner$/i).test( i ) && GF.isset( this.dependencies[ i ].targets[ field.id ] ) ) {
					// Check the field's dependencies
					this.checkDependency( this.fields[ i ], this.dependencies[i] );
				}
			}
		},

		// Check if all field dependencies are matched
		checkDependency: function( field, dep ) {
			var visible = dep.relationship == 'AND', valid, i;
			for( i in dep.targets ) {
				valid = this.checkValue( this.values[ i ], dep.targets[ i ].compare, dep.targets[ i ].value );

				if( dep.relationship == 'AND' && !valid ) {
					visible = false;
				}

				if( dep.relationship == 'OR' && valid ) {
					visible = true;
				}
			}
			
			field.row[ visible ? 'show' : 'hide' ]();
		},

		// Compare certain value agains specific rules
		checkValue: function( checkedValue, rule, goodValue ) {
			var valid, currentValue;

			if( typeof( checkedValue ) != 'object' ) {
				checkedValue = [ checkedValue ]
			}

			for( i in checkedValue ) {
				currentValue = checkedValue[ i ];

				switch( rule ) {
					case '>=':
						if( typeof( goodValue ) == 'number' ) {
							valid = currentValue >= parseFloat( goodValue );
						} else {
							valid = currentValue.length >= parseInt( goodValue );
						}
						break;
					case '<=':
						if( typeof( goodValue ) == 'number' ) {
							valid = currentValue <= parseFloat( goodValue );
						} else {
							valid = currentValue.length <= parseInt( goodValue );
						}
						break;
					case '<':
						if( typeof( goodValue ) == 'number' ) {
							valid = currentValue < parseFloat( goodValue );
						} else {
							valid = currentValue.length < parseInt( goodValue );
						}
						break;
					case '>':
						if( typeof( goodValue ) == 'number' ) {
							valid = currentValue > parseFloat( goodValue );
						} else {
							valid = currentValue.length > parseInt( goodValue );
						}
						break;
					case '!=':
						valid = currentValue != goodValue;
						break;
					case 'NOT_NULL':
						valid = currentValue ? true : false;
						break;
					case 'NULL':
						valid = !currentValue;
						break;
					case 'IN':
						if( currentValue.indexOf( ',' ) != -1 ) {
							var i, parts = currentValue.split( ',' );
							valid = false;
							for( i in parts ) {
								if( goodValue.indexOf( parts[i] ) != -1 )
									valid = true;
							}
						} else {
							valid = goodValue.indexOf( currentValue ) != -1;							
						}
						break;
					case 'NOT_IN':
						if( currentValue.indexOf( ',' ) != -1 ) {
							var i, parts = currentValue.split( ',' );
							valid = false;
							for( i in parts ) {
								if( goodValue.indexOf( parts[i] ) != -1 )
									valid = true;
							}
						} else {
							valid = goodValue.indexOf( currentValue ) == -1;							
						}
						break;
					default:
					case '=':
					case '==':
						valid = currentValue == goodValue;
						break;
				}

				if( !valid ) {
					return false;
				}
			}

			return true;
		}
	});

	/**
	 * Options page class
	 */
	GF.ContainerOptions = function( $container ) {
		this.constructContainer( $container );
	}

	// Inherit the Container class
	_.extend( GF.ContainerOptions.prototype, GF.Container.prototype );

	// Add additional methods and properties to the options page container
	_.extend( GF.ContainerOptions.prototype, {
		// Bind validation on form submit
		bindValidation: function() {
			var container = this;

			true && this.element.on( 'submit', 'form', function( e ) {
				var valid = container.validate();

				e.preventDefault();

				if( ! valid ) {
					return false;
				}

				// Submit the form with AJAX if there are no errors
				container.submit( this );
			});
		},

		// Pass data to the server through AJAX
		submit: function( form ) {
			var container = this, $f, $btn, $loader, $header;

			this.form = $f = $( form );
			$btn      = $f.find( 'input[type=submit].button-primary' ),
			$loader   = $f.find( '.ajax-loader' ),
			$header   = $f.siblings( '.head' );

			// Display the loading icon and disable the buttons
			$loader.addClass('loading');
			$btn.attr('disabled', 'disabled');

			// Force fields to collect their values to make sure everything is saved
			// TO BE REMOVED
			_.each( this.fields, function( field ){
				field.triggerChange();
			});

			// Do the ajax itself
			$.ajax({
				type: 'post',
				url: $f.attr( 'href' ),
				data: $f.serialize(),

				// Handle succesgfull save
				success: function( data ) {
					var $message = $( "#message", data );

					// Enable submitting
					$loader.removeClass( 'loading');
					$btn.attr( 'disabled', false );

					// Hide/remove old messages and display the new one
					$header.find( '.error-msg' ).hide();
					$header.find( '#message' ).remove();
					$header.find( 'h2' ).after( $message );
					$message.fadeIn();

					// Scroll to the top to make the message visible
					setTimeout(function() {
						$('html,body').animate({
							scrollTop: 0
						});
					}, 100);
				},

				// Handle server-side errors
				error: function( jqXHR, textStatus, errorThrown ) {
					$loader.removeClass( 'loading' );
					$btn.attr( 'disabled', false );

					alert( GF.Strings.saveError );
				}
			});
		},

		// Add automatic positioning of the tabs bar
		initFixedTabs: function() {
			var container = this;

			// Find elements
			this.tabsWrap  = this.tabs.find( '.tabs-nav-wrap' );
			this.tabsInner = this.tabs.find( '.tabs-nav-inner' );

			// Adjust height of the tabs
			GF.window.on( 'resize', function() {
				container.setTabsWrapHeight();
				container.setTabsPosition();
			});

			this.setTabsWrapHeight();

			// On window scroll, set position
			GF.window.on( 'scroll', function() {
				container.setTabsPosition();
			}).on( 'resize', function() {
				container.setTabsPosition();
			});
		},

		// Set a height to the tabs wrapper
		setTabsWrapHeight: function() {
			this.tabsWrap.css({
				height: this.tabsInner.outerHeight()
			});
		},

		// Position the tabs nav for horizontal tabs
		setTabsPosition: function() {
			var s = GF.window.scrollTop() + GF.adminBarHeight;

			if( s > this.tabsWrap.offset().top ) {
				this.tabsInner.addClass( 'fixed' ).css({
					top: GF.adminBarHeight,
					left: this.tabsWrap.offset().left,
					right: GF.window.width() - this.tabsWrap.offset().left - this.tabsWrap.width(),
					position: 'fixed'
				});
			} else {
				this.tabsInner.removeClass( 'fixed' ).css({
					top: 0,
					left: 0,
					right: 0,
					position: 'absolute'
				});
			}
		}
	});

	/**
	 * Post Meta Container Class
	 */
	GF.ContainerPostMeta = function( id, data ) {
		var $container;

		// Save the data
		this.panelData = data;

		// Get the container
		$container = $( '#' + id );

		// Save the data and ID
		this.constructContainer( $container );
	}

	// Add a static listener
	_.extend( GF.ContainerPostMeta, {
		listen: function() {
			$( document ).ajaxSend( function( event, jqXHR, ajaxOptions ) {
				if( ajaxOptions.type == "POST" && ajaxOptions.data.indexOf('action=meta-box-order') != -1 ) {
					$( '.gf-field' ).trigger( 'gf-sorted' );
				}
			}) ;
		}
	} );

	// Inherit the container class
	_.extend( GF.ContainerPostMeta.prototype, GF.Container.prototype );

	// Additional methods
	_.extend( GF.ContainerPostMeta.prototype, {
		// After-constructor method
		initializeContainer: function() {
			// Get all elements of the page (selects, toggles, etc)
			this.getPageElements();

			// Bind changes to elements
			this.bindElementEvents();

			// Trigger an initial validation
			this.validatePanel();

			// Change the validation message container
			this.errorMessage = $( '#gf-postmeta-error' );
		},

		// Prepares all elements that the container might depend on
		getPageElements: function() {
			var i;

			// General elements
			this.$elements = {
				toggle: $( 'input.hide-postbox-tog[value="' + this.id + '"]' ).parent(),
				level: $( '#parent_id' ),
				template: $( '#page_template' )
			}

			// Hierarchical taxonomy checkboxes
			if( GF.isset( this.panelData.terms ) ) {
				this.$elements.terms = {};

				for( i in this.panelData.terms ) {
					this.$elements.terms[ i ] = $( '#' + i + 'checklist input[type=checkbox]' );
				}
			}
		},

		// Bind events to elements
		bindElementEvents: function() {
			var field = this, i;

			this.$elements.toggle.change(function() { field.validatePanel() });
			this.$elements.level.change(function() { field.validatePanel() });
			this.$elements.template.change(function() { field.validatePanel() });

			for( i in this.$elements.terms ) {
				this.$elements.terms[ i ].change( function() { field.validatePanel() } );
			}
		},

		// Checks conditions for the panel and "validates" it
		validatePanel: function() {
			var valid = this.checkTemplates() && this.checkLevels() && this.checkTerms();

			this.element.stop( true, true )[ valid ? 'show' : 'hide' ]();
			this.$elements.toggle[ valid ? 'show' : 'hide' ]();
		},

		// Check templates
		checkTemplates: function() {
			var count = 0, i, template;

			// If there's no dropdown for templates, this has no point
			if( ! this.$elements.template.size() ) {
				return true;
			}

			// Get the current template
			template = this.$elements.template.val();

			// Check hidden templates
			for( i in this.panelData.templates_hidden ) {
				count++;

				// If the chosen template is hidden, straightly quit
				if( template == i ) {
					return false;
				}
			}

			// Check for visible templates
			for( i in this.panelData.templates ) {
				count++;

				// If the template is the right one, it's okay
				if( template == i ) {
					return true;
				}
			}

			return count == 0;
		},

		// Check levels
		checkLevels: function() {
			var level = 1,
				count = 0,
				i;

			// No different levels, nothing to do here
			if( ! this.$elements.level.size() ) {
				return true;
			}

			// Get the current level
			this.$elements.level.children( ':selected' ).each(function() {
				var c = this.className;

				if( typeof(c) != 'undefined' ) {
					level = parseInt( c.replace(/^level\-(\d+)$/i, '$1') ) + 2;
				}

				if( isNaN( level ) ) {
					level = 1;
				}
			});

			// Check visible levels
			for( i in this.panelData.levels ) {
				count++;

				// All good
				if( i == level ) {
					return true;
				}
			}

			// Check hidden levels
			for( i in this.panelData.levels_hidden ) {
				count++;

				// Hide the panel from the level
				if( i == level ) {
					return false;
				}
			}

			return ! count;
		},

		// Checks terms
		checkTerms: function() {
			var valid = true, taxonomy, hasItems, i, items, hidden;

			// Check each taxonomy
			for( taxonomy in this.panelData.terms ) {
				// Shortcuts for acceptable and hidden terms
				items  = this.panelData.terms[ taxonomy ];
				hidden = this.panelData.terms_hidden[ taxonomy ];

				// No categories, no point to choose
				if( !this.$elements.terms[ taxonomy ].size() ) {
					continue;
				}

				// Check if there are items for this taxonomy
				hasItems = false;
				for( i in items ) {
					hasItems = true;
					break;
				}

				if( hasItems ) {
					has_checked = false;

					for( i in items ) {
						if( this.$elements.terms[ taxonomy ].filter( '[value=' + i + ']' ).is( ':checked') ) {
							has_checked = true;
						}
					}

					// Nothing checked
					if(!has_checked) {
						valid = false;
					}
				}

				// If there are terms where this should be hidden
				for( i in hidden ) {
					if( this.$elements.terms[ taxonomy ].filter( '[value=' + i + ']' ).is( ':checked') ) {
						valid = false;
					}
				}
			}			

			return valid;
		},

		// Bind validation on form submit
		bindValidation: function() {
			var container = this;

			this.element.closest( 'form' ).on( 'submit', function( e ) {
				var valid = container.validate();

				if( ! valid ) {
					$( '.spinner' ).hide();
					$( '.button-primary-disabled' ).removeClass( 'button-primary-disabled' ).attr( 'disabled', false );
					return false;
				}
			});
		}
	});

	// Simple text input
	GF.Field.extend( 'Text', {
		// Initial constructor
		initialize: function() {			
			// Hold autocomplete suggestions
			this.suggestions = [];

			// Try intializing autocomplete
			this.initAutocomplete();
		},

		// Binds events for input changes, which happen on key up
		bindChange: function() {
			var field = this;

			this.row.on( 'keyup', 'input', function() {
				var $el = $(this);
				field.setValueFromInput( $el.val(), $el );
			} );
		},

		// Trigger change to the input
		triggerChange: function() {
			this.row.find( 'input' ).trigger( 'keyup' );
		},

		// Collect autocomplete suggestions
		prepareSuggestions: function() {
			var $source = this.row.find('.gf-autocompletes');

			if( $source.size() ) {
				this.suggestions = $.parseJSON( $source.html() );
			}
		},

		// Initialize autocomplete, might be overwritteh to replace jQuery UI
		autocomplete: function() {
			this.input.autocomplete({
				source: this.suggestions
			});
		},

		// Initialize the autocomplete functionality
		initAutocomplete: function() {	
			this.prepareSuggestions();
			// If there are suggestions, initialize jQUery UI Autocomplete
			if( this.suggestions.length ) {
				this.autocomplete();	
			}
		}
	});

	// Textarea, Header and Footer scripts fields
	GF.Field.extend( 'Textarea,HeaderScripts,FooterScripts', {
		// Binds events for input changes, which happen on key up
		bindChange: function() {
			var field = this;

			this.row.on( 'keyup', 'textarea', function() {
				var $el = $(this);
				field.setValueFromInput( $el.val(), $el );
			} );
		},

		// Trigger change to the input
		triggerChange: function() {
			this.row.find( 'textarea' ).trigger( 'keyup' );
		}
	});

	// Number input
	GF.Field.extend( 'Number', {
		// Custom constructor
		initialize: function() {
			// Try initializing a slider if needed
			this.initSlider();
		},

		// Initialize a jQuery UI slider if the right attribute is set
		initSlider: function() {
			var field = this, min, max, step;

			// No slider needed
			if( !GF.isset( this.data.slider ) ) {
				return;
			}

			// Prepare numbers
			this.data.min  = parseFloat( this.data.min );
			this.data.max  = parseFloat( this.data.max );
			this.data.step = parseFloat( this.data.step );

			// Create a slider for each input
			this.input.filter( 'input' ).each(function() {
				var $input = $( this );
				field.slider( $input );
			});			
		},

		// Create a single slider
		slider: function( $input ) {
			var field      = this,
				$slider    = $( '<div class="gf-slider" />' ),
				$indicator = $( '<div class="gf-slider-indicator" />' );

			// Add the slider and the indicator after the input, then hide it
			$input
				.after( $slider )
				.after( $indicator )
				.hide();

			// Set the initial value
			$indicator.text( $input.val() ? $input.val() : this.data.min );

			// Create the slider
			$slider.slider({
				min: this.data.min,
				max: this.data.max,
				step: this.data.step,
				value: $input.val(),
				range: 'min',
				slide: function( event, ui ) {
					$input.val( ui.value );
					$indicator.text( ui.value );
					field.setValueFromInput( ui.value, $input );
				}
			});
		},

		// Binds events for input changes, which happen on key up or change
		bindChange: function() {
			var field = this;

			this.row.on( 'keyup, change', 'input', function() {
				var $el = $(this);
				field.setValueFromInput( $el.val(), $el );
			} );
		},

		// Trigger change to the input
		triggerChange: function() {
			this.row.find( 'input' ).trigger( 'change' );
		}
	});

	// Select field
	GF.Field.extend( 'Select,SelectPage,SelectTerm', {
		initialize: function() {
			var field = this;

			if( GF.isset( this.data.chosen ) ) {
				this.input.filter( 'select' ).each(function() {
					field.custom( $(this) );
				});
			}
		},

		// Initialise a custom select
		custom: function( $input ) {
			$input.select2();
		}
	});

	// Radio group
	GF.Field.extend( 'Radio', {
		// Binds events for input changes, which happen on key up
		bindChange: function() {
			var field = this;

			this.row.on( 'change', 'input', function() {
				var $el = $(this);

				// Only save the value of the checked input
				if( $el.is( ':checked' ) ) {
					field.setValueFromInput( $el.val(), $el );
				}
			} );
		},

		// Trigger change to the input
		triggerChange: function() {
			this.row.find( 'input:checked' ).trigger( 'change' );
		}
	});

	// Richtext field
	GF.Field.extend( 'Richtext', {
		initialize: function() {
			var field = this;

			// This will hold all editors
			this.editors = [];

			// Collect all editors
			this.input.each(function(){
				var $t = $(this);

				// Collect each editor's data
				field.editors.push({
					// The HTML container of the editor
					$container: $t,

					// The code that's the backbone when initializing the editor
					originalCode: $t.parent().html(),

					// ID of the new editor
					id: ('GFFieldRichtext' + (GF.Field.Richtext.i++)).toLowerCase(),

					// The ID placeholder that will need to get replaced with the new ID
					mceId: $t.data('mce-id')
				});
			});

			// Initialize all editors
			this.initEditors();
		},

		// Initialize all editors
		initEditors: function() {
			for( i in this.editors ) {
				// Add the porper ids to the code and trigger tinyMCE
				this.initEditor( this.editors[i] );
			}
		},

		// Initialize a single editor
		initEditor: function( editor ) {
			var field = this, $parent;

			// Save the parent
			$parent = editor.$container.parent();

			// Get the code for the editor and add the proper ID
			$parent.html( editor.originalCode.replace(new RegExp( editor.mceId, 'gi' ), editor.id ) );

			// Restore the container
			editor.$container = $parent.children();

			// Initialize the editor
			this.initMce( editor.id );

			// Set active editor on click over parent
			editor.$container.on('click', function(){
				window.wpActiveEditor = editor.id;
				tinyMCE.execInstanceCommand(editor.id, "mceFocus");
			});
			
			// Bind regeneration
			this.row.on( 'gf-sorted', function() {
				field.regenerate( editor );
			});

			// Bind saving - what's in the editor is not always in the textarea
			this.row.on( 'gf-before-save', function() {
				field.getValue( editor );
				wpActiveEditor = null;
			});
		},

		// Upon DOM movement, regenerates the editor
		regenerate: function( editor ) {
			var $parent, value;

			// Get the current value
			value = this.getValue( editor );

			// Get the parent
			$parent = $( editor.$container.parent() );

			// Restore the backbone
			$parent.html( editor.originalCode.replace(new RegExp( editor.mceId, 'gi' ), editor.id ) );

			// Restore jQuery objects
			editor.$container = $parent.children();

			// Restore the value
			editor.$container.find('textarea').val( value );

			// Setup the editor
			this.initMce( editor.id );
			wpActiveEditor = null
		},

		// Initialize an editor
		initMce: function( id ) {
			var oldId, i;

			// Get an existing ID
			// This uses the last available editor's config, but we preffer gf_dummy_editor_id 
			// for( i in tinyMCEPreInit.mceInit ) oldId = i; }
			oldId = 'gf_dummy_editor_id';

			// Setup the Richtext editor
			var mceInit = $.extend({}, tinyMCEPreInit.mceInit[oldId], { body_class: id, elements: id, rows: $('#' + id).attr('rows') });
			tinyMCEPreInit.mceInit[id] = $.extend({}, mceInit);
			tinymce.init( tinyMCEPreInit.mceInit[id] );

			// Setup quicktags
			var qtInit = $.extend({}, tinyMCEPreInit.qtInit[oldId], { id: id });
			tinyMCEPreInit.qtInit[id] = $.extend({}, qtInit);
			quicktags(tinyMCEPreInit.qtInit[id]);

			// Init QuickTags
			QTags._buttonsInit();
		},

		// Get the value of an editor
		getValue: function( editor ) {
			var value;

			if( GF.isset( tinyMCE.get( editor.id ) ) ) {
				value = $( tinyMCE.get( editor.id ).dom.doc ).find( 'body' ).html();
				editor.$container.find( 'textarea' ).val( tinyMCE.get( editor.id ).getContent() );
				return value;
			} else {
				return '';
			}
		}
	}, {
		// Since each editor needs a different ID, use this and increment it
		i: 0
	});

	// Image field
	GF.Field.extend( 'ImageSelect', {
		// Binds events for input changes.
		bindChange: function() {
			var field = this;

			this.row.on( 'change', 'input[type=radio]', function() {
				var $el = $(this);

				// Change the object value
				field.setValueFromInput( $el.val(), $el );

				// Toggle active classes
				field.setActive();
			} );
		},

		// Trigger change to the input
		triggerChange: function() {
			this.row.find( 'input:checked' ).trigger( 'change' );
		},

		// Add active classes to selected elements
		setActive: function() {
			this.input.find( 'label' ).removeClass( 'gf-selected' ).find( ':checked' ).parent().addClass( 'gf-selected' );
		}
	});

	// Google Font Select field
	GF.Field.extend( 'GoogleFont', {
		// After-constructor function
		initialize: function() {
			var field = this;

			// Do it for each inputs group
			this.input.each(function() {
				var $inputs = $( this ).children(),
					$select = $inputs.filter( 'select' ),
					$button = $inputs.filter( 'a' ),
					$iframe = $inputs.filter( 'iframe' );

				// Save the iframe source
				field.source = ajaxurl + $button.attr( 'href' );

				console.log( field.source );
				// On click on the button, load the new font preview
				$button.on( 'click', function( e ) {
					e.preventDefault();
					field.loadFont( $select.val(), $iframe );
				});
			});
		},

		// Generate the source of an iframe
		getFontSource: function( font ) {
			return this.source + encodeURI( font );
		},

		// Loads a font in a specified iframe
		loadFont: function( font, $iframe ) {
			var field = this;

			// Save the iframe
			this.$activeFrame = $iframe;

			// Save the active inputs group
			GF.Field.GoogleFont.activeField = this;

			// Hide the current iframe
			$iframe.removeClass( 'has-content' );

			// Show the loader
			field.input.find( '.loader' ).animate({ opacity: 1 });
			
			// Set the iframe source and start loading
			//console.log( this.getFontSource( font ));
			$iframe.removeClass( 'has-content' ).attr( 'src', this.getFontSource( font ) );
		},

		// Sets the height of the active iFrame
		changeFrameHeight: function( height ) {
			// Hide the loader
			this.input.find( '.loader' ).animate({ opacity: 0 });

			// Display the frame
			this.$activeFrame.css( 'height', height ).addClass( 'has-content' );
		}
	}, {
		// Hold the currently clicked button
		activeInput: null,

		// Set the height of the iframe through an external function
		changeFrameHeight: function( height ) {
			GF.Field.GoogleFont.activeField.changeFrameHeight( height );
		}
	});

	// Color field
	GF.Field.extend( 'Color', {
		// Custom after-constructor
		initialize: function() {
			var field = this;

			// Create the structure and init the picker for each input
			this.input.each(function() {
				field.createStructure( $( this ) );
			});
		},

		// Initialize a particular colorpicker. Might be overwritten a plugin different than Iris
		initPicker: function( $input, $div, callback ) {
			if( ! $input.is( ':visible' ) || $input.find( '.iris-picker' ).size() ) {
				// Someone has already been here or the input isn't visible
				return;
			}

			$input.iris({
				color: $input.val(),
				hide: false,
				// border: false,
				target: $div,
				width: $div.width(),
				change: function( event, ui ) {
					// This might be used for different colorpickers, so it's inside
					callback( ui.color.toString() );
				}
			});
		},

		// Add appropriate divs and wraps
		createStructure: function( $input ) {
			var field   = this,
				$picker = $( '<div class="gf-color-pick" />' ),
				$wrap   = $( '<div class="gf-color-wrap" />' ),
				hoverTimeout;

			// Add hover animations
			$wrap.hover(function() {
				clearTimeout( hoverTimeout );

				hoverTimeout = setTimeout( function() {
					$picker.css({
						height: 0,
						overflow: 'hidden',
						display: 'block'
					});

					field.initPicker( $input, $picker, function( color ) {
						field.changeColor( $input, color )
					} );

					$picker.css({
						display: 'none',
						height: 'auto'
					});

					// Initialize the colorpicker again, as this works only when it's visible
					$picker.stop( true, true ).slideDown();
				}, 200 );
			}, function() {
				clearTimeout( hoverTimeout )
				$picker.stop( true, true ).slideUp();
			});

			// Wrap the input and add the picker after it
			$input.wrap( $wrap ).after( $picker );

			// Initialize the colorpicker
			this.initPicker( $input, $picker, function( color ) {
				field.changeColor( $input, color )
			} );

			// Hide the picker initially
			$picker.hide();

			// Change the initial color
			this.changeColor( $input, $input.val() );

			// Filter input values
			var lastValue = $input.val();

			$input.keyup( function( e ) {
				var value = $( this ).val();

				if( ! value.match( /^#[0-9a-f]{0,6}$/ ) ) {
					$input.val( lastValue );
				} else {
					lastValue = value;
				}
			} );
		},

		// Set the color as a background, add it to the input and save it to the field
		changeColor: function( $input, color ) {
			// Set the background
			$input.parent().css( 'background', color );

			// Set the value to the input
			$input.val( color );

			// Save the value
			this.setValueFromInput( color, $input );
		}
	});

	// Datepicker field
	GF.Field.extend( 'Date', {
		// Sub-constructor
		initialize: function() {
			var field = this;

			this.input.filter( '.gf-datepicker' ).each(function() {
				field.initInput( $( this ) );
			});
		},

		// Prepare datepicker options. Easy to overwrite
		getOptions: function( $input ) {
			return {
				altField: $input,
				altFormat: this.data.format,
				defaultDate: $.datepicker.parseDate( this.data.format, $input.val() ),
				changeYear: true,
				changeMonth: true
			};
		},

		// Get i18n data
		getLanguage: function() {
			// Detect language
			var currentLocale = $( 'html' ).attr( 'lang' ),
				parts = currentLocale.split( '-' ),
				language = $.datepicker.regional[ parts[ 0 ] ];

			if( !GF.isset( language ) ) {
				language = $.datepicker.regional[ currentLocale ];
			}

			if( !GF.isset( language ) ) {
				language = $.datepicker.regional['en-GB'];
			}

			return language;
		},

		// Initialize a single input
		initInput: function( $input ) {
			var $holder  = $("<div />"),
				$wrap    = $('<div class="gf-datepicker-wrap" />'),
				language = this.getLanguage(),
				options  = this.getOptions( $input );

			// Check for existing one
			if( $input.is( '.datepicker-added' ) ) {
				return;
			}

			$input.addClass( 'datepicker-added' );

			// Add the DIV element
			$input.before( $holder );

			// Initiate the datepicker
			$holder.datepicker( $.extend( {}, language, options ) );

			// Hide the input
			$input.hide();
		}
	});

	// Time field
	GF.Field.Date.extend( 'Time', {
		// Initialize a single input
		initInput: function( $input ) {
			var $holder  = $("<div />"),
				$wrap    = $('<div class="gf-datepicker-wrap" />'),
				language = this.getLanguage(),
				options  = this.getOptions( $input );

			// Check for existing one
			if( $input.is( '.datepicker-added' ) ) {
				return;
			}

			$input.addClass( 'datepicker-added' );

			// Add the DIV element
			$input.before( $holder );

			// Initiate the datepicker
			$holder.timepicker( $.extend( {}, language, options ) );

			// Hide the input
			$input.hide();
		},

		// Used to convert a string into a date object
		parseTime: function( format, time ) {
			var d = new Date(), obj;

			// If the time is null, set all to 0
			if( ( /[1-9]/ ).test( time ) ) {
				obj = $.datepicker.parseTime( format, time );
				d.setHours( obj.hour, obj.minute, obj.second, obj.millisec );
			} else {
				d.setHours( 0, 0, 0, 0 );
			}

			return d;
		},

		// Prepare timepicker options. Easy to overwrite
		getOptions: function( $input ) {
			var time, options, val;

			// Default options
			options = {
				altField: $input,
				altTimeFormat: this.data.format,
				showButtonPanel: false
			};

			// Try parsing time
			val = $input.val();
			if( ( /[1-9]/ ).test( val ) ) {
				obj = $.datepicker.parseTime( this.data.format, val );
				_.extend( options, obj );
			}

			return options;
		}
	});

	// Set field
	GF.Field.extend( 'Set', {
		// Custom after-constructor
		initialize: function() {
			var field = this;

			// Check and initialize sortable
			if( GF.isset( this.data.sortable ) && this.data.sortable ) {
				this.input.each(function() {
					field.initSortable( $( this ) );
				});
			}
		},

		// Initialize sortable set
		initSortable: function( $fieldset ) {
			var field = this,
				separator = this.data.separator,
				$order = $fieldset.find( 'input[type=hidden]' );

			$fieldset.sortable({
				update: function( event, ui ) {
					var sort = [];

					// Don't cache this selection - it has to be done with the 
					// current DOM order
					$fieldset.find( "input[type='checkbox']" ).each(function() {
						sort.push( $( this ).val() );
					});

					// Implode the sort string
					sort = sort.join( separator );

					// Set the order to the right field
					$order.val( sort );
				}
			});

			$fieldset.disableSelection();
		},

		// Bind custom change events
		bindChange: function() {
			var field = this;

			// Bind events separately for each set
			this.input.each(function() {
				var $set = $(this), value;

				$set.on( 'change', 'input:checkbox', function() {
					value = [];

					$set.find( 'input:checked' ).each(function() {
						value.push( this.value );
					});

					field.setValueFromInput( value.join( ',' ), $set );
				});
			});
		},

		// Trigger change to the input
		triggerChange: function() {
			this.row.find( 'input:checkbox' ).trigger( 'change' );
		}
	});

	GF.Field.Set.extend( 'Tags', {
		// Custom after-container
		initialize: function() {
			var field = this;

			// Initialize each input
			this.input.each(function() {
				field.initTags( $( this ) );
			});
		},

		// Initialize tags
		initTags: function( $el ) {
			var field = this, $input;

			// $input = $el.find( '.input' ).magicSuggest({
			// 	// allowFreeEntries: false,
			// 	data: $.parseJSON( $el.find( 'script' ).html() ),
			// 	value: $.parseJSON( $el.find( '.input' ).attr( 'values' ) )
			// });

			$input = $el.select2({

			});

			$( $input ).on( 'focus', function() {
				field.row.addClass( 'focused' );
			} )
			$( $input ).on( 'blur', function() {
				field.row.removeClass( 'focused' );
			} )
		}
	} );

	// Checkbox field
	GF.Field.extend( 'Checkbox', {
		// Bind custom change events
		bindChange: function() {
			var field = this;

			// Bind events separately for each set
			this.row.on( 'change', 'input:checkbox', function() {
				field.setValueFromInput( this.checked, $( this ) );
			});
		},

		// Trigger change to the input
		triggerChange: function() {
			this.row.find( 'input:checkbox' ).trigger( 'change' );
		}
	});

	// Map field
	GF.Field.extend( 'Map', {
		initialize: function() {
			var field = this;

			// Initialize each input separately
			this.input.filter('.gf-map-wrap' ).each(function() {
				field.initMap( $( this ) );
			});
		},

		// Initiate a map
		initMap: function( $wrap ) {
			var field   = this,
				$holder = $wrap.find( '.gf-map' ),
				$input  = $wrap.find( 'input[type=hidden]' ),
				$search = $wrap.find( '.gf-map-search' ),
				$go     = $wrap.find( '.gf-map-submit' ),
				coords  = $input.val().split( ',' ),
				center, mapOptions, map, markerOptions, marker;

			// Add default coordinates
			if( coords.length < 3 ) {
				coords = [ 37.0625, -95.677068, 4 ];
			}

			// If the Google Maps API is not available, display the right message
			if( typeof( google ) == 'undefined' || typeof( google.maps ) == 'undefined' || typeof( google.maps.Map ) == 'undefined' ) {
				$wrap.hide().siblings( '.no-maps' ).show();
				return;
			}

			// Prepare the map center and marker location
			center = new google.maps.LatLng(coords[0], coords[1]);

			// Get map options and create the map. Rewrite getMapOptions if something should be done differently
			map = new google.maps.Map( $holder[0], field.getMapOptions( center, parseInt( coords[2] ) ) );

			// Get marker options and create the marker
			marker = new google.maps.Marker( field.getMarkerOptions( center, map ));

			// Add events to the map
			field.addListeners( map, {
				// On double click, change the marker's position
				dblclick: function( e ) {
					// Move the marker
					marker.setPosition( e.latLng );

					// Save the values
					coords[0] = e.latLng.lat();
					coords[1] = e.latLng.lng();
					field.saveCoordinates( coords, $input );

					return false;		
				},

				// On zoom change save the soom
				zoom_changed: function( e ) {
					coords[2] = map.getZoom();
					field.saveCoordinates( coords, $input );
				}
			});

			// On "Search" click, look for the coordinates
			$go.bind('click', function( e ) {
				e.preventDefault();
				field.search( $search.val(), field, map, marker, $input, coords );
			});

			// When ented is pressed in the search form, do the search
			$search.bind( 'keydown', function( e ) {
				if( e.which == 13 ) {
					e.preventDefault();
					(function() {
						$go.click();
					})();
					return false;
				}
			});

			// On window resize or when a panel is moved, resize the map
			GF.window.on( 'resize', function() {
				setTimeout( function() {
					field.resize( map, marker );
				}, 100 );
			});

			GF.window.on( 'gf_container_moved', function() {
				setTimeout( function() {
					field.resize( map, marker );
				}, 100 );
			});
		},

		// Return the map's options
		getMapOptions: function( latLng, zoom ) {
			return {
				center: latLng,
				zoom: zoom,
				mapTypeId: google.maps.MapTypeId.ROADMAP,
				disableDoubleClickZoom: true,
				disableDefaultUI: true,
				zoomControl: true
			};
		},

		// Return marker attributes
		getMarkerOptions: function( location, map ) {
			return {
				position: location,
				map: map
			}
		},

		// Saves the coordinates to the input and triggers actions
		saveCoordinates: function( coordinates, $input ) {
			var value = coordinates[0] + ',' + coordinates[1] + ',' + coordinates[2];

			// Put the value in the input
			$input.val( value ).change();

			// Trigger the change
			this.setValueFromInput( value, $input );
		},

		// Bind custom change events
		bindChange: function() {
			var field = this;

			// Bind events separately for each set
			this.row.on( 'change', 'input:hidden', function() {
				field.setValueFromInput( this.value, $( this ) );
			});
		},

		// Trigger change to the input
		triggerChange: function() {
			this.row.find( 'input:hidden' ).trigger( 'change' );
		},

		// Binds event listeners to a map
		addListeners: function( map, listeners ) {
			var event;

			for( event in listeners ) {
				google.maps.event.addListener( map, event, listeners[ event ] );
			}
		},

		// Handles the form submit
		search: function( address, field, map, marker, $input, coords ) {
			var coder = new google.maps.Geocoder(), latLng;

			if( address ) {
				coder.geocode({
					address: address
				}, function( result ) {
					if( result.length ) {
						// If there are result extract the first one
						latLng = result[0].geometry.location;

						if( latLng ) {
							// Save the new location
							coords[0] = latLng.lat();
							coords[1] = latLng.lng();
							field.saveCoordinates( coords, $input );

							// Move the map
							map.setCenter( latLng );
							map.setZoom( 12 )

							// Move the marker
							marker.setPosition(latLng)
						}
					} else {
						alert( GF.Strings.noGoogleResults );
					}
				});
			} else {
				alert( GF.Strings.emptyInput );
			}
		},

		// Resizes a map
		resize: function( map, marker ) {
			// Resize the map
			google.maps.event.trigger( map, "resize" );
			
			// Move the map to the center
			map.setCenter( marker.getPosition() );
		}
	});

	// Sidebar chooser field
	GF.Field.extend( 'SelectSidebar', {
		// After constructor
		initialize: function() {
			var field = this;

			if( !this.data.manipulate ) {
				// If those sidebars can't be manupulated,
				// there's nothing to do here as only a normal select is displayed
				return;
			}

			// Initialize each table
			this.input.each(function() {
				field.initializeTable( $( this ) );
			});
		},

		// Sets up a table which lets things happen
		initializeTable: function( $table ) {
			var field = this;

			// Handle delete button clicks
			$table.on( 'click', '.delete', function( e ) {
				field.deleteSidebar( $( this ).closest( 'tr' ) );
				e.preventDefault();
			});

			// Handle add button clicks
			$table.on( 'click', '.add', function( e ) {
				field.addSidebar( $table );
				e.preventDefault();
			});

			$table.on( 'click', 'p', function( e ) {
				e.preventDefault();

				$( e.target ).closest( 'tr' ).find( ':radio' ).attr( 'checked', 'checked' ).change();
			});

			// Handle Enter and Escape presses
			$table.on( 'keydown', 'input[type=text]', function( e ) {
				// Enter
				if( e.which == 13 ) {
					e.preventDefault();
					// If a timeout is set, alert() won't stop preventing defaults
					setTimeout(function() {
						field.addSidebar( $table );
					}, 1);
				}

				// Escape
				if( e.which == 27 ) {
					e.preventDefault();
					// If a timeout is set, alert() won't stop preventing defaults
					setTimeout(function() {
						$( e.target ).closest( 'tr' ).find( 'input[type=text]' ).val( '' ).blur();
					}, 1);
				}
			});
		},

		// Delete a sidebar
		deleteSidebar: function( $row ) {
			var $table = $row.closest( 'table' );

			if( confirm( GF.Strings.deleteSidebarConfirmation ) ) {
				var name = $row.find( 'p:first' ).html(),
					$input = $( '<input type="hidden" />' );

				// Add the input that will indicate that the sidebar is deleted
				$input.attr( 'name', 'deleted_' + $row.find( ':radio' ).attr( 'name' ) + '[]' );
				$input.val( $row.find( ':radio' ).val() );
				$table.find( '.new-template' ).after( $input );

				// Finally, remove the row
				$row.remove();

				// Check if there's something selected, if not, select the first one
				if( ! $table.find( ':checked' ).size() ) {
					$table.find( 'input:radio:first' ).attr( 'checked', 'checked' ).change();
				}
			}
		},

		// Collect the data from the add form and create the new sidebar
		addSidebar: function( $table ) {
			var $name        = $table.find( '.name' ),	
				$description = $table.find( '.description' ),
				$addRow      = $name.closest( 'tr' ),
				template,
				$template,
				duplicate = false;

			// Ensure there's a title
			if( ! $name.val() ) {
				alert( GF.Strings.noSidebarName );
				$name.focus();
				return false;
			}

			// Check for duplicates
			$table.find( ':radio' ).each(function() {
				if( $( this ).val() == $name.val() ) {
					duplicate = true;
				}
			});

			if( duplicate ) {
				alert( GF.Strings.duplicateSidebar );
				return false;
			}

			// Create the new row
			template = $table.find( '.new-template' ).html();

			// Add the name and description
			template = template.replace( /<# sidebar_name #>/ig, GF.escape( $name.val() ) );
			template = template.replace( /<# sidebar_description #>/ig, GF.escape( $description.val() ) );
			template = template.replace( /<# i #>/ig, GF.Field.SelectSidebar.i++ );

			// Deselect old sidebars
			$table.find( ':checked' ).attr( 'checked', false );

			// Generate the new template and add it to the DOM
			$template = $( template );
			$addRow.before( $template );

			// In cases of quotes and other special characters, the code above won't add them properly to the HTML
			$template.find( '[name*=name]' ).val( $name.val() );
			$template.find( '[name*=description]' ).val( $description.val() );

			// Select the new sidebar
			$template.find( ':radio' ).attr( 'checked', 'checked' ).trigger( 'change' );

			// Clean names
			$table.find( 'input[type=text]' ).val( '' ).blur();
		},

		// Binds events for input changes. Could be inherited later, along with triggerChange
		bindChange: function() {
			var field = this;

			this.row.on( 'change', 'input,select,textarea', function() {
				var $el = $(this);

				// Only save the value of the checked input
				if( $el.is( ':checked' ) ) {
					field.setValueFromInput( $el.val(), $el );
				}
			} );
		},

		// Trigger change to the input
		triggerChange: function() {
			this.row.find( 'input:radio' ).trigger( 'change' );
		}
	}, {
		i: 0
	});

	// File field
	GF.Field.extend( 'File', {
		// Custom constructor
		initialize: function() {
			var field = this;

			// Render each input separately
			this.input.each(function(){
				field.initializeInput( $( this ) );
			});
		},

		// Initializes a single input in case of multiple languages
		initializeInput: function( $wrap ) {
			var field   = this,
				$elements = {
					input: $wrap.find( 'input[type=hidden]' ),
					preview: $wrap.find( '.gf-file-preview' ),
					button: $wrap.find( '.button-primary' ),
					remove: $wrap.find( '.gf-remove-file' )
				};

			// Handle removing
			$elements.remove.click( function( e ) {
				e.preventDefault();
				field.clear( $elements );
			} );

			// Handle choose button clicks. Edit does the same
			$wrap.on( 'click', '.edit-link, .button-primary', function( e ) {
				e.preventDefault();
				field.choose( $elements );
			} );
		},

		// Opens a media popup on click
		choose: function( $elements ) {
			var field = this,
				options = {
					type: this.getType()
				}

			// Add the selected item if there is one
			if( id = $elements.input.val() )
				options.selected = id;

			GF.Media.requestFile( options, function( attachment ) {
				field.selected( $elements, attachment );
			} );
		},

		// Get the type of the needed file. Meant for inheritors
		getType: function() {
			return 'all';
		},

		// Handles file selects
		selected: function( $elements, attachment ) {
			// Set the hidden input's val
			$elements.input.val( attachment.id );

			// Change items in the preview
			this.changePreview( $elements, attachment );

			// Show the remove button in case it's been hidden
			$elements.remove.show();
		},

		// Changes the preview area
		changePreview: function( $elements, attachment ) {
			// Show the preview
			$elements.preview.fadeIn();

			// Change texts and attributes
			$elements.preview.find( '.file-title' ).text( attachment.title );
			$elements.preview.find( '.file-link' ).attr( 'href', attachment.url );
			$elements.preview.find( '.edit-link' ).attr( 'href', attachment.editLink );
			$elements.preview.find( 'img' ).attr( 'src', attachment.icon );
		},

		// De-selects the selected file
		clear: function( $elements ) {
			// Remove the value
			$elements.input.val( '' );

			// Hide the preview
			$elements.preview.fadeOut();

			// Hide the remove button too
			$elements.remove.fadeOut();
		},

		// Disable values from going to the parent
		bindChange: function() {},
	});

	// Image field
	GF.Field.File.extend( 'Image', {
		// Get the type of the needed file. Meant for inheritors
		getType: function() {
			return 'image';
		},

		// Changes the preview, which includes image and buttons
		changePreview: function( $elements, attachment ) {
			// Change the image source and show the new image
			$elements.preview.fadeIn();			
			$elements.preview.find( 'img' ).attr( 'src', attachment.url );
			$elements.preview.find( '.file-link' ).attr( 'href', attachment.url );
			$elements.preview.find( '.edit-link' ).attr( 'href', attachment.editLink );
		},
	});

	// Audio field
	GF.Field.File.extend( 'Audio', {
		// Get the type of the needed file. Meant for inheritors
		getType: function() {
			return 'audio';
		},

		changePreview: function( $elements, attachment ) {
			// Build the player
			var url = attachment.url,
				player = '<audio class="wp-audio-shortcode" id="gf-audio-' + ( GF.Field.Audio.playersCount++ ) + '" preload="none" style="width: 100%" controls="controls">\
						<source type="audio/mpeg" src="' + url + '" />\
						<a href="' + url + '">' + url + '</a>\
					</audio>';

			// Show the preview
			$elements.preview.fadeIn();

			// Add/modify HTML elements
			$elements.preview.find( '.player' ).html( player );
			$elements.preview.find( '.file-title' ).html( attachment.title );
			$elements.preview.find( '.edit-link' ).attr( 'href', attachment.editLink );

			// Add the player
			this.addPlayer();
		},

		// Initializes all media players
		addPlayer: function() {
			// add mime-type aliases to MediaElement plugin support
			mejs.plugins.silverlight[0].types.push( 'video/x-ms-wmv' );
			mejs.plugins.silverlight[0].types.push( 'audio/x-ms-wma' );

			var settings = {};
			if ( typeof _wpmejsSettings !== 'undefined' )
				settings.pluginPath = _wpmejsSettings.pluginPath;

			$('.wp-audio-shortcode').mediaelementplayer( settings );
		}
	});

	/*
		Repeater Row Class, Extends Container
	*/
	GF.RepeaterRow = function( $row, initial ) {
		this.initial = initial;

		this.constructContainer( $row );
	}

	// Inherit the Container class
	_.extend( GF.RepeaterRow.prototype, GF.Container.prototype );

	// Add additional methods and properties to the repater row
	_.extend( GF.RepeaterRow.prototype, {
		// Custom after-constructor
		initializeContainer: function() {
			var container = this;

			// Collect jQuery elements for most items
			this.getElements();

			// Add actions
			this.addActions();

			// Collect row data
			this.data = this.element.data();

			// Add this to the element's data
			this.element.data( 'gf', this );

			// Mark the element as processed
			this.element.addClass( 'ready' );

			// Check titles, etc.
			this.$fields.each(function() {
				var field = $( this ).data( 'gf' );
				container.afterValueChanged( field, field.value );
			});

			// Add the last class to the last field
			this.$fields.last().addClass( 'last' );
			
			// When everything's done, show the row (toggle it) or hide it;
			if( this.initial ) {
				this.element.addClass( 'closed' );
			} else {
				this.element.removeClass( 'closed' );
			}
		},

		// Get elements of the repeater, delete, toggle, etc.
		getElements: function() {
			this.$elements = {
				fieldsWrap: this.element.find( '.gf-inside:eq(0)' ),
				deleteRow: this.element.children( '.delete-row' ),
				toggle: this.element.children( '.hndle, .handlediv' ),
				title: this.element.children( 'h3' ).find( '.group-title' )
			}
		},

		// Bind actions to elements
		addActions: function() {
			var row = this;

			// Handle deleting
			this.$elements.deleteRow.on( 'click', function( e ) {
				row.deleteRow();
				e.preventDefault();
			});

			// Handle toggling
			setTimeout( function() {
				row.$elements.toggle.unbind('click.postboxes' ).on( 'click', function( e ) {
					var $target = $( e.target );

					// On sort, don't toggle
					if( $target.closest('.gf-row').is( '.ui-sortable-helper' ) ) {
						return;
					}

					// Toggle the row and prevent the default click
					row.toggle();

					e.preventDefault();

					return false;
				});
			}, 100 );
		},

		// Delete the row
		deleteRow: function() {
			// Always make the user confirm
			if( ! confirm( GF.Strings.sure ) ) {
				return;
			}
			
			// Remove the actual row
			this.element.remove();

			// If there's an action set, toggle it
			if( GF.isset( this.onDelete ) ) {
				this.onDelete();
			}
		},

		// Toggle the fields part of the row
		toggle: function() {
			// Simply toggle the visibility class
			if( this.element.hasClass('closed') ) {
				this.element.removeClass( 'closed' );
			} else {				
				this.element.addClass( 'closed' );
			}

			GF.window.trigger( 'resize' );
		},

		// Get the fields' DOM elements
		getFields: function() {
			// Get all fields except separator
			return this.$fields = this.element.find( '.gf-inside:eq(0)' ).children( '.gf-field:not(.gf-separator)' );
		},

		// This is triggered when a field changes it's value and it's saved
		afterValueChanged: function( field, value ) {
			if( field.id == this.data.titleField ) {
				this.setTitle( value );
			}
		},

		// Change the value in the title
		setTitle: function( value ) {
			var i, text;

			if( typeof( value ) == 'object' ) {
				for( i in value ) {
					text = value[ i ];
					break;
				}
			} else {
				text = value;				
			}

			this.$elements.title.text( text ).parent()[ text ? 'show' : 'hide' ]();
		},

		// Spreads the sorted event through the fields
		sorted: function() {
			this.$fields.trigger( 'gf-sorted' );
		},

		// Add inner dependencies
		addDependencies: function( dependencies ) {
			var field = this, i;

			this.dependencies = dependencies;

			for( i in this.fields ) {
				this.fields[ i ].triggerChange();

				// Go deepeer
				if( GF.isset( this.fields[ i ].setInnerDependencies ) && GF.isset( dependencies[ i + '__inner' ] ) ) {
					this.fields[ i ].setInnerDependencies( dependencies[ i + '__inner' ] );
				}
			}
		},

		// Checks all fields for valid values
		check: function() {
			var errors = false, i;

			for( i in this.fields ) {
				if( this.fields[ i ].check() ) {
					errors = true;
				}
			}

			this.element[ errors ? 'addClass' : 'removeClass' ]( 'invalid-row' );

			return errors;
		}
	});

	// The repeater itself
	GF.Field.extend( 'Repeater', {
		// Custom constructor
		initialize: function() {
			var field = this;

			// Set elements index
			this.next_input_id = 0;

			// Hold all rows
			this.rows = [];

			// Since there might be none, add a placeholder for dependencies
			this.dependencies = {};

			// Hold the jquery elements
			this.$elements = {
				fields: this.input.children( '.fields' ),
				placeholder: this.input.children( '.fields' ).children( '.placeholder' ),
				prototypes: this.input.children( '.prototypes' ),
				addButton: this.input.children( '.controls' ).find( '.add' ),
				helper: this.input.children( 'h4' )
			}

			// Initialize existing rows
			this.input.find( '.fields:eq(0) > .gf-row' ).each(function() {
				field.initRow( $( this ), true );
			});

			// Init sortable
			this.initSortable();

			// On click on a prototype, directly add it
			this.$elements.prototypes.unbind( 'click' ).on( 'click', '.gf-row', function() {
				var $row = $( this ).clone().removeClass( 'closed' );

				if( $row.is( '.ui-draggable-dragging' ) ) {
					// Don't continue if the field is draggable
					return false;
				}

				// CLone the row and add it to the fields
				$row.appendTo( field.$elements.fields );

				// Init the row
				field.prepareRow( $row );
				field.initRow( $row, false );

				// Hide the placeholder
				field.$elements.placeholder.hide();

				// Close the prototype - don't let WordPress mess up the state
				$( this ).addClass( 'closed' );

				return false;
			} );

			// Handle the Add button click
			this.$elements.addButton.on( 'click', function( e ) {
				field.$elements.prototypes.find( '.gf-row:eq(0)' ).trigger( 'click' );
				e.preventDefault();
			});
		},

		// Init jQuery UI sortable
		initSortable: function() {
			var field = this;

			// Init the sortable part
			this.$elements.fields.sortable({
				axis: 'y',
				handle: '.hndle',
				revert: true,
				// containment: field.input,
				// tolerance: 'pointer',
				receive: function( e, ui ) {
					var $newRow = field.$elements.fields.children( '.gf-row:not(.ready)' );

					if( ! $newRow.is( '.ready' ) ) {
						// Initiate row fields
						field.prepareRow( $newRow );
						field.initRow( $newRow, false );

						// Hide the placeholder
						field.$elements.placeholder.hide();
					}
				},
				stop: function( e, ui ) {
					// Send the event to the sorted item
					$( ui.item ).data( 'gf' ).sorted();
				}
			});

			// Init the draggable part
			this.$elements.prototypes.children().children( '.gf-row' ).draggable({
				connectToSortable: this.$elements.fields,
				helper: 'clone',
				revert: 'invalid',
				containment: field.input
			});
		},

		// Deletes a row
		rowDeleted: function( row ) {
			// If needed, show the "No fields message"
			if( ! this.$elements.fields.children( '.gf-row' ).size() ) {
				this.$elements.placeholder.show();
			}

			// If limits have been reached, now there's space again
			if( this.$elements.prototypes.children().size() > 1) {
				this.$elements.prototypes.show();
				this.$elements.helper.show();				
			} else {
				this.$elements.addButton.show();				
			}
		},

		// Replaces neccessary strings
		prepareRow: function( $row ) {
			$row.html( $row.html().replace(new RegExp(this.data.placeholder, 'g'), this.next_input_id) );
		},

		// Inits a row (group)
		initRow: function( $row, initial ) {
			var field = this,
				row = new GF.RepeaterRow( $row, initial ),
				rowIndex,
				type = $row.data( 'gf-id' );

			// Add the row to the fields's holder
			rowIndex = this.next_input_id;
			this.rows[ rowIndex ] = row;

			// Add dependencies if existing
			if( GF.isset( this.dependencies[ type ] ) ) {
				row.addDependencies( this.dependencies[ type ] );
			}

			// Bind the delete handler
			row.onDelete = function() {
				field.rowDeleted( row );

				// Unset the field from the rows array
				delete field.rows[ rowIndex ];

				// Trigger the save method
				field.setValue( field.rowsCount() );
			}

			// Update the value count
			this.setValue( this.rowsCount() );

			// Increase IDs
			this.next_input_id++;

			// If the limit is reached, hide controls
			if( this.rowsCount() == this.data.limit ) {
				// If limits have been reached, now there's space again
				if( this.$elements.prototypes.children().size() > 1) {
					this.$elements.prototypes.hide();
					this.$elements.helper.hide();				
				} else {
					this.$elements.addButton.hide();				
				}
			}
		},

		// Get the count of rows
		rowsCount: function() {
			var p, i = 0;
			for( p in this.rows ) {
				i++;
			}

			return i;
		},

		// Binds events for input changes. Could be inherited later, along with triggerChange
		bindChange: function() {

			var field = this;

			this.row.on( 'change', 'input,select,textarea', function() {
				var $el = $(this);
				field.setValueFromInput( $el.val(), $el );
			} );
		},

		// Trigger change to the input
		triggerChange: function() {
			// There's nothing to trigger, simply fire the change event
			this.trigger( 'valueChanged', this.rowsCount() );
		},

		// Recieves inner dependencies from the container
		setInnerDependencies: function( dependencies ) {
			var field = this, i, type;

			// Save for later
			this.dependencies = dependencies;

			// Spread the dependencies through existing groups
			for( i in this.rows ) {
				type = this.rows[ i ].data.gfId;

				if( GF.isset( dependencies[ type ] ) ) {
					this.rows[ i ].addDependencies( dependencies[ type ] )
				}
			}
		},

		// Checks if the field's value is valid for required fields
		check: function() {
			var errors = false, i;

			// Force validation of each field group
			for( i in this.rows ) {
				if( this.rows[ i ].check() ) {
					errors = true;
				}
			}

			this.row[ errors ? 'addClass' : 'removeClass' ]( 'invalid-repeater' );

			return errors;
		}
	});	

	// Widgets
	GF.ContainerWidget = function( $container ) {
		// Prevent double initialization
		if( $container.is( '.gf-added' ) ) {
			return;
		}

		// Pass the processing to the parent class
		this.constructContainer( $container );

		// Add the class
		$container.addClass( 'gf-added' );
	}

	// Extend container
	_.extend( GF.ContainerWidget.prototype, GF.Container.prototype );

	// Static methods
	_.extend( GF.ContainerWidget, {
		// Starts listening for AJAX calls so widgets can be initialized
		listen: function() {
			// When ajax is done, initialize widgets and regenerate fields
			$( document ).ajaxSuccess( function(event, jqXHR, ajaxOptions) {
				// Init new widgets
				$('#widgets-right .gf-widget').each( function() {
					new GF.ContainerWidget( $( this ) );
				} );

				// In case the sort has changed, trigger the resize event
				$( '.gf-widget .gf-field' ).trigger( 'gf-sorted' );
			});

			// Prepare data before the "click" event is triggered on the save button
			$( document ).on( 'mousedown', 'input.widget-control-save', function(){
				$( this ).closest( '.widget-inside' ).find( '.gf-field' ).trigger( 'gf-before-save' );
			});

			$( document ).on( 'click', '.widget-top', function() {
				GF.window.trigger( 'gf_container_moved' );
			} );

			// Listen to momments when the user is somehow trying to save and validate
			this.listenAjax();
		},

		// Listens for saving AJAX calls and prevents them when the data is invalid
		listenAjax: function() {
			$(document).ajaxSend(function(event, jqXHR, opt) {
				// Only do something when we're dealing with widget saving
				if( opt.type != "POST" || opt.data.indexOf('action=save-widget') == -1 || opt.data.indexOf('delete_widget') != -1 ) {
					return;
				}

				var widgetId = opt.data.replace( /.*widget-id=([^&]+).*/i, '$1' ),
					$form = $( 'input[name="widget-id"]' ).filter( '[value="' + widgetId + '"]' ).closest( '.widget' );

				// Only perform actions on GF widgets
				if( ! $form.find( '.gf-widget' ).size() ) {
					return;
				}

				if( ! GF.isset( $form.find( '.gf-widget' ).data( 'gf' ) ) ) {
					return;
				}

				if( ! $form.find( '.gf-widget' ).data( 'gf' ).validate() ) {
					jqXHR.abort();
					$form.find( '.ajax-feedback,.spinner' ).css( 'visibility', 'hidden' );
				}
			});
		}
	});

	// Add new methods
	_.extend( GF.ContainerWidget.prototype, {
		// Custom after-construct method
		initializeContainer: function() {
			// Change the validation message container
			this.errorMessage = this.element.find( '.gf-widget-error' );

			// Adding the error class a bit later so WordPress doesn't remove it
			this.errorMessage.children().addClass( 'error' );
		}
	});

	// Terms meta
	GF.ContainerTermsMeta = function( $container ) {
		// Pass processing to the super class
		this.constructContainer( $container );
	}

	_.extend( GF.ContainerTermsMeta.prototype, GF.Container.prototype );

	// Add additional method for terms meta
	_.extend( GF.ContainerTermsMeta.prototype, {
		// Custom after-construct method
		initializeContainer: function() {
			// Change the validation message container
			this.errorMessage = $( '#gf-termsmeta-error' );
		},

		// Bind validation on form submit
		bindValidation: function() {
			var container = this;

			this.element.closest( 'form' ).on( 'submit', function( e ) {
				var valid = container.validate();

				if( ! valid ) {
					e.preventDefault();
				}
			});
		}
	});

	/**
	 * Organize the Add Field popup
	 */
	GF.Shortcode = {
		/**
		 * Bind the click event for opening the popup
		 */
		init: function() {
			// If gf_shortcode_field_types is not set, there are no editors
			if( typeof( gf_shortcode_field_types ) == 'undefined' ) {
				return;
			}

			// Get elements
			GF.Shortcode.getElements();

			// Bind the click to the big button
			GF.Shortcode.bindClicks();

			// Bind change events and actions
			GF.Shortcode.bindEvents();

			// Initially, hide some inputs
			GF.Shortcode.initialSetup();
		},

		/**
		 * Bind the click event for opening and closing the popup
		 */
		bindClicks: function() {
			// Use a global event because editors might be dynamically added
			$( document ).on( 'click', '#insert-field-button', function() {
				GF.Shortcode.showPopup( $( this ).data( 'editor' ) );
				return false;
			} );

			// Allow closing of the popup
			$( '.gf-shortcode-popup .media-modal-close, .gf-shortcode-popup .overlay' ).on( 'click', function() {
				GF.Shortcode.hidePopup();
				return false;
			});
		},

		/**
		 * Get handles for all elements
		 */
		getElements: function() {
			GF.Shortcode.popup = $( '.gf-shortcode-popup' );
			GF.Shortcode.form = $( '.gf-shortcode-popup form' );
			GF.Shortcode.key = $( '#field_key' );
			GF.Shortcode.typeField = $( '[name="field_type"]' );
			GF.Shortcode.typeWrap = $( '#field_type_wrap' );
			GF.Shortcode.itemId = $( '#field_item_id' );
			GF.Shortcode.itemIdWrap = $( '#field_item_id_wrap' );
		},

		/**
		 * Shows the add shortcode popup
		 *
		 * @param string editorID The ID of the editor the content should be send to
		 */
		showPopup: function( editorID ) {
			// Save the ID of the editor for later
			GF.Shortcode.editorID = editorID;

			// Show the popup
			GF.Shortcode.popup.fadeIn( 'fast' );
		},

		/**
		 * Hides the shortcode popup
		 */
		hidePopup: function() {
			GF.Shortcode.popup.fadeOut( 'fast' );
		},

		/**
		 * Hide unused items initially
		 */
		initialSetup: function() {
			GF.Shortcode.typeWrap.hide();
			GF.Shortcode.itemIdWrap.hide();
		},

		/**
		 * Add actions to select changes
		 */
		bindEvents: function() {
			GF.Shortcode.key.change( GF.Shortcode.keyChanged );
			GF.Shortcode.typeField.change( GF.Shortcode.typeChanged );
			GF.Shortcode.form.submit( GF.Shortcode.insertShortcode );
		},

		/**
		 * Handles changing of the key select
		 */
		keyChanged: function() {
			var $t = GF.Shortcode.key,
				value = $t.val(),
				types;

			// Check if there is a field selected, not just the placeholder
			if( value ) {
				// Get the types which the key is available for
				types = gf_shortcode_field_types[ value ].types;

				// Disable unavailable items
				GF.Shortcode.typeField.each(function() {
					$( this ).parent().css( 'display', types.indexOf( $( this ).attr( 'value' ) ) != -1 ? 'block' : 'none' );
				});

				GF.Shortcode.typeField.prop( 'checked', false ).filter( ':visible:eq(0)' ).prop( 'checked', 'checked' );

				// Trigger a change to show/hide item ID
				GF.Shortcode.typeField.trigger( 'change' );

				// Actually show the type field
				GF.Shortcode.typeWrap.show();
			} else {
				// Nothing selected, hide the other fields
				GF.Shortcode.typeWrap.hide();
				GF.Shortcode.itemIdWrap.hide();
			}			
		},

		/**
		 * Handles changing of the type field
		 */
		typeChanged: function() {
			var value = GF.Shortcode.typeField.filter( ':checked' ).val();

			// Since this method handles all items, only apply it for checked ones
			if( ! this.checked ) {
				return;
			}

			if( value == 'widget' || value == 'option' ) {
				GF.Shortcode.itemIdWrap.hide();
			} else {
				// Show the item wrap and display a loader
				GF.Shortcode.itemIdWrap.show().addClass( 'loading' );

				// Load items that belong to the selected container dynamically
				GF.Shortcode.loadItems();
			}
		},

		/**
		 * Queries the server through AJAX and loads items that
		 * are associated with the selected container and type
		 */
		loadItems: function() {
			$.ajax({
				url: ajaxurl,
				method: 'post',
				data: {
					action: 'gf_container_items',
					container: GF.Shortcode.key.find( 'option:selected' ).parent().data( 'containerId' ),
					type: GF.Shortcode.typeField.filter( ':checked' ).val()
				},
				success: function( data ) {
					data = $.parseJSON( data );

					if( data.found ) {
						GF.Shortcode.itemId.show().html( '' ).siblings( 'p' ).hide();
						for( i in data.items ) {
							$option = $( '<option />' );
							$option.val( i ).text( data.items[ i ] );
							GF.Shortcode.itemId.append( $option );
						}						
					} else {
						GF.Shortcode.itemId.hide().html( '' ).siblings( 'p' ).show();
					}

					GF.Shortcode.itemIdWrap.removeClass( 'loading' );
				}
			})
		},

		/**
		 * Collects data and sends the shortcode to the editor
		 */
		insertShortcode: function() {
			var atts = {
					key: gf_shortcode_field_types[ GF.Shortcode.key.val() ].id
				},
				type = GF.Shortcode.typeField.filter( ':checked' ).val(),
				itemId = GF.Shortcode.itemId.val(),
				parts,
				shortcode;

			// Zero is not a value
			if( itemId == '0' || ! GF.Shortcode.itemIdWrap.is( ':visible' ) ) {
				itemId = '';
			}

			// We don't want to include 'post' as type
			if( type == 'post' ) {
				type = '';
			}

			if( type || itemId ) {
				parts = [];
				if( type ) {
					parts.push( type );
				}
				if( itemId ) {
					parts.push( itemId );
				}
				atts.type = parts.join( '_' );
			}

			shortcode = '[gf';
			for( i in atts ) {
				shortcode += ' ' + i + '="' + atts[ i ] + '"';
			}
			shortcode += ']';

			tinyMCE.activeEditor = tinyMCE.get( GF.Shortcode.editorID );
			window.send_to_editor( shortcode );
			GF.Shortcode.hidePopup();

			return false;
		}
	}

	// On document ready, init everything about the popup
	$( document ).ready( GF.Shortcode.init );

})(jQuery, document, window);