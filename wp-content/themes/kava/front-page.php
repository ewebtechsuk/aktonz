<?php
/**
 * Front page template for Aktonz.
 *
 * A richer homepage inspired by Foxtons' layout.
 *
 * @package Kava
 */

get_header();
?>

<section class="aktonz-hero">
    <div class="container">
        <h1><?php _e( "London's Estate Agent", 'kava' ); ?></h1>
        <?php get_search_form(); ?>
    </div>
</section>

<section class="aktonz-stats">
    <div class="container">
        <ul class="stats-grid">
            <li class="stat">
                <span class="stat-number">96%</span>
                <span class="stat-label"><?php _e( 'Customer satisfaction', 'kava' ); ?></span>
            </li>
            <li class="stat">
                <span class="stat-number">20k+</span>
                <span class="stat-label"><?php _e( 'Properties managed', 'kava' ); ?></span>
            </li>
            <li class="stat">
                <span class="stat-number">332</span>
                <span class="stat-label"><?php _e( 'London specialists', 'kava' ); ?></span>
            </li>
            <li class="stat">
                <span class="stat-number">24/7</span>
                <span class="stat-label"><?php _e( 'Online tracking', 'kava' ); ?></span>
            </li>
        </ul>
    </div>
</section>

<section class="aktonz-selling">
    <div class="container">
        <h2><?php _e( 'Looking to sell or let your property?', 'kava' ); ?></h2>
        <p><?php _e( 'Book a free valuation to find out what your property is worth.', 'kava' ); ?></p>
        <a class="cta-button" href="/valuation"><?php _e( 'Book valuation', 'kava' ); ?></a>
    </div>
</section>

<section class="aktonz-search">
    <div class="container">
        <h2><?php _e( 'Find a property that\'s right for you', 'kava' ); ?></h2>
        <?php get_search_form(); ?>
    </div>
</section>

<section class="aktonz-testimonials">
    <div class="container">
        <h2><?php _e( 'Testimonials', 'kava' ); ?></h2>
        <blockquote>
            <p><?php _e( 'Aktonz were brilliant helping me find my new home.', 'kava' ); ?></p>
            <cite>Greg Nelson</cite>
        </blockquote>
    </div>
</section>

<?php
get_footer();
?>

