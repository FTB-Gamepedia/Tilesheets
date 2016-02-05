# Changelog
This changelog only shows recent version history, because of the lack of documentation from the former maintainers. The very first changelog (1.1.2) is likely incomplete.

## Version 2
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
