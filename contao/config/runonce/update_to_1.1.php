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


class ChildLayoutRunOnce
{
    public function run()
    {
        try {
            $objChildLayouts = LayoutModel::findBy(
                [
                    'isChild=1',
                    'specificFields<>\'\'',
                    'specificLegends=\'\''
                ],
                []
            );
        } catch (Exception $e) {
            return;
        }

        while (null !== $objChildLayouts && $objChildLayouts->next()) {
            $fields  = deserialize($objChildLayouts->specificFields);
            $legends = [];

            foreach ($fields as $section) {
                preg_match('/\{([^\}]+?)\}/', $section, $matches);
                $legends[] = strstr($matches[1], ':', true) ?: $matches[1];
            }

            $objChildLayouts->specificLegends = implode(',', $legends);
            $objChildLayouts->save();
        }
    }
}

$childLayoutsRunOnce = new ChildLayoutRunOnce();
$childLayoutsRunOnce->run();
