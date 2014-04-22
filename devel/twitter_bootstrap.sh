#!/bin/bash
#
# Install Twitter Bootstrap framework to <LMS_DIR>/assets/bootstrap/*
#

RELEASE="3.1.1"
echo $(tput setaf 7)"TWBS $RELEASE"$(tput sgr0)

# download
echo -n $(tput setaf 4)"Go to LMS root dir: "$(tput sgr0)
cd ..
echo $(tput setaf 2)"DONE"$(tput sgr0)

# download
echo $(tput setaf 4)"Downloading: "$(tput sgr0)
wget https://github.com/twbs/bootstrap/releases/download/v${RELEASE}/bootstrap-${RELEASE}-dist.zip
echo $(tput setaf 2)"DONE"$(tput sgr0)

# extracting package
echo -n $(tput setaf 4)"Extracting: "$(tput sgr0)
unzip bootstrap-${RELEASE}-dist.zip
echo $(tput setaf 2)"DONE"$(tput sgr0)

# creating
echo -n $(tput setaf 4)"Creating dir: "$(tput sgr0)
mkdir assets
echo $(tput setaf 2)"DONE"$(tput sgr0)

# merging
echo -n $(tput setaf 4)"Merging: "$(tput sgr0)
mv bootstrap-${RELEASE}-dist/ assets/twbs/
echo $(tput setaf 2)"DONE"$(tput sgr0)

# cleanup
echo -n $(tput setaf 4)"Cleaning up... " $(tput sgr0)
rm -Rf bootstrap-${RELEASE}-dist  bootstrap-${RELEASE}-dist.zip
echo $(tput setaf 2)"DONE"$(tput sgr0)