<?php
// Offer Actions Handler for Accept/Reject/Counter Offer
add_action('init', function() {
    if (!isset($_GET['offer_action']) || !isset($_GET['offer_id'])) return;

    $action = sanitize_text_field($_GET['offer_action']);
    $offer_id = intval($_GET['offer_id']);
    $redirect_url = home_url();

    // Get offer post
    $offer = get_post($offer_id);
    if (!$offer || $offer->post_type !== 'property_offer') {
        wp_die('Invalid offer.');
    }

    // Only allow if user is logged in and is agent/landlord, or if you want public links, add a security token check here
    // For demo, allow all

    // Update offer status
    switch ($action) {
        case 'accept':
            update_post_meta($offer_id, 'offer_status', 'accepted');
            // Send email to applicant
            $applicant_email = get_post_meta($offer_id, 'email', true);
            $property_title = get_post_meta($offer_id, 'property_title', true);
            $deposit_amount = get_post_meta($offer_id, 'holding_deposit', true);
            $payment_url = add_query_arg(['offer_id' => $offer_id], home_url('/holding-deposit/'));
            wp_mail(
                $applicant_email,
                'Your Offer Has Been Accepted',
                "Congratulations! Your offer for $property_title has been accepted.\n\nTo secure the property, please pay the holding deposit here: $payment_url\n\nDeposit Amount: £$deposit_amount\n\nBest regards,\nThe Lettings Team"
            );
            $redirect_url = add_query_arg('offer_status','accepted', home_url('/offer-confirmation/'));
            break;
        case 'reject':
            update_post_meta($offer_id, 'offer_status', 'rejected');
            $applicant_email = get_post_meta($offer_id, 'email', true);
            $property_title = get_post_meta($offer_id, 'property_title', true);
            wp_mail(
                $applicant_email,
                'Your Offer Was Not Accepted',
                "We regret to inform you that your offer for $property_title was not accepted.\n\nBest regards,\nThe Lettings Team"
            );
            $redirect_url = add_query_arg('offer_status','rejected', home_url('/offer-confirmation/'));
            break;
        case 'counter':
            update_post_meta($offer_id, 'offer_status', 'counter');
            // You may want to send a counter offer email or show a form
            $redirect_url = add_query_arg('offer_status','counter', home_url('/offer-confirmation/'));
            break;
        default:
            wp_die('Invalid action.');
    }
    wp_redirect($redirect_url);
    exit;
});

// Add offer_id to JetFormBuilder email (add this as a hidden field or use a filter)
// Add holding_deposit meta when offer is created (can be set via form or default)
