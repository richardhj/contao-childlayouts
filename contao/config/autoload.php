<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
	'ChildLayouts',
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Library
	'ChildLayouts\Dca'    => 'system/modules/childlayouts/library/ChildLayouts/Dca.php',
	'ChildLayouts\Helper' => 'system/modules/childlayouts/library/ChildLayouts/Helper.php',
));
