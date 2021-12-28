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
 * Leeloo LXP Vimeo external functions and service definitions.
 *
 * @package    mod_leeloolxpvimeo
 * @category   external
 * @copyright  2020 Leeloo LXP (https://leeloolxp.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

defined('MOODLE_INTERNAL') || die;

$functions = array(

    'mod_leeloolxpvimeo_view_leeloolxpvimeo' => array(
        'classname' => 'mod_leeloolxpvimeo_external',
        'methodname' => 'view_leeloolxpvimeo',
        'description' => 'Simulate the view.php web interface page: trigger events, completion, etc...',
        'type' => 'write',
        'capabilities' => 'mod/leeloolxpvimeo:view',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

    'mod_leeloolxpvimeo_get_leeloolxpvimeos_by_courses' => array(
        'classname'     => 'mod_leeloolxpvimeo_external',
        'methodname'    => 'get_leeloolxpvimeos_by_courses',
        'description'   => 'Returns a list of leeloolxpvimeos in a provided list of courses, if no list is provided all leeloolxpvimeos that the user
                            can view will be returned.',
        'type'          => 'read',
        'capabilities'  => 'mod/leeloolxpvimeo:view',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

    'mod_leeloolxpvimeo_markcomplete_leeloolxpvimeo' => array(
        'classname' => 'mod_leeloolxpvimeo_external',
        'methodname' => 'markcomplete_leeloolxpvimeo',
        'description' => 'Simulate the completion of activity.',
        'type' => 'write',
        'capabilities' => 'mod/leeloolxpvimeo:view',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

);
