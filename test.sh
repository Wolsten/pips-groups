#!/bin/sh
#
# Run this script to copy the source code into a test Wordpress app
# 
# Set the plugins and bundle variables to match your setup
#

# Target plugins folders
plugin=/Users/steve/Sites/test/wp-content/plugins/pips_groups_plugin

# Remove the old plugin from the test application
rm -R ${plugin}/

# Make a new test plugins directory
mkdir ${plugin}

# Copy the plugin files
cp -r includes ${plugin}/
cp -r readme.md ${plugin}/
cp -r pips_groups.php ${plugin}/
cp -r styles.css ${plugin}/
