<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2011 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5.3
 * @copyright  Richard Henkenjohann 2013
 * @author     Richard Henkenjohann
 * @package    Language
 * @license    LGPL
 * @filesource
 */


/**
 * Config
 */
$GLOBALS['TL_DCA']['tl_layout']['config']['onload_callback'][] = array('tl_layout_childLayouts', 'updatePalettes');
$GLOBALS['TL_DCA']['tl_layout']['config']['onsubmit_callback'][] = array('tl_layout_childLayouts', 'updateChildLayouts');


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
$GLOBALS['TL_DCA']['tl_layout']['subpalettes']['isChild'] = 'parentLayout,specificFields';


/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_layout']['fields']['isChild'] = array
(
	'label'                 => &$GLOBALS['TL_LANG']['tl_layout']['isChild'],
	'exclude'               => true,
	'inputType'             => 'checkbox',
	'eval'                  => array('submitOnChange'=>true, 'tl_class'=>'w50 m12')
);

$GLOBALS['TL_DCA']['tl_layout']['fields']['parentLayout'] = array
(
	'label'                 => &$GLOBALS['TL_LANG']['tl_layout']['parentLayout'],
	'exclude'               => true,
	'inputType'             => 'select',
	'options_callback'      => array('tl_layout_childLayouts', 'getPossibleParentLayouts'),
	'eval'                  => array('chosen'=>true, 'submitOnChange'=>true, 'tl_class'=>'w50')
);

$GLOBALS['TL_DCA']['tl_layout']['fields']['specificFields'] = array
(
	'label'                 => &$GLOBALS['TL_LANG']['tl_layout']['specificFields'],
	'exclude'               => true,
	'inputType'             => 'checkbox',
	'options_callback'      => array('tl_layout_childLayouts', 'getPalettes'),
	'eval'                  => array('multiple'=>true, 'submitOnChange'=>true)
);


/**
 * Class tl_layout_childLayouts
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Richard Henkenjohann 2013
 * @author     Richard Henkenjohann
 * @package    Controller
 */
class tl_layout_childLayouts extends Backend
{

	/**
	 * The original (unshortened) palette
	 * @var string
	 */
	protected $strOriginalPalette;


	/**
	 * Set original palette
	 */
	public function __construct()
	{
		parent::__construct();
		$this->strOriginalPalette = $GLOBALS['TL_DCA']['tl_layout']['palettes']['default'];
	}


	/**
	 * Return all page layouts grouped by theme
	 * @return array
	 */
	public function getPossibleParentLayouts()
	{
		$objLayout = $this->Database->execute("SELECT l.id, l.name, t.name AS theme FROM tl_layout l LEFT JOIN tl_theme t ON l.pid=t.id WHERE l.isChild <> 1 ORDER BY t.name, l.name");

		if ($objLayout->numRows < 1)
		{
			return array();
		}

		$return = array();

		while ($objLayout->next())
		{
			$return[$objLayout->theme][$objLayout->id] = $objLayout->name;
		}

		return $return;
	}


	/**
	 * Return all palette sections
	 * @return array
	 */
	public function getPalettes()
	{
		// Split palettes in legends
		$arrPalettes = trimsplit(';', $this->strOriginalPalette);

		$return = array();

		foreach ($arrPalettes as $i=>$palette)
		{
			// Skip title legend
			if ($i == 0)
			{
				continue;
			}

			// Extract legend
			$legend = preg_split('/\{([^\}]+)\}/', $palette, -1, PREG_SPLIT_DELIM_CAPTURE);
			$legend = trimsplit(':', $legend[1]);
			$legend = $legend[0];

			$return[$palette] = $GLOBALS['TL_LANG']['tl_layout'][$legend];
		}

		return $return;
	}


	/**
	 * Shorten the child layout palettes
	 * @param DataContainer $dc
	 */
	public function updatePalettes(DataContainer $dc)
	{
		// Get child layout row
		$objChildLayout = $this->Database->prepare("SELECT isChild,parentLayout,specificFields FROM tl_layout WHERE id=?")
		                                 ->limit(1)
		                                 ->execute($dc->id);

		// Cancel if there is no need
		if (!$objChildLayout->isChild || !$objChildLayout->parentLayout)
		{
			return;
		}

		// Modify palettes by means of user settings
		$strTitleLegend = strstr($this->strOriginalPalette, ';', true);

		if (!$objChildLayout->specificFields)
		{
			$GLOBALS['TL_DCA']['tl_layout']['palettes']['default'] = $strTitleLegend;
		}
		else
		{
			$GLOBALS['TL_DCA']['tl_layout']['palettes']['default'] = $strTitleLegend . ';' . implode(';', deserialize($objChildLayout->specificFields));
		}
	}


	/**
	 * Update all child layouts belonging to parent layout
	 * @param DataContainer $dc
	 */
	public function updateChildLayouts(DataContainer $dc)
	{
		// Get child layouts
		$objChildLayouts = $this->Database->prepare("SELECT id,isChild,parentLayout,specificFields FROM tl_layout WHERE id IN (SELECT id FROM tl_layout WHERE parentLayout=?)")
		                                  ->execute($dc->id);

		if ($objChildLayouts->numRows > 0)
		{
			// Get parent layout row
			$objParentLayout = $this->Database->prepare("SELECT * FROM tl_layout WHERE isChild <> 1 AND id=?")
			                        ->limit(1)
			                        ->execute($dc->id);

			$arrData = $objParentLayout->row();

			// Delete specific columns
			unset($arrData['id']);
			unset($arrData['pid']);
			unset($arrData['tstamp']);
			unset($arrData['name']);
			unset($arrData['fallback']);
			unset($arrData['isChild']);
			unset($arrData['parentLayout']);
			unset($arrData['specificFields']);

			while ($objChildLayouts->next())
			{
				if ($objChildLayouts->specificFields)
				{
					foreach (deserialize($objChildLayouts->specificFields) as $value)
					{
						$values = substr(strstr($value, ','), 1);
						$values = trimsplit(',', $values);

						foreach ($values as $field)
						{
							unset($arrData[$field]);
						}
					}
				}

				// Update child layout row
				$this->Database->prepare("UPDATE tl_layout %s WHERE id=?")
				               ->set($arrData)
				               ->execute($dc->id);
			}
		}
	}
}
