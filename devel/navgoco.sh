#!/bin/bash
#
# Install Navgoco <LMS_DIR>/assets/twbs/navgoco/
#

RELEASE="0.2.1"
echo $(tput setaf 7)"NAVGOCO $RELEASE"$(tput sgr0)

# download
echo -n $(tput setaf 4)"Go to LMS root dir: "$(tput sgr0)
cd ..
echo $(tput setaf 2)"DONE"$(tput sgr0)

# download
echo $(tput setaf 4)"Downloading: "$(tput sgr0)
wget https://github.com/tefra/navgoco/archive/${RELEASE}.zip
echo $(tput setaf 2)"DONE"$(tput sgr0)

# extracting package
echo -n $(tput setaf 4)"Extracting: "$(tput sgr0)
unzip ${RELEASE}.zip
echo $(tput setaf 2)"DONE"$(tput sgr0)

# creating
#echo -n $(tput setaf 4)"Creating dir: "$(tput sgr0)
#mkdir assets
#echo $(tput setaf 2)"DONE"$(tput sgr0)

# merging
echo -n $(tput setaf 4)"Merging: "$(tput sgr0)
mv navgoco-${RELEASE}/ assets/twbs/navgoco
echo $(tput setaf 2)"DONE"$(tput sgr0)

# cleanup
echo -n $(tput setaf 4)"Cleaning up... " $(tput sgr0)
rm -Rf navgoco-${RELEASE}  ${RELEASE}.zip
echo $(tput setaf 2)"DONE"$(tput sgr0)