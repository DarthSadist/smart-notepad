<?php
/**
 * Smart Notepad functions and definitions
 */

if ( ! function_exists( 'smart_notepad_setup' ) ) :
    function smart_notepad_setup() {
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'custom-background', array( 'default-color' => 'f9fafb' ) ) ;
        add_theme_support( 'custom-logo' );
        register_nav_menus( array( 'menu-1' => 'Primary' ) );
    }
endif;
add_action( 'after_setup_theme', 'smart_notepad_setup' );

/**
 * УЛЬТИМАТИВНЫЙ ФИЛЬТР: ЗАМЕТКИ ВЕЗДЕ
 */
function sn_force_include_notes( $query ) {
    // Работаем только на фронтенде и только для главного запроса
    if ( ! is_admin() && $query->is_main_query() ) {
        
        // Получаем текущие типы постов (обычно это только 'post')
        $post_types = $query->get( 'post_type' );
        
        if ( ! $post_types ) {
            $post_types = array( 'post' );
        }
        
        if ( is_array( $post_types ) ) {
            if ( ! in_array( 'note', $post_types ) ) {
                $post_types[] = 'note';
            }
        } else {
            $post_types = array( $post_types, 'note' );
        }

        // Если это не одиночная страница (page), добавляем заметки
        if ( ! is_singular('page') ) {
            $query->set( 'post_type', $post_types );
        }
    }
}
add_action( 'pre_get_posts', 'sn_force_include_notes' );

/**
 * СБРОС КЭША ССЫЛОК ПРИ АКТИВАЦИИ
 */
add_action( 'after_switch_theme', 'flush_rewrite_rules' );

function smart_notepad_scripts() {
    wp_enqueue_style( 'smart-notepad-style', get_stylesheet_uri(), array(), '1.5' );
}
add_action( 'wp_enqueue_scripts', 'smart_notepad_scripts' );
