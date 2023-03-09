<?php

/**
 * Retorna uma categoria aleatória de um determinado post
 *
 * @param null $postId
 * @param string $taxonomy
 * @return WP_Term|null
 */
function _theme_get_random_category($postId, $taxonomy = 'category')
{
    $categories = get_the_terms($postId, $taxonomy);
    if (!empty($categories)) {
        $totalCategories = count($categories) > 0 ? count($categories) -1 : count($categories);
        $randomCategory = $categories[rand(0, $totalCategories)];

        return isset($randomCategory) ? $randomCategory : null;
    }

    return null;
}

/**
 * Retorna os posts relacionados pela taxonomy
 *
 * @param $postId
 * @param int $perPage
 * @param string $taxonomy
 * @return int[]|WP_Post[]
 */
function _theme_get_relatives($postId, $perPage = 5, $taxonomy = 'category')
{
    $categories = get_the_terms($postId, $taxonomy);
    $catIds = array_map(function ($category) {
        return $category->term_id;
    }, $categories);


    $query = new WP_Query(array(
        'post_type' => get_post_type($postId),
        'cat' => implode(',', $catIds),
        'posts_per_page' => $perPage,
        'post__not_in' => array($postId),
    ));

    return $query->get_posts();
}

/**
 * Ativa alguns features do wordpress
 */
function _theme_setup()
{
    add_theme_support('automatic-feed-links');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');

    register_nav_menus(array(
        'main' => esc_html__('Principal'),
        'footer' => esc_html__('Rodapé'),
    ));

}
add_action('after_setup_theme', '_theme_setup');

/**
 * Ocultar barra administrativa para usuários sem permissão
 */
function _theme_hide_admin_bar()
{
    if (!current_user_can('manage_options')) {
        add_filter('show_admin_bar', '__return_false');
    }
}
add_action('init', '_theme_hide_admin_bar');
