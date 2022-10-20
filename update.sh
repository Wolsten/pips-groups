#!/bin/sh
#
# Run this script to copy the source code into a test Wordpress app
# 
# Set the plugins and bundle variables to match your setup
#

# Target plugins folder
plugins=/Users/steve/Sites/test/wp-content/plugins

# Copy the plugin files
rsync -aru includes ${plugins}/sjd_subscribe_plugin/
rsync -aru images ${plugins}/sjd_subscribe_plugin/
rsync -aru readme.md ${plugins}/sjd_subscribe_plugin/
rsync -aru sjd_subscribe.php ${plugins}/sjd_subscribe_plugin/
rsync -aru styles.css ${plugins}/sjd_subscribe_plugin/
