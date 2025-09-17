<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Class Namespace
    |--------------------------------------------------------------------------
    |
    | This value sets the root namespace for Livewire component classes
    | in your application. This is typically "App\\Livewire" if you’re
    | following Laravel’s default structure.
    |
    */
    'class_namespace' => 'App\\Livewire',

    /*
    |--------------------------------------------------------------------------
    | View Path
    |--------------------------------------------------------------------------
    |
    | This is the directory where Livewire will look for component views.
    | By default it is "resources/views/livewire".
    |
    */
    'view_path' => resource_path('views/livewire'),

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | The Blade layout to wrap Livewire “page” components in.
    | For Breeze, this should point to resources/views/layouts/app.blade.php
    |
    */
    'layout' => 'layouts.app',

    /*
    |--------------------------------------------------------------------------
    | Temporary File Uploads
    |--------------------------------------------------------------------------
    |
    | Livewire uses temporary file storage for uploads before moving them
    | to permanent storage. Configure the disk and expiration here.
    |
    */
    'temporary_file_upload' => [
        'disk' => null,
        'rules' => null,
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => ['png', 'gif', 'bmp', 'svg', 'wav', 'mp4', 'mov', 'avi', 'wmv', 'mp3', 'm4a', 'jpg', 'jpeg', 'mpga'],
        'max_upload_time' => 5, // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy Model Binding
    |--------------------------------------------------------------------------
    |
    | Livewire v3 changed how route model binding works. If you need legacy
    | behaviour for older code, toggle this back on.
    |
    */
    'legacy_model_binding' => false,

    /*
    |--------------------------------------------------------------------------
    | Inject Assets
    |--------------------------------------------------------------------------
    |
    | Automatically include Livewire’s JavaScript and styles via @livewireStyles
    | and @livewireScripts in your app layout.
    |
    */
    'inject_assets' => true,

    /*
    |--------------------------------------------------------------------------
    | Pagination Theme
    |--------------------------------------------------------------------------
    |
    | Tailwind CSS is the default pagination view theme. You can also
    | use "bootstrap" if needed.
    |
    */
    'pagination_theme' => 'tailwind',
];
