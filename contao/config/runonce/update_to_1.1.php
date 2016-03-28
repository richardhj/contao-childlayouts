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


class ChildLayoutRunOnce extends Controller
{
	public function run()
	{
		try
		{
			$objChildLayouts = LayoutModel::findBy(
				array(
					'isChild=1',
					'specificFields<>\'\'',
					'specificLegends=\'\''
				),
				array()
			);
		}
		catch (Exception $e)
		{
			return;
		}

		while (null !== $objChildLayouts && $objChildLayouts->next())
		{
			$arrFields = deserialize($objChildLayouts->specificFields);

			$arrLegends = array();

			foreach ($arrFields as $section)
			{
				preg_match('/\{([^\}]+?)\}/', $section, $matches);
				$arrLegends[] = strstr($matches[1], ':', true) ?: $matches[1];
			}

			$objChildLayouts->specificLegends = implode(',', $arrLegends);
			$objChildLayouts->save();
		}
	}
}

$objRunOnce = new ChildLayoutRunOnce();
$objRunOnce->run();
