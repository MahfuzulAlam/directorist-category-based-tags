# Directorist - Category Based Tags

Filter Directorist tags by selected categories across the admin tag editor, add listing form, and search form.

## Overview

This plugin extends Directorist with category-based tag mapping.

It adds:

- a category multiselect field on the Directorist tag edit screen
- category-based tag filtering on the add listing form
- category-based tag filtering on the search form
- Directorist settings to enable or disable the feature and control fallback behavior

## Features

- Assign one or more Directorist categories to each Directorist tag
- Show all tags by default on listing and search forms
- Automatically narrow tag options when a category is selected
- Fallback to all tags when no related tags are found, if enabled in settings
- Works with Directorist Select2-based tag fields

## Requirements

- WordPress 5.2+
- PHP 5.6+
- Directorist

## Installation

1. Copy the plugin folder into `wp-content/plugins/`
2. Activate `Directorist - Category Based Tags` from the WordPress admin
3. Make sure Directorist is active

## Configuration

The plugin adds two Directorist options:

- `Category Based Tags`
- `Show All Tags If Empty`

You can find them in the Directorist settings under the categories settings section.

### Option Behavior

`Category Based Tags`

- If enabled, category-based tag filtering runs on supported forms
- If disabled, the plugin does not apply frontend or admin-side runtime filtering

`Show All Tags If Empty`

- If enabled, all tags are returned when no related tags match the selected category
- If disabled, only matching related tags are returned
- Tags without assigned categories are not returned as a fallback when this option is disabled

## How To Use

### 1. Assign Categories To Tags

Edit any Directorist tag in the WordPress admin.

On the tag edit screen, the plugin adds a `Categories` multiselect field. Select one or more related categories for that tag and save.

Path example:

`WP Admin -> Directory Listings -> Tags -> Edit Tag`

Screenshot placeholder:

- Add screenshot of the tag edit screen with the categories multiselect

### 2. Enable Category Based Filtering

Go to Directorist settings and enable `Category Based Tags`.

If you want all tags to appear when no related tags are found, also enable `Show All Tags If Empty`.

Screenshot placeholder:

- Add screenshot of the Directorist settings options

### 3. Use On The Add Listing Form

On the add listing form:

- the tag field initially shows all tags
- when a category is selected in `#at_biz_dir-categories`, the plugin fetches related tags through AJAX
- the tag field `#at_biz_dir-tags` is updated dynamically
- if no related tags are found, the fallback behavior depends on `Show All Tags If Empty`

### 4. Use On The Search Form

On the Directorist search form:

- the tag field initially shows all tags
- when a category is selected in `select[name="in_cat"]`, the plugin fetches related tags through AJAX
- the tag inputs `in_tag[]` are rebuilt dynamically
- if no related tags are found, the fallback behavior depends on `Show All Tags If Empty`

Screenshot placeholder:

- Add screenshot of the add listing form category and tag fields
- Add screenshot of the search form category and tag fields

## Functional Logic

### Default State

- Tags are shown by default
- No category selection means the full tag list is available

### When A Category Is Selected

- The plugin looks up tags whose saved related categories match the selected category IDs
- Matching tags are returned and displayed

### When No Related Tags Are Found

- If `Show All Tags If Empty` is enabled, the plugin returns all tags
- If `Show All Tags If Empty` is disabled, the plugin returns an empty result

## Admin Usage Notes

- Category assignment for tags is managed per tag on the edit screen
- The category selector uses Select2 when Directorist Select2 assets are available

## Developer Notes

### Main Files

- `directorist-custom-code.php`
  Plugin bootstrap and loading
- `inc/class-directorist-category-based-tags-tag-field.php`
  Admin tag edit field and tag-category meta saving
- `inc/class-directorist-category-based-tags-manager.php`
  AJAX handling, Directorist settings integration, and runtime asset loading
- `assets/js/directorist-category-based-tags-admin.js`
  Select2 initialization for the admin tag edit field
- `assets/js/directorist-category-based-tags.js`
  Dynamic tag filtering for listing and search forms

### Stored Data

Related category IDs for each tag are stored in term meta using:

`_directorist_category_based_tags_categories`

### AJAX Action

The plugin uses this AJAX action:

`directorist_category_based_tags_get_related_tags`

## Troubleshooting

### Tags Are Not Filtering

Check the following:

- Directorist is active
- `Category Based Tags` is enabled in Directorist settings
- the tag has related categories assigned on its edit screen

### Select2 Style Is Missing In Admin

Check whether Directorist Select2 assets are registered on the tag edit screen.

### No Tags Are Shown After Category Selection

This usually means one of the following:

- no related categories were assigned to the selected tags
- `Show All Tags If Empty` is disabled
- no matching tags exist for the selected categories

## Changelog

### 2.0.0

- renamed and reorganized the plugin
- added category assignment field on the tag edit screen
- added dynamic category-based tag filtering for add listing form
- added dynamic category-based tag filtering for search form
- added Directorist settings integration for feature control

## License

GPL v2 or later
