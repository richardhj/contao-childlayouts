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

namespace ChildLayouts;

use Contao\Database;
use Contao\DataContainer;
use Contao\LayoutModel;


/**
 * Class Dca
 *
 * @package ChildLayouts
 */
class Dca
{

    /**
     * The original tl_layout's palette
     *
     * @var string
     */
    protected static $originalPalette;


    /**
     * @return string
     */
    static public function getOriginalPalette()
    {
        if (!self::$originalPalette) {
            self::setOriginalPalette($GLOBALS['TL_DCA']['tl_layout']['palettes']['default']);
        }

        return self::$originalPalette;
    }


    /**
     * @param string $strPalette
     */
    static public function setOriginalPalette($strPalette)
    {
        self::$originalPalette = $strPalette;
    }


    /**
     * Shorten the child layout's palette
     *
     * @category onload_callback
     *
     * @param DataContainer $dc
     */
    public function updatePalettes(DataContainer $dc)
    {
        $layout = LayoutModel::findByPk($dc->id);
        if (!$layout->isChild || !$layout->parentLayout) {
            return;
        }

        // Set original palette before changing it
        self::setOriginalPalette($GLOBALS['TL_DCA']['tl_layout']['palettes']['default']);

        $titleLegend = strstr(static::$originalPalette, ';', true);

        if (!$layout->specificLegends) {
            $GLOBALS['TL_DCA']['tl_layout']['palettes']['default'] = $titleLegend;
        } else {
            $GLOBALS['TL_DCA']['tl_layout']['palettes']['default'] = $titleLegend;

            foreach (trimsplit(',', $layout->specificLegends) as $legend) {
                $legendDetails = Helper::findLegendInOriginalPalette($legend);

                $GLOBALS['TL_DCA']['tl_layout']['palettes']['default'] .= ';' . $legendDetails[1] . ','
                                                                          . $legendDetails[3];
            }
        }
    }


    /**
     * Return all palette sections excluding the title_legend
     *
     * @category options_callback
     *
     * @return array
     */
    public function getPalettes()
    {
        preg_match_all('/\{([^\}]+?)\}/', static::$originalPalette, $matches);

        // Remove potential ':hide' from all legends
        $legends = array_map(
            function ($legend) {
                return strstr($legend, ':', true) ?: $legend;
            },
            $matches[1]
        );

        // Remove title legend
        unset($legends[array_search('title_legend', $legends)]);

        // Add legend translations to complete options array
        return array_combine(
            $legends,
            array_map(
                function ($legend) {
                    return $GLOBALS['TL_LANG']['tl_layout'][$legend];

                },
                $legends
            )
        );
    }


    /**
     * Return all parent layouts grouped by theme
     *
     * @category options_callback
     *
     * @return array
     */
    public function getPossibleParentLayouts()
    {
        $return = [];
        $layout = Database::getInstance()
            ->query(
                "SELECT l.id, l.name, t.name AS theme FROM tl_layout l LEFT JOIN tl_theme t ON l.pid=t.id WHERE l.isChild <> 1 ORDER BY t.name, l.name"
            );

        if ($layout->numRows < 1) {
            return [];
        }

        while ($layout->next()) {
            $return[$layout->theme][$layout->id] = $layout->name;
        }

        return $return;
    }


    /**
     * Check if parent layouts may available for one child layout
     *
     * @category save_callback
     *
     * @param mixed $value
     *
     * @return mixed
     * @throws \Exception
     * @internal param DataContainer $dc
     */
    public function checkIfChildPossible($value)
    {
        if ($value) {
            $possibleParentLayouts = LayoutModel::countBy(['isChild<>1']);
            if ($possibleParentLayouts < 1) {
                throw new \Exception($GLOBALS['TL_LANG']['ERR']['noPossibleParentLayouts']);
            }
        }

        return $value;
    }
}
