#!/bin/bash
#
# Install JQuery <LMS_DIR>/assets/twbs/jquery/
#

echo $(tput setaf 7)"JQUERY"$(tput sgr0)

# download
echo -n $(tput setaf 4)"Go to LMS root dir: "$(tput sgr0)
cd ..
echo $(tput setaf 2)"DONE"$(tput sgr0)

# download
echo $(tput setaf 4)"Downloading: "$(tput sgr0)
wget http://code.jquery.com/jquery-latest.min.js
echo $(tput setaf 2)"DONE"$(tput sgr0)

# creating
echo -n $(tput setaf 4)"Creating dir: "$(tput sgr0)
mkdir -p assets/twbs/jquery
echo $(tput setaf 2)"DONE"$(tput sgr0)

# merging
echo -n $(tput setaf 4)"Merging: "$(tput sgr0)
mv jquery-latest.min.js assets/twbs/jquery/.
echo $(tput setaf 2)"DONE"$(tput sgr0)
