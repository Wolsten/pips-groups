# Timeline Plugin

Timeline is a Wordpress plugin based on the Timeline component built in Svelte.

## Installation

The plugin comprises a single php file and associated svelte bundled code in the assets folder.

To install, copy the required source files (not including the test.sh script) into it's own plugin folder of the same name in a Wordpress application and then Activate as normal.

## Usage

In an post or page insert the required shortcode which has two parameters, a data file url and optional settings:

```
[timeline data="url" settings="s1=x,s2=y,..."]
```

The plugin enables data files to be uploaded as media files and then referenced directly in the shortcode. However, any url to a valid data file could be provided. If you are concerned about the security risk associated with being able to upload json files then comment out the lines enabling this functionality.

### Settings

@todo
