<?php

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Hook para ejecutar la función al activar el plugin
register_activation_hook(__FILE__, 'crear_tabla_preferencias');

// Función para crear la tabla wp_user_preference_log
function crear_tabla_preferencias() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'user_preference_log'; // Añadir prefijo de la base de datos
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $tabla (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        field_name VARCHAR(255) NOT NULL,
        old_value TEXT,
        new_value TEXT,
        change_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX (user_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql); // Ejecutar la consulta para crear o actualizar la tabla
}
