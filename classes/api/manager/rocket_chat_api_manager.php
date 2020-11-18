<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * rocket chat api config class
 *
 * @package     mod_rocketchat
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @author Céline Pervès<cperves@unistra.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_rocketchat\api\manager;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/mod/rocketchat/vendor/autoload.php');

class rocket_chat_api_manager{
    private $rocketchatapiconfig;
    private $adminuser;
    private $verbose;

    public function get_instance_url() {
        return $this->rocketchatapiconfig->get_instanceurl();
    }
    public function get_admin_user() {
        return $this->adminuser;
    }
    public function is_verbose() {
        return $this->verbose;
    }
    public function __construct($user=null, $password=null) {
        $this->verbose = get_config('mod_rocketchat', 'verbose_mode');
        $this->rocketchatapiconfig = new rocket_chat_api_config();
        $this->initiate_connection($user, $password);
    }

    private function initiate_connection($user = null, $password = null) {
        // User amanager object , logged to remote Rocket.Chat.
        $this->adminuser = new \RocketChat\UserManager(
            is_null($user) ? $this->rocketchatapiconfig->get_apiuser() : $user,
            is_null($user) ? $this->rocketchatapiconfig->get_apipassword() : $password,
            $this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
    }

    public function close_connection() {
        $adminuser = new \RocketChat\UserManager($this->rocketchatapiconfig->get_apiuser(),
            $this->rocketchatapiconfig->get_apipassword(), $this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
        // Log in with save option in order to add id and token to header.
        if (!$adminuser->logout()) {
            error_log('Rocket.Chat admin not logged in');
        }

    }
    public function get_rocketchat_channel_object($channelid, $channelname='') {
        $channel = new \stdClass();
        $channel->_id = $channelid;
        $channel->name = $channelname;
        return new \RocketChat\Channel($channel, array(),  $this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
    }

    public function get_rocketchat_group_object($groupid, $groupname='') {
        $group = new \stdClass();
        $group->_id = $groupid;
        $group->name = $groupname;
        return new \RocketChat\Group($group, array(), array(), $this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
    }

    public function get_rocketchat_user_with_id_object($userid) {
        $fields = array();
        $fields['_id'] = $userid;
        return new \RocketChat\User(null, null , $fields,
            $this->rocketchatapiconfig->get_instanceurl(), $this->rocketchatapiconfig->get_restapiroot());
    }

    public function get_rocketchat_user_object($username, $password='', $fields = array()) {
        return new \RocketChat\User($username, $password , $fields, $this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
    }

    public function get_rocketchat_client_object() {
        return new \RocketChat\Client($this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
    }

    public function create_rocketchat_group($name) {
        $group = new \RocketChat\Group($name, array(), array(), $this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
        // Check that group is not already exists.
        $groupexists = $group->isGroupAlreadyExists($this->verbose);
        if (!$groupexists) {
            $group->create($this->verbose);
        } else {
            // Change group name.
            return $this->create_rocketchat_group($name.'_'.time());
        }
        return $group->id;
    }

    public function delete_rocketchat_group($id, $groupname='') {
        $identifier = new \stdClass();
        $identifier->_id = $id;
        $identifier->name = $groupname;
        $group = new \RocketChat\Group($identifier, array(), array(), $this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
        return $group->delete();
    }

    public function archive_rocketchat_group($id) {
        $identifier = new \stdClass();
        $identifier->_id = $id;
        $identifier->name = '';
        $group = new \RocketChat\Group($identifier, array(), array(), $this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
        return $group->archive();
    }

    public function unarchive_rocketchat_group($id) {
        $identifier = new \stdClass();
        $identifier->_id = $id;
        $identifier->name = '';
        $group = new \RocketChat\Group($identifier, array(), array(), $this->rocketchatapiconfig->get_instanceurl(),
            $this->rocketchatapiconfig->get_restapiroot());
        return $group->unarchive();
    }

    public function enrol_user_to_group($groupid, $moodleuser, &$user=null) {
        $createusermode = get_config('mod_rocketchat', 'create_user_account_if_not_exists');
        $identifier = new \stdClass();
        $identifier->username = $moodleuser->username;
        $group = $this->get_rocketchat_group_object($groupid);
        if ($createusermode) {
            $user = $this->create_user_if_not_exists($moodleuser);
            if (!$user) {
                error_log("User $moodleuser->username not exists in Rocket.Chat and was not succesfully created.");
            }
        } else {
            $user = $group->user_info($identifier);
            if (!$user) {
                error_log("User $moodleuser->username not exists in Rocket.Chat");
            }
        }
        if (!$user) {
            return false;
        }
        $return = $group->invite($user->_id);
        if (!$return) {
            error_log("User $moodleuser->username not added as user to remote Rocket.Chat group");
        }
        return $return ? $group : false;
    }

    public function enrol_moderator_to_group($groupid, $moodleuser) {
        $identifier = new \stdClass();
        $identifier->username = $moodleuser->username;
        $user = null;
        $group = $this->enrol_user_to_group($groupid, $moodleuser, $user);
        if ($group) {
            return $group->addModerator($user->_id, $this->verbose);
        } else {
            error_log("User $moodleuser->username not added as moderator to remote Rocket.Chat group");
        }
        return false;
    }

    public function unenrol_user_from_group($groupid, $moodleuser) {
        $createusermode = get_config('mod_rocketchat', 'create_user_account_if_not_exists');
        $identifier = new \stdClass();
        $identifier->username = $moodleuser->username;
        $group = $this->get_rocketchat_group_object($groupid);
        $user = $group->user_info($identifier, $this->verbose);
        if (!$user) {
            error_log("User $user->username not exists in Rocket.Chat");
            return false;
        }
        $return = $group->kick($user->_id, $this->verbose);
        if (!$return) {
            error_log("User $user->username not removed as user from remote Rocket.Chat group");
        }
        return $return ? $group : false;
    }

    public function unenrol_moderator_from_group($groupid, $moodleuser) {
        $identifier = new \stdClass();
        $identifier->username = $moodleuser->username;
        $group = $this->get_rocketchat_group_object($groupid);
        $user = $group->user_info($identifier, $this->verbose);
        $return = $group->removeModerator($user->_id, $this->verbose);
        if (!$return) {
            error_log("User $user->username not removed as moderator from remote Rocket.Chat group");
        }
        $return2 = $this->unenrol_user_from_group($groupid, $moodleuser);
        return $return && $return2;
    }

    public function create_user_if_not_exists($moodleuser) {
        $rocketchatuserinfos = new \stdClass();
        $rocketchatuserinfos->nickname = get_string('rocketchat_nickname', 'mod_rocketchat', $moodleuser);
        $rocketchatuserinfos->email = $moodleuser->email;
        $rocketchatuserinfos->username = $moodleuser->username;
        $rocketchatuserinfos->password = generate_password();
        $user = $this->adminuser->create($rocketchatuserinfos, $this->verbose);
        if (PHPUNIT_TEST) {
            $user->password = $rocketchatuserinfos->password;
        }
        return $user;
    }

    public function get_group_members($groupid, $groupname = '') {
        $group = $this->get_rocketchat_group_object($groupid, $groupname);
        if ($group) {
            $members = $group->members();
            if (!$members) {
                return array();
            } else {
                return $members;
            }
        }
        return array();
    }

    public function delete_user($moodleusername) {
        $rocketuser = new \stdClass();
        $rocketuser->username = $moodleusername;
        $rocketuser = $this->adminuser->info($rocketuser);
        if (!$rocketuser || !isset($rocketuser->user->_id)) {
            error_log("user $moodleusername not found in Rocket.Chat while attempt to delete");
            return false;
        }
        return $this->adminuser->delete($rocketuser->user->_id, $this->verbose);
    }

    public function unenroll_all_users_from_group($groupid) {
        $group = $$this->get_rocketchat_group_object($groupid);
        $group->cleanHistory($this->verbose);
        $members = $group->members();
        foreach ($members as $member) {
            $user = $this->get_rocketchat_user_object($member->username);
            $group->kick($user->_id, $this->verbose);
        }
    }

    public function clean_history($roomid) {
        $group = $this->get_rocketchat_group_object($roomid);
        $group->cleanHistory(get_config('mod_rocketchat', 'verbose_mode'));
    }

    public function get_groupname($groupid) {
        $group = $this->get_rocketchat_group_object($groupid);
        if ($group) {
            $groupinfo = $group->info();
            if ($groupinfo) {
                return $groupinfo->group->name;
            }
        }
        return null;
    }

    public function get_group_infos($groupid) {
        $group = $this->get_rocketchat_group_object($groupid);
        if(!empty($group) && !empty($group->info($this->verbose))){
            return $group->info($this->verbose);
        }
        return false;
    }

    public function group_exists($groupid) {
        $group = $this->get_rocketchat_group_object($groupid);
        if (!empty($group) && !empty($group->info($this->verbose))) {
            return true;
        }
        return false;
    }

    public function get_user_infos($username) {
        $identifier = new \stdClass();
        $identifier->username = $username;
        $client = $this->get_rocketchat_client_object();
        return $client->user_info($identifier, $this->verbose);
    }

    public function kick_all_group_members($groupid) {
        $rocketchatapiusername = get_config('mod_rocketchat', 'apiuser');
        $group = $this->get_rocketchat_group_object($groupid);
        $members = $group->members($this->verbose);
        foreach ($members as $member) {
            if ($member->username != $rocketchatapiusername) {
                $rocketchatuser = $this->get_user_infos($member->username);
                if (!$group->kick($rocketchatuser->_id, $this->verbose)) {
                    error_log("Rockat.Chat API error : user $member->username not kicked from Rocket.Chat group"
                        ." $groupid");
                }
            }
        }
    }

    public function post_message($roomid, $message) {
        $channel = $this->get_rocketchat_channel_object($roomid);
        $channel->postMessage($message);
    }

    public function get_group_messages($groupid) {
        $group = $this->get_rocketchat_group_object($groupid);
        return $group->getMessages($this->verbose);
    }

    public function user_exists($username) {
        $user = $this->get_rocketchat_user_object($username);
        if (!empty($user) && !empty($user->info())) {
            return true;
        }
        return false;
    }

    public function group_archived($groupid) {
        $group = $this->get_rocketchat_group_object($groupid);
        if (!empty($group)) {
            $groupinfo = $group->info()->group;
            if (!empty($groupinfo)) {
                if (property_exists($groupinfo, 'archived')) {
                    return $groupinfo->archived;
                }
            }
        } else {
            error_log("Rocket.Chat API Rest error group $groupid not found");
        }
        return false;
    }

    public function get_group_moderators($groupid) {
        $group = $this->get_rocketchat_group_object($groupid);
        if ($group) {
            $moderators = $group->moderators($this->verbose);
            if (!$moderators) {
                return array();
            } else {
                return $moderators;
            }
        }
        return array();
    }
}
