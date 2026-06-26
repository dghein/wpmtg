/**
 * Admin set selector for the Card Importer.
 *
 * Flow:
 * 1. PHP renders an empty div (#wpmtg-set-selector-root) and a hidden input (#importFormFieldSetCode).
 * 2. This script mounts a React search UI into that div.
 * 3. User picks a set → we write the set code into the hidden input.
 * 4. The existing import form (js/admin.js) submits that hidden input value via AJAX.
 */
import { createRoot, useEffect, useMemo, useRef, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

// Bedrock (and some installs) serve REST at home_url('/wp-json/') rather than WP_SITEURL.
// PHP passes the correct root via wpmtgAdmin.restRoot in Wpmtg::enqueueAdminScripts().
if (window.wpmtgAdmin?.restRoot) {
	apiFetch.use( apiFetch.createRootURLMiddleware( window.wpmtgAdmin.restRoot ) );
}

// DOM ids shared with WpmtgAdminOptions.php and js/admin.js — keep these in sync.
const ROOT_ID = 'wpmtg-set-selector-root';
const HIDDEN_INPUT_ID = 'importFormFieldSetCode';

// Cap dropdown length so rendering stays fast with 800+ sets loaded.
const MAX_RESULTS = 50;

function SetSelector() {
	// Full set list from the REST API: [{ code, name }, ...]
	const [ sets, setSets ] = useState( [] );
	// Text the user has typed into the search box (also shown after a selection).
	const [ query, setQuery ] = useState( '' );
	// Whether the results dropdown is visible.
	const [ isOpen, setIsOpen ] = useState( false );
	// True while the initial API request is in flight.
	const [ isLoading, setIsLoading ] = useState( true );
	// User-facing message when the API request fails.
	const [ error, setError ] = useState( '' );
	// Locked during card import so the user cannot change their selection mid-request.
	const [ isDisabled, setIsDisabled ] = useState( false );
	// Reference to the wrapper div — used to detect clicks outside the component.
	const containerRef = useRef( null );

	/**
	 * Fetch the set catalog once when the component first renders.
	 * The REST endpoint returns cached data (see WpmtgApiHelper::getCardSets).
	 */
	useEffect( () => {
		// Guard against setState after unmount if the user navigates away mid-fetch.
		let isMounted = true;

		apiFetch( { path: '/wpmtg/v1/sets' } )
			.then( ( data ) => {
				if ( ! isMounted ) {
					return;
				}

				setSets( Array.isArray( data ) ? data : [] );
				setError( '' );
			} )
			.catch( () => {
				if ( ! isMounted ) {
					return;
				}

				setError(
					'Unable to load card sets. Please refresh and try again.'
				);
			} )
			.finally( () => {
				if ( isMounted ) {
					setIsLoading( false );
				}
			} );

		// Cleanup: mark unmounted so late responses are ignored.
		return () => {
			isMounted = false;
		};
	}, [] );

	/**
	 * Listen for custom events dispatched by js/admin.js.
	 * This lets the plain-JS import handler control the React UI without importing React.
	 */
	useEffect( () => {
		const handleReset = () => {
			setQuery( '' );
			setIsOpen( false );

			const hiddenInput = document.getElementById( HIDDEN_INPUT_ID );
			if ( hiddenInput ) {
				hiddenInput.value = '';
			}
		};

		const handleDisable = () => setIsDisabled( true );
		const handleEnable = () => setIsDisabled( false );

		document.addEventListener( 'wpmtg-set-selector-reset', handleReset );
		document.addEventListener( 'wpmtg-set-selector-disable', handleDisable );
		document.addEventListener( 'wpmtg-set-selector-enable', handleEnable );

		// Remove listeners when the component unmounts to avoid memory leaks.
		return () => {
			document.removeEventListener(
				'wpmtg-set-selector-reset',
				handleReset
			);
			document.removeEventListener(
				'wpmtg-set-selector-disable',
				handleDisable
			);
			document.removeEventListener(
				'wpmtg-set-selector-enable',
				handleEnable
			);
		};
	}, [] );

	/**
	 * Close the dropdown when the user clicks anywhere outside this component.
	 */
	useEffect( () => {
		const handleClickOutside = ( event ) => {
			if ( ! containerRef.current?.contains( event.target ) ) {
				setIsOpen( false );
			}
		};

		document.addEventListener( 'mousedown', handleClickOutside );

		return () => {
			document.removeEventListener( 'mousedown', handleClickOutside );
		};
	}, [] );

	/**
	 * Derive the dropdown list from the search query.
	 * Re-runs only when `query` or `sets` change (useMemo avoids filtering on every render).
	 */
	const filteredSets = useMemo( () => {
		const normalizedQuery = query.trim().toLowerCase();

		// No search text → show the first N sets as a starting list.
		if ( ! normalizedQuery ) {
			return sets.slice( 0, MAX_RESULTS );
		}

		// Match against both display name and set code (e.g. "bro" or "brothers' war").
		return sets
			.filter( ( set ) => {
				return (
					set.name.toLowerCase().includes( normalizedQuery ) ||
					set.code.toLowerCase().includes( normalizedQuery )
				);
			} )
			.slice( 0, MAX_RESULTS );
	}, [ query, sets ] );

	/** User clicked a set in the dropdown — store the code and update the search field label. */
	const handleSelect = ( set ) => {
		const hiddenInput = document.getElementById( HIDDEN_INPUT_ID );

		// The import form reads this hidden field on submit, not the visible search text.
		if ( hiddenInput ) {
			hiddenInput.value = set.code;
		}

		setQuery( `${ set.name } (${ set.code })` );
		setIsOpen( false );
	};

	return (
		<div
			ref={ containerRef }
			className={ `wpmtg-set-selector${
				isDisabled ? ' wpmtg-set-selector--disabled' : ''
			}` }
		>
			<label
				className="wpmtg-set-selector__label"
				htmlFor="wpmtg-set-selector-input"
			>
				Search for a set
			</label>

			{ /* Visible search box — filtering happens in `filteredSets` above. */ }
			<input
				id="wpmtg-set-selector-input"
				type="text"
				className="regular-text wpmtg-set-selector__input"
				placeholder={
					isLoading
						? 'Loading sets...'
						: 'Type to search sets by name or code'
				}
				value={ query }
				disabled={ isDisabled || isLoading }
				onChange={ ( event ) => {
					setQuery( event.target.value );
					setIsOpen( true );

					// Editing the search text invalidates the previous selection.
					const hiddenInput = document.getElementById( HIDDEN_INPUT_ID );
					if ( hiddenInput ) {
						hiddenInput.value = '';
					}
				} }
				onFocus={ () => {
					if ( ! isDisabled && ! isLoading ) {
						setIsOpen( true );
					}
				} }
			/>

			{ error && <p className="wpmtg-set-selector__error">{ error }</p> }

			{ /* Results dropdown — only shown when open, enabled, and data loaded successfully. */ }
			{ isOpen && ! isDisabled && ! error && (
				<ul className="wpmtg-set-selector__list" role="listbox">
					{ filteredSets.length === 0 ? (
						<li className="wpmtg-set-selector__empty">
							No matching sets found.
						</li>
					) : (
						filteredSets.map( ( set ) => (
							<li key={ set.code }>
								<button
									type="button"
									className="wpmtg-set-selector__option"
									role="option"
									onClick={ () => handleSelect( set ) }
								>
									<span className="wpmtg-set-selector__option-name">
										{ set.name }
									</span>
									<span className="wpmtg-set-selector__option-code">
										{ set.code }
									</span>
								</button>
							</li>
						) )
					) }
				</ul>
			) }

			{ /* Scoped styles for the dropdown — kept inline to avoid a separate CSS build step. */ }
			<style>{ `
				.wpmtg-set-selector {
					position: relative;
					max-width: 420px;
					margin: 12px 0;
				}

				.wpmtg-set-selector__label {
					display: block;
					margin-bottom: 6px;
					font-weight: 600;
				}

				.wpmtg-set-selector__input {
					width: 100%;
					box-sizing: border-box;
				}

				.wpmtg-set-selector__list {
					position: absolute;
					z-index: 100;
					top: calc(100% + 4px);
					left: 0;
					right: 0;
					max-height: 280px;
					margin: 0;
					padding: 0;
					overflow-y: auto;
					list-style: none;
					background: #fff;
					border: 1px solid #8c8f94;
					border-radius: 4px;
					box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
				}

				.wpmtg-set-selector__option {
					display: flex;
					justify-content: space-between;
					gap: 12px;
					width: 100%;
					padding: 8px 12px;
					border: 0;
					background: transparent;
					text-align: left;
					cursor: pointer;
				}

				.wpmtg-set-selector__option:hover,
				.wpmtg-set-selector__option:focus {
					background: #f0f6fc;
					outline: none;
				}

				.wpmtg-set-selector__option-name {
					flex: 1;
				}

				.wpmtg-set-selector__option-code {
					color: #646970;
					font-family: monospace;
					text-transform: uppercase;
				}

				.wpmtg-set-selector__empty,
				.wpmtg-set-selector__error {
					margin: 8px 0 0;
					color: #646970;
				}

				.wpmtg-set-selector__error {
					color: #b32d2e;
				}

				.wpmtg-set-selector--disabled {
					opacity: 0.65;
					pointer-events: none;
				}
			` }</style>
		</div>
	);
}

/** Find the mount point PHP rendered and attach the React tree to it. */
function mountSetSelector() {
	const rootElement = document.getElementById( ROOT_ID );

	// Script may load on other admin pages — exit quietly if the mount point is absent.
	if ( ! rootElement ) {
		return;
	}

	createRoot( rootElement ).render( <SetSelector /> );
}

// DOMContentLoaded may have already fired if this script loads late in the page.
if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', mountSetSelector );
} else {
	mountSetSelector();
}
