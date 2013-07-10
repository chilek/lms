#!/bin/bash
#
# Install Twitter Bootstrap framework to <LMS_DIR>/assets/bootstrap/*
#

# download
echo -n "Go to LMS root dir: "
cd ..
echo "DONE"

# download
echo -n "Downloading: "
wget http://twitter.github.io/bootstrap/assets/bootstrap.zip
echo "DONE"

# extracting package
echo -n "Extracting: "
unzip bootstrap.zip
echo "DONE"

# creating
echo -n "Creating dir: "
mkdir assets
echo "DONE"

# merging
echo -n "Merging: "
mv bootstrap/ assets/bootstrap/
echo "DONE"

# cleanup
echo -n "Cleaning up... " 
rm -Rf bootstrap  bootstrap.zip
echo "DONE"