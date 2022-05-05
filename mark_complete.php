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
 * Leeloo LXP Vimeo module version information
 *
 * @package mod_leeloolxpvimeo
 * @copyright  2020 Leeloo LXP (https://leeloolxp.com)
 * @author     Leeloo LXP <info@leeloolxp.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

global $DB;
global $USER;

$moduleid = optional_param('cm', 0, PARAM_RAW);

$userid = $USER->id;

if (isset($moduleid) && isset($moduleid) != '' && isset($userid) && isset($userid) != '') {
    $checkcompletion = $DB->get_record_sql('SELECT COUNT(*) iscompleted FROM {course_modules_completion} WHERE `coursemoduleid` = ? AND `userid` = ?', [$moduleid, $userid]);

    $iscompleted = $checkcompletion->iscompleted;

    if ($iscompleted == 0) {
        $object = new stdClass;
        $object->coursemoduleid = $moduleid;
        $object->userid = $userid;
        $object->completionstate = 1;
        $object->viewed = 1;
        $object->timemodified = time();

        $DB->insert_record('course_modules_completion', $object);
    }
}

die;
