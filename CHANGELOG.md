# Changelog
This changelog only shows recent version history, because of the lack of documentation from the former maintainers. The very first changelog (1.1.2) is likely incomplete.

## Version 3
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
