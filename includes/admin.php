<?php
// Admin functionalities

// Register settings for the plugin
function wedding_wishes_register_settings() {
    add_option('wedding_wishes_limit', 5); // Set default limit
    register_setting('wedding_wishes_options_group', 'wedding_wishes_limit');
}

// Create admin menu page
function wedding_wishes_menu() {
    add_menu_page('Wedding Wishes Generator', 'Wishes Generator', 'manage_options', 'wedding_wishes_settings', 'wedding_wishes_settings_page');
}

// Display settings page in the admin panel
function wedding_wishes_settings_page() {
    ?>
    <div class="wrap">
        <h2>Wedding Wishes Generator Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('wedding_wishes_options_group'); ?>
            <?php do_settings_sections('wedding_wishes_options_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Limit Wishes per IP Address:</th>
                    <td>
                        <input type="number" name="wedding_wishes_limit" value="<?php echo esc_attr(get_option('wedding_wishes_limit')); ?>" />
                        <p class="description">Enter the limit for wishes per 24 hours per IP address.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Hook functions to appropriate actions
add_action('admin_menu', 'wedding_wishes_menu');
add_action('admin_init', 'wedding_wishes_register_settings');
