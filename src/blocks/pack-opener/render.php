<?php
/**
 * Pack Opener block front-end markup.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

$set_slug = isset( $attributes['setSlug'] ) ? $attributes['setSlug'] : '';
?>
<div <?php echo get_block_wrapper_attributes(
	array(
		'data-set'     => $set_slug,
		'data-rest-url' => esc_url( rest_url( 'wpmtg/v1/pack' ) ),
	)
); ?>>
	<?php if ( empty( $set_slug ) ) : ?>
		<p class="wpmtg-pack-opener__notice">
			<?php esc_html_e( 'Select a card set in the block settings.', 'wpmtg' ); ?>
		</p>
	<?php else : ?>
		<button type="button" class="wpmtg-pack-opener__button">
			<?php esc_html_e( 'Open Pack', 'wpmtg' ); ?>
		</button>
		<div class="wpmtg-pack-opener__cards" aria-live="polite"></div>
	<?php endif; ?>
</div>
