<?php
/**
 * Front page template.
 *
 * Provides a simple hero section with search form and
 * a featured listings grid to improve the default homepage.
 *
 * @package Kava
 */

get_header();
?>

<section class="hero">
    <div class="container">
        <h1><?php bloginfo( 'name' ); ?></h1>
        <p><?php bloginfo( 'description' ); ?></p>
        <?php get_search_form(); ?>
        <a class="cta-button" href="<?php echo esc_url( get_post_type_archive_link( 'post' ) ); ?>">
            <?php _e( 'Browse All Posts', 'kava' ); ?>
        </a>
    </div>
</section>

<section class="featured-properties">
    <div class="container">
        <h2><?php _e( 'Featured Listings', 'kava' ); ?></h2>
        <?php
        $featured = new WP_Query( array(
            'post_type'      => 'post',
            'posts_per_page' => 3,
        ) );

        if ( $featured->have_posts() ) :
            echo '<div class="properties">';
            while ( $featured->have_posts() ) : $featured->the_post(); ?>
                <article <?php post_class(); ?>>
                    <a href="<?php the_permalink(); ?>">
                        <?php if ( has_post_thumbnail() ) {
                            the_post_thumbnail( 'medium' );
                        } ?>
                        <h3><?php the_title(); ?></h3>
                    </a>
                </article>
            <?php endwhile;
            echo '</div>';
            wp_reset_postdata();
        else :
            _e( 'No listings found.', 'kava' );
        endif;
        ?>
    </div>
</section>

<?php
get_footer();
