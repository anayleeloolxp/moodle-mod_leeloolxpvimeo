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
 * Provides support for the conversion of moodle1 backup to the moodle2 format
 *
 * @package mod_leeloolxpvimeo
 * @copyright  2020 Leeloo LXP (https://leeloolxp.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Leeloo LXP Vimeo conversion handler. This resource handler is called by moodle1_mod_resource_handler
 */
class moodle1_mod_leeloolxpvimeo_handler extends moodle1_resource_successor_handler {

    /** @var moodle1_file_manager instance */
    protected $fileman = null;

    /**
     * Converts /MOODLE_BACKUP/COURSE/MODULES/MOD/RESOURCE data
     * Called by moodle1_mod_resource_handler::process_resource()
     *
     * @param array $data
     * @param array $raw
     */
    public function process_legacy_resource(array $data, array $raw = null) {

        // Get the course module id and context id.
        $instanceid = $data['id'];
        $cminfo = $this->get_cminfo($instanceid, 'resource');
        $moduleid = $cminfo['id'];
        $contextid = $this->converter->get_contextid(CONTEXT_MODULE, $moduleid);

        // Convert the legacy data onto the new leeloolxpvimeo record.
        $leeloolxpvimeo = array();
        $leeloolxpvimeo['id'] = $data['id'];
        $leeloolxpvimeo['name'] = $data['name'];
        $leeloolxpvimeo['vimeo_video_id'] = $data['vimeo_video_id'];
        $leeloolxpvimeo['vimeo_token'] = $data['vimeo_token'];
        $leeloolxpvimeo['width'] = $data['width'];
        $leeloolxpvimeo['height'] = $data['height'];
        $leeloolxpvimeo['border'] = $data['border'];
        $leeloolxpvimeo['allow'] = $data['allow'];
        $leeloolxpvimeo['intro'] = $data['intro'];
        $leeloolxpvimeo['introformat'] = $data['introformat'];
        $leeloolxpvimeo['content'] = $data['alltext'];

        if ($data['type'] === 'html') {
            // Legacy Resource of the type Web leeloolxpvimeo.
            $leeloolxpvimeo['contentformat'] = FORMAT_HTML;
        } else {
            // Legacy Resource of the type Plain text leeloolxpvimeo.
            $leeloolxpvimeo['contentformat'] = (int) $data['reference'];

            if ($leeloolxpvimeo['contentformat'] < 0 or $leeloolxpvimeo['contentformat'] > 4) {
                $leeloolxpvimeo['contentformat'] = FORMAT_MOODLE;
            }
        }

        $leeloolxpvimeo['legacyfiles'] = RESOURCELIB_LEGACYFILES_ACTIVE;
        $leeloolxpvimeo['legacyfileslast'] = null;
        $leeloolxpvimeo['revision'] = 1;
        $leeloolxpvimeo['timemodified'] = $data['timemodified'];

        // Populate display and displayoptions fields.
        $options = array('printheading' => 1, 'printintro' => 0);
        if ($data['popup']) {
            $leeloolxpvimeo['display'] = RESOURCELIB_DISPLAY_POPUP;
            $rawoptions = explode(',', $data['popup']);
            foreach ($rawoptions as $rawoption) {
                list($name, $value) = explode('=', trim($rawoption), 2);
                if ($value > 0 and ($name == 'width' or $name == 'height')) {
                    $options['popup' . $name] = $value;
                    continue;
                }
            }
        } else {
            $leeloolxpvimeo['display'] = RESOURCELIB_DISPLAY_OPEN;
        }
        $leeloolxpvimeo['displayoptions'] = serialize($options);

        // Get a fresh new file manager for this instance.
        $this->fileman = $this->converter->get_file_manager($contextid, 'mod_leeloolxpvimeo');

        // Convert course files embedded into the intro.
        $this->fileman->filearea = 'intro';
        $this->fileman->itemid = 0;
        $leeloolxpvimeo['intro'] = moodle1_converter::migrate_referenced_files($leeloolxpvimeo['intro'], $this->fileman);

        // Convert course files embedded into the content.
        $this->fileman->filearea = 'content';
        $this->fileman->itemid = 0;
        $leeloolxpvimeo['content'] = moodle1_converter::migrate_referenced_files($leeloolxpvimeo['content'], $this->fileman);

        // Write leeloolxpvimeo.xml .
        $this->open_xml_writer("activities/leeloolxpvimeo_{$moduleid}/leeloolxpvimeo.xml");
        $this->xmlwriter->begin_tag('activity', array(
            'id' => $instanceid, 'moduleid' => $moduleid,
            'modulename' => 'leeloolxpvimeo', 'contextid' => $contextid
        ));
        $this->write_xml('leeloolxpvimeo', $leeloolxpvimeo, array('/leeloolxpvimeo/id'));
        $this->xmlwriter->end_tag('activity');
        $this->close_xml_writer();

        // Write inforef.xml for migrated resource file.
        $this->open_xml_writer("activities/leeloolxpvimeo_{$moduleid}/inforef.xml");
        $this->xmlwriter->begin_tag('inforef');
        $this->xmlwriter->begin_tag('fileref');
        foreach ($this->fileman->get_fileids() as $fileid) {
            $this->write_xml('file', array('id' => $fileid));
        }
        $this->xmlwriter->end_tag('fileref');
        $this->xmlwriter->end_tag('inforef');
        $this->close_xml_writer();
    }
}
