-- ********************************************************
-- *                                                      *
-- * IMPORTANT NOTE                                       *
-- *                                                      *
-- * Do not import this file manually but use the Contao  *
-- * install tool to create and maintain database tables! *
-- *                                                      *
-- ********************************************************


-- --------------------------------------------------------

--
-- Table `tl_layout`
--

CREATE TABLE `tl_layout` (
  `isChild` char(1) NOT NULL default '',
  `parentLayout` int(10) unsigned NOT NULL default '0',
  `specificFields` blob NULL,
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
