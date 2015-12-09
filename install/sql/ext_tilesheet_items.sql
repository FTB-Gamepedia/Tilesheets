CREATE TABLE IF NOT EXISTS /*_*/ext_tilesheet_items (
  `entry_id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `item_name` varchar(100) NOT NULL,
  `mod_name` varchar(10) NOT NULL,
  `x` int(11) NOT NULL,
  `y` int(11) NOT NULL
) /*$wgDBTableOptions*/ ;

CREATE INDEX /*i*/item_name ON /*_*/ext_tilesheet_items (`item_name`);
CREATE INDEX /*i*/mod_name ON /*_*/ext_tilesheet_items (`mod_name`);
