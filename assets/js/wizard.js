( function () {
	'use strict';

	function $( selector ) {
		return document.querySelector( selector );
	}

	function $$( selector ) {
		return document.querySelectorAll( selector );
	}

	function escHtml( str ) {
		const div = document.createElement( 'div' );
		div.textContent = str;
		return div.innerHTML;
	}

	function ajaxPost( action, data, callback ) {
		const formData = new FormData();
		formData.append( 'action', action );
		formData.append( 'nonce', wciData.nonce );
		Object.entries( data ).forEach( ( [ key, value ] ) => {
			formData.append( key, value );
		} );

		fetch( wciData.ajaxUrl, { method: 'POST', body: formData } )
			.then( ( res ) => res.json() )
			.then( ( res ) => {
				if ( res.success ) {
					callback( res.data );
				} else {
					alert( res.data?.message || 'An error occurred.' );
				}
			} )
			.catch( () => alert( 'Request failed.' ) );
	}

	// Configure step: mode toggle and post-type field refresh.
	function initConfigure() {
		const modeSelect = $( '#wci-mode' );
		if ( ! modeSelect ) return;

		modeSelect.addEventListener( 'change', function () {
			const needsMatch =
				'update' === this.value || 'upsert' === this.value;
			$( '.wci-match-row' ).style.display = needsMatch ? '' : 'none';
		} );

		const postTypeSelect = $( '#wci-post-type' );
		if ( postTypeSelect ) {
			postTypeSelect.addEventListener( 'change', function () {
				ajaxPost(
					'wci_get_fields',
					{ post_type: this.value },
					( data ) => {
						const select = $( '#wci-match-field' );
						select.innerHTML = '';
						data.forEach( ( field ) => {
							const opt = document.createElement( 'option' );
							opt.value = field.key;
							opt.textContent =
								field.name + ' (' + field.group + ')';
							select.appendChild( opt );
						} );
					}
				);
			} );

			// Load match fields on init if the dropdown is empty.
			const matchField = $( '#wci-match-field' );
			if ( matchField && 0 === matchField.options.length ) {
				postTypeSelect.dispatchEvent( new Event( 'change' ) );
			}
		}

		// Template selection sets the template ID (mapping applied server-side).
		const templateSelect = $( '#wci-template' );
		if ( templateSelect ) {
			templateSelect.addEventListener( 'change', function () {
				const option = this.options[ this.selectedIndex ];
				$( '#wci-template-id' ).value = option.value || '';
			} );
		}
	}

	// Mapping step: dynamic table, column tags, template preview.
	function initMapping() {
		if ( ! $( '#wci-mapping-table' ) ) return;

		const headers = wciData.headers || [];
		const fields = wciData.fields || [];
		const preview = wciData.preview || [];
		const mapping = wciData.mapping || null;

		buildDataPreview( headers, preview );
		buildMappingTable( fields, headers, preview, mapping );

		$( '#wci-add-mapping' ).addEventListener( 'click', () => {
			addMappingRow( fields, headers, preview );
		} );

		$( '#wci-save-template' ).addEventListener(
			'change',
			function () {
				$( '#wci-template-name-wrap' ).style.display =
					this.checked ? '' : 'none';
			}
		);

		// On form submit, collect mapping JSON into hidden field.
		$( '#wci-mapping-form' ).addEventListener( 'submit', ( e ) => {
			const mapping = collectMapping();

			if ( Object.keys( mapping ).length === 0 ) {
				e.preventDefault();
				alert( 'Please map at least one field.' );
				return;
			}

			$( '#wci-mapping-data' ).value = JSON.stringify( mapping );
		} );
	}

	function buildDataPreview( headers, preview ) {
		const div = $( '#wci-data-preview' );
		if ( ! div || ! preview.length ) return;

		let html =
			'<details class="wci-preview-details" open><summary>' +
			escHtml( 'Available columns (first row preview)' ) +
			'</summary>' +
			'<table class="widefat striped"><thead><tr>';
		headers.forEach( ( h ) => {
			html += '<th>' + escHtml( h ) + '</th>';
		} );
		html += '</tr></thead><tbody><tr>';
		headers.forEach( ( h ) => {
			const val = preview[ 0 ][ h ] || '';
			html +=
				'<td>' +
				escHtml(
					val.length > 60
						? val.substring( 0, 60 ) + '...'
						: val
				) +
				'</td>';
		} );
		html += '</tr></tbody></table></details>';
		div.innerHTML = html;
	}

	function createFieldSelect( fields, selectedKey ) {
		const wrap = document.createElement( 'div' );
		wrap.classList.add( 'wci-target-wrap' );

		const select = document.createElement( 'select' );
		select.classList.add( 'wci-target-select' );

		const skipOpt = document.createElement( 'option' );
		skipOpt.value = '';
		skipOpt.textContent = '\u2014 Select Field \u2014';
		select.appendChild( skipOpt );

		let currentGroup = '';
		fields.forEach( ( field ) => {
			if ( field.group !== currentGroup ) {
				currentGroup = field.group;
				const optgroup = document.createElement( 'optgroup' );
				optgroup.label = currentGroup;
				select.appendChild( optgroup );
			}
			const opt = document.createElement( 'option' );
			opt.value = field.key;
			opt.textContent = field.name;
			opt.dataset.type = field.type;
			select.lastElementChild.appendChild( opt );
		} );

		// Add "Custom Meta Key" option.
		const customGroup = document.createElement( 'optgroup' );
		customGroup.label = 'Custom';
		const customOpt = document.createElement( 'option' );
		customOpt.value = '__custom__';
		customOpt.textContent = 'Custom meta key\u2026';
		customOpt.dataset.type = 'text';
		customGroup.appendChild( customOpt );
		select.appendChild( customGroup );

		const customInput = document.createElement( 'input' );
		customInput.type = 'text';
		customInput.classList.add( 'wci-custom-meta-input', 'regular-text' );
		customInput.placeholder = 'Enter meta key';
		customInput.setAttribute( 'autocomplete', 'off' );
		customInput.setAttribute( 'data-1p-ignore', '' );
		customInput.setAttribute( 'data-lpignore', 'true' );
		customInput.style.display = 'none';

		// Check if selectedKey is a known field or a custom meta key.
		const isCustom =
			selectedKey &&
			! fields.some( ( f ) => f.key === selectedKey );

		if ( isCustom ) {
			select.value = '__custom__';
			customInput.value = selectedKey;
			customInput.style.display = '';
		} else if ( selectedKey ) {
			select.value = selectedKey;
		}

		select.addEventListener( 'change', () => {
			customInput.style.display =
				'__custom__' === select.value ? '' : 'none';
			if ( '__custom__' !== select.value ) {
				customInput.value = '';
			}
		} );

		wrap.appendChild( select );
		wrap.appendChild( customInput );

		return wrap;
	}

	function createTemplateInput( headers, preview, templateValue ) {
		const wrap = document.createElement( 'div' );
		wrap.classList.add( 'wci-template-wrap' );

		const input = document.createElement( 'input' );
		input.type = 'text';
		input.classList.add( 'wci-template-input', 'regular-text' );
		input.setAttribute( 'autocomplete', 'off' );
		input.setAttribute( 'data-1p-ignore', '' );
		input.setAttribute( 'data-lpignore', 'true' );
		input.value = templateValue || '';
		input.placeholder = 'e.g. {voornaam} {achternaam} or a static value';
		wrap.appendChild( input );

		const previewEl = document.createElement( 'div' );
		previewEl.classList.add( 'wci-template-preview' );
		wrap.appendChild( previewEl );

		const tags = document.createElement( 'div' );
		tags.classList.add( 'wci-column-tags' );

		headers.forEach( ( header ) => {
			const tag = document.createElement( 'button' );
			tag.type = 'button';
			tag.classList.add( 'wci-column-tag' );
			tag.textContent = header;
			tag.title = 'Insert {' + header + '}';
			tag.addEventListener( 'click', () => {
				const pos = input.selectionStart || input.value.length;
				const placeholder = '{' + header + '}';
				input.value =
					input.value.slice( 0, pos ) +
					placeholder +
					input.value.slice( pos );
				input.focus();
				const newPos = pos + placeholder.length;
				input.setSelectionRange( newPos, newPos );
				input.dispatchEvent( new Event( 'input' ) );
			} );
			tags.appendChild( tag );
		} );

		wrap.appendChild( tags );

		const updatePreview = () => {
			if ( ! preview.length || ! input.value ) {
				previewEl.innerHTML = '';
				previewEl.style.display = 'none';
				return;
			}
			const row = preview[ 0 ];
			let previewHtml = '';
			const parts = input.value.split( /(\{[^}]+\})/ );

			parts.forEach( ( part ) => {
				const match = part.match( /^\{([^}]+)\}$/ );
				if ( ! match ) {
					previewHtml += escHtml( part );
					return;
				}

				const segments = match[ 1 ].split( '|' );
				const col = segments[ 0 ].trim();
				const colValue = row[ col ] !== undefined ? row[ col ] : col;
				previewHtml += escHtml( colValue );

				for ( let i = 1; i < segments.length; i++ ) {
					previewHtml +=
						' <span class="wci-modifier-pill">' +
						escHtml( segments[ i ].trim() ) +
						'</span>';
				}
			} );

			previewEl.innerHTML = '\u2192 ' + previewHtml;
			previewEl.style.display = '';
		};

		input.addEventListener( 'input', updatePreview );
		updatePreview();

		return wrap;
	}

	function addMappingRow( fields, headers, preview, targetKey, templateValue ) {
		const tbody = $( '#wci-mapping-table tbody' );
		const tr = document.createElement( 'tr' );

		const tdTarget = document.createElement( 'td' );
		const fieldWrap = createFieldSelect( fields, targetKey || '' );
		tdTarget.appendChild( fieldWrap );
		tr.appendChild( tdTarget );

		const tdTemplate = document.createElement( 'td' );
		tdTemplate.appendChild(
			createTemplateInput( headers, preview, templateValue || '' )
		);
		tr.appendChild( tdTemplate );

		const tdRemove = document.createElement( 'td' );
		const removeBtn = document.createElement( 'button' );
		removeBtn.type = 'button';
		removeBtn.classList.add( 'button', 'wci-remove-row' );
		removeBtn.textContent = '\u00d7';
		removeBtn.addEventListener( 'click', () => tr.remove() );
		tdRemove.appendChild( removeBtn );
		tr.appendChild( tdRemove );

		tbody.appendChild( tr );
	}

	function buildMappingTable( fields, headers, preview, mapping ) {
		$( '#wci-mapping-table tbody' ).innerHTML = '';

		if ( mapping && typeof mapping === 'object' ) {
			Object.entries( mapping ).forEach( ( [ targetKey, config ] ) => {
				addMappingRow(
					fields,
					headers,
					preview,
					targetKey,
					config.template || ''
				);
			} );
		} else {
			addMappingRow( fields, headers, preview );
		}
	}

	function collectMapping() {
		const mapping = {};

		$$( '#wci-mapping-table tbody tr' ).forEach( ( tr ) => {
			const targetSelect = tr.querySelector( '.wci-target-select' );
			let target = targetSelect.value;
			if ( ! target ) return;

			// Resolve custom meta key.
			if ( '__custom__' === target ) {
				const customInput = tr.querySelector( '.wci-custom-meta-input' );
				target = customInput ? customInput.value.trim() : '';
				if ( ! target ) return;
			}

			const template = tr.querySelector( '.wci-template-input' ).value;
			if ( ! template ) return;

			const selectedOption = targetSelect.selectedOptions[ 0 ];

			mapping[ target ] = {
				template: template,
				type: selectedOption.dataset.type || 'text',
			};
		} );

		return mapping;
	}

	// Import step: start import and poll progress.
	function initImport() {
		const startBtn = $( '#wci-start-btn' );
		if ( ! startBtn ) return;

		startBtn.addEventListener( 'click', () => {
			startBtn.style.display = 'none';

			ajaxPost(
				'wci_start_import',
				{ job_id: wciData.jobId },
				() => {
					$( '#wci-progress' ).style.display = '';
					startPolling();
				}
			);
		} );
	}

	function startPolling() {
		const interval = setInterval( () => {
			ajaxPost(
				'wci_job_status',
				{ job_id: wciData.jobId },
				( data ) => {
					const total = data.total_rows || 1;
					const done = data.processed_rows + data.failed_rows;
					const pct = Math.round( ( done / total ) * 100 );

					$( '.wci-progress-bar' ).style.width = pct + '%';
					$( '#wci-progress-text' ).textContent =
						done +
						' / ' +
						total +
						' rows processed (' +
						data.failed_rows +
						' failed)';

					if (
						data.status === 'completed' ||
						data.status === 'failed'
					) {
						clearInterval( interval );
						$( '#wci-progress' ).style.display = 'none';
						$( '#wci-complete' ).style.display = '';

						const detailUrl = wciData.ajaxUrl.replace(
							'admin-ajax.php',
							'admin.php?page=wp-content-importer&view=job&job_id=' +
								wciData.jobId
						);

						$( '#wci-complete-summary' ).innerHTML =
							'<p>Import ' +
							data.status +
							'. ' +
							data.processed_rows +
							' succeeded, ' +
							data.failed_rows +
							' failed.</p>' +
							'<a href="' +
							detailUrl +
							'" class="button">View Details</a>';
					}
				}
			);
		}, 5000 );
	}

	// Init: detect which step is active and initialize only what's needed.
	document.addEventListener( 'DOMContentLoaded', () => {
		initConfigure();
		initMapping();
		initImport();
	} );
} )();
