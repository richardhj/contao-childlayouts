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
 * Register the namespaces
 */
ClassLoader::addNamespaces(
    [
        'ChildLayouts',
    ]
);


/**
 * Register the classes
 */
ClassLoader::addClasses(
    [
        // Library
        'ChildLayouts\Dca'    => 'system/modules/childlayouts/library/ChildLayouts/Dca.php',
        'ChildLayouts\Helper' => 'system/modules/childlayouts/library/ChildLayouts/Helper.php',
    ]
);
