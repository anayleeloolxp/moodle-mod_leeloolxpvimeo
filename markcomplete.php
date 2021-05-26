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
 * Toggles the manual completion flag.
 *
 * @package mod_leeloolxpvimeo
 * @copyright  2020 Leeloo LXP (https://leeloolxp.com)
 * @author     Leeloo LXP <info@leeloolxp.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/completionlib.php');

// Parameters
$cmid = optional_param('id', 0, PARAM_INT);
$courseid = optional_param('course', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

// Check if we are marking a user complete via the completion report.
$user = optional_param('user', 0, PARAM_INT);
$rolec = optional_param('rolec', 0, PARAM_INT);

if (!$cmid && !$courseid) {
    print_error('invalidarguments');
}

$targetstate = required_param('completionstate', PARAM_INT);
$fromajax = optional_param('fromajax', 0, PARAM_INT);

$PAGE->set_url('/course/togglecompletion.php', array('id' => $cmid, 'completionstate' => $targetstate));

switch ($targetstate) {
    case COMPLETION_COMPLETE:
    case COMPLETION_INCOMPLETE:
        break;
    default:
        print_error('unsupportedstate');
}

// Get course-modules entry
$cm = get_coursemodule_from_id(null, $cmid, null, true, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

// Check user is logged in
require_login($course, false, $cm);
require_capability('moodle/course:togglecompletion', context_module::instance($cmid));

if (isguestuser() or !confirm_sesskey()) {
    print_error('error');
}

// Set up completion object and check it is enabled.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

if (!$completion->is_enabled()) {
    throw new moodle_exception('completionnotenabled', 'completion');
}

// NOTE: All users are allowed to toggle their completion state, including
// users for whom completion information is not directly tracked. (I.e. even
// if you are a teacher, or admin who is not enrolled, you can still toggle
// your own completion state. You just don't appear on the reports.)

// Check completion state is manual
if ($cm->completion != COMPLETION_TRACKING_MANUAL) {
    error_or_ajax('cannotmanualctrack', $fromajax);
}

$completion->update_state($cm, $targetstate);

// And redirect back to course
if ($fromajax) {
    print 'OK';
} else {
    // In case of use in other areas of code we allow a 'backto' parameter,
    // otherwise go back to course page

    if ($backto = optional_param('backto', null, PARAM_URL)) {
        redirect($backto);
    } else {
        redirect(course_get_url($course, $cm->sectionnum));
    }
}

// utility functions
/**
 * Ajax check
 *
 * @param string $message message
 * @param string $fromajax fromajax
 */
function error_or_ajax($message, $fromajax) {
    if ($fromajax) {
        print get_string($message, 'error');
        exit;
    } else {
        print_error($message);
    }
}
