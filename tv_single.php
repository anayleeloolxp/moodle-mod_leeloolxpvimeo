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
        throw new moodle_exception('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('leeloolxpvimeo', $leeloolxpvimeo->id, $leeloolxpvimeo->course, false, MUST_EXIST);
} else {
    if (!$cm = get_coursemodule_from_id('leeloolxpvimeo', $id)) {
        throw new moodle_exception('invalidcoursemodule');
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

$PAGE->set_url('/mod/leeloolxpvimeo/tv_single.php', array('id' => $cm->id));

$options = empty($leeloolxpvimeo->displayoptions) ? array() : unserialize($leeloolxpvimeo->displayoptions);

if ($inpopup and $leeloolxpvimeo->display == RESOURCELIB_DISPLAY_POPUP) {
    $PAGE->set_pagelayout('popup');
    $PAGE->set_title($course->shortname . ': ' . $leeloolxpvimeo->name);
} else {
    $PAGE->set_title($course->shortname . ': ' . $leeloolxpvimeo->name);
    $PAGE->set_activity_record($leeloolxpvimeo);
}
echo $OUTPUT->header();

$videotitlearr = explode(' ', $leeloolxpvimeo->name);

$namesql = '';
foreach ($videotitlearr as $videtitlesin) {
    $videtitlesinsql = str_ireplace('?', '', $videtitlesin);
    $namesql .= ' OR v.name LIKE "%' . $videtitlesinsql . '%"';
}

$leeloolxprelatedvimeosall = $DB->get_records_sql("SELECT v.*, c.fullname coursename FROM {leeloolxpvimeo} v left join {course} c on c.id = v.course where v.course = ?" . $namesql, [$course->id], 0, 10);

$leeloolxprelatedvimeos = array_values($leeloolxprelatedvimeosall);

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

$courseurl = new moodle_url('\course/view.php', array('id' => $course->id));

if ($_COOKIE['autoplay'] == 1) {
    $autoplaychecked = 'checked';
} else {
    $autoplaychecked = '';
}

echo '<div class="search_vimeotv_nav"><div class="search_vimeotv_left"><ul><li><a href="' . $CFG->wwwroot . '"><img src="' . $CFG->wwwroot . '/mod/leeloolxpvimeo/pix/home-icn-img.png"></a></li><li><a href="' . $courseurl . '"><div class="vimeotv-iim"><div class="vimeotv-im"><img src="' . leeloolxpvimeo_course_image($course) . '"></div><div class="vimeotv-txtt"><p>' . $course->fullname . '</p></div></div></a></li></ul></div> <div class="search_vimeotv_div"><form method="GET" action="' . $CFG->wwwroot . '/mod/leeloolxpvimeo/tv.php" ><input class="search_vimeotv" name="search" value="" placeholder="Search Videos"> <button class="search_vimeotv_btn">Search</button></form></div><div class="search_vimeotv_right"><div class="vimeotv_auto">Autoplay <span><input ' . $autoplaychecked . ' type="checkbox" name="autoplay_vimeo" id="autoplay_vimeo" /><label for="autoplay_vimeo"></label></span></div><div class="vimeotv_close"><a href="' . $courseurl . '">X</a></div></div></div>';

echo '<div class="tv_single_page_container"><div class="tv_single_page_left">';

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

$content = file_rewrite_pluginfile_urls(
    $leeloolxpvimeo->content,
    'pluginfile.php',
    $context->id,
    'mod_leeloolxpvimeo',
    'content',
    $leeloolxpvimeo->revision
);
$formatoptions = new stdClass;
$formatoptions->noclean = true;
$formatoptions->overflowdiv = true;
$formatoptions->context = $context;
$content = format_text($content, $leeloolxpvimeo->contentformat, $formatoptions);
echo '<h3>' . $leeloolxpvimeo->name . '</h3>';
echo '<p class="publisheddate">' .
    get_string('publishedon', 'mod_leeloolxpvimeo') .
    date('M-d-Y', $leeloolxpvimeo->timemodified) . '</p></h3>';
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
                            console.log("marked complete");
                    });

                }
            });

            var timeElapsed = Cookies.get("vimeotimeElapsed' . $cm->id . '");
            if(timeElapsed){
                player.setCurrentTime(timeElapsed);
            }

            player.on("ended", function() {
                console.log("ended the video!");
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


    echo '</div><div class="tv_single_page_right">';

    $relatedvideoshtml = '';

    $nextvideo = '';

    $count = 1;
    $thiskey = 0;
    foreach ($leeloolxprelatedvimeos as $key => $relatedvideo) {

        $leeloolxpmod = $DB->get_record_sql(
            "SELECT cm.id FROM {course_modules} cm left join {modules} m on m.id = cm.module left join {leeloolxpvimeo} vinner on vinner.id = cm.instance where m.name = 'leeloolxpvimeo' and vinner.id = ?",
            array($relatedvideo->id)
        );

        $relatedvideourl = $CFG->wwwroot . '/mod/leeloolxpvimeo/tv_single.php?id=' . $leeloolxpmod->id;

        $url = 'https://api.vimeo.com/videos/' . $relatedvideo->vimeo_video_id;

        $leeloolxprelatedvimeos[$key]->url = $relatedvideourl;

        if ($leeloolxpmod->id == $id) {
            $thiskey = $key;
        }

        $postdata = array();
        $curl = new curl;
        $headers = array();
        $headers[] = 'Authorization: bearer ' . $relatedvideo->vimeo_token;
        $curloptions = array(
            'CURLOPT_HTTPHEADER' => $headers,
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_CUSTOMREQUEST' => 'GET',
        );
        $output = $curl->post($url, $postdata, $curloptions);
        $arroutput = json_decode($output);
        if ($arroutput->pictures->base_link != '') {
            $videoicon = '<img src="' . $arroutput->pictures->base_link . '"/>';
        } else {
            $videoicon = '<img src="' . $CFG->wwwroot . '/mod/leeloolxpvimeo/pix/default_icon.png"/>';
        }

        $relatedvideoshtml .= '<div class="related_video_item">
            <div class="related_video_img">
                ' . $videoicon . '
            </div>
            <div class="related_video_txt">
                <h4><a href="' . $relatedvideourl . '">' . $relatedvideo->name . '</a></h4>
                <p>' . get_string('publishedon', 'mod_leeloolxpvimeo') . date('M-d-Y', $relatedvideo->timemodified) . '</p>
            </div>
        </div>';

        $count++;
    }

    if (isset($leeloolxprelatedvimeos[$thiskey + 1])) {
        $nextvideo = $leeloolxprelatedvimeos[$thiskey + 1]->url;
    } else {
        $nextvideo = $leeloolxprelatedvimeos[0]->url;
    }

    echo '
        <div class="related_video_section">
            <div class="related_video_head">Related Videos</div>
            <div class="related_video_items">

                ' . $relatedvideoshtml . '

            </div>
            <input type="hidden" id="nextvideo" value="' . $nextvideo . '"/>
        </div>
    ';

    echo '</div></div>';
}

echo $OUTPUT->footer();
