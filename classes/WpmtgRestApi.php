<?php

namespace Wpmtg;

class WpmtgRestApi
{
    private WpmtgPackOpener $packOpener;

    public function __construct()
    {
        $this->packOpener = new WpmtgPackOpener();
    }

    public function registerRoutes(): void
    {
        register_rest_route('wpmtg/v1', '/pack', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'getPack'],
            'permission_callback' => '__return_true',
            'args' => [
                'set' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_title',
                ],
            ],
        ]);

        register_rest_route('wpmtg/v1', '/sets', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'getSets'],
            // Public read: set names/codes are public Scryfall catalog data.
            // WordPress REST cookie auth also requires a nonce, so manage_options
            // blocks direct browser testing even for logged-in admins.
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * @return \WP_REST_Response|\WP_Error
     */
    public function getPack(\WP_REST_Request $request)
    {
        $setSlug = $request->get_param('set');
        $result = $this->packOpener->generatePack($setSlug);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response($result);
    }

    /**
     * @return \WP_REST_Response|\WP_Error
     */
    public function getSets(\WP_REST_Request $request)
    {
        $setsData = WpmtgApiHelper::getCardSets();

        if (!$setsData || !isset($setsData->data) || !is_array($setsData->data)) {
            return new \WP_Error(
                'wpmtg_sets_fetch_failed',
                __('Unable to fetch card sets.', 'wpmtg'),
                ['status' => 502]
            );
        }

        $sets = array_map(static function ($set) {
            return [
                'code' => $set->code,
                'name' => $set->name,
            ];
        }, $setsData->data);

        return rest_ensure_response($sets);
    }
}
