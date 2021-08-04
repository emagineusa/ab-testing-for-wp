<?php

namespace ABTestingForWP;

if(!function_exists('is_plugin_active')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

class Integration {

    protected $wpdb;
    private $query = '';
    private $transform = false;

    public function __construct() {
        if (!$this->isPluginActive()) return;
        $this->loadIntegration();

        global $wpdb;
        $this->wpdb = $wpdb;
    }

    protected function loadIntegration() {
    }

    protected function getPluginSlug() {
        return $this->pluginSlug;
    }

    private function isPluginActive() {
        return is_plugin_active($this->getPluginSlug()) && $this->extraPluginCheck();
    }

    protected function addCustomQuery($type = '', $query = '', $transform = false) {
        if ($query === '') return;

        $this->type = $type;
        $this->query = $query;
        $this->transform = $transform;

        add_filter(
            "ab-testing-for-wp_custom-query-{$type}",
            array($this, 'performCustomQuery'),
            10,
            0
        );
    }

    public function performCustomQuery() {
        $results = $this->wpdb->get_results(str_replace('%s', $this->wpdb->prefix, $this->query));

        if ($this->transform) {
            return array_map($this->transform, $results);
        }

        return $results;
    }

    /**
     * You can overwrite this method for an extra check in the isPluginActive
     */
    protected function extraPluginCheck() {
        return true;
    }

}
