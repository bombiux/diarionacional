<?php

namespace App\Setup;

class CustomPostTypes
{
    public function __construct()
    {
        add_action('init', [$this, 'registerGaleriaCpt']);
    }

    public function registerGaleriaCpt()
    {
        $labels = [
            'name'                  => 'Galerías',
            'singular_name'         => 'Galería',
            'menu_name'             => 'Galerías',
            'name_admin_bar'        => 'Galería',
            'add_new'               => 'Añadir Nueva',
            'add_new_item'          => 'Añadir Nueva Galería',
            'new_item'              => 'Nueva Galería',
            'edit_item'             => 'Editar Galería',
            'view_item'             => 'Ver Galería',
            'all_items'             => 'Todas las Galerías',
            'search_items'          => 'Buscar Galerías',
            'parent_item_colon'     => 'Galerías Padre:',
            'not_found'             => 'No se encontraron galerías.',
            'not_found_in_trash'    => 'No se encontraron galerías en la papelera.',
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => ['slug' => 'galerias'],
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'menu_icon'          => 'dashicons-format-gallery',
            'supports'           => ['title'],
            'show_in_rest'       => true,
        ];

        register_post_type('galeria', $args);
    }
}
