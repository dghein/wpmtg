/**
 * Front-end pack opening for the Pack Opener block.
 */

function getRestUrl( block ) {
	if ( window.wpmtgPackOpener?.restUrl ) {
		return window.wpmtgPackOpener.restUrl;
	}

	if ( block.dataset.restUrl ) {
		return block.dataset.restUrl;
	}

	return '/wp-json/wpmtg/v1/pack';
}

function renderCards( container, cards ) {
	container.innerHTML = '';

	if ( ! cards.length ) {
		container.textContent = 'No cards could be generated for this set.';
		return;
	}

	const grid = document.createElement( 'div' );
	grid.className = 'wpmtg-pack-opener__grid';

	cards.forEach( ( card ) => {
		const item = document.createElement( 'article' );
		item.className = 'wpmtg-pack-opener__card';

		if ( card.permalink ) {
			const link = document.createElement( 'a' );
			link.href = card.permalink;
			link.className = 'wpmtg-pack-opener__card-link';

			if ( card.image ) {
				const img = document.createElement( 'img' );
				img.src = card.image;
				img.alt = card.name;
				img.loading = 'lazy';
				img.className = 'wpmtg-pack-opener__card-image';
				link.appendChild( img );
			}

			const name = document.createElement( 'span' );
			name.className = 'wpmtg-pack-opener__card-name';
			name.textContent = card.name;
			link.appendChild( name );

			item.appendChild( link );
		} else {
			const name = document.createElement( 'span' );
			name.className = 'wpmtg-pack-opener__card-name';
			name.textContent = card.name;
			item.appendChild( name );
		}

		if ( card.rarity ) {
			const rarity = document.createElement( 'span' );
			rarity.className = 'wpmtg-pack-opener__card-rarity';
			rarity.textContent = card.rarity;
			item.appendChild( rarity );
		}

		grid.appendChild( item );
	} );

	container.appendChild( grid );
}

function initPackOpener( block ) {
	const setSlug = block.dataset.set;

	if ( ! setSlug ) {
		return;
	}

	const button = block.querySelector( '.wpmtg-pack-opener__button' );
	const cardsContainer = block.querySelector( '.wpmtg-pack-opener__cards' );

	if ( ! button || ! cardsContainer ) {
		return;
	}

	const restUrl = getRestUrl( block );

	button.addEventListener( 'click', async () => {
		button.disabled = true;
		button.textContent = 'Opening…';
		cardsContainer.textContent = '';

		try {
			const response = await fetch(
				`${ restUrl }?set=${ encodeURIComponent( setSlug ) }`
			);
			const data = await response.json();

			if ( ! response.ok ) {
				const message =
					data?.message || 'Unable to open a pack right now.';
				cardsContainer.textContent = message;
				return;
			}

			renderCards( cardsContainer, data.cards || [] );
		} catch ( error ) {
			cardsContainer.textContent = 'Unable to open a pack right now.';
		} finally {
			button.disabled = false;
			button.textContent = 'Open Pack';
		}
	} );
}

document.querySelectorAll( '.wp-block-wpmtg-pack-opener' ).forEach( initPackOpener );
