<?php

return [
    'products' => [
        'label'           => 'EPD Products',
        'command'         => 'mmc:import-products',
        'path_option'     => '--path',
        'dataset_option'  => null,
        'supports_reset'  => true,
        'extra_args'      => [],
    ],

    'viability' => [
        'label'           => 'Viability Rules',
        'command'         => 'mmc:import-viability-rules',
        'path_option'     => '--path',
        'dataset_option'  => '--dataset-version',
        'supports_reset'  => true,
        'extra_args'      => [],
    ],

    'environmental' => [
        'label'           => 'Environmental Layers',
        'command'         => 'mmc:import-env-layers',
        'path_option'     => '--path',
        'dataset_option'  => '--dataset-version',
        'supports_reset'  => true,
        'extra_args'      => [
            '--sheet' => 'Wall Systems', // default; UI can override
        ],
    ],

    'manufacturers' => [
        'label'           => 'Manufacturers',
        'command'         => 'mmc:import-manufacturers',
        'path_option'     => '--path',
        'dataset_option'  => null,
        'supports_reset'  => true,
        'extra_args'      => [],
    ],

    'counties' => [
        'label'           => 'Counties',
        'command'         => 'mmc:import-counties',
        'path_option'     => '--path',
        'dataset_option'  => null,
        'supports_reset'  => false,
        'extra_args'      => [],
    ],
];
