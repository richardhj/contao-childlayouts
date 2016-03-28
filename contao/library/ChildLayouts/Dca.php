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

namespace ChildLayouts;


/**
 * Class Dca
 * @package ChildLayouts
 */
class Dca
{

	/**
	 * The original tl_layout's palette
	 *
	 * @var string
	 */
	protected static $strOriginalPalette;


	/**
	 * @return string
	 */
	static public function getOriginalPalette()
	{
		if (!self::$strOriginalPalette)
		{
			self::setOriginalPalette($GLOBALS['TL_DCA']['tl_layout']['palettes']['default']);
		}
		
		return self::$strOriginalPalette;
	}


	/**
	 * @param string $strPalette
	 */
	static public function setOriginalPalette($strPalette)
	{
		self::$strOriginalPalette = $strPalette;
	}


	/**
	 * Shorten the child layout's palette
	 * @category onload_callback
	 *
	 * @param \DataContainer $dc
	 */
	public function updatePalettes(\DataContainer $dc)
	{
		/** @type \Model $objLayout */
		$objLayout = \LayoutModel::findByPk($dc->id);

		// Cancel if we don't have a child layout here
		if (!$objLayout->isChild || !$objLayout->parentLayout)
		{
			return;
		}

		// Set original palette before changing it
		self::setOriginalPalette($GLOBALS['TL_DCA']['tl_layout']['palettes']['default']);

		// Modify palettes by means of user settings
		$strTitleLegend = strstr(static::$strOriginalPalette, ';', true);

		if (!$objLayout->specificLegends)
		{
			$GLOBALS['TL_DCA']['tl_layout']['palettes']['default'] = $strTitleLegend;
		}
		else
		{
			$GLOBALS['TL_DCA']['tl_layout']['palettes']['default'] = $strTitleLegend;

			foreach (trimsplit(',', $objLayout->specificLegends) as $legend)
			{
				$legend_details = Helper::findLegendInOriginalPalette($legend);
				
				$GLOBALS['TL_DCA']['tl_layout']['palettes']['default'] .= ';' . $legend_details[1] .','. $legend_details[3];
			}
		}
	}


	/**
	 * Return all palette sections excluding the title_legend
	 * @category options_callback
	 *
	 * @return array
	 */
	public function getPalettes()
	{
		preg_match_all('/\{([^\}]+?)\}/', static::$strOriginalPalette, $matches);

		// Remove potential ':hide' from all legends
		$legends = array_map(function ($legend)
		{
			return strstr($legend, ':', true) ?: $legend;

		}, $matches[1]);

		// Remove title legend
		unset($legends[array_search('title_legend', $legends)]);

		// Add legend translations to complete options array
		return array_combine($legends, array_map(function ($legend)
		{
			return $GLOBALS['TL_LANG']['tl_layout'][$legend];

		}, $legends));
	}


	/**
	 * Return all parent layouts grouped by theme
	 * @category options_callback
	 *           
	 * @return array
	 */
	public function getPossibleParentLayouts()
	{
		$objLayout = \Database::getInstance()->query("SELECT l.id, l.name, t.name AS theme FROM tl_layout l LEFT JOIN tl_theme t ON l.pid=t.id WHERE l.isChild <> 1 ORDER BY t.name, l.name");

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
	 * Check if parent layouts may available for one child layout
	 * @category save_callback
	 * 
	 * @param mixed $varValue
	 * @param \DataContainer $dc
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function checkIfChildPossible($varValue, \DataContainer $dc)
	{
		if ($varValue)
		{
			/** @noinspection PhpUndefinedMethodInspection */
			$intPossibleParentLayouts = \LayoutModel::countBy(array(
				'isChild<>1'
			));

			if ($intPossibleParentLayouts < 1)
			{
				throw new \Exception($GLOBALS['TL_LANG']['ERR']['noPossibleParentLayouts']);
			}
		}

		return $varValue;
	}
}
