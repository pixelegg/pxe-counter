<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Counter (animated number)',
    'description' => 'Animated number counter content element: counts up when scrolled into view, locale-aware, accessible, no dependencies. With a backend overview of all counters.',
    'category' => 'fe',
    'author' => 'pixelegg',
    'author_email' => 'info@pixelegg.de',
    'author_company' => 'pixelegg Informatik & Design GmbH',
    'state' => 'stable',
    'version' => '2.2.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.99.99',
            'php' => '8.2.0-8.4.99',
            'fluid_styled_content' => '13.4.0-14.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
