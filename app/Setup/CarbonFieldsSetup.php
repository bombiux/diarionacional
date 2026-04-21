<?php

namespace App\Setup;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

class CarbonFieldsSetup
{
    public function __construct()
    {
        add_action('carbon_fields_register_fields', [$this, 'registerGaleriaMeta']);
        add_action('carbon_fields_register_fields', [$this, 'registerUserMeta']);
        add_action('after_setup_theme', [$this, 'bootCarbonFields']);
    }

    public function registerUserMeta()
    {
        Container::make('user_meta', 'Información Adicional')
            ->add_fields([
                Field::make('image', 'crb_user_avatar', 'Foto de Perfil')
                    ->set_value_type('url')
            ]);
    }

    public function registerGaleriaMeta()
    {
        Container::make('post_meta', 'Imágenes de la Galería')
            ->where('post_type', '=', 'galeria')
            ->add_fields([
                Field::make('rich_text', 'crb_gallery_description', 'Descripción de la Galería'),
                Field::make('media_gallery', 'crb_media_gallery', 'Seleccione las imágenes')
                    ->set_type(['image'])
            ]);
    }

    public function bootCarbonFields()
    {
        \Carbon_Fields\Carbon_Fields::boot();
    }
}
