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
 * Class Helper
 *
 * @package ChildLayouts
 */
class Helper
{

    /**
     * Update all child layouts belonging to parent layout
     *
     * @category onsubmit_callback
     *
     * @param DataContainer $dc
     */
    public function updateChildLayouts(DataContainer $dc)
    {
        if ($dc->activeRecord->isChild) {
            $parentLayout = LayoutModel::findByPk($dc->activeRecord->parentLayout);
            $data         = $parentLayout->row();

            // Delete columns that must not be overridden
            foreach (self::getColumnsToPersist() as $column) {
                unset($data[$column]);
            }

            // Delete columns specific for this child layout
            if ($dc->activeRecord->specificLegends) {
                $this->deleteSpecificColumns($dc->activeRecord->specificLegends, $data, $dc->activeRecord->row());
            }

            // Save parent data in database
            // Do not use the LayoutModel here
            Database::getInstance()->prepare("UPDATE tl_layout %s WHERE id=?")
                ->set($data)
                ->execute($dc->id);
        } else {
            $childLayouts = LayoutModel::findBy(
                [
                    'tl_layout.isChild=1',
                    'tl_layout.id IN (SELECT tl_layout.id FROM tl_layout WHERE tl_layout.parentLayout=?)'
                ],
                [$dc->id]
            );

            if (null !== $childLayouts) {
                $data = $dc->activeRecord->row();

                // Delete columns specific for each layout
                foreach (self::getColumnsToPersist() as $column) {
                    unset($data[$column]);
                }

                while ($childLayouts->next()) {
                    if ($childLayouts->specificLegends) {
                        $this->deleteSpecificColumns(
                            $childLayouts->specificLegends,
                            $data,
                            $childLayouts->row()
                        );
                    }

                    // Update child layout row
                    // Do not use the LayoutModel here
                    Database::getInstance()->prepare("UPDATE tl_layout %s WHERE id=?")
                        ->set($data)
                        ->execute($childLayouts->id);
                }
            }
        }
    }


    /**
     * Delete specific columns
     *
     * @param mixed $specificLegends
     * @param array $data     The parent layout's row
     * @param array $childRow The child layout's row
     */
    protected function deleteSpecificColumns($specificLegends, &$data, $childRow)
    {
        if (!is_array($specificLegends)) {
            $specificLegends = trimsplit(',', $specificLegends);
        }

        foreach ($specificLegends as $legend) {
            $palette = self::findLegendInOriginalPalette($legend);
            $fields  = trimsplit(',', $palette[3]);

            foreach ($fields as $field) {
                $config = $GLOBALS['TL_DCA']['tl_layout']['fields'][$field];

                // Delete field
                unset($data[$field]);

                // Delete orderField which is not part of the palette. See #5
                if ('fileTree' === $config['inputType'] && isset($config['eval']['orderField'])) {
                    unset($data[$config['eval']['orderField']]);
                }

                $keys = [
                    $field, // For deleting subpalette fields
                    $field . '_' . $childRow[$field] // For deleting subpalette fields with trigger
                ];

                // Delete subpalette fields
                foreach ($keys as $key) {
                    if (array_key_exists($key, $GLOBALS['TL_DCA']['tl_layout']['subpalettes'])) {
                        foreach (trimsplit(',', $GLOBALS['TL_DCA']['tl_layout']['subpalettes'][$key]) as $subfield) {
                            unset($data[$subfield]);
                        }
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
        $titleLegend         = self::findLegendInOriginalPalette('title_legend');
        $fieldsInTitleLegend = array_reduce(
            trimsplit(',', $titleLegend[3]),
            function ($carry, $field) {

                return array_merge(
                    $carry,
                    [$field],
                    trimsplit(',', $GLOBALS['TL_DCA']['tl_layout']['subpalettes'][$field])
                );
            },
            []
        );

        return array_merge(
            ['id', 'pid', 'tstamp'],
            $fieldsInTitleLegend
        );
    }


    /**
     * @param string $legendName The lengend's name. See example output for 'feed_legend' below:
     *
     * @return array[0] => {feed_legend:hide},newsfeeds,calendarfeeds;
     *              [1] => {feed_legend:hide}
     *              [2] => feed_legend
     *              [3] => newsfeeds,calendarfeeds
     */
    public static function findLegendInOriginalPalette($legendName)
    {
        // Find legend incl. brackets (#1) and associated fields (#3) by legend name (#2) in palette
        preg_match('/(\{(' . $legendName . ')[^\}]*?\})\,(.+?)\;/', Dca::getOriginalPalette(), $matches);

        return $matches;
    }
}
