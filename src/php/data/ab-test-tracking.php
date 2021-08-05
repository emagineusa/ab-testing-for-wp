<?php

namespace ABTestingForWP;

class ABTestTracking {
    private $abTestManager;

    public function __construct() {
        $this->abTestManager = new ABTestManager();
    }

    public function track($request) {
        if (DoNotTrack::isEnabled($request)) {
            return rest_ensure_response(false);
        }

        $body = $request->get_body();
        $data = json_decode($body, true);

        if (empty($data['url']) || empty($data['variantId']) || empty($data['goal']) || empty($data['goalType'])) {
            return new \WP_Error('rest_invalid_request', 'Invalid beacon.', ['status' => 400]);
        }

        if ($data['goalType'] !== 'outbound') {
            $postId = url_to_postid($data['url']);
            if ($postId) {
                $postUrl = get_permalink($postId);
            }
        }

        $targetUrl = wp_parse_url($data['url']);
        $goalUrl = wp_parse_url(!empty($postUrl) ? $postUrl : $data['goal']);

        if ($targetUrl['host'] === $goalUrl['host'] && $targetUrl['path'] === $goalUrl['path']) {
            $this->abTestManager->addTracking($data['variantId'], 'C');
            return rest_ensure_response(true);
        }

        return rest_ensure_response(false);
    }
}
