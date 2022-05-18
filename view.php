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
require_once($CFG->dirroot . '/mod/leeloolxpvimeo/locallib.php');
require_once($CFG->libdir . '/completionlib.php');

global $CFG;
require_once($CFG->libdir . '/filelib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID.
$p = optional_param('p', 0, PARAM_INT); // Page instance ID.
$inpopup = optional_param('inpopup', 0, PARAM_BOOL);

if ($p) {
    if (!$leeloolxpvimeo = $DB->get_record('leeloolxpvimeo', array('id' => $p))) {
        print_error('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('leeloolxpvimeo', $leeloolxpvimeo->id, $leeloolxpvimeo->course, false, MUST_EXIST);
} else {
    if (!$cm = get_coursemodule_from_id('leeloolxpvimeo', $id)) {
        print_error('invalidcoursemodule');
    }
    $leeloolxpvimeo = $DB->get_record('leeloolxpvimeo', array('id' => $cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/leeloolxpvimeo:view', $context);

// Trigger module viewed event.
$event = \mod_leeloolxpvimeo\event\course_module_viewed::create(array(
    'objectid' => $leeloolxpvimeo->id,
    'context' => $context,
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('leeloolxpvimeo', $leeloolxpvimeo);
$event->trigger();

// Update 'viewed' state if required by completion system.
require_once($CFG->libdir . '/completionlib.php');
$completion = new completion_info($course);

$PAGE->set_url('/mod/leeloolxpvimeo/view.php', array('id' => $cm->id));

$options = empty($leeloolxpvimeo->displayoptions) ? array() : unserialize($leeloolxpvimeo->displayoptions);

if ($inpopup and $leeloolxpvimeo->display == RESOURCELIB_DISPLAY_POPUP) {
    $PAGE->set_pagelayout('popup');
    $PAGE->set_title($course->shortname . ': ' . $leeloolxpvimeo->name);
    $PAGE->set_heading($course->fullname);
} else {
    $PAGE->set_title($course->shortname . ': ' . $leeloolxpvimeo->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($leeloolxpvimeo);
}
echo $OUTPUT->header();
if (!isset($options['printheading']) || !empty($options['printheading'])) {
    echo $OUTPUT->heading(format_string($leeloolxpvimeo->name), 2);
}

if (!empty($options['printintro'])) {
    if (trim(strip_tags($leeloolxpvimeo->intro))) {
        echo $OUTPUT->box_start('mod_introbox', 'leeloolxpvimeointro');
        echo format_module_intro('leeloolxpvimeo', $leeloolxpvimeo, $cm->id);
        echo $OUTPUT->box_end();
    }
}

$leeloolxplicense = get_config('mod_leeloolxpvimeo')->license;
$markcompleteafter = get_config('mod_leeloolxpvimeo')->markcompleteafter;
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
}

$infoleeloolxp = json_decode($output);

if ($infoleeloolxp->status != 'false') {
    $leeloolxpurl = $infoleeloolxp->data->install_url;
} else {
    notice(get_string('nolicense', 'mod_leeloolxpvimeo'));
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

$show = 1;

if (!$output = $curl->post($url, $postdata, $options)) {
    notice(get_string('nolicense', 'mod_leeloolxpvimeo'));
    $show = 0;
}

if ($leeloolxpvimeo->vimeo_video_id && $show == 1) {
    echo '<div class="videoWrapper"><iframe
    id="vimeoiframe"
    src="https://player.vimeo.com/video/' . $leeloolxpvimeo->vimeo_video_id . '"
    width="' . $leeloolxpvimeo->width . '"
    height="' . $leeloolxpvimeo->height . '"
    frameborder="' . $leeloolxpvimeo->border . '"
    allow="' . $leeloolxpvimeo->allow . '" allowfullscreen=""></iframe></div>
    <style>
    .videoWrapper {
        position: relative;
        padding-bottom: 56.25%; /* 16:9 */
        height: 0;
        margin-bottom: 20px;
      }
      .videoWrapper iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
      }
    </style>
    ';
}

$content = file_rewrite_pluginfile_urls($leeloolxpvimeo->content, 'pluginfile.php', $context->id, 'mod_leeloolxpvimeo', 'content', $leeloolxpvimeo->revision);
$formatoptions = new stdClass;
$formatoptions->noclean = true;
$formatoptions->overflowdiv = true;
$formatoptions->context = $context;
$content = format_text($content, $leeloolxpvimeo->contentformat, $formatoptions);
echo '<h3>' . $leeloolxpvimeo->name . '</h3>';
echo '<p class="publisheddate">' . get_string('publishedon', 'mod_leeloolxpvimeo') . date('M-d-Y', $leeloolxpvimeo->timemodified) . '</p></h3>';
echo $OUTPUT->box($content, "generalbox center clearfix");
global $USER;
if ($show == 1) {
    $PAGE->requires->js_init_code('require(["jquery"], function ($) {
        $(document).ready(function () {
            var iframe = document.querySelector("#vimeoiframe");
            var player = new Vimeo.Player(iframe);

            $("#autoplay_vimeo").change(function() {
                if(this.checked) {
                    Cookies.set("autoplay", 1);
                }else{
                    Cookies.set("autoplay", 0);
                }
            });


            var marked = 0;
            var markcompleteafter = ' . ($markcompleteafter / 100) . ';
            player.on("timeupdate", function(data){
                var running_time = data.seconds;
                Cookies.set("vimeotimeElapsed' . $cm->id . '", data.seconds);

                if( marked == 0 && data.percent >= markcompleteafter  ){
                    console.warn("marked");
                    marked = 1;

                    $.post(
                        "' . $CFG->wwwroot . '/mod/leeloolxpvimeo/markcomplete.php",
                        {
                            id:"' . $cm->id . '",
                            completionstate:"1",
                            fromajax:"1",
                            sesskey:"' . $USER->sesskey . '"
                        }, function(response){
                            var autoplay = Cookies.get("autoplay");
                            if( autoplay == 1 ){
                                //console.log("autoplay");
                                var nextvideo = $("#nextvideo").val();
                                if( nextvideo != "" ){
                                    window.location.href = nextvideo;
                                }
                            }else{
                                //console.log("notautoplay");
                            }
                            //console.log("marked complete");
                    });

                }

            });

            var timeElapsed = Cookies.get("vimeotimeElapsed' . $cm->id . '");
            if(timeElapsed){
                player.setCurrentTime(timeElapsed);
            }

            player.on("ended", function() {
                console.log("ended the video!");
            });

            player.on("play", function() {
                console.log("played the video!");
            });

            player.getVideoTitle().then(function(title) {
                //console.log("title:", title);
            });
        });
    });');
    echo '<script src="https://player.vimeo.com/api/player.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/js-cookie@rc/dist/js.cookie.min.js"></script>';
}

echo $OUTPUT->footer();
