#!/bin/sh
#
# Run this script to copy the source code into a test Wordpress app
# 
# Set the plugins and bundle variables to match your setup
#

# Target plugins folder
plugins=/Users/steve/Sites/test/wp-content/plugins

# Remove the old plugin from the test application
rm -R ${plugins}/sjd_subscribe_plugin/

# Make a new test plugins directory
mkdir ${plugins}/sjd_subscribe_plugin

# Copy the plugin files
cp -r includes ${plugins}/sjd_subscribe_plugin/
cp -r readme.md ${plugins}/sjd_subscribe_plugin/
cp -r sjd_subscribe.php ${plugins}/sjd_subscribe_plugin/
cp -r styles.css ${plugins}/sjd_subscribe_plugin/
