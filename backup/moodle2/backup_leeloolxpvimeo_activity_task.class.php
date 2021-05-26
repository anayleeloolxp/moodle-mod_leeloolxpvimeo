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
 * Defines backup_leeloolxpvimeo_activity_task class
 *
 * @package   mod_leeloolxpvimeo
 * @category  backup
 * @copyright 2020 Leeloo LXP (https://leeloolxp.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/leeloolxpvimeo/backup/moodle2/backup_leeloolxpvimeo_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the Leeloo LXP Vimeo instance
 */
class backup_leeloolxpvimeo_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the leeloolxpvimeo.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_leeloolxpvimeo_activity_structure_step('leeloolxpvimeo_structure', 'leeloolxpvimeo.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts
     *
     * @param string $content some HTML text
     * @return string the content with the URLs encoded
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of leeloolxpvimeos
        $search = "/(" . $base . "\/mod\/leeloolxpvimeo\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@LEELOOLXPVIMEOINDEX*$2@$', $content);

        // Link to leeloolxpvimeo view by moduleid
        $search = "/(" . $base . "\/mod\/leeloolxpvimeo\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@LEELOOLXPVIMEOVIEWBYID*$2@$', $content);

        return $content;
    }
}
