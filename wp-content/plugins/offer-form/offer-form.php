<?php
/**
 * Plugin Name: Offer Form
 * Description: Adds a slide-in offer form on the right for property pages.
 * Version: 1.0.0
 */
if ( ! defined('ABSPATH') ) {
    exit; // Exit if accessed directly.
}

function offer_form_enqueue_assets() {
    if ( is_singular('property') ) {
        wp_enqueue_style('offer-form', plugin_dir_url(__FILE__) . 'offer-form.css', array(), '1.0.0');
        wp_enqueue_script('offer-form', plugin_dir_url(__FILE__) . 'offer-form.js', array(), '1.0.0', true);
    }
}
add_action('wp_enqueue_scripts', 'offer_form_enqueue_assets');

function offer_form_render_markup() {
    if ( ! is_singular('property') ) {
        return;
    }
    ?>
    <div id="offer-form-panel" class="offer-form-panel">
        <div class="offer-form-content">
            <button id="offer-form-close" class="offer-form-close" type="button">&times;</button>
            <h2><?php echo esc_html__('Make an offer', 'offer-form'); ?></h2>
            <form class="offer-form-fields">
                <p>
                    <label for="offer-name"><?php esc_html_e('Name', 'offer-form'); ?></label>
                    <input type="text" id="offer-name" name="name" required />
                </p>
                <p>
                    <label for="offer-email"><?php esc_html_e('Email', 'offer-form'); ?></label>
                    <input type="email" id="offer-email" name="email" required />
                </p>
                <p>
                    <label for="offer-phone"><?php esc_html_e('Phone', 'offer-form'); ?></label>
                    <input type="tel" id="offer-phone" name="phone" />
                </p>
                <p>
                    <label for="offer-message"><?php esc_html_e('Message', 'offer-form'); ?></label>
                    <textarea id="offer-message" name="message"></textarea>
                </p>
                <p>
                    <button type="submit" class="offer-form-submit"><?php esc_html_e('Submit', 'offer-form'); ?></button>
                </p>
            </form>
        </div>
    </div>
    <button id="offer-form-open" class="offer-form-open" type="button"><?php esc_html_e('Make an offer', 'offer-form'); ?></button>
    <?php
}
add_action('wp_footer', 'offer_form_render_markup');
