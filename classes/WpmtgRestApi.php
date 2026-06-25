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
}
