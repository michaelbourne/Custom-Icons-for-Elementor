jQuery(document).ready(function($) {

		/* For Upload */
		var drop = $( "#ec-icons-files" );
		drop.on( 'dragenter', function( e ) {
			$( ".ec-icons-drop" ).css( {
				"border": "4px dashed #09f",
				"background": "rgba(0, 153, 255, .05)"
			} );
			$( ".ec-icons-cont" ).css( {
				"color": "#09f"
			} );
		} ).on( 'dragleave dragend mouseout drop', function( e ) {
			$( ".ec-icons-drop" ).css( {
				"border": "3px dashed #DADFE3",
				"background": "transparent"
			} );
			$( ".ec-icons-cont" ).css( {
				"color": "#8E99A5"
			} );
		} );

		var timeout;

		function progress_bar( max, time ) {
			$( '.ec-icons-progress .bar' ).stop().animate({"width": max + '%'}, time );
		}

		function reloadCss() {
			$( '#eci-icon-fonts-css' ).each( function() {
				this.href = this.href.replace( /\?.*|$/, '?ver=' + Math.random() * (10000 - 1) + 1 );
			} );
		}

		function ajaxSend( request, callback, context ) {

			$.ajax( {
				url: EC_ICONS.ajaxurl,
				data: request,
				cache: false,
				contentType: false,
				dataType: 'json',
				context: context,
				processData: false,
				type: 'POST',
				success: function( response, textStatus ) {
					callback( response, this );
				}

			} );
		}

		function normalizeResponse( response ) {
			if ( response && typeof response === 'object' && Object.prototype.hasOwnProperty.call( response, 'success' ) ) {
				return response.data || {};
			}

			return response || {};
		}

		/* New Font */
		$( '#ec-icons-files' ).on( 'change', function( evt ) {

			var reader = new FileReader(),
				files = evt.target.files,
				files_one = files[ 0 ];

			progress_bar( 100, 200 );

			// Closure to capture the file information.
			reader.onload = (function( theFile ) {
				return function( e ) {

					var file_name = escape( theFile.name );

					$( '.ec-icons-tit,.ec-icons-desc,.ec-icons-browse' ).hide();
					$( '.ec-icons-progress' ).addClass( 'show' );

					var request = new FormData();

					request.append( "file_name", file_name );
					request.append( "source_file", theFile, file_name );
					request.append( "action", EC_ICONS.saveAction );
					request.append( "_wpnonce", $( '.ec-icons-drop' ).find( '#_wpnonce' ).val() );

					ajaxSend( request, function( response ) {
						var data = normalizeResponse( response );

						if ( data.status_save === 'updated' ) {

							var $el = $( '.ec-icons-clone' ).clone();
							$el.find( '.eci-extension-title' ).text( data.name );
							$el.find( '.eci-extension-info-details .value' ).text( data.count_icons );
							$el.find( '.eci' ).addClass( data.first_icon );
							$el.find( '.iconlist' ).html( data.iconlist );

							$( '.wrapper-list-fonts' )
								.removeClass( 'hidden' )
								.find( '.eci-extensions' ).append( $el[ 0 ] );

							$el.show().find( '.delete-font' ).attr( 'data-font', JSON.stringify( data.data ) );

							reloadCss();

						}
						else if ( data.status_save === 'exist' ) {
							alert( EC_ICONS.exist );
						} 
						else if ( data.status_save === 'failedopen' ) {
							alert( EC_ICONS.failedopen );
						} 
						else if ( data.status_save === 'failedextract' ) {
							alert( EC_ICONS.failedextract );
						} 
						else if ( data.status_save === 'emptyfile' ) {
							alert( EC_ICONS.emptyfile );
						}
						else if ( data.status_save === 'updatefailed' ) {
							alert( EC_ICONS.updatefailed );
						}
						else if ( data.status_save === 'invalidfiletype' ) {
							alert( EC_ICONS.invalidfiletype );
						}
						else if ( data.status_save === 'invalidfile' ) {
							alert( EC_ICONS.invalidfiletype );
						}
						else if ( data.status_save === 'deletefailed' ) {
							alert( EC_ICONS.deletefailed );
						}
						else if ( data.status_save === 'remove' ) {
							alert( EC_ICONS.remove );
						}
						else {
							alert( EC_ICONS.unknownerror );
						}

						progress_bar( 0, 5 );
						$( '.ec-icons-progress' ).removeClass( 'show' );
						$( '.ec-icons-tit,.ec-icons-desc,.ec-icons-browse' ).show();

					} );

				}
			})( files_one );

			reader.readAsDataURL( files_one );
		} );

		/* Delete Font */
		$( document ).on( 'click', '.eci-box-content.wrapper-list-fonts .delete-font', function( e ) {
			e.preventDefault();

			var conf = confirm( EC_ICONS.delete );

			if(conf == true){

				var request = new FormData(),
				$this = $( this ),
				data = $this.data( 'font' );

				request.append( "file_name", data.name );
				request.append( "action", EC_ICONS.deleteAction );
				request.append( "_wpnonce", $( '.ec-icons-drop' ).find( '#_wpnonce' ).val() );

				ajaxSend( request, function( response, context ) {
					var data = normalizeResponse( response );

					if ( data.status_save === 'remove' ) {

						var $item = $( context ).closest( '.eci-extension' );
						$item.css( {
							'transition': 'all 0.5s',
							'transform': 'scale(0)'
						} );

						setTimeout( function() {

							$item.remove( 0 );
							if ( !$( '.eci-extension:visible' ).length ) {
								$( '.wrapper-list-fonts' ).addClass( 'hidden' );
							}

							reloadCss();

						}, 1500 );

					} else {
						alert( EC_ICONS.deletefailed );
					}

				}, this );

			}

		} );

		/* Regen */
		$( '.ec-icons-regen' ).on( 'click', function( e ) {

			e.preventDefault();

			var request = new FormData();
			request.append( "action", EC_ICONS.regenAction );
			request.append( "_wpnonce", $( '.ec-icons-drop' ).find( '#_wpnonce' ).val() );

			ajaxSend( request, function(response) {
				var data = normalizeResponse( response );

				if ( data.status_regen === 'regen' ) {
					alert(EC_ICONS.regen);
				} else {
					alert( EC_ICONS.unknownerror );
				}

			} );

		} );


		$('.eci-main').on('click', '.eci-extension-info-details', function(){
			$(this).next( $('.iconlist') ).slideToggle('slow');
		});
	}
);