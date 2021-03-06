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
 * Leeloo LXP Vimeo external API
 *
 * @package    mod_leeloolxpvimeo
 * @category   external
 * @copyright  2020 Leeloo LXP (https://leeloolxp.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/filelib.php');
require_once("$CFG->libdir/externallib.php");

/**
 * Leeloo LXP Vimeo external functions
 *
 * @package    mod_leeloolxpvimeo
 * @category   external
 * @copyright  2020 Leeloo LXP (https://leeloolxp.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */
class mod_leeloolxpvimeo_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function view_leeloolxpvimeo_parameters() {
        return new external_function_parameters(
            array(
                'leeloolxpvimeoid' => new external_value(PARAM_INT, 'leeloolxpvimeo instance id'),
            )
        );
    }

    /**
     * Simulate the leeloolxpvimeo/view.php web interface leeloolxpvimeo: trigger events, completion, etc...
     *
     * @param int $leeloolxpvimeoid the leeloolxpvimeo instance id
     * @return array of warnings and status result
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function view_leeloolxpvimeo($leeloolxpvimeoid) {
        global $DB, $CFG;
        require_once($CFG->dirroot . "/mod/leeloolxpvimeo/lib.php");

        $params = self::validate_parameters(
            self::view_leeloolxpvimeo_parameters(),
            array(
                'leeloolxpvimeoid' => $leeloolxpvimeoid,
            )
        );
        $warnings = array();

        // Request and permission validation.
        $leeloolxpvimeo = $DB->get_record('leeloolxpvimeo', array('id' => $params['leeloolxpvimeoid']), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($leeloolxpvimeo, 'leeloolxpvimeo');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/leeloolxpvimeo:view', $context);

        // Call the leeloolxpvimeo/lib API.
        leeloolxpvimeo_view($leeloolxpvimeo, $course, $cm, $context);

        $result = array();
        $result['status'] = true;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function view_leeloolxpvimeo_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_leeloolxpvimeos_by_courses.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_leeloolxpvimeos_by_courses_parameters() {
        return new external_function_parameters(
            array(
                'courseids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Course id'),
                    'Array of course ids',
                    VALUE_DEFAULT,
                    array()
                ),
            )
        );
    }

    /**
     * Returns a list of leeloolxpvimeos in a provided list of courses.
     * If no list is provided all leeloolxpvimeos that the user can view will be returned.
     *
     * @param array $courseids course ids
     * @return array of warnings and leeloolxpvimeos
     * @since Moodle 3.3
     */
    public static function get_leeloolxpvimeos_by_courses($courseids = array()) {

        global $USER, $CFG;
        $token = optional_param('wstoken', '', PARAM_ALPHANUM);
        $warnings = array();
        $returnedleeloolxpvimeos = array();

        $params = array(
            'courseids' => $courseids,
        );
        $params = self::validate_parameters(self::get_leeloolxpvimeos_by_courses_parameters(), $params);

        $mycourses = array();
        if (empty($params['courseids'])) {
            $mycourses = enrol_get_my_courses();
            $params['courseids'] = array_keys($mycourses);
        }

        // Ensure there are courseids to loop through.
        if (!empty($params['courseids'])) {

            list($courses, $warnings) = external_util::validate_courses($params['courseids'], $mycourses);

            // Get the leeloolxpvimeos in this course, this function checks users visibility permissions.
            // We can avoid then additional validate_context calls.
            $leeloolxpvimeos = get_all_instances_in_courses("leeloolxpvimeo", $courses);
            foreach ($leeloolxpvimeos as $leeloolxpvimeo) {
                $context = context_module::instance($leeloolxpvimeo->coursemodule);
                // Entry to return.
                $leeloolxpvimeo->name = external_format_string($leeloolxpvimeo->name, $context->id);

                $options = array('noclean' => true);

                list($leeloolxpvimeo->intro, $leeloolxpvimeo->introformat) =
                    external_format_text(
                        $leeloolxpvimeo->intro,
                        $leeloolxpvimeo->introformat,
                        $context->id,
                        'mod_leeloolxpvimeo',
                        'intro',
                        null,
                        $options
                    );

                $leeloolxpvimeo->introfiles = external_util::get_area_files(
                    $context->id,
                    'mod_leeloolxpvimeo',
                    'intro',
                    false,
                    false
                );

                $options = array('noclean' => true);
                list($leeloolxpvimeo->content, $leeloolxpvimeo->contentformat) = external_format_text(
                    $leeloolxpvimeo->content,
                    $leeloolxpvimeo->contentformat,
                    $context->id,
                    'mod_leeloolxpvimeo',
                    'content',
                    $leeloolxpvimeo->revision,
                    $options
                );
                $leeloolxpvimeo->contentfiles = external_util::get_area_files($context->id, 'mod_leeloolxpvimeo', 'content');

                $leeloolxpvimeo->iframesrc = 'https://player.vimeo.com/video/' . $leeloolxpvimeo->vimeo_video_id;

                $leeloolxpvimeo->iframesrc = $CFG->wwwroot .
                    '/mod/leeloolxpvimeo/view_app.php?id=' .
                    $leeloolxpvimeo->coursemodule .
                    '&token=' . $token . '&userid=' . $USER->id;

                $returnedleeloolxpvimeos[] = $leeloolxpvimeo;
            }
        }

        $result = array(
            'leeloolxpvimeos' => $returnedleeloolxpvimeos,
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the get_leeloolxpvimeos_by_courses return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_leeloolxpvimeos_by_courses_returns() {
        return new external_single_structure(
            array(
                'leeloolxpvimeos' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Module id'),
                            'coursemodule' => new external_value(PARAM_INT, 'Course module id'),
                            'course' => new external_value(PARAM_INT, 'Course id'),
                            'name' => new external_value(PARAM_RAW, 'leeloolxpvimeo name'),
                            'iframesrc' => new external_value(PARAM_RAW, 'leeloolxpvimeo iframesrc'),
                            'intro' => new external_value(PARAM_RAW, 'Summary'),
                            'introformat' => new external_format_value('intro', 'Summary format'),
                            'introfiles' => new external_files('Files in the introduction text'),
                            'content' => new external_value(PARAM_RAW, 'leeloolxpvimeo content'),
                            'contentformat' => new external_format_value('content', 'Content format'),
                            'contentfiles' => new external_files('Files in the content'),
                            'legacyfiles' => new external_value(PARAM_INT, 'Legacy files flag'),
                            'legacyfileslast' => new external_value(PARAM_INT, 'Legacy files last control flag'),
                            'display' => new external_value(PARAM_INT, 'How to display the leeloolxpvimeo'),
                            'displayoptions' => new external_value(PARAM_RAW, 'Display options (width, height)'),
                            'revision' => new external_value(PARAM_INT, 'Incremented when after each file changes, to avoid cache'),
                            'timemodified' => new external_value(PARAM_INT, 'Last time the leeloolxpvimeo was modified'),
                            'section' => new external_value(PARAM_INT, 'Course section id'),
                            'visible' => new external_value(PARAM_INT, 'Module visibility'),
                            'groupmode' => new external_value(PARAM_INT, 'Group mode'),
                            'groupingid' => new external_value(PARAM_INT, 'Grouping id'),
                        )
                    )
                ),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function markcomplete_leeloolxpvimeo_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value(PARAM_INT, 'module instance id'),
                'completionstate' => new external_value(PARAM_INT, 'completion state'),
                'userid' => new external_value(PARAM_INT, 'userid'),
            )
        );
    }

    /**
     * Simulate the leeloolxpvimeo/view.php web interface leeloolxpvimeo: trigger events, completion, etc...
     *
     * @param int $cmid the leeloolxpvimeo instance id
     * @param int $completionstate the leeloolxpvimeo completionstate
     * @param int $userid the leeloolxpvimeo userid
     * @return array of warnings and status result
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function markcomplete_leeloolxpvimeo($cmid, $completionstate, $userid) {
        global $DB, $CFG;
        require_once($CFG->dirroot . "/mod/leeloolxpvimeo/lib.php");
        require_once($CFG->libdir . '/completionlib.php');

        $params = self::validate_parameters(
            self::markcomplete_leeloolxpvimeo_parameters(),
            array(
                'cmid' => $cmid,
                'completionstate' => $completionstate,
                'userid' => $userid,
            )
        );
        $warnings = array();

        $targetstate = COMPLETION_COMPLETE;
        $thisuserid = $params['userid'];
        $fromajax = 1;

        $cm = get_coursemodule_from_id(null, $params['cmid'], null, true, MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

        $completion = new completion_info($course);
        $completion->set_module_viewed($cm);

        if (!$completion->is_enabled()) {
            throw new moodle_exception('completionnotenabled', 'completion');
        }

        if ($cm->completion != COMPLETION_TRACKING_MANUAL) {
            error_or_ajax('cannotmanualctrack', $fromajax);
        }

        $completion->update_state($cm, $targetstate, $thisuserid);

        $result = array();
        $result['status'] = true;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function markcomplete_leeloolxpvimeo_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'warnings' => new external_warnings(),
            )
        );
    }
}
