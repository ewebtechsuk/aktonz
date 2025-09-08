<?php
/**
 * Plugin Name: Offer Form
 * Description: Adds a slide-in offer form on the right for property pages.
 * Version: 1.0.0
 */
if ( ! defined('ABSPATH') ) {
    exit; // Exit if accessed directly.
}

function offer_form_is_property_page() {
    if (function_exists('get_query_var') && get_query_var('apex27_page_name') === 'property-details') {
        return true;
    }
    return is_singular('property');
}

function offer_form_get_current_listing() {
    if (!class_exists('Apex27')) {
        return null;
    }
    global $apex27;
    if (isset($apex27)) {
        if (property_exists($apex27, 'listing_details') && $apex27->listing_details) {
            return $apex27->listing_details;
        }
        if (method_exists($apex27, 'get_property_details')) {
            return $apex27->get_property_details();
        }
    }
    return null;
}

function offer_form_register_cpt() {
    register_post_type('property_offer', [
        'labels' => [
            'name' => 'Property Offers',
            'singular_name' => 'Property Offer'
        ],
        'public' => false,
        'show_ui' => true,
        'supports' => ['title']
    ]);
}
add_action('init', 'offer_form_register_cpt');

function offer_form_enqueue_assets() {
    if (offer_form_is_property_page()) {
        wp_enqueue_style('offer-form', plugin_dir_url(__FILE__) . 'offer-form.css', array(), '1.0.0');
        wp_enqueue_script('offer-form', plugin_dir_url(__FILE__) . 'offer-form.js', array(), '1.0.0', true);
        $details = offer_form_get_current_listing();
        $property_id = $details->id ?? '';
        $property_title = $details->displayAddress ?? '';
        wp_localize_script('offer-form', 'offerFormData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'property_id' => $property_id,
            'property_title' => $property_title,
            'nonce' => wp_create_nonce('offer_form_submit')
        ]);
    }
}
add_action('wp_enqueue_scripts', 'offer_form_enqueue_assets');

function offer_form_render_markup() {
    if ( ! offer_form_is_property_page() ) {
        return;
    }
    $details = offer_form_get_current_listing();
    $property_id = $details->id ?? '';
    $property_title = $details->displayAddress ?? '';
    ?>
    <div id="offer-form-panel" class="offer-form-panel">
        <div class="offer-form-content">
            <button id="offer-form-close" class="offer-form-close" type="button">&times;</button>
            <h2><?php echo esc_html__('Make an offer', 'offer-form'); ?></h2>
            <form class="offer-form-fields">
                <?php wp_nonce_field('offer_form_submit', 'offer_form_nonce'); ?>
                <input type="hidden" name="action" value="submit_property_offer" />
                <input type="hidden" name="property_id" value="<?php echo esc_attr($property_id); ?>" />
                <input type="hidden" name="property_title" value="<?php echo esc_attr($property_title); ?>" />
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

function offer_form_handle_submission() {
    if (!check_ajax_referer('offer_form_submit', 'offer_form_nonce', false)) {
        wp_send_json_error(['message' => 'Invalid form submission.']);
    }
    $name   = sanitize_text_field($_POST['name'] ?? '');
    $email  = sanitize_email($_POST['email'] ?? '');
    $phone  = sanitize_text_field($_POST['phone'] ?? '');
    $message= sanitize_textarea_field($_POST['message'] ?? '');
    $property_id = sanitize_text_field($_POST['property_id'] ?? '');
    $property_title = sanitize_text_field($_POST['property_title'] ?? '');

    $offer_id = wp_insert_post([
        'post_type' => 'property_offer',
        'post_title' => $property_title . ' - ' . $name,
        'post_status' => 'publish'
    ]);

    if ($offer_id) {
        update_post_meta($offer_id, 'property_id', $property_id);
        update_post_meta($offer_id, 'property_title', $property_title);
        update_post_meta($offer_id, 'name', $name);
        update_post_meta($offer_id, 'email', $email);
        update_post_meta($offer_id, 'phone', $phone);
        update_post_meta($offer_id, 'message', $message);
        update_post_meta($offer_id, 'offer_status', 'pending');

        $admin_email = get_option('admin_email');
        wp_mail($admin_email, 'New Property Offer', "Property: $property_title\nName: $name\nEmail: $email\nPhone: $phone\nMessage: $message");

        do_action('offer_form_submitted', $offer_id, [
            'property_id' => $property_id,
            'property_title' => $property_title,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
        ]);

        wp_send_json_success(['message' => 'Offer submitted']);
    }

    wp_send_json_error(['message' => 'Unable to save offer']);
}
add_action('wp_ajax_submit_property_offer', 'offer_form_handle_submission');
add_action('wp_ajax_nopriv_submit_property_offer', 'offer_form_handle_submission');
