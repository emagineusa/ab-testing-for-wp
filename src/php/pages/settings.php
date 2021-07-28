<?php

namespace ABTestingForWP;

defined('ABSPATH') or exit;

if ( ! current_user_can( 'manage_options' ) ) {
    return;
}

if ( isset( $_GET['settings-updated'] ) ) {
    // add settings saved message with the class of "updated"
    add_settings_error( 'ab-testing-for-wp_messages', 'ab-testing-for-wp_message', __( 'Settings Saved', 'ab-testing-for-wp' ), 'updated' );
}

?>

<div class="wrap">
    <h1><?php esc_html_e('Settings', 'ab-testing-for-wp'); ?></h1>
    <form action="options.php" method="POST">
         <?php
		// This prints out all hidden setting fields.
		settings_fields( 'ab-testing-for-wp' );
		do_settings_sections( 'ab-testing-for-wp' );
		submit_button( __( 'Save Settings', 'ab-testing-for-wp' ) );
		?>
    </form>
</div>
