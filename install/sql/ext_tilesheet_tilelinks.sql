CREATE TABLE IF NOT EXISTS /*_*/ext_tilesheet_tilelinks (
  `tl_from` int(10) UNSIGNED NOT NULL, /* Page ID */
  `tl_from_namespace` int(10) NOT NULL, /* Page namespace */
  `tl_to` int(11) NOT NULL /* Tile entry ID */
) /*$wgDBTableOptions*/ ;