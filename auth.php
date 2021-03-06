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
 * Authorization by direct link.
 *
 * @package    auth_link
 * @copyright  2017 Valentin Popov (https://valentineus.link/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');

/**
 * Plugin for no authentication.
 */
class auth_plugin_link extends auth_plugin_base {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'link';
    }

    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     * @deprecated since Moodle 3.1
     */
    public function auth_plugin_link() {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::__construct();
    }

    /**
     * Returns true if the username and password work or don't exist and false
     * if the user exists and the password is wrong.
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    public function user_login($username, $password) {
        global $CFG, $DB;

        if ($user = $DB->get_record('user', array('username' => $username, 'mnethostid' => $CFG->mnet_localhost_id))) {
            return validate_internal_user_password($user, $password);
        }

        return true;
    }

    /**
     * No password updates.
     */
    public function user_update_password($user, $newpassword) {
        return false;
    }

    public function prevent_local_passwords() {
        return false;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     * @return bool
     */
    public function is_internal() {
        return true;
    }

    /**
     * No changing of password.
     */
    public function can_change_password() {
        return false;
    }

    /**
     * Returns the URL for changing the user's pw, or empty if the default can
     * be used.
     * @return moodle_url
     */
    public function change_password_url() {
        return null;
    }

    /**
     * No password resetting.
     */
    public function can_reset_password() {
        return true;
    }

    /**
     * Returns true if plugin can be manually set.
     * @return bool
     */
    public function can_be_manually_set() {
        return true;
    }

    /**
     * Hook for overriding behaviour before going to the login page.
     */
    public function pre_loginpage_hook() {
        $this->loginpage_hook();
    }

    /**
     * Hook for overriding behaviour of login page.
     */
    public function loginpage_hook() {
        global $DB;

        $username = optional_param('username', '', PARAM_RAW);
        $password = optional_param('password', '', PARAM_RAW);

        if (!isloggedin()) {
            if (!empty($username) && !empty($password)) {
                // User existence check.
                if ($user = $DB->get_record('user', array('username' => $username) )) {
                    // Verification of authorization data.
                    if (validate_internal_user_password($user, $password)) {
                        complete_user_login($user);
                        $this->redirect_user();
                    }
                }
            }
        }
    }

    /**
     * Redirect client to the original target.
     */
    public function redirect_user() {
        global $CFG, $SESSION;

        $wantsurl = optional_param('wantsurl', '', PARAM_URL);
        $redirect = new moodle_url($CFG->wwwroot, $_GET);

        if (isset($SESSION->wantsurl)) {
            $redirect = new moodle_url($SESSION->wantsurl, $_GET);
        } else if (!empty($wantsurl)) {
            $redirect = new moodle_url($wantsurl);
        }

        redirect($redirect);
    }
}
