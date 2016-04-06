CREATE TABLE IF NOT EXISTS ext_tilesheet_languages (
  `entry_id` int(11) NOT NULL,
  `lang` varchar(10) DEFAULT 'en',
  `display_name` varchar(100) NOT NULL,
  `description` varchar(100) NOT NULL
);
/* Populate language table with IDs and default display names. */
INSERT INTO ext_tilesheet_languages (entry_id, display_name)
SELECT entry_id, item_name FROM ext_tilesheet_items;