<?php

namespace Wpmtg;

class WpmtgBlocks
{
    private const PACK_OPENER_VIEW_HANDLE = 'wpmtg-pack-opener-view-script';

    public function registerBlocks(): void
    {
        $build_dir = dirname(__DIR__) . '/build/blocks';
        $manifest  = dirname(__DIR__) . '/build/blocks-manifest.php';

        if (! is_dir($build_dir) || ! file_exists($manifest)) {
            return;
        }

        if (function_exists('wp_register_block_types_from_metadata_collection')) {
            wp_register_block_types_from_metadata_collection($build_dir, $manifest);
            return;
        }

        if (function_exists('wp_register_block_metadata_collection')) {
            wp_register_block_metadata_collection($build_dir, $manifest);
            $manifest_data = require $manifest;
            foreach (array_keys($manifest_data) as $block_type) {
                register_block_type_from_metadata($build_dir . '/' . $block_type);
            }
            return;
        }

        foreach (glob($build_dir . '/*/block.json') ?: [] as $block_json) {
            register_block_type_from_metadata(dirname($block_json));
        }
    }

    public function localizePackOpenerScript(): void
    {
        if (is_admin() || !has_block('wpmtg/pack-opener')) {
            return;
        }

        if (!wp_script_is(self::PACK_OPENER_VIEW_HANDLE, 'registered')) {
            return;
        }

        wp_localize_script(self::PACK_OPENER_VIEW_HANDLE, 'wpmtgPackOpener', [
            'restUrl' => rest_url('wpmtg/v1/pack'),
        ]);
    }
}
