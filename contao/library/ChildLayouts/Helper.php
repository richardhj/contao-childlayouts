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
 * Class Helper
 * @package ChildLayouts
 */
class Helper
{

	/**
	 * Update all child layouts belonging to parent layout
	 * @category onsubmit_callback
	 *
	 * @param \DataContainer $dc
	 */
	public function updateChildLayouts(\DataContainer $dc)
	{
		if ($dc->activeRecord->isChild)
		{
			/** @type \Model $objParentLayout */
			$objParentLayout = \LayoutModel::findByPk($dc->activeRecord->parentLayout);

			$arrData = $objParentLayout->row();

			// Delete columns that must not be overridden
			foreach (self::getColumnsToPersist() as $v)
			{
				unset($arrData[$v]);
			}

			// Delete columns specific for this child layout
			if ($dc->activeRecord->specificLegends)
			{
				$this->deleteSpecificColumns($dc->activeRecord->specificLegends, $arrData);
			}

			// Save parent data in database
			// Do not use the LayoutModel here
			\Database::getInstance()->prepare("UPDATE tl_layout %s WHERE id=?")
				->set($arrData)
				->execute($dc->id);
		}
		else
		{
			$objChildLayouts = \LayoutModel::findBy(
				array(
					'tl_layout.isChild=1',
					'tl_layout.id IN (SELECT tl_layout.id FROM tl_layout WHERE tl_layout.parentLayout=?)'
				),
				array($dc->id)
			);

			if (null !== $objChildLayouts)
			{
				$arrData = $dc->activeRecord->row();

				// Delete columns specific for each layout
				foreach (self::getColumnsToPersist() as $v)
				{
					unset($arrData[$v]);
				}

				while ($objChildLayouts->next())
				{
					if ($objChildLayouts->specificLegends)
					{
						$this->deleteSpecificColumns($objChildLayouts->specificLegends, $arrData);
					}

					// Update child layout row
					// Do not use the LayoutModel here
					\Database::getInstance()->prepare("UPDATE tl_layout %s WHERE id=?")
						->set($arrData)
						->execute($objChildLayouts->id);
				}
			}
		}
	}


	/**
	 * Delete specific columns
	 *
	 * @param mixed
	 * @param array
	 */
	protected function deleteSpecificColumns($arrSpecificLegends, &$arrData)
	{
		if (!is_array($arrSpecificLegends))
		{
			$arrSpecificLegends = trimsplit(',', $arrSpecificLegends);
		}

		foreach ($arrSpecificLegends as $legend)
		{
			$palette = self::findLegendInOriginalPalette($legend);
			$fields = trimsplit(',', $palette[3]);

			foreach ($fields as $field)
			{
				unset($arrData[$field]);

				// Delete subpalette fields too
				if (array_key_exists($field, $GLOBALS['TL_DCA']['tl_layout']['subpalettes']))
				{
					foreach (trimsplit(',', $GLOBALS['TL_DCA']['tl_layout']['subpalettes'][$field]) as $subfield)
					{
						unset($arrData[$subfield]);
					}
				}
			}
		}
	}


	/**
	 * Get all columns that must not overridden by the parent layout
	 *
	 * @return array
	 */
	public static function getColumnsToPersist()
	{
		$title_legend = self::findLegendInOriginalPalette('title_legend');

		$arrFieldsInTitleLegend = array_reduce(trimsplit(',', $title_legend[3]), function ($carry, $field)
		{

			return array_merge($carry, array($field), trimsplit(',', $GLOBALS['TL_DCA']['tl_layout']['subpalettes'][$field]));
		}, array());

		return array_merge
		(
			array('id', 'pid', 'tstamp'),
			$arrFieldsInTitleLegend
		);
	}


	/**
	 * @param string $strLegendName The lengend's name. See example output for 'feed_legend' below:
	 *
	 * @return array[0] => {feed_legend:hide},newsfeeds,calendarfeeds;
	 *              [1] => {feed_legend:hide}
	 *              [2] => feed_legend
	 *              [3] => newsfeeds,calendarfeeds
	 */
	public static function findLegendInOriginalPalette($strLegendName)
	{
		// Find legend incl. brackets (#1) and associated fields (#3) by legend name (#2) in palette
		preg_match('/(\{(' . $strLegendName . ')[^\}]*?\})\,(.+?)\;/', Dca::getOriginalPalette(), $matches);

		return $matches;
	}
}
