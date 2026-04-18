( function( $, window ) {
	'use strict';

	var config = window.directoristCategoryBasedTags || {};
	var listingCategorySelector = '#at_biz_dir-categories';
	var listingTagSelector = '#at_biz_dir-tags';
	var searchFormSelector = '.directorist-search-form';
	var searchCategorySelector = "select[name='in_cat']";
	var searchTagsContainerSelector = '.directorist-search-tags';
	var searchTagInputSelector = "input[name='in_tag[]']";

	function hasRuntimeConfig() {
		return !! ( config.ajaxUrl && config.action && config.nonce );
	}

	function getSelectedTagNames( $tagField ) {
		var selected = [];
		var select2Data = [];

		if ( ! $tagField.length ) {
			return selected;
		}

		if ( $tagField.hasClass( 'select2-hidden-accessible' ) && 'function' === typeof $tagField.select2 ) {
			try {
				select2Data = $tagField.select2( 'data' ) || [];
			} catch ( error ) {
				select2Data = [];
			}

			if ( select2Data.length ) {
				$.each(
					select2Data,
					function( _, item ) {
						if ( item && item.text ) {
							selected.push( String( item.text ) );
						}
					}
				);
			}
		}

		if ( ! selected.length ) {
			selected = $.map(
				$tagField.find( 'option:selected' ),
				function( option ) {
					return $.trim( $( option ).text() );
				}
			);
		}

		return $.grep(
			selected,
			function( value, index ) {
				return value && selected.indexOf( value ) === index;
			}
		);
	}

	function getListingCategoryIds() {
		var $categoryField = $( listingCategorySelector );
		var value = $categoryField.val();

		if ( Array.isArray( value ) ) {
			return value;
		}

		return value ? [ value ] : [];
	}

	function buildSelect2Options( $tagField ) {
		var allowNew = $tagField.data( 'allow_new' );
		var placeholder = $tagField.data( 'placeholder' );
		var maximumSelectionLength = parseInt( $tagField.data( 'max' ), 10 );
		var options = {
			allowClear: true,
			tags: true === allowNew || 1 === allowNew || '1' === allowNew,
			width: '100%',
		};

		if ( placeholder ) {
			options.placeholder = placeholder;
		}

		if ( ! isNaN( maximumSelectionLength ) && maximumSelectionLength > 0 ) {
			options.maximumSelectionLength = maximumSelectionLength;
		}

		return options;
	}

	function rebuildListingTagField( tags ) {
		var $tagField = $( listingTagSelector );
		var previousSelections = getSelectedTagNames( $tagField );
		var selectedLabels = [];
		var selectedIds = [];

		if ( ! $tagField.length ) {
			return;
		}

		if ( $tagField.hasClass( 'select2-hidden-accessible' ) && 'function' === typeof $tagField.select2 ) {
			$tagField.select2( 'destroy' );
		}

		$tagField.empty();

		$.each(
			tags,
			function( _, tag ) {
				var isSelected = -1 !== previousSelections.indexOf( tag.name );
				var option = new Option( tag.name, tag.name, isSelected, isSelected );

				$( option ).attr( 'data-term-id', tag.id );
				$tagField.append( option );

				if ( isSelected ) {
					selectedLabels.push( tag.name );
					selectedIds.push( String( tag.id ) );
				}
			}
		);

		$tagField.attr( 'data-selected-id', selectedIds.join( ',' ) );
		$tagField.attr( 'data-selected-label', selectedLabels.join( ',' ) );

		if ( 'function' === typeof $.fn.select2 ) {
			$tagField.select2( buildSelect2Options( $tagField ) );
		}

		$tagField.trigger( 'change' );
	}

	function getSelectedSearchTagIds( $form ) {
		var selected = [];

		$form.find( searchTagInputSelector + ':checked' ).each(
			function() {
				selected.push( String( $( this ).val() ) );
			}
		);

		return selected;
	}

	function getUniqueIdPrefix() {
		return String( Date.now ? Date.now() : new Date().getTime() ) + String( Math.floor( Math.random() * 1000 ) );
	}

	function renderSearchTags( $form, tags ) {
		var $tagsContainer = $form.find( searchTagsContainerSelector );
		var selectedIds = getSelectedSearchTagIds( $form );
		var randomPrefix = getUniqueIdPrefix();

		if ( ! $tagsContainer.length ) {
			return;
		}

		$tagsContainer.empty();

		$.each(
			tags,
			function( index, tag ) {
				var inputId = randomPrefix + '-' + String( tag.id ) + '-' + String( index );
				var isChecked = -1 !== selectedIds.indexOf( String( tag.id ) );
				var markup = '' +
					'<div class="directorist-checkbox directorist-checkbox-primary">' +
						'<input type="checkbox" name="in_tag[]" value="' + String( tag.id ) + '" id="' + inputId + '"' + ( isChecked ? ' checked="checked"' : '' ) + '>' +
						'<label for="' + inputId + '" class="directorist-checkbox__label">' + $( '<div />' ).text( tag.name ).html() + '</label>' +
					'</div>';

				$tagsContainer.append( markup );
			}
		);
	}

	function fetchRelatedTags( categoryIds, onSuccess ) {
		if ( ! hasRuntimeConfig() ) {
			onSuccess( [] );
			return;
		}

		$.ajax(
			{
				url: config.ajaxUrl,
				type: 'POST',
				data: {
					action: config.action,
					category_ids: Array.isArray( categoryIds ) ? categoryIds : [],
					nonce: config.nonce,
				},
				dataType: 'json',
			}
		).done(
			function( response ) {
				if ( ! response || ! response.success || ! response.data ) {
					onSuccess( [] );
					return;
				}

				onSuccess( response.data.tags || [] );
			}
		).fail(
			function() {
				onSuccess( [] );
			}
		);
	}

	function refreshListingTags() {
		fetchRelatedTags( getListingCategoryIds(), rebuildListingTagField );
	}

	function getSearchCategoryIds( $form ) {
		var $categoryField = $form.find( searchCategorySelector ).first();
		var value;

		if ( ! $categoryField.length ) {
			return [];
		}

		value = $categoryField.val();

		if ( Array.isArray( value ) ) {
			return value;
		}

		return value ? [ value ] : [];
	}

	function refreshSearchTags( $form ) {
		fetchRelatedTags(
			getSearchCategoryIds( $form ),
			function( tags ) {
				renderSearchTags( $form, tags );
			}
		);
	}

	function refreshAllSearchForms() {
		$( searchFormSelector ).each(
			function() {
				var $form = $( this );

				if ( $form.find( searchCategorySelector ).length && $form.find( searchTagsContainerSelector ).length ) {
					refreshSearchTags( $form );
				}
			}
		);
	}

	$( function() {
		$( document ).on( 'change select2:select select2:unselect select2:clear', listingCategorySelector, refreshListingTags );

		$( document ).on(
			'change select2:select select2:unselect select2:clear',
			searchFormSelector + ' ' + searchCategorySelector,
			function() {
				refreshSearchTags( $( this ).closest( searchFormSelector ) );
			}
		);

		if ( $( listingCategorySelector ).length && $( listingTagSelector ).length ) {
			refreshListingTags();
		}

		refreshAllSearchForms();

		$( window ).on( 'directorist-search-form-nav-tab-reloaded directorist-instant-search-reloaded directorist-type-change', refreshAllSearchForms );

		$( document ).on(
			'directorist-category-changed',
			function() {
				if ( $( listingCategorySelector ).length && $( listingTagSelector ).length ) {
					refreshListingTags();
				}

				refreshAllSearchForms();
			}
		);
	} );
}( jQuery, window ) );
