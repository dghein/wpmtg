<?php

namespace Wpmtg;

class WpmtgBlocks
{
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
}
