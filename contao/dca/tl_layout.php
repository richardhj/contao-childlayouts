<?php

/**
 * This file is part of richardhj/contao-childlayouts.
 *
 * Copyright (c) 2013-2017 Richard Henkenjohann
 *
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2013-2017 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-childlayouts/blob/master/LICENSE LGPL-3.0
 */


/**
 * Config
 */
$GLOBALS['TL_DCA']['tl_layout']['config']['onload_callback'][]   = ['ChildLayouts\Dca', 'updatePalettes'];
$GLOBALS['TL_DCA']['tl_layout']['config']['onsubmit_callback'][] = ['ChildLayouts\Helper', 'updateChildLayouts'];


/**
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_layout']['palettes']['__selector__'][] = 'isChild';
$GLOBALS['TL_DCA']['tl_layout']['palettes']['default']        = str_replace(
    ';{header_legend}',
    ',isChild;{header_legend}',
    $GLOBALS['TL_DCA']['tl_layout']['palettes']['default']
);


/**
 * Subpalettes
 */
$GLOBALS['TL_DCA']['tl_layout']['subpalettes']['isChild'] = 'parentLayout,specificLegends';


/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_layout']['fields']['isChild'] = [
    'label'         => &$GLOBALS['TL_LANG']['tl_layout']['isChild'],
    'exclude'       => true,
    'inputType'     => 'checkbox',
    'eval'          => [
        'submitOnChange' => true,
        'tl_class'       => 'clr long'
    ],
    'save_callback' => [['ChildLayouts\Dca', 'checkIfChildPossible']],
    'sql'           => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_layout']['fields']['parentLayout'] = [
    'label'            => &$GLOBALS['TL_LANG']['tl_layout']['parentLayout'],
    'exclude'          => true,
    'inputType'        => 'select',
    'options_callback' => ['ChildLayouts\Dca', 'getPossibleParentLayouts'],
    'eval'             => [
        'chosen'         => true,
        'submitOnChange' => true,
        'tl_class'       => 'long'
    ],
    'sql'              => "int(10) unsigned NOT NULL default '0'"
];

$GLOBALS['TL_DCA']['tl_layout']['fields']['specificLegends'] = [
    'label'            => &$GLOBALS['TL_LANG']['tl_layout']['specificLegends'],
    'exclude'          => true,
    'inputType'        => 'checkbox',
    'options_callback' => ['ChildLayouts\Dca', 'getPalettes'],
    'eval'             => [
        'multiple'       => true,
        'csv'            => ',',
        'submitOnChange' => true,
        'tl_class'       => 'long'
    ],
    'sql'              => "varchar(255) NOT NULL default ''"
];
