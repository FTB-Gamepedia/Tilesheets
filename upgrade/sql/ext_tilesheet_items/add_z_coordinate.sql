/* Populate the column and set NOT NULL after. We don't want a default on this column. */
ALTER TABLE /*_*/ext_tilesheet_items ADD `z` int(11) NULL;
UPDATE /*_*/ext_tilesheet_items SET `z` = 0;
ALTER TABLE /*_*/ext_tilesheet_items MODIFY `z` int(11) NOT NULL;