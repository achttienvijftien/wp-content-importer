(function () {
	'use strict';

	const state = {
		jobId: null,
		headers: [],
		preview: [],
		fields: [],
		pollInterval: null,
		currentStep: 1,
		maxStep: 1,
	};

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

	function goToStep( step ) {
		state.currentStep = step;

		$$( '.wci-panel' ).forEach( ( panel ) => {
			panel.style.display =
				panel.dataset.step === String( step ) ? '' : 'none';
		} );
		$$( '.wci-step' ).forEach( ( el ) => {
			const elStep = Number( el.dataset.step );
			el.classList.toggle( 'active', elStep === step );
			el.classList.toggle( 'completed', elStep < step );
		} );

		// Track the highest step reached for back-navigation.
		if ( step > ( state.maxStep || 0 ) ) {
			state.maxStep = step;
		}
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

	function ajaxUpload( action, file, callback ) {
		const formData = new FormData();
		formData.append( 'action', action );
		formData.append( 'nonce', wciData.nonce );
		formData.append( 'file', file );

		fetch( wciData.ajaxUrl, { method: 'POST', body: formData } )
			.then( ( res ) => res.json() )
			.then( ( res ) => {
				if ( res.success ) {
					callback( res.data );
				} else {
					alert( res.data?.message || 'Upload failed.' );
				}
			} )
			.catch( () => alert( 'Upload failed.' ) );
	}

	// Step 1: Upload.
	function initUpload() {
		const fileInput = $( '#wci-file' );
		const uploadBtn = $( '#wci-upload-btn' );

		fileInput.addEventListener( 'change', () => {
			uploadBtn.disabled = ! fileInput.files.length;
		} );

		uploadBtn.addEventListener( 'click', () => {
			uploadBtn.disabled = true;
			uploadBtn.textContent = 'Uploading...';

			ajaxUpload(
				'wci_upload_file',
				fileInput.files[ 0 ],
				( data ) => {
					state.jobId = data.job_id;
					state.headers = data.headers;
					state.preview = data.preview;

					$( '#wci-name' ).value = data.name || '';

					$( '#wci-upload-status' ).innerHTML =
						'<p class="notice notice-success">Parsed ' +
						data.total +
						' rows.</p>';

					loadPostTypes();
					goToStep( 2 );
				}
			);
		} );
	}

	// Step 2: Configure.
	function loadPostTypes( callback ) {
		ajaxPost( 'wci_get_post_types', {}, ( data ) => {
			const select = $( '#wci-post-type' );
			select.innerHTML = '';
			data.forEach( ( pt ) => {
				const opt = document.createElement( 'option' );
				opt.value = pt.name;
				opt.textContent = pt.label + ' (' + pt.name + ')';
				select.appendChild( opt );
			} );
			if ( callback ) {
				callback();
			}
		} );
	}

	function initConfigure() {
		$( '#wci-mode' ).addEventListener( 'change', function () {
			$( '.wci-match-row' ).style.display =
				this.value === 'update' ? '' : 'none';
		} );

		$( '#wci-template' ).addEventListener( 'change', function () {
			const option = this.options[ this.selectedIndex ];
			if ( option.value ) {
				const postType = option.dataset.postType;
				const mode = option.dataset.mode;
				const matchField = option.dataset.matchField;

				if ( postType ) {
					$( '#wci-post-type' ).value = postType;
				}
				if ( mode ) {
					$( '#wci-mode' ).value = mode;
					$( '#wci-mode' ).dispatchEvent(
						new Event( 'change' )
					);
				}
				if ( matchField ) {
					$( '#wci-match-field' ).value = matchField;
				}
			}
		} );

		$( '#wci-configure-btn' ).addEventListener( 'click', () => {
			const postType = $( '#wci-post-type' ).value;
			const mode = $( '#wci-mode' ).value;
			const matchField =
				mode === 'update' ? $( '#wci-match-field' ).value : '';

			ajaxPost(
				'wci_configure_job',
				{
					job_id: state.jobId,
					name: $( '#wci-name' ).value,
					post_type: postType,
					mode: mode,
					match_field: matchField,
				},
				( data ) => {
					state.fields = data.fields;
					buildDataPreview();
					buildMappingTable();

					if ( state.editMapping ) {
						applyMapping( state.editMapping );
						state.editMapping = null;
					} else {
						applyTemplateMapping();
					}

					goToStep( 3 );
				}
			);
		} );

		// Populate match field when post type changes.
		$( '#wci-post-type' ).addEventListener( 'change', function () {
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
	}

	// Step 3: Mapping.

	function buildDataPreview() {
		const div = $( '#wci-data-preview' );
		if ( ! state.preview.length ) {
			div.innerHTML = '';
			return;
		}

		let html =
			'<details class="wci-preview-details" open><summary>' +
			escHtml( 'Available columns (first row preview)' ) +
			'</summary>' +
			'<table class="widefat striped"><thead><tr>';
		state.headers.forEach( ( h ) => {
			html += '<th>' + escHtml( h ) + '</th>';
		} );
		html += '</tr></thead><tbody><tr>';
		state.headers.forEach( ( h ) => {
			const val = state.preview[ 0 ][ h ] || '';
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

	function createFieldSelect( selectedKey ) {
		const select = document.createElement( 'select' );
		select.classList.add( 'wci-target-select' );

		const skipOpt = document.createElement( 'option' );
		skipOpt.value = '';
		skipOpt.textContent = '\u2014 Select Field \u2014';
		select.appendChild( skipOpt );

		let currentGroup = '';
		state.fields.forEach( ( field ) => {
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

		if ( selectedKey ) {
			select.value = selectedKey;
		}

		return select;
	}

	function createTemplateInput( templateValue ) {
		const wrap = document.createElement( 'div' );
		wrap.classList.add( 'wci-template-wrap' );

		// Template text input.
		const input = document.createElement( 'input' );
		input.type = 'text';
		input.classList.add( 'wci-template-input', 'regular-text' );
		input.setAttribute( 'autocomplete', 'off' );
		input.setAttribute( 'data-1p-ignore', '' );
		input.setAttribute( 'data-lpignore', 'true' );
		input.value = templateValue || '';
		input.placeholder = 'e.g. {voornaam} {achternaam} or a static value';
		wrap.appendChild( input );

		// Live preview.
		const preview = document.createElement( 'div' );
		preview.classList.add( 'wci-template-preview' );
		wrap.appendChild( preview );

		// Column insert tags.
		const tags = document.createElement( 'div' );
		tags.classList.add( 'wci-column-tags' );

		state.headers.forEach( ( header ) => {
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

		// Update preview on input.
		const updatePreview = () => {
			if ( ! state.preview.length || ! input.value ) {
				preview.textContent = '';
				preview.style.display = 'none';
				return;
			}
			const row = state.preview[ 0 ];
			const resolved = input.value.replace(
				/\{([^}]+)\}/g,
				( match, col ) => {
					return row[ col ] !== undefined ? row[ col ] : match;
				}
			);
			preview.textContent = '\u2192 ' + resolved;
			preview.style.display = '';
		};

		input.addEventListener( 'input', updatePreview );
		updatePreview();

		return wrap;
	}

	function addMappingRow( targetKey, templateValue ) {
		const tbody = $( '#wci-mapping-table tbody' );
		const tr = document.createElement( 'tr' );

		// Target field dropdown.
		const tdTarget = document.createElement( 'td' );
		tdTarget.appendChild( createFieldSelect( targetKey || '' ) );
		tr.appendChild( tdTarget );

		// Template value input with column tags.
		const tdTemplate = document.createElement( 'td' );
		tdTemplate.appendChild(
			createTemplateInput( templateValue || '' )
		);
		tr.appendChild( tdTemplate );

		// Remove button.
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

	function buildMappingTable() {
		$( '#wci-mapping-table tbody' ).innerHTML = '';
		addMappingRow();
	}

	function applyMapping( mapping ) {
		$( '#wci-mapping-table tbody' ).innerHTML = '';

		Object.entries( mapping ).forEach( ( [ targetKey, config ] ) => {
			addMappingRow( targetKey, config.template || '' );
		} );
	}

	function applyTemplateMapping() {
		const templateSelect = $( '#wci-template' );
		const option =
			templateSelect.options[ templateSelect.selectedIndex ];

		if ( ! option || ! option.value ) return;

		try {
			const mapping = JSON.parse( option.dataset.mapping );
			if ( ! mapping ) return;

			$( '#wci-mapping-table tbody' ).innerHTML = '';

			Object.entries( mapping ).forEach(
				( [ targetKey, config ] ) => {
					addMappingRow(
						targetKey,
						config.template || ''
					);
				}
			);
		} catch ( e ) {
			// Ignore invalid template data.
		}
	}

	function collectMapping() {
		const mapping = {};

		$$( '#wci-mapping-table tbody tr' ).forEach( ( tr ) => {
			const targetSelect = tr.querySelector(
				'.wci-target-select'
			);
			const target = targetSelect.value;
			if ( ! target ) return;

			const template = tr.querySelector(
				'.wci-template-input'
			).value;
			if ( ! template ) return;

			const selectedOption = targetSelect.selectedOptions[ 0 ];

			mapping[ target ] = {
				template: template,
				type: selectedOption.dataset.type || 'text',
			};
		} );

		return mapping;
	}

	function initMapping() {
		$( '#wci-save-template' ).addEventListener(
			'change',
			function () {
				$( '#wci-template-name-wrap' ).style.display =
					this.checked ? '' : 'none';
			}
		);

		$( '#wci-add-mapping' ).addEventListener( 'click', () => {
			addMappingRow();
		} );

		$( '#wci-map-btn' ).addEventListener( 'click', () => {
			const mapping = collectMapping();

			if ( Object.keys( mapping ).length === 0 ) {
				alert( 'Please map at least one field.' );
				return;
			}

			const data = {
				job_id: state.jobId,
				mapping: JSON.stringify( mapping ),
				save_template: $( '#wci-save-template' ).checked
					? '1'
					: '0',
				template_name: $( '#wci-template-name' ).value,
			};

			ajaxPost( 'wci_save_mapping', data, ( result ) => {
				renderSummary( result.summary );
				goToStep( 4 );
			} );
		} );
	}

	// Step 4: Import.
	function renderSummary( summary ) {
		$( '#wci-summary' ).innerHTML =
			'<table class="form-table">' +
			'<tr><th>Post Type</th><td>' +
			escHtml( summary.post_type ) +
			'</td></tr>' +
			'<tr><th>Mode</th><td>' +
			escHtml( summary.mode ) +
			'</td></tr>' +
			( summary.match_field
				? '<tr><th>Match Field</th><td>' +
					escHtml( summary.match_field ) +
					'</td></tr>'
				: '' ) +
			'<tr><th>Total Rows</th><td>' +
			summary.total_rows +
			'</td></tr>' +
			'<tr><th>Mapped Fields</th><td>' +
			summary.mappings +
			'</td></tr>' +
			'</table>';
	}

	function initImport() {
		$( '#wci-start-btn' ).addEventListener( 'click', () => {
			$( '#wci-start-btn' ).style.display = 'none';

			ajaxPost(
				'wci_start_import',
				{ job_id: state.jobId },
				() => {
					$( '#wci-progress' ).style.display = '';
					startPolling();
				}
			);
		} );
	}

	function startPolling() {
		state.pollInterval = setInterval( () => {
			ajaxPost(
				'wci_job_status',
				{ job_id: state.jobId },
				( data ) => {
					const total = data.total_rows || 1;
					const done =
						data.processed_rows + data.failed_rows;
					const pct = Math.round(
						( done / total ) * 100
					);

					$( '.wci-progress-bar' ).style.width =
						pct + '%';
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
						clearInterval( state.pollInterval );
						$( '#wci-progress' ).style.display = 'none';
						$( '#wci-complete' ).style.display = '';
						$( '#wci-complete-summary' ).innerHTML =
							'<p>Import ' +
							data.status +
							'. ' +
							data.processed_rows +
							' succeeded, ' +
							data.failed_rows +
							' failed.</p>' +
							'<a href="' +
							wciData.ajaxUrl.replace(
								'admin-ajax.php',
								'admin.php?page=wp-content-importer&view=job&job_id=' +
									state.jobId
							) +
							'" class="button">View Details</a>';
					}
				}
			);
		}, 5000 );
	}

	function initStepNavigation() {
		$$( '.wci-step' ).forEach( ( el ) => {
			el.addEventListener( 'click', () => {
				const target = Number( el.dataset.step );

				// Allow clicking completed steps to go back,
				// but not forward beyond the highest reached step.
				if ( target < state.currentStep && target >= 1 ) {
					goToStep( target );
				}
			} );
		} );
	}

	function loadExistingJob( jobId ) {
		ajaxPost( 'wci_get_job', { job_id: jobId }, ( data ) => {
			state.jobId = data.job_id;
			state.headers = data.headers || [];
			state.fields = data.fields || [];
			state.preview = [];

			$( '#wci-name' ).value = data.name || '';

			loadPostTypes( () => {
				$( '#wci-post-type' ).value = data.post_type || '';
				$( '#wci-mode' ).value = data.mode || 'create';
				$( '#wci-mode' ).dispatchEvent( new Event( 'change' ) );

				if ( data.match_field ) {
					ajaxPost(
						'wci_get_fields',
						{ post_type: data.post_type },
						( fields ) => {
							const select = $( '#wci-match-field' );
							select.innerHTML = '';
							fields.forEach( ( field ) => {
								const opt =
									document.createElement( 'option' );
								opt.value = field.key;
								opt.textContent =
									field.name +
									' (' +
									field.group +
									')';
								select.appendChild( opt );
							} );
							select.value = data.match_field;
						}
					);
				}
			} );

			// Pre-build mapping if there is one.
			if ( data.mapping ) {
				state.editMapping = data.mapping;
			}

			goToStep( 2 );
		} );
	}

	// Init.
	document.addEventListener( 'DOMContentLoaded', () => {
		if ( ! $( '#wci-wizard' ) ) return;

		initStepNavigation();
		initUpload();
		initConfigure();
		initMapping();
		initImport();

		if ( Number( wciData.editJobId ) ) {
			loadExistingJob( wciData.editJobId );
		}
	} );
} )();
