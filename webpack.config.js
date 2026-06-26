/**
 * Extends the default @wordpress/scripts webpack config.
 *
 * wp-scripts auto-discovers block entry points from block.json files under src/blocks/.
 * This config adds a separate admin entry for the Card Importer set selector (React).
 */
const path = require('path');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

module.exports = {
	...defaultConfig,

	// defaultConfig.entry is a function (not a plain object), so we wrap it rather
	// than spread it directly — otherwise block builds would be dropped.
	entry: () => {
		const entries =
			typeof defaultConfig.entry === 'function'
				? defaultConfig.entry()
				: defaultConfig.entry;

		return {
			...entries,

			// Admin-only React control for searching/selecting Scryfall sets.
			// Builds to: build/admin-set-selector/index.js
			'admin-set-selector/index': path.resolve(
				__dirname,
				'src/admin-set-selector/index'
			),
		};
	},
};
