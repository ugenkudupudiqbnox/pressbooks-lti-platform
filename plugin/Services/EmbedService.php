<?php
namespace PB_LTI\Services;

/**
 * EmbedService
 *
 * Provides minimal "embedded" view for LTI launches by hiding
 * site navigation, headers, footers, and sidebar elements.
 *
 * When ?lti_launch=1 is present, only the main content is shown.
 */
class EmbedService {

    /**
     * Initialize embed mode hooks
     */
    public static function init() {
        if (self::is_lti_launch()) {
            // Remove theme elements
            add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_embed_styles'], 999);
            add_filter('show_admin_bar', '__return_false');

            // Hide Pressbooks-specific elements
            add_filter('pressbooks_show_header', '__return_false');
            add_filter('pressbooks_show_footer', '__return_false');
            add_filter('pressbooks_show_navigation', '__return_false');

            // Add body class for additional styling
            add_filter('body_class', [__CLASS__, 'add_body_class']);
        }
    }

    /**
     * Check if current request is an LTI launch
     */
    public static function is_lti_launch() {
        return isset($_GET['lti_launch']) && $_GET['lti_launch'] === '1';
    }

    /**
     * Enqueue CSS to hide theme chrome
     */
    public static function enqueue_embed_styles() {
        $css = "
            /* Hide Pressbooks header */
            header.header,
            .header,
            .js-header-nav,
            .reading-header,
            .reading-header__inside,

            /* Hide navigation elements */
            nav.nav-reading,
            .nav-reading,
            nav[aria-labelledby='book-toc'],
            nav[aria-labelledby='reading-nav'],
            .nav__wrapper,

            /* Hide footer */
            footer.footer,
            .footer,
            .footer__inner,
            .footer__pressbooks,

            /* Hide license, share, and copyright info */
            .license-attribution,
            .block-reading-meta,
            .block-reading-meta__share,
            .block-reading-meta__subtitle,
            .sharer,
            .entry-footer,
            .copyright,

            /* Hide admin and misc */
            #wpadminbar,
            .edit-link,
            .post-edit-link,
            .entry-meta,
            #comments {
                display: none !important;
            }

            /* Make content full width */
            body.lti-embed {
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }

            body.lti-embed .wrap,
            body.lti-embed .content,
            body.lti-embed main,
            body.lti-embed article {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 2rem !important;
            }

            /* Focus on chapter content */
            body.lti-embed .entry-content,
            body.lti-embed .chapter-content {
                max-width: 900px;
                margin: 0 auto !important;
                padding: 2rem !important;
            }
        ";

        wp_add_inline_style('wp-block-library', $css);

        // If wp-block-library isn't loaded, add as inline style
        if (!wp_style_is('wp-block-library', 'enqueued')) {
            echo '<style id="lti-embed-styles">' . $css . '</style>';
        }
    }

    /**
     * Add body class for LTI embeds
     */
    public static function add_body_class($classes) {
        $classes[] = 'lti-embed';
        $classes[] = 'lti-launch';
        return $classes;
    }
}
