# LMS BillTech payments plugin

## Description
This plugin provides integration with BillTech platform.
It injects payment details into mail headers and a button/link to a new invoice e-mail notification.

## Installation
* Run in lms root directory:
```bash
git remote add billtech https://github.com/BillTechPL/lms.git
git pull billtech master
```
* Run `composer update` in lms root direcory
* Enable the plugin in *configuration -> plugins*
* Add payment button to your *new invoice* template - insert `%billtech_btn` placeholder for the button.

## Configuration
* Create new configuration entry billtech.isp_id. Use your *isp_id* provided by BillTech <michal(at)billtech.pl>