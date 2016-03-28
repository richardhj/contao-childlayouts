<?php
/**
 * ChildLayouts extension for Contao Open Source CMS gives you the possibility to modify only certain layout sections
 * by defining one parent layout all other settings are inherited from.
 *
 * Copyright (c) 2016 Richard Henkenjohann
 *
 * @package ChildLayouts
 * @author  Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 */


/**
 * Config
 */
$GLOBALS['TL_DCA']['tl_layout']['config']['onload_callback'][] = array('ChildLayouts\Dca', 'updatePalettes');
$GLOBALS['TL_DCA']['tl_layout']['config']['onsubmit_callback'][] = array('ChildLayouts\Helper', 'updateChildLayouts');


/**
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_layout']['palettes']['__selector__'][] = 'isChild';
$GLOBALS['TL_DCA']['tl_layout']['palettes']['default'] = str_replace
(
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
$GLOBALS['TL_DCA']['tl_layout']['fields']['isChild'] = array
(
	'label'         => &$GLOBALS['TL_LANG']['tl_layout']['isChild'],
	'exclude'       => true,
	'inputType'     => 'checkbox',
	'eval'          => array('submitOnChange' => true, 'tl_class' => 'long'),
	'save_callback' => array(array('ChildLayouts\Dca', 'checkIfChildPossible')),
	'sql'           => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_layout']['fields']['parentLayout'] = array
(
	'label'            => &$GLOBALS['TL_LANG']['tl_layout']['parentLayout'],
	'exclude'          => true,
	'inputType'        => 'select',
	'options_callback' => array('ChildLayouts\Dca', 'getPossibleParentLayouts'),
	'eval'             => array('chosen' => true, 'submitOnChange' => true, 'tl_class' => 'long'),
	'sql'              => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_layout']['fields']['specificLegends'] = array
(
	'label'            => &$GLOBALS['TL_LANG']['tl_layout']['specificLegends'],
	'exclude'          => true,
	'inputType'        => 'checkbox',
	'options_callback' => array('ChildLayouts\Dca', 'getPalettes'),
	'eval'             => array('multiple' => true, 'csv' => ',', 'submitOnChange' => true, 'tl_class' => 'long'),
	'sql'              => "varchar(255) NOT NULL default ''"
);
