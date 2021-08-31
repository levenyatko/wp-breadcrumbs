<?php
/**
 * Class LevDBreadcrumbsData
 *
 * Creates an array with Breadcrumbs titles and links to display anywhere in Wordpress
 *
 * @author Levchenko Daria
 * @version 2021.08.15
 * @license MIT
 *
 */
class LevDBreadcrumbsData
{
    private $crumbs;

    private $settings;

    public function build($settings = [])
    {
        global $post;

        $default_settings = [
            'show_on_home' => 0, // show breadcrumbs on homepage
            'show_home_link' => 1,
            'show_current' => 1,
            'show_paged' => 1,
            'show_archive' => 1, // show post type archive link. For posts it will be page_for_posts page
            'show_category' => 1, // show category link
            'labels' => [
                'home' => __('Home', 'yt-empty'),
                'category' => '%s',
                'post_tag' => __('Tag: "%s"', 'yt-empty'),
                'author' => __('Author: "%s"', 'yt-empty'),
                'search' => __('Search results for: "%s"', 'yt-empty'),
                '404' => __('Page 404', 'yt-empty'),
                'page' => __('Page %s', 'yt-empty'),
                'cpage' => __('Comments Page %s', 'yt-empty')
            ],
            // terms to show in breadcrumbs for each post type
            'post_terms' => [
                'post' => 'category',
                // post type => taxonomy
            ],
            'archive_pages' => [
                // cpt custom archive pages 'post type' => archive page id
            ]
        ];

        $this->settings = wp_parse_args($settings, $default_settings);

        $parent_id = ( empty($post) ) ? '' : $post->post_parent;

        $this->crumbs = [];

        if ( is_front_page() ) {

            if ( $this->settings['show_on_home'] ) {
                $this->add_crumb($this->settings['labels']['home']);
            }

        } else {

            if ( $this->settings['show_home_link'] ) {
                $this->add_crumb($this->settings['labels']['home'], get_home_url());
            }

            // if it is blog posts page
            if ( is_home() && ! is_front_page() ) {

                $page_for_posts_id = get_option('page_for_posts');

                if ( ! empty($page_for_posts_id) && $this->settings['show_current']) {

                    $paged = $this->get_paged();
                    if ( empty($paged) ) {
                        $this->add_crumb(get_the_title($page_for_posts_id));
                    } else {
                        $this->add_crumb(get_the_title($page_for_posts_id), get_permalink($page_for_posts_id));
                        $this->add_crumb(sprintf($this->settings['labels']['page'], $paged));
                    }
                }

            } elseif ( is_page() ) {

                if ( ! empty($parent_id) ) {
                    $parents = get_post_ancestors(get_the_ID());
                    foreach (array_reverse($parents) as $pageID) {
                        $this->add_crumb(get_the_title($pageID), get_page_link($pageID));
                    }
                }

                if ( $this->settings['show_current'] ) {
                    $paged = $this->get_paged('cpage');
                    if ( empty($paged) ) {
                        $this->add_crumb( get_the_title() );
                    } else {
                        $this->add_crumb( get_the_title(), get_permalink() );
                        $this->add_crumb(sprintf($this->settings['labels']['cpage'], $paged));
                    }
                }

            } elseif ( is_attachment() ) {

                $parent = get_post($parent_id);

                if ( ! empty($parent) ) {
                    $cat = get_the_category($parent->ID);

                    if ( ! empty($cat) ) {
                        $catID = $cat[0]->cat_ID;

                        $parents = get_ancestors($catID, 'category');
                        $parents = array_reverse($parents);
                        $parents[] = $catID;
                        foreach ($parents as $cat) {
                            $this->add_crumb(get_cat_name($cat), get_category_link($cat));
                        }
                    }

                    $this->add_crumb($parent->post_title, get_permalink($parent));
                }

                if ( $this->settings['show_current'] ) {
                    $this->add_crumb(get_the_title());
                }

            } elseif ( is_single() ) {

                $post_type_slug = get_post_type();

                if ( ! empty($parent_id) ) {
                    $parents = get_post_ancestors(get_the_ID());
                    foreach (array_reverse($parents) as $pageID) {
                        $this->add_crumb(get_the_title($pageID), get_page_link($pageID));
                    }
                }

                if ( $this->settings['show_archive'] ) {
                    if ( ! empty( $this->settings['archive_pages'][$post_type_slug] ) ) {
                        $this->add_crumb(
                            get_the_title($this->settings['archive_pages'][$post_type_slug]),
                            get_permalink($this->settings['archive_pages'][$post_type_slug])
                        );
                    } else {
                        if ( 'post' == $post_type_slug ) {
                            $posts_page_id = get_option('page_for_posts');
                            if ( ! empty($posts_page_id) ) {
                                $this->add_crumb(
                                    get_the_title($posts_page_id),
                                    get_permalink($posts_page_id)
                                );
                            }
                        } else {
                            // custom post types
                            $post_type = get_post_type_object($post_type_slug);

                            if ( $post_type->has_archive ) {
                                $this->add_crumb($post_type->labels->name, get_post_type_archive_link($post_type->name));
                            } elseif( ! empty($this->settings['custom_archives'][$post_type->name]) ) {
                                $this->add_crumb(
                                    get_the_title($this->settings['custom_archives'][$post_type->name]),
                                    get_permalink($this->settings['custom_archives'][$post_type->name])
                                );
                            } else {
                                // $this->add_crumb($post_type->labels->name);
                            }
                        }
                    }
                }

                if ( $this->settings['show_category'] ) {

                    if ( ! empty( $this->settings['post_terms'][$post_type_slug] ) ) {

                        $terms_args = [
                            'number' => 1,
                            'fields' => 'ids',
                        ];
                        $terms = wp_get_object_terms($post->ID, $this->settings['post_terms'][$post_type_slug], $terms_args);
                        if ( ! empty($terms) ) {
                            $parents = get_ancestors($terms[0], $this->settings['post_terms'][$post_type_slug]);
                            $parents = array_reverse($parents);
                            $parents[] = $terms[0];
                            foreach ($parents as $t_id) {
                                $parent_cat = get_term($t_id);
                                $this->add_crumb($parent_cat->name, get_category_link($t_id));
                            }
                        }
                    }

                }

                if ($this->settings['show_current']) {
                    $paged = $this->get_paged('cpage');
                    if ( empty($paged) ) {
                        $this->add_crumb( get_the_title() );
                    } else {
                        $this->add_crumb( get_the_title(), get_permalink() );
                        $this->add_crumb(sprintf($this->settings['labels']['cpage'], $paged));
                    }
                }

            } elseif ( is_category() || is_tag() || is_tax() ) {

                if ( is_category() ) {
                    $taxonomy = 'category';
                    $current_tag_id = get_query_var( 'cat' );
                } elseif ( is_tag() ) {
                    $taxonomy = 'post_tag';
                    $current_tag_id = get_query_var( 'tag_id' );
                } else { // is_tax()
                    $taxonomy = get_query_var( 'taxonomy' );
                    $current_tag_id = get_queried_object_id();
                }

                if ( empty($this->settings['labels'][$taxonomy]) ) {
                    $title_format = '%s';
                } else {
                    $title_format = $this->settings['labels'][$taxonomy];
                }

                $parents = get_ancestors($current_tag_id, $taxonomy);
                foreach (array_reverse($parents) as $cat) {
                    $tag = get_term($cat);
                    $title = sprintf($title_format, $tag->name);
                    $this->add_crumb($title, get_term_link($cat, $taxonomy));
                }

                if ( $this->settings['show_current'] ) {

                    $title = sprintf($title_format, single_tag_title('', false));
                    $paged = $this->get_paged();

                    if ( empty($paged) ) {
                        $this->add_crumb($title);
                    } else {
                        $this->add_crumb($title, get_term_link($current_tag_id, $taxonomy));
                        $this->add_crumb(sprintf($this->settings['labels']['page'], $paged));

                    }
                }

            } elseif ( is_author() ) {

                if ( $this->settings['show_current'] ) {

                    $author_id = get_query_var( 'author' );
                    $author_name = get_the_author_meta( 'display_name', $author_id );

                    $title = sprintf($this->settings['labels']['author'], $author_name);

                    $paged = $this->get_paged();
                    if ( empty($paged) ) {
                        $this->add_crumb($title);
                    } else {
                        $this->add_crumb($title, get_author_posts_url($author_id));
                        $this->add_crumb(sprintf($this->settings['labels']['page'], $paged));
                    }

                }

            } elseif ( is_search() ) {

                if ( $this->settings['show_current'] ) {

                    $title = sprintf($this->settings['labels']['search'], get_search_query());
                    $paged = $this->get_paged();

                    if ( empty($paged) ) {
                        $this->add_crumb($title);
                    } else {
                        $link = add_query_arg('s', get_search_query(), get_home_url());
                        $this->add_crumb($title, $link);
                        $this->add_crumb(sprintf($this->settings['labels']['page'], $paged));
                    }
                }

            } elseif (is_year()) {

                if ($this->settings['show_current']) {
                    $year = get_the_time('Y');
                    $paged = $this->get_paged();
                    if ( empty($paged) ) {
                        $this->add_crumb($year);
                    } else {
                        $this->add_crumb($year, get_year_link($year));
                        $this->add_crumb(sprintf($this->settings['labels']['page'], $paged));
                    }
                }

            } elseif (is_month()) {

                $year = get_the_time('Y');
                $this->add_crumb($year, get_year_link($year));

                if ($this->settings['show_current']) {
                    $paged = $this->get_paged();
                    if ( empty($paged) ) {
                        $this->add_crumb( get_the_time('F') );
                    } else {
                        $this->add_crumb(get_the_time('F'), get_month_link($year, get_the_time('m')));
                        $this->add_crumb(sprintf($this->settings['labels']['page'], $paged));
                    }
                }

            } elseif (is_day()) {

                $year = get_the_time('Y');
                $this->add_crumb($year, get_year_link($year));

                $this->add_crumb(get_the_time('F'), get_month_link($year, get_the_time('m')));

                if ($this->settings['show_current']) {
                    $paged = $this->get_paged();
                    if ( empty($paged) ) {
                        $this->add_crumb( get_the_time('d') );
                    } else {
                        $this->add_crumb(get_the_time('d'), get_day_link($year, get_the_time('m'), get_the_time('d')));
                        $this->add_crumb(sprintf($this->settings['labels']['page'], $paged));
                    }
                }

            } elseif (is_post_type_archive()) {

                $post_type = get_post_type_object(get_post_type());
                if ($this->settings['show_current']) {
                    $paged = $this->get_paged();
                    if ( empty($paged) ) {
                        $this->add_crumb($post_type->label);
                    } else {
                        $this->add_crumb($post_type->label, get_post_type_archive_link($post_type->name));
                        $this->add_crumb(sprintf($this->settings['labels']['page'], $paged));
                    }
                }

            } elseif (is_404()) {

                if ($this->settings['show_current']) {
                    $this->add_crumb($this->settings['labels']['404']);
                }

            }

        }

        return $this->crumbs;
    }

    private function add_crumb($title, $link = '')
    {
        $this->crumbs[] = [
            'title' => wp_strip_all_tags( $title ),
            'url'  => $link,
        ];
    }

    private function get_paged($var_name = 'paged')
    {
        if ($this->settings['show_paged']) {
            return get_query_var($var_name);
        }
        return 0;
    }

}
