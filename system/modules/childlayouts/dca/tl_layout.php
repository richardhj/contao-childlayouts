<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */


/**
 * Config
 */
$GLOBALS['TL_DCA']['tl_layout']['config']['onload_callback'][] = array('tl_layout_childLayouts', 'updatePalette');
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
	'eval'                  => array('submitOnChange'=>true, 'tl_class'=>'long'),
	'save_callback'         => array(array('tl_layout_childLayouts', 'checkIfChildPossible')),
	'sql'                   => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_layout']['fields']['parentLayout'] = array
(
	'label'                 => &$GLOBALS['TL_LANG']['tl_layout']['parentLayout'],
	'exclude'               => true,
	'inputType'             => 'select',
	'options_callback'      => array('tl_layout_childLayouts', 'getPossibleParentLayouts'),
	'eval'                  => array('chosen'=>true, 'submitOnChange'=>true, 'tl_class'=>'long'),
	'sql'                   => "int(10) unsigned NOT NULL default '0'",
);

$GLOBALS['TL_DCA']['tl_layout']['fields']['specificFields'] = array
(
	'label'                 => &$GLOBALS['TL_LANG']['tl_layout']['specificFields'],
	'exclude'               => true,
	'inputType'             => 'checkbox',
	'options_callback'      => array('tl_layout_childLayouts', 'getPalettes'),
	'eval'                  => array('multiple'=>true, 'submitOnChange'=>true, 'tl_class'=>'long'),
	'sql'                   => "blob NULL"
);


/**
 * Class tl_layout_childLayouts
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @copyright  Richard Henkenjohann 2015
 * @author     Richard Henkenjohann
 * @package    ChildLayouts
 */
class tl_layout_childLayouts extends Backend
{

	/**
	 * The original (unshortened) palette
	 *
	 * @var string
	 */
	protected $strOriginalPalette;


	/**
	 * The specific columns that must not be updated
	 */
	protected $arrSpecificColumns = array('id', 'pid', 'tstamp', 'name', 'fallback', 'isChild', 'parentLayout', 'specificFields');


	/**
	 * Set original palette
	 */
	public function __construct()
	{
		parent::__construct();

		$this->strOriginalPalette = $GLOBALS['TL_DCA']['tl_layout']['palettes']['default'];

		// Workaround for Contao >= 3.1
		if (version_compare(VERSION, '3.1', '>='))
		{
			if (!$GLOBALS['TL_DCA']['tl_layout']['originalPalettes']['default'])
			{
				$GLOBALS['TL_DCA']['tl_layout']['originalPalettes']['default'] = $GLOBALS['TL_DCA']['tl_layout']['palettes']['default'];
			}

			$this->strOriginalPalette = $GLOBALS['TL_DCA']['tl_layout']['originalPalettes']['default'];
		}
	}


	/**
	 * Check if parent layouts exist and child layouts are possible
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function checkIfChildPossible($varValue, DataContainer $dc)
	{
		if ($varValue)
		{
			$intPossibleParentLayouts = $this->Database->query("SELECT id FROM tl_layout WHERE isChild <> 1")
			                                           ->numRows;

			if ($intPossibleParentLayouts < 1)
			{
				throw new Exception($GLOBALS['TL_LANG']['ERR']['noPossibleParentLayouts']);
			}
		}

		return $varValue;
	}


	/**
	 * Return all page layouts grouped by theme
	 *
	 * @return array
	 */
	public function getPossibleParentLayouts()
	{
		/** @var Database\Result $objLayout */
		$objLayout = $this->Database->query("SELECT l.id, l.name, t.name AS theme FROM tl_layout l LEFT JOIN tl_theme t ON l.pid=t.id WHERE l.isChild <> 1 ORDER BY t.name, l.name");

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
	 * Return all palettes as options_callback
	 *
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
	 *
	 * @param DataContainer $dc
	 */
	public function updatePalette(DataContainer $dc)
	{
		// Get child layout
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
	 * Save child layout OR update all child layouts belonging to parent layout
	 *
	 * @param DataContainer $dc
	 */
	public function updateChildLayouts(DataContainer $dc)
	{
		// Is child layout
		if ($dc->activeRecord->isChild)
		{
			$objParentLayout = LayoutModel::findByPk($dc->activeRecord->parentLayout);
			$arrData = $objParentLayout->row();

			// Delete specific columns
			foreach ($this->arrSpecificColumns as $v)
			{
				unset($arrData[$v]);
			}

			if ($dc->activeRecord->specificFields)
			{
				$arrData = $this->deleteSpecificColumns(deserialize($dc->activeRecord->specificFields), $arrData, $dc->id);
			}

			// Update child layout
			$this->Database->prepare("UPDATE tl_layout %s WHERE id=?")
			               ->set($arrData)
			               ->execute($dc->id);
		}
		// Might be parent layout
		else
		{
			/** @var Database\Result $objChildLayouts */
			$objChildLayouts = $this->Database->prepare("SELECT id,isChild,parentLayout,specificFields FROM tl_layout WHERE id IN (SELECT id FROM tl_layout WHERE parentLayout=?)")
			                                  ->execute($dc->id);

			if ($objChildLayouts->numRows > 0)
			{
				$arrData = LayoutModel::findByPk($dc->id)->row(); # $dc->activeRecord->row() contains obsolete data

				// Delete specific columns
				foreach ($this->arrSpecificColumns as $v)
				{
					unset($arrData[$v]);
				}

				while ($objChildLayouts->next())
				{
					$arrRowData = $arrData;

					if ($objChildLayouts->specificFields)
					{
						$arrRowData = $this->deleteSpecificColumns(deserialize($objChildLayouts->specificFields), $arrData, $objChildLayouts->id);
					}

					// Update child layout
					$this->Database->prepare("UPDATE tl_layout %s WHERE id=?")
					               ->set($arrRowData)
					               ->execute($objChildLayouts->id);
				}
			}
		}
	}


	/**
	 * Delete specific columns
	 *
	 * @param array   $arrSpecificFields The specific boxes with its specific fields as comma separated string
	 * @param array   $arrData           The layout data set
	 * @param integer $intId             The layout's id
	 *
	 * @return array The data set written in database
	 */
	protected function deleteSpecificColumns($arrSpecificFields, $arrData, $intId)
	{
		$objFields = LayoutModel::findByPk($intId);
		$arrUnset = array();

		// Process specific fields
		foreach ($arrSpecificFields as $box)
		{
			$strBoxFields = substr(strstr($box, ','), 1);
			$arrUnset = array_merge($arrUnset, trimsplit(',', $strBoxFields));
		}

		// Fetch subpalettes fields
		foreach ($GLOBALS['TL_DCA'][$objFields->getTable()]['palettes']['__selector__'] as $name)
		{
			$trigger = $objFields->$name;

			if ($trigger != '' && in_array($name, $arrUnset))
			{
				if ($GLOBALS['TL_DCA'][$objFields->getTable()]['fields'][$name]['inputType'] == 'checkbox' && !$GLOBALS['TL_DCA'][$objFields->getTable()]['fields'][$name]['eval']['multiple'])
				{
					// Look for a subpalette
					if (strlen($GLOBALS['TL_DCA'][$objFields->getTable()]['subpalettes'][$name]))
					{
						$arrUnset = array_merge($arrUnset, trimsplit(',', $GLOBALS['TL_DCA'][$objFields->getTable()]['subpalettes'][$name]));
					}
				}
				else
				{
					$key = $name .'_'. $trigger;

					// Look for a subpalette
					if (strlen($GLOBALS['TL_DCA'][$objFields->getTable()]['subpalettes'][$key]))
					{
						$arrUnset = array_merge($arrUnset, trimsplit(',', $GLOBALS['TL_DCA'][$objFields->getTable()]['subpalettes'][$key]));
					}
				}
			}
		}

		// Delete fields
		foreach ($arrUnset as $unset)
		{
			unset($arrData[$unset]);
		}

		return $arrData;
	}
}
