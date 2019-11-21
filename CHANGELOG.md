# Changelog
This changelog only shows recent version history, because of the lack of documentation from the former maintainers. The very first changelog (1.1.2) is likely incomplete.

## Version 4
### 4.2.3
* Fix API descriptions/summaries (#95).

### 4.2.2
* Fix HTML entities breaking input forms on TileTranslator and TileManager (#91)
* Update for MediaWiki 1.32/1.33:
  * Updates for ApiBase::dieUsage (#94, #93) (chaud)
  * Update Special:ViewTile for MediaWiki 1.32/1.33 (#92)
* Do not try to array_unique or foreach null. (#88) (Alexia E. Smith)
* Use addIndex for add_primary_key schema update, fixes failure when running update.php (#87) (Mischanix)
* Fix GrantPermissions. (#86) (Alexia E. Smith)

### 4.2.1
* Improve grammatical and formatting stuff on WhatUsesThisTile. Proper quotations, periods, colons, and bolding to match WhatLinksHere.
* Add pagination stuff to the bottom of WhatUsesThisTile.
* Fix WhatUsesThisTile list missing actual `ul` element (and thus looking very weird)
* Add missing localization for whatusesthistile on Special:Specialpages
* Fix `current() expects parameter 1 to be array, null given` error (#83).

### 4.2.0
* Internationalization improvements (#52, #47)
  * "Query returned empty set" message now internationalized
  * SheetList, TileList, CreateTileSheet now completely internationalized
  * API help messages now internationalized
  * Warnings/errors/notices/log messages (in the edit window) now internationalized
* SheetList edit column now hidden when not accessible, rather than just empty (#73)
* TileList edit column and translate column now hidden when accessible, rather than just empty (#70)
* Fix `TypeError` when trying to use the "overwrite existing" option in CreateTileSheet (#74)
* New tile backlinks functionality (#66, #78)
  * New table, `ext_tilesheet_tilelinks`, that is updated when pages are modified. It contains page and tile ID information, like the `backlinks` table native to MediaWiki.
  * New special page, WhatUsesThisTile, which uses subpage syntax like ViewTile. It is basically WhatLinksHere without the filtering options. It links back to the ViewTile page.
  * ViewTile (hackily) displays a WhatUsesThisTile link in the sidebar tools section.
  * New list query API for getting tile backlinks.
* Fix type in Version special page so that it is correctly put into the parser hooks section (#77)
* Update outdated reference to "wiki staff" (#68)

### 4.1.0
* New Item Viewer special page Special:ViewTile/ID ($62, #67)
  * Displays a table of the item for each size that it is registered for.
* Missing special page alias file. (#65) (Alexia E. Smith)

### 4.0.1
* Improve tile searching code in TileList.
  * Fix blank-regex searching issue. When mod, formattedEntryIDs, or regex are empty, instead of doing `whatever = ''`, the condition is simply omitted. This fixes the issue where it was searching for regex `//` instead of `item_name = ''` (#57 and #58).

### 4.0.0
* Add ability to grant permissions to bots (for Special:BotPasswords thing on Gamepedia) (#55) (Alexia E. Smith)
* SheetList filtering improvement (#53 and #49):
  * Remove prefix filtering
* TileList filtering improvements (#53 and #49):
  * Remove "Filter by prefix"
  * Add regex searching, which falls back to standard string searching.
  * Add language filter with an invert selection box.
  * Add searching after a certain entry ID.
* addtile -> addtiles, which allows for importing many tiles at once (#51 and #39)
  * This is a breaking change to the API!
  * Specify tiles to add using the format "X Y Name|X Y Name|X Y Name" in the import parameter.
  * API no longer has name, x, and y parameters.
  * Return value is in the "addtiles" object, not the "addtile" object.
* Set entry_id and lang as a composite primary key for lang table (#50 and #48)

## Version 3
### 3.3.1
* Improve easily fixable mod parameter log warning to improve item name.

### 3.3.0
* New tracking categories:
  * Pages using an invalid tilesheet
  * Pages using an incorrect tilesheet size
  * Pages using a missing tilesheet image
  * Pages with a missing tile name
  * Pages with a missing mod parameter
  * Pages with an easily fixable missing mod parameter

### 3.2.0
* New translatetiles right.

### 3.1.2
* Fix potential SQL injection in query tiles API (#38).

### 3.1.1
* Fix a typo on the delete tiles API documentation.
* Return entry ID as a field rather than the key in list=tiles (#35).
* Deleting tiles through the TileManager no longer results in 2 log entries (37).

### 3.1.0
* Use query continue API for sheet and tile query APIs.
* Fix entry_id and language returning null in translation query API.
* lang is no longer a required parameter for translation query API.

### 3.0.2
* Description row is now `test` instead of `varchar` (#29, PR #32)
* Add original item name (from items table) to the translation log (#31).
* Return empty string when type is not name, and the entry does not exist (#30).

### 3.0.1
* Add the ext_tilesheet_languages table on schema update.

### 3.0.0
* Tiles can now be translated (Issue #8, Pull Request #28).
  * New table in the database ext_tilesheet_languages that has 4 rows: `entry_id`, `lang`, `display_name`, and `description`
  * New special page TileTranslator where translators can translate tilesheet icon display names and descriptions for their languages. This will create a log entry.
    * Default display names and descriptions can be added by translating to `en`. This will override the default functionality, which can be useful for things like Clay (Item) which should be shown as simply Clay.
  * New `#iconloc` parser function to get the localized name or description for a tilesheet item. It takes 4 arguments: item name, mod abbreviation, type (name or description), and language code.


## Version 2
### 2.0.2
* Fix parameter typo in deletesheet API causing it to do absolutely nothing (#24).

### 2.0.1
* Fix autoloading through the extension JSON (PR #20).
* Update various method and variable calls, which might resolve issues with some special pages (PR #18).
* Fix typo in editsheet API's return value, 'edittile' is now 'editsheet' (#17).
* Fix API deletesheet's return value; no longer returns empty array (#16).

### 2.0.0
* Implement a web API for the extension with the following new actions (issue #2 PR #15):
  * action=query&list=tilesheets
  * action=query&list=tiles
  * action=editsheet
  * action=addtile
  * action=createsheet
  * action=deletetiles
  * action=edittile

## Version 1
### 1.1.4
* Fix minor SQL installation issues
* Update to use new extension.json
* Add wgAvailableRights
* Remove use of deprecated wgSpecialPageGroups
* Update links in localization for FTB Gamepedia's Scribunto modules that replace old templates.
* Add action and right localization.

### 1.1.3
* Fix naming consistency: Tilesheets is now the used term.
* Fix issue where tables were styled outside of tilesheet list special pages.
* Improve HTML and CSS for tiles and tilesheets.
* Standardize input form CSS (#3)

### 1.1.2
* Remove slow and unused grid fallback.
* Remove mod parameter from SheetList pagination code.
