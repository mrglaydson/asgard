<?php

/**
 * Customiza a paginação dos posts
 *
 * @param string $postType
 * @param null $search
 * @return string
 */
function _theme_show_pagination($postType = 'post', $search = null)
{
    if (!is_null(get_search_query())) {
        $args['s'] = get_search_query();
    }

    if (!is_null(get_query_var('taxQuery'))) {
        $args['tax_query'] = get_query_var('taxQuery');
    }

    if (!is_null(get_query_var('cat'))) {
        $args['cat'] = get_query_var('cat');
    }

    if (!is_null(get_query_var('postNotIn'))) {
        $args['post__not_in'] = get_query_var('postNotIn');
    }

    $args['post_type'] = $postType;
    $args['paged'] = (get_query_var('paged') == 0) ? 1 : get_query_var('paged');
    $query = new WP_Query($args);

    $maxPage = 99999;
    $pages = paginate_links(array(
        'base' => str_replace($maxPage, '%#%', esc_url(get_pagenum_link($maxPage))),
        'format' => '?paged=%#%',
        'current' => max(1, get_query_var('paged')),
        'total' => $query->max_num_pages,
        'type' => 'array',
        'prev_next' => true,
        'prev_text' => __('<i class="fas fa-fw fa-chevron-left"></i> <span>Anterior</span>'),
        'next_text' => __('<span>Próximo</span> <i class="fas fa-fw fa-chevron-right"></i>'),
    ));

    $output = '';
    if (is_array($pages)) {
        $output .= '<ul class="pagination">';
        foreach ($pages as $page) {
            $output .= "<li>{$page}</li>";
        }
        $output .= '</ul>';
    }
    wp_reset_postdata();

    return $output;
}

/**
 * Adiciona um script no footer que vai inserir uma variável js com uma URL
 * que será utilizada para requisições AJAX
 */
function _theme_load_ajax()
{
    $script = '<script>';
    $script .= 'var ajaxUrl = "' . admin_url('admin-ajax.php') . '";';
    $script .= '</script>';

    echo $script;
}
add_action('wp_footer', '_theme_load_ajax');

/**
 * Renderiza o código do analytics salvo pelo ACF no admin
 *
 * @return false|string
 */
function _theme_render_analytics()
{
    if (function_exists('get_field')) {
        $codeAnalytics = get_field('analytics_code', 'option');
        if (!empty($codeAnalytics)) {
            ob_start();
            ?>
            <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $codeAnalytics; ?>"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());

                gtag('config', 'UA-<?php echo $codeAnalytics; ?>');
            </script>
            <?php

            return ob_get_clean();
        }
    }
}

/**
 * Gera um iframe do youtube a partir de uma URL do youtube em qualquer formato
 *
 * @param string $url
 * @param string $classes
 * @return string|string[]|null
 */
function _theme_generate_youtube_iframe(string $url, string $classes = '')
{
    return preg_replace(
        "/\s*[a-zA-Z\/\/:\.]*youtu(be.com\/watch\?v=|.be\/)([a-zA-Z0-9\-_]+)([a-zA-Z0-9\/\*\-\_\?\&\;\%\=\.]*)/i",
        "<iframe src=\"//www.youtube.com/embed/$2\" class=\"$classes\" allowfullscreen></iframe>",
        $url
    );
}

/**
 * Retorna imagem destacada, caso não tenha ele retorna o placeholder
 *
 * @param int|null $postId
 * @return false|mixed|string|null
 */
function _theme_get_thumbnail(int $postId = null)
{
    $thumb = get_field('placeholder', 'options');
    if (null !== $postId) {
        if (has_post_thumbnail($postId)) {
            $thumb = get_the_post_thumbnail_url($postId);
        }
    }

    return $thumb;
}

/**
 * Retorna a logo principal do site
 *
 * @return mixed|null
 */
function _theme_get_logo()
{
    return get_field('logo', 'options');
}

/**
 * Retorna as redes sociais
 *
 * @return mixed|null
 */
function _theme_get_social_networks()
{
    return get_field('social_networks', 'options');
}

/**
 * Retorna o endereço
 *
 * @return mixed|null
 */
function _theme_get_address()
{
    return get_field('address', 'options');
}

/**
 * Esta função deverá ser utilizada para paginação via AJAX, onde é possível paginar qualquer
 * post_type e usar parâmetros de busca, taxonomies, authors e categorias como filtro.
 */
function _theme_load_more()
{
    $taxonomies = (isset($_POST['taxonomies']) ? ($_POST['taxonomies']) : null);
    $postType = (isset($_POST['postType']) ? sanitize_text_field($_POST['postType']) : 'post');
    $search = (isset($_POST['search']) ? sanitize_text_field($_POST['search']) : null);
    $paged = (isset($_POST['paged']) ? sanitize_text_field($_POST['paged']) : get_query_var('paged'));
    $catIds = (isset($_POST['catIds']) ? sanitize_text_field($_POST['catIds']) : null);
    $author = (isset($_POST['author']) ? sanitize_text_field($_POST['author']) : null);
    $perPage = (isset($_POST['per_page']) ? sanitize_text_field($_POST['per_page']) : get_option('posts_per_page'));

    $args = array(
        'paged' => $paged,
        'post_status' => 'publish',
        'post_type' => $postType,
        'posts_per_page' => $perPage,
    );

    if (null !== $catIds) {
        $args['cat'] = $catIds;
    }

    if (null !== $author) {
        $args['author'] = $author;
    }

    if (!empty($search)) {
        $args['s'] = $search;
    }

    if (!empty($taxonomies) && count($taxonomies)) {
        $taxQueries = array();
        foreach ($taxonomies as $taxonomy) {
            if (isset($taxQueries[$taxonomy['name']])) {
                $taxQueries[$taxonomy['name']]['terms'][] = $taxonomy['term_id'];
            } else {
                $taxQueries[$taxonomy['name']] = array(
                    'taxonomy' => $taxonomy['name'],
                    'field' => 'id',
                    'terms' => array($taxonomy['term_id']),
                );
            }
        }

        $args['tax_query'] = array_values($taxQueries);
    }

    $query = new WP_Query($args);
    $isFinished = $query->post_count > (get_option('posts_per_page') - 1);

    ob_start();
    if ($query->have_posts()) :
        while ($query->have_posts()) : $query->the_post(); ?>
        <!-- Insira o HTML do loop aqui -->
    <?php
        endwhile;
        $html = ob_get_clean();
    else :
        $html = '<p>Nenhum resultado encontrado</p>';
    endif;

    echo json_encode([
        'dataHtml' => $html,
        'isFinished' => $isFinished,
    ]);

    wp_reset_query();
    wp_die();
}
add_action('wp_ajax__theme_load_more', '_theme_load_more');
add_action('wp_ajax_nopriv__theme_load_more', '_theme_load_more');

/**
 * Realiza login do usuário assinante
 */
function _theme_user_login()
{
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : null;
    $password = isset($_POST['password']) ? sanitize_text_field($_POST['password']) : null;

    $user = wp_authenticate($email, $password);
    if (is_wp_error($user)) {
        wp_send_json_error($user->get_error_message(), 422);
        wp_die();
    }

    _theme_set_login($user);

    wp_send_json_success('Login realizado com sucesso');
    wp_die();
}
add_action('wp_ajax_nopriv__theme_user_login', '_theme_user_login');

/**
 * Realiza o logout do usuário assinante
 */
function _theme_user_logout()
{
    wp_logout();
    wp_send_json_success('Logout realizado com sucesso');
}
add_action('wp_ajax__theme_user_logout', '_theme_user_logout');

/**
 * Realiza o cadastro do usuário assinante
 */
function _theme_user_create()
{
    $errors = array();
    $fields = _theme_get_user_fields();
    foreach ($fields as $key => $field) {
        if (!isset($_POST[$key]) && empty($_POST[$key])) {
            $errors[$key] = "O campo $fields[$key] é obrigatório";
        }
        $$key = sanitize_text_field($_POST[$key]);
    }

    if (!empty($errors)) {
        wp_send_json_error($errors, 422);
        wp_die();
    }

    if ($password !== $passwordConfirmation) {
        wp_send_json_error(array('password' => 'Confirmação de Senha inválida'), 422);
        wp_die();
    }

    $userdata = array(
        'user_pass' => $password,
        'user_email' => $email,
        'user_login' => $email,
        'first_name' => $name,
        'display_name' => $name,
        'role' => 'subscriber',
    );

    $user = wp_insert_user($userdata);
    if (is_wp_error($user)) {
        wp_send_json_error(array('error' => $user->get_error_message()), 422);
        wp_die();
    }

    update_field('profession', $profession, "user_{$user}");
    update_field('crm', $crm, "user_{$user}");

    $user = get_user_by('email', $email);
    _theme_set_login($user);

    wp_send_json_success('Cadastro realizado com sucesso');
    wp_die();
}
add_action('wp_ajax_nopriv__theme_user_create', '_theme_user_create');

/**
 * Envia e-mail para recuperação de senha
 */
function _theme_forgot_password()
{
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : null;

    if (empty($email)) {
        wp_send_json_error(array('email' => 'E-mail é obrigatório'), 422);
        wp_die();
    } elseif (!strpos($email, '@')) {
        wp_send_json_error(array('email' => 'E-mail inválido'), 422);
        wp_die();
    }

    if (!$user = get_user_by('email', trim($email))) {
        wp_send_json_error(array('email' => 'E-mail não registrado'), 404);
        wp_die();
    }

    $key = get_password_reset_key($user);
    $message = __('Alguém solicitou que a senha fosse redefinida para a seguinte conta:') . "\r\n";
    $message .= sprintf(__('E-mail: %s'), $email) . "\r\n\r\n";
    $message .= __('Se isso foi um erro, apenas ignore este e-mail e nada acontecerá.') . "\r\n";
    $message .= __('Para alterar sua senha clique no link abaixo:') . "\r\n";
    $resetLink = get_field('user_reset_password', 'options');
    $message .= $resetLink . "?key={$key}&login=". rawurlencode($email) . "\r\n";

    $blogname = get_bloginfo('name');
    $title = sprintf(__('[%s] Recuperação de Senha'), $blogname);
    $title = apply_filters('retrieve_password_title', $title, $email, $user);
    $message = apply_filters('retrieve_password_message', $message, $key, $email, $user);

    if (!wp_mail($email, wp_specialchars_decode($title), $message)) {
        wp_send_json_error(array('email' => 'Serviço indisponível, por favor tente novamente mais tarde'), 503);
        wp_die();
    }

    wp_send_json_success('Você receberá um e-mail para recuperar sua senha');
    wp_die();
}
add_action('wp_ajax_nopriv__theme_forgot_password', '_theme_forgot_password');

/**
 * Reseta a senha do usuário
 */
function _theme_reset_password()
{
    $key = isset($_POST['key']) ? sanitize_text_field($_POST['key']) : null;
    $login = isset($_POST['login']) ? sanitize_email($_POST['login']) : null;
    $password = isset($_POST['password']) ? sanitize_text_field($_POST['password']) : null;
    $passwordConfirmation = isset($_POST['passwordConfirmation'])
        ? sanitize_text_field($_POST['passwordConfirmation'])
        : null
    ;

    if (!isset($password, $passwordConfirmation) || $password != $passwordConfirmation) {
        wp_send_json_error(array('password' => 'Senha inválida'), 422);
        wp_die();
    }

    $user = get_user_by('email', $login);
    $user = check_password_reset_key($key, $user->user_login);
    if (is_wp_error($user)) {
        wp_send_json_error(array('user' => $user->get_error_message()), 422);
        wp_die();
    }

    reset_password($user, $password);

    wp_send_json_success('Senha atualizada com sucesso. Você será redirecionado para fazer login com sua nova senha');
    wp_die();
}
add_action('wp_ajax_nopriv__theme_reset_password', '_theme_reset_password');

/**
 * Retorna todos os campos do formulário de cadastro do usuário
 *
 * @return string[]
 */
function _theme_get_user_fields()
{
    return array(
        'name' => 'Nome',
        'email' => 'E-mail',
        'phone' => 'Telefone',
        'profession' => 'Profissão',
        'crm' => 'Número do Conselho',
        'password' => 'Senha',
        'passwordConfirmation' => 'Confirmação de Senha',
    );
}

/**
 * Seta o usuário logado
 *
 * @param $user
 */
function _theme_set_login($user)
{
    clean_user_cache($user->ID);
    wp_clear_auth_cookie();

    wp_set_current_user($user->ID, $user->user_login);
    wp_set_auth_cookie($user->ID, false, is_ssl());
    update_user_caches($user);
}

/**
 * Adiciona as meta tags de compartilhamento do facebook no head
 */
function _theme_facebook_share_header()
{
    global $post;

    if (is_single()) {
        ?>
        <meta name="title" content="<?php echo get_the_title($post->ID); ?>" />
        <meta name="description" content="<?php echo get_the_excerpt($post->ID); ?>" />
        <?php if (has_post_thumbnail($post->ID)) : ?>
            <link rel="image_src" href="<?php echo get_the_post_thumbnail_url($post->ID); ?>" />
        <?php endif; ?>
        <?php
    }
}
add_action('wp_head', '_theme_facebook_share_header');

/**
 * Gera URL para compartilhamento do Facebook
 *
 * @param int $postId
 * @return string
 */
function _theme_generate_href_facebook_share(int $postId)
{
    $baseUrl = 'https://www.facebook.com/sharer.php?u=';
    $baseUrl .= urlencode(get_permalink($postId)) . '&t=' . urlencode(get_the_title($postId));

    return $baseUrl;
}
