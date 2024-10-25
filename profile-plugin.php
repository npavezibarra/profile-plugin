<?php
/*
Plugin Name: My Profile Plugin
Description: Un plugin personalizado para añadir una página de perfil privada con preferencias y productos.
Version: 1.0
Author: Nicolás Pavez
*/

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Hook para crear la página de perfil
add_action('init', 'crear_pagina_mi_perfil');
function crear_pagina_mi_perfil() {
    // Comprobar si la página ya existe
    $pagina = get_page_by_path('profile');
    if (!$pagina) {
        wp_insert_post(array(
            'post_title' => 'Profile',
            'post_content' => '[profile]', // Shortcode que insertaremos más adelante
            'post_status' => 'publish',
            'post_type' => 'page'
        ));
    }
}

// Registrar scripts y estilos
add_action('wp_enqueue_scripts', 'mi_perfil_plugin_scripts');
function mi_perfil_plugin_scripts() {
    wp_enqueue_style('profile-css', plugins_url('profile.css', __FILE__));
    wp_enqueue_script('profile-js', plugins_url('profile.js', __FILE__), array('jquery'), '', true);

    // Localizar el script para definir ajaxurl
    wp_localize_script('profile-js', 'ajaxurl', admin_url('admin-ajax.php'));
}

/* SHORTCODE */
add_shortcode('profile', 'mostrar_pagina_mi_perfil');
function mostrar_pagina_mi_perfil() {
    ob_start();
    ?>
    <div class="mi-perfil-contenedor">
        <ul class="mi-perfil-tabs">
            <li><a href="#escritos">Mis Escritos</a></li>
            <li><a href="#preferencias">Mis Preferencias</a></li>
            <li><a href="#productos">Mis Productos</a></li>
        </ul>
        <div id="escritos" class="tab-content">
            <?php echo mostrar_mis_escritos(); ?>
        </div>
        <div id="preferencias" class="tab-content">
            <?php echo mostrar_mis_preferencias(); ?>
        </div>
        <div id="productos" class="tab-content">
            <?php echo mostrar_mis_productos(); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function mostrar_mis_escritos() {
    $user_id = get_current_user_id();
    $args = array(
        'author' => $user_id,
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 10
    );
    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        echo '<ul>';
        while ($query->have_posts()) {
            $query->the_post();
            echo '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
        }
        echo '</ul>';
    } else {
        echo 'No has escrito ningún post aún.';
    }
    wp_reset_postdata();
}

function mostrar_mis_preferencias() {
    $user_id = get_current_user_id();
    $political_preference = get_user_meta($user_id, 'political_preference', true);
    $is_public = get_user_meta($user_id, 'political_preference_public', true);
    
    ?>
    <form id="mis-preferencias-form" method="POST">
        <label for="political_preference">Rango Político (Izquierda - Derecha):</label>
        <input type="range" id="political_preference" name="political_preference" min="0" max="100" value="<?php echo esc_attr($political_preference ? $political_preference : 50); ?>">
        
        <label for="is_public">Hacer visible en mi perfil:</label>
        <input type="checkbox" id="is_public" name="is_public" <?php checked($is_public, 'yes'); ?>>
        
        <button type="submit">Guardar Preferencias</button>
    </form>
    <?php
}

function mostrar_mis_productos() {
    $user_id = get_current_user_id();
    $args = array(
        'author' => $user_id,
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => 10
    );
    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        echo '<ul>';
        while ($query->have_posts()) {
            $query->the_post();
            echo '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
        }
        echo '</ul>';
    } else {
        echo 'No tienes productos aún.';
    }
    wp_reset_postdata();
}

// Manejar la solicitud AJAX para guardar preferencias
add_action('wp_ajax_guardar_preferencias', 'guardar_preferencias_usuario_ajax');
add_action('wp_ajax_nopriv_guardar_preferencias', 'guardar_preferencias_usuario_ajax');

function guardar_preferencias_usuario_ajax() {
    // Verificar si el usuario está conectado
    if (!is_user_logged_in()) {
        wp_send_json_error('No estás logeado.');
        wp_die();
    }

    $user_id = get_current_user_id();
    
    // Verificar que las claves existan en $_POST
    if (isset($_POST['political_preference']) && isset($_POST['is_public'])) {
        $political_preference = intval($_POST['political_preference']); // Sanitizar como número entero
        $is_public = $_POST['is_public'] === 'yes' ? 'yes' : 'no';

        // Actualizar y registrar cambios
        actualizar_preferencia($user_id, 'political_preference', $political_preference);
        actualizar_preferencia($user_id, 'political_preference_public', $is_public);

        wp_send_json_success('Preferencias guardadas.');
    } else {
        wp_send_json_error('Faltan datos.');
    }
    wp_die();
}

function actualizar_preferencia($user_id, $field_name, $new_value) {
    $old_value = get_user_meta($user_id, $field_name, true);
    
    if ($old_value != $new_value) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'user_preference_log', // Usar prefijo para tablas
            array(
                'user_id' => $user_id,
                'field_name' => $field_name,
                'old_value' => $old_value,
                'new_value' => $new_value,
                'change_date' => current_time('mysql')
            )
        );
        update_user_meta($user_id, $field_name, $new_value);
    }
}


// CREAR TABLA LOG USER PREFERENCES //

// Incluir el archivo create-table.php
require_once plugin_dir_path(__FILE__) . 'create-table.php';

// Hook para ejecutar la función al activar el plugin
register_activation_hook(__FILE__, 'crear_tabla_preferencias');
