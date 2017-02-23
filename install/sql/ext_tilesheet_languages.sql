CREATE TABLE IF NOT EXISTS /*_*/ext_tilesheet_languages (
  `entry_id` int(11) NOT NULL,
  `lang` varchar(10) NOT NULL,
  `display_name` varchar(100),
  `description` text,
  PRIMARY KEY (entry_id, lang)
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/entry_id ON /*_*/ext_tilesheet_languages(`entry_id`);