# Rocket.Chat activity for Moodle #

This plugin allows teachers to push students from Moodle into a dedicated Rocket.Chat channel.

## Main feature
Adding this activity to a Moodle course will create a channel in Rocket.Chat and push Moodle users associated to this activity as members of this newly created channel. The list of members will then be kept up to date.

## Rocket.Chat settings requirements
* create a local RocketChat account
  * other authentification account will not work
* confirm it (verified button) through Rocket.Chat administration
### permissions
for moodle Rocket.Chat account
* view-room-administration
### Authentication
* LDAP and CAS account fusion is adviced when moodle user account creation is activated


## Installation

### Moodle plugin
1. Copy the Rocket.Chat plugin to the `mod` directory of your Moodle instance:

```bash
git clone https://github.com/EsupPortail/esup-mod_rocketchat MOODLE_ROOT_DIRECTORY/mod/rocketchat
```
2. Visit the notifications page to complete the installation process
## Settings
* recyclebin_patch check this if patch is applied to core moodle file admin/tool/recyclebin/classes/course_bin.php
* patch is available in patch subdirectory
* you can apply it with patch command
```bash
patch -p1 /moodlepath/admin/tool/recyclebin/classes/course_bin.php < /moodlepath//mod/rocketchat/patch/admin_tool_recyclebin_classes_course_bin.patch
patch -p1 /moodlepath/admin/tool/recyclebin/classes/category_bin.php < /moodlepath/mod/rocketchat/patch/admin_tool_recyclebin_classes_category_bin.patch
patch -p1 /moodlepath/user/classes/output/user_roles_editable.php  < /moodlepath/mod/rocketchat/patch/user_classes_output_user_roles_editable.patch

```
### Authentication settings 
### user/password mode
* apiuser is the RocketChat account username
* apipassword is the password (do not put any generated token here)
### token mode
* if token mode is checked
* apiuser will be rocket-chat userid
* apipassword a token generated on Rocket.Chat for the apiuser
  * can be generated on Rocket.Chat for apiuser on Profile -> My Account -> Security -> Personal Access Tokens menu
  * don't forget to enable create-personal-access-tokens permission for the apiuser through its role

## Specials capabilities
* mod/rocketchat:change_embedded_display_mode : enable a user to choose embbeded Rocket.Chat web client display mode while eidting the module instance 
* mod/rocketchat:candefineroles : enable a user to change defaults roles mapping while editing the module instance
## Unit tests
### settings
* in Rocket.Chat you must adjust the Rate Limiter to enable unit tests large amount of requests
* to run unit tests that involved Rocket.Chat remote server just create a config-test.php file into the module rocketchat root directory
* fill in with following parameters
```php
<?php
set_config('instanceurl','https://rocketchat-server_url','mod_rocketchat');
set_config('restapiroot','/api/v1/','mod_rocketchat');
set_config('apiuser','your_user_on_rocket.chat','mod_rocketchat');
set_config('apipassword','#############','mod_rocketchat');
// fake config test to avoird email domain troubles
set_config('domainmail','your_domain_mail_if_necessary','mod_rocketchat'); // Optional argument.line.
```
### requirements
* see [moodle documentation](https://docs.moodle.org/dev/PHPUnit)

### Provided tests
* don't forget to init phpunit moodle tests environment (requirements) 
```bash
cd /www_moodle_path
/www_moodle_path/vendor/bin/phpunit "mod_rocketchat_background_enrolments_testcase" mod/rocketchat/tests/backup_enrolments_test.php
/www_moodle_path/vendor/bin/phpunit "backup_restore_testcase" mod/rocketchat/tests/backup_restore_test.php
/www_moodle_path/vendor/bin/phpunit "course_reset_testcase" mod/rocketchat/tests/course_reset_test.php
/www_moodle_path/vendor/bin/phpunit "observer_testcase" mod/rocketchat/tests/observer_test.php
/www_moodle_path/vendor/bin/phpunit   "mod_rocketchat_api_manager_testcase" mod/rocketchat/tests/rocket_chat_api_manager_test.php
/www_moodle_path/vendor/bin/phpunit "recyclebin_category_testcase" mod/rocketchat/tests/recyclebin_category_test.php
/www_moodle_path/vendor/bin/phpunit "recyclebin_testcase" mod/rocketchat/tests/recyclebin_test.php
/www_moodle_path/vendor/bin/phpunit "mod_rocketchat_privacy_testcase" mod/rocketchat/tests/privacy_provider_test.php
/www_moodle_path/vendor/bin/phpunit "mod_rocketchat_tools_testcase" mod/rocketchat/tests/mod_rocketchat_tools_test.php
/www_moodle_path/vendor/bin/phpunit "mod_rocketchat_moderator_and_user_roles_testcase" mod/rocketchat/tests/moderator_and_user_roles_test.php
```
## Functional tests with behat
### Requirements
* see [moodle documentation](https://docs.moodle.org/dev/Running_acceptance_test)
### Settings
* see phpunit settings section
### Provided tests
* don't forget to init behat moodle tests environment (requirements)
```shell script
cd /www_moodle_path
vendor/bin/behat --config /datas/mdlfarm/behat_datas/behatrun/behat/behat.yml --tags mod_rocketchat
```
for behat funcitonal tests by file
```shell script
cd /www_moodle_path
vendor/bin/behat --config /datas/mdlfarm/behat_datas/behatrun/behat/behat.yml  /www_moodle_path/mod/rocketchat/tests/behat/rocketchat_activity_creation.feature
vendor/bin/behat --config /datas/mdlfarm/behat_datas/behatrun/behat/behat.yml  /www_moodle_path/mod/rocketchat/tests/behat/rocketchat_create_deletion.feature

```
## License ##

2020 ESUP-Portail (https://www.esup-portail.org)

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.

## Rocket.Chat moodle module ESUP team
* The Rocket.Chat moodle Esup team is composed of people from various places and from differents working domains.
  * Pedagocical engineer
  * System administrators
  * Developpers
* Institutions
  * Centrale Marseille
  * Université de Lorraine
  * Université de Strasbourg
  * Université de technologie de Troyes
