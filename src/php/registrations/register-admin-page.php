<?php

namespace ABTestingForWP;

class RegisterAdminPage {
    private $abTestMananger;
    private $fileRoot;
    private $srcRoot = __DIR__ . '/../../';

    public function __construct($fileRoot) {
        $this->abTestManager = new ABTestManager();
        $this->fileRoot = $fileRoot;
        $this->optionsManager = new OptionsManager();
        $this->options = $this->optionsManager->getAllOptions();

        add_action('admin_menu', [$this, 'menu']);
        add_action('current_screen', [$this, 'screenLoad']);
        add_action('admin_init', array( $this, 'register_settings' ) );
    }

    public function screenLoad() {
        $screen = get_current_screen();
        $base = 'a-b-testing_page_ab-testing-for-wp';
        $toplevelBase = 'toplevel_page_ab-testing-for-wp';

        if ($screen->is_block_editor) {
            // on all admin editor pages
            $this->loadEditorScripts();
        }

        if (substr($screen->id, 0, strlen($base)) === $base || $screen->id === $toplevelBase) {
            // an A/B Testing for WordPress admin page
            $this->loadStyles();
            $this->loadPageScripts($this->getPageData($screen->id));

            add_action('admin_footer_text', array($this, 'footerText'));
        }
    }

    public function footerText() {
        return sprintf(
            __('Enjoying <strong>A/B Testing for WordPress</strong>? Please <a href="%s">leave a review</a> on WordPress.org and <a href="%s">write about it</a> on your website.', 'ab-testing-for-wp'),
            'https://wordpress.org/support/plugin/ab-testing-for-wp/reviews/#new-post',
            admin_url('post-new.php')
        );
    }

    private function loadStyles() {
        wp_register_style('ab_testing_for_wp_admin_style', plugins_url('/src/css/admin.css', $this->fileRoot), []);
        wp_enqueue_style('ab_testing_for_wp_admin_style');
    }

    private function loadEditorScripts() {
        wp_register_script(
            'ab-testing-for-wp-admin-editor',
            plugins_url('/dist/admin-editor.js', $this->fileRoot),
            ['wp-plugins', 'wp-edit-post', 'wp-data', 'wp-i18n', 'wp-compose', 'wp-blocks']
        );
        wp_set_script_translations('ab-testing-for-wp-admin-editor', 'ab-testing-for-wp');

        wp_enqueue_script('ab-testing-for-wp-admin-editor');
    }

    private function loadPageScripts($data = null) {
        wp_register_script(
            'ab-testing-for-wp-admin-page',
            plugins_url('/dist/admin-page.js', $this->fileRoot),
            ['wp-api-fetch', 'wp-element', 'wp-i18n']
        );
        wp_set_script_translations('ab-testing-for-wp-admin-page', 'ab-testing-for-wp');

        if (isset($data)) {
            wp_localize_script(
                'ab-testing-for-wp-admin-page',
                'ABTestingForWP_Data',
                $data
            );
        }

        wp_enqueue_script('ab-testing-for-wp-admin-page');
    }

    private function getPageData($pageName) {
        $base = 'a-b-testing_page_ab-testing-for-wp';
        $toplevelBase = 'toplevel_page_ab-testing-for-wp';

        $pageName = str_replace($base, '', $pageName);
        $pageName = str_replace($toplevelBase, '', $pageName);

        switch ($pageName) {
            case '':
                return $this->overviewData();
            default:
                return [];
        }
    }

    public function menu() {
        $icon = file_get_contents($this->srcRoot . 'assets/ab-testing-for-wp-base64-logo.svg');

        add_menu_page(
            'A/B Testing',
            'A/B Testing',
            'manage_options',
            'ab-testing-for-wp',
            [$this, 'appContainer'],
            $icon,
            61
        );

        add_submenu_page(
            'ab-testing-for-wp',
            __('Active A/B Tests Overview', 'ab-testing-for-wp'),
            __('All A/B Tests', 'ab-testing-for-wp'),
            'manage_options',
            'ab-testing-for-wp',
            [$this, 'appContainer']
        );

        add_submenu_page(
            'ab-testing-for-wp',
            __('Add New A/B Test', 'ab-testing-for-wp'),
            __('Add New A/B Test', 'ab-testing-for-wp'),
            'manage_options',
            'post-new.php?post_type=abt4wp-test',
            [$this, 'gotoEditor']
        );

        add_submenu_page(
            'ab-testing-for-wp',
            __('How to Use A/B Testing for WordPress', 'ab-testing-for-wp'),
            __('How to Use', 'ab-testing-for-wp'),
            'manage_options',
            'ab-testing-for-wp_howto',
            [$this, 'howto']
        );

        add_submenu_page(
            'ab-testing-for-wp',
            __('Settings', 'ab-testing-for-wp'),
            __('Settings', 'ab-testing-for-wp'),
            'manage_options',
            'ab-testing-for-wp_settings',
            [$this, 'settings']
        );

        add_submenu_page(
            'ab-testing-for-wp',
            __('Advanced Options', 'ab-testing-for-wp'),
            __('Advanced Options', 'ab-testing-for-wp'),
            'manage_options',
            'ab-testing-for-wp_advanced',
            [$this, 'advanced']
        );
    }

    public function appContainer() {
        echo "<div id='admin_app'></div>";
    }

    public function gotoEditor() {
        echo "<script>window.location = \"" . admin_url('/post-new.php?post_type=abt4wp-test') . "\";</script>";
    }

    private function overviewData() {
        $testsData = $this->abTestManager->getAllTests();

        $testsData = array_map(
            function ($test) {
                return $this->abTestManager->mapToOutput($test);
            },
            $testsData
        );

        return [
            'activeTests' => $testsData,
        ];
    }

    public function howto() {
        $assets = plugins_url('/src/assets/', $this->fileRoot);

        require $this->srcRoot . 'php/pages/howto.php';
    }

    public function advanced() {
        require $this->srcRoot . 'php/pages/advanced.php';
    }

    public function settings() {
        require $this->srcRoot . 'php/pages/settings.php';
    }

    public function register_settings() {
        register_setting( 'ab-testing-for-wp', 'ab-testing-for-wp-options' );

        add_settings_section(
            'ab-testing-for-wp-settings-section',
            '',
            array( $this, 'settings_section_callback' ),
            'ab-testing-for-wp'
        );
    }

    public function settings_section_callback() {
        $renderMethod = $this->options['renderMethod'] ? $this->options['renderMethod'] : 'server';
        ?>
        <div class="card">
            <label><h2><?php esc_html_e( 'Render Method', 'ab-testing-for-wp' ); ?></h2></label>
            <p class="description"><?php esc_html_e( 'Change this setting to "Client-side Rendering" if you notice that the same variants are being shown to all users. This can happen if your website has page caching enabled.', 'ab-testing-for-wp' ); ?></p>
            <p>
                <label for="ab-render-method-server">
                    <input id="ab-render-method-server" name="ab-testing-for-wp-options[renderMethod]" value="server" type="radio" <?php checked( $renderMethod, 'server' ); ?>>
                    <?php esc_html_e( 'Server-Side Render (PHP)', 'ab-testing-for-wp' ); ?>
                </label>
                <label for="ab-render-method-client">
                    <input id="ab-render-method-client" name="ab-testing-for-wp-options[renderMethod]" value="client" type="radio" <?php checked( $renderMethod, 'client' ); ?>>
                    <?php esc_html_e( 'Client-Side Render (JS)', 'ab-testing-for-wp' ); ?>
                </label>
            </p>
        </div>
        <input name="ab-testing-for-wp-options[lastMigration]" type="hidden" value="<?php echo esc_attr( $this->options['lastMigration'] ); ?>">
        <input name="ab-testing-for-wp-options[completedOnboarding]" type="hidden" value="<?php echo esc_attr( $this->options['completedOnboarding'] ); ?>">
        <?php
    }
}
