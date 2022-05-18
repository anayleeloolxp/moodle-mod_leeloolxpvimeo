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
 * Leeloo LXP Vimeo configuration form
 *
 * @package mod_leeloolxpvimeo
 * @copyright  2020 Leeloo LXP (https://leeloolxp.com)
 * @author     Leeloo LXP <info@leeloolxp.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/leeloolxpvimeo/locallib.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Form Class
 */
class mod_leeloolxpvimeo_mod_form extends moodleform_mod {

    /**
     * Defination of form
     */
    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        require_once($CFG->libdir . '/filelib.php');

        $leeloolxplicense = get_config('mod_leeloolxpvimeo')->license;
        $url = 'https://leeloolxp.com/api_moodle.php/?action=page_info';
        $postdata = [
            'license_key' => $leeloolxplicense,
        ];

        $curl = new curl;

        $options = array(
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_HEADER' => false,
            'CURLOPT_POST' => count($postdata),
        );

        if (!$output = $curl->post($url, $postdata, $options)) {
            notice(get_string('nolicense', 'mod_leeloolxpvimeo'));
            return;
        }

        $infoleeloolxp = json_decode($output);

        if ($infoleeloolxp->status != 'false') {
            $leeloolxpurl = $infoleeloolxp->data->install_url;
        } else {
            notice(get_string('nolicense', 'mod_leeloolxpvimeo'));
            return;
        }

        $url = $leeloolxpurl . '/admin/Theme_setup/get_vimeo_videos_settings';

        $postdata = [
            'license_key' => $leeloolxplicense,
        ];

        $curl = new curl;

        $options = array(
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_HEADER' => false,
            'CURLOPT_POST' => count($postdata),
        );

        if (!$output = $curl->post($url, $postdata, $options)) {
            notice(get_string('nolicense', 'mod_leeloolxpvimeo'));
            return;
        }

        $resposedata = json_decode($output);

        if (!isset($resposedata->data->vimeo_videos)) {
            notice(get_string('updatesetting', 'mod_leeloolxpvimeo'));
            return;
        }

        $settingleeloolxp = $resposedata->data->vimeo_videos;
        $config = $settingleeloolxp;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size' => '48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->standard_intro_elements();

        $mform->addElement('header', 'contentsection', get_string('contentheader', 'leeloolxpvimeo'));

        $mform->addElement('float', 'vimeo_video_id', get_string('regular_vimeo_video_id', 'leeloolxpvimeo'), array('size' => '48'));
        $mform->addElement('text', 'vimeo_token', get_string('regular_vimeo_token', 'leeloolxpvimeo'), array('size' => '48'));
        $mform->setDefault('vimeo_token', $config->vimeo_token);

        $mform->addElement('float', 'width', get_string('regular_width', 'leeloolxpvimeo'), array('size' => '48'));
        $mform->setDefault('width', $config->default_width);
        $mform->addElement('float', 'height', get_string('regular_height', 'leeloolxpvimeo'), array('size' => '48'));
        $mform->setDefault('height', $config->default_height);
        $mform->addElement('advcheckbox', 'border', get_string('regular_border', 'leeloolxpvimeo'));
        $mform->addElement('text', 'allow', get_string('regular_allow', 'leeloolxpvimeo'), array('size' => '48'));
        $mform->setDefault('allow', $config->default_allow);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('allow', PARAM_TEXT);
        } else {
            $mform->setType('allow', PARAM_CLEANHTML);
        }

        $mform->addElement('editor', 'leeloolxpvimeo', get_string('content', 'leeloolxpvimeo'), null, leeloolxpvimeo_get_editor_options($this->context));
        $mform->addRule('leeloolxpvimeo', get_string('required'), 'required', null, 'client');

        $mform->addElement('header', 'appearancehdr', get_string('appearance'));

        if ($this->current->instance) {
            $options = resourcelib_get_displayoptions(explode(',', $config->displayoptions), $this->current->display);
        } else {
            $options = resourcelib_get_displayoptions(explode(',', $config->displayoptions));
        }
        if (count($options) == 1) {
            $mform->addElement('hidden', 'display');
            $mform->setType('display', PARAM_INT);
            reset($options);
            $mform->setDefault('display', key($options));
        } else {
            $mform->addElement('select', 'display', get_string('displayselect', 'leeloolxpvimeo'), $options);
            $mform->setDefault('display', $config->display);
        }

        if (array_key_exists(RESOURCELIB_DISPLAY_POPUP, $options)) {
            $mform->addElement('text', 'popupwidth', get_string('popupwidth', 'leeloolxpvimeo'), array('size' => 3));
            if (count($options) > 1) {
                $mform->disabledIf('popupwidth', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupwidth', PARAM_INT);
            $mform->setDefault('popupwidth', $config->popupwidth);

            $mform->addElement('text', 'popupheight', get_string('popupheight', 'leeloolxpvimeo'), array('size' => 3));
            if (count($options) > 1) {
                $mform->disabledIf('popupheight', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupheight', PARAM_INT);
            $mform->setDefault('popupheight', $config->popupheight);
        }

        $mform->addElement('advcheckbox', 'printheading', get_string('printheading', 'leeloolxpvimeo'));
        $mform->setDefault('printheading', $config->printheading);
        $mform->addElement('advcheckbox', 'printintro', get_string('printintro', 'leeloolxpvimeo'));
        $mform->setDefault('printintro', $config->printintro);

        // Add legacy files flag only if used
        if (isset($this->current->legacyfiles) and $this->current->legacyfiles != RESOURCELIB_LEGACYFILES_NO) {
            $options = array(
                RESOURCELIB_LEGACYFILES_DONE => get_string('legacyfilesdone', 'leeloolxpvimeo'),
                RESOURCELIB_LEGACYFILES_ACTIVE => get_string('legacyfilesactive', 'leeloolxpvimeo')
            );
            $mform->addElement('select', 'legacyfiles', get_string('legacyfiles', 'leeloolxpvimeo'), $options);
            $mform->setAdvanced('legacyfiles', 1);
        }

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();

        $mform->addElement('hidden', 'revision');
        $mform->setType('revision', PARAM_INT);
        $mform->setDefault('revision', 1);
    }

    /**
     * Data processing
     *
     * @param object $defaultvalues default values
     */
    public function data_preprocessing(&$defaultvalues) {
        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('leeloolxpvimeo');
            $defaultvalues['leeloolxpvimeo']['format'] = $defaultvalues['contentformat'];
            $defaultvalues['leeloolxpvimeo']['text'] = file_prepare_draft_area(
                $draftitemid,
                $this->context->id,
                'mod_leeloolxpvimeo',
                'content',
                0,
                leeloolxpvimeo_get_editor_options($this->context),
                $defaultvalues['content']
            );
            $defaultvalues['leeloolxpvimeo']['itemid'] = $draftitemid;
        }
        if (!empty($defaultvalues['displayoptions'])) {
            $displayoptions = unserialize($defaultvalues['displayoptions']);
            if (isset($displayoptions['printintro'])) {
                $defaultvalues['printintro'] = $displayoptions['printintro'];
            }
            if (isset($displayoptions['printheading'])) {
                $defaultvalues['printheading'] = $displayoptions['printheading'];
            }
            if (!empty($displayoptions['popupwidth'])) {
                $defaultvalues['popupwidth'] = $displayoptions['popupwidth'];
            }
            if (!empty($displayoptions['popupheight'])) {
                $defaultvalues['popupheight'] = $displayoptions['popupheight'];
            }
        }
    }
}
