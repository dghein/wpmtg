import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { setSlug, setName } = attributes;

	const cardSets = useSelect( ( select ) => {
		return select( coreStore ).getEntityRecords(
			'taxonomy',
			'wpmtg_card_setname',
			{ per_page: -1, orderby: 'name', order: 'asc' }
		);
	}, [] );

	const isResolvingSets = cardSets === null;

	const setOptions = [
		{ label: __( 'Select a card set…', 'wpmtg' ), value: '' },
		...( cardSets || [] ).map( ( term ) => ( {
			label: term.name,
			value: term.slug,
		} ) ),
	];

	const onSetChange = ( slug ) => {
		const selected = ( cardSets || [] ).find(
			( term ) => term.slug === slug
		);

		setAttributes( {
			setSlug: slug,
			setName: selected ? selected.name : '',
		} );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Pack Settings', 'wpmtg' ) }>
					{ isResolvingSets ? (
						<Spinner />
					) : (
						<SelectControl
							label={ __( 'Card Set', 'wpmtg' ) }
							value={ setSlug }
							options={ setOptions }
							onChange={ onSetChange }
							help={ __(
								'Visitors will open packs from this set.',
								'wpmtg'
							) }
						/>
					) }
				</PanelBody>
			</InspectorControls>

			<div { ...useBlockProps() }>
				{ ! setSlug ? (
					<p className="wpmtg-pack-opener__notice">
						{ __(
							'Select a card set in the block settings.',
							'wpmtg'
						) }
					</p>
				) : (
					<>
						<p className="wpmtg-pack-opener__set-label">
							{ setName || setSlug }
						</p>
						<button
							type="button"
							className="wpmtg-pack-opener__button"
							disabled
						>
							{ __( 'Open Pack', 'wpmtg' ) }
						</button>
						<p className="wpmtg-pack-opener__editor-hint">
							{ __(
								'Pack opening is available on the front end.',
								'wpmtg'
							) }
						</p>
					</>
				) }
			</div>
		</>
	);
}
