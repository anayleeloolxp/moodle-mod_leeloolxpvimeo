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
 * Leeloo LXP Vimeo module admin settings and defaults
 *
 * @package mod_leeloolxpvimeo
 * @copyright  2020 Leeloo LXP (https://leeloolxp.com)
 * @author     Leeloo LXP <info@leeloolxp.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'mod_leeloolxpvimeo/license',
        get_string('license', 'mod_leeloolxpvimeo'),
        get_string('license', 'mod_leeloolxpvimeo'),
        0
    ));
    $choices = array(
        '10' => '10%',
        '20' => '20%',
        '30' => '30%',
        '40' => '40%',
        '50' => '50%',
        '60' => '60%',
        '70' => '70%',
        '80' => '80%',
        '90' => '90%',
        '100' => '100%',
    );
    $namemarkcompleteafter = 'mod_leeloolxpvimeo/markcompleteafter';
    $titlemarkcompleteafter = get_string('markcompleteafter', 'mod_leeloolxpvimeo');
    $descriptionmarkcompleteafter = get_string('markcompleteafter_description', 'mod_leeloolxpvimeo');
    $settings->add(new admin_setting_configselect($namemarkcompleteafter, $titlemarkcompleteafter, $descriptionmarkcompleteafter, 10, $choices));
}
