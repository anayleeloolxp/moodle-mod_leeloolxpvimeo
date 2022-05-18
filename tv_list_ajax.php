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

global $CFG, $DB, $USER;
require_once($CFG->libdir . '/filelib.php');

$searchstring = optional_param('search', '', PARAM_RAW); // Course Module ID
$p = optional_param('p', 1, PARAM_INT); // Page instance ID
$limit = optional_param('limit', 12, PARAM_INT); // Page instance ID
$sortby = optional_param('sortby', 'latest', PARAM_RAW); // Page instance ID

$sortbysql = 'ORDER BY v.name ASC';
if( $sortby == 'nameasc' ){
    $sortbysql = 'ORDER BY v.name ASC';
}elseif( $sortby == 'namedesc' ){
    $sortbysql = 'ORDER BY v.name DESC';
}elseif( $sortby == 'latest' ){
    $sortbysql = 'ORDER BY v.id DESC';
}

$perpage = $limit;
$from = 0;

if( $p ){
    $from = $perpage * ($p - 1);
}

if ($searchstring) {

    $leeloolxpvimeos = $DB->get_records_sql("SELECT v.*, c.fullname coursename FROM {leeloolxpvimeo} v left join {course} c on c.id = v.course WHERE v.vimeo_video_id != '' AND ".$DB->sql_like('v.name', ':name', false, false)." ".$sortbysql, ['name' => '%'.$DB->sql_like_escape($searchstring).'%'], $from, $perpage);

    $leeloolxpvimeoscount = $DB->get_record_sql("SELECT count(*) total FROM {leeloolxpvimeo} v left join {course} c on c.id = v.course WHERE v.vimeo_video_id != '' AND ".$DB->sql_like('v.name', ':name', false, false)." ", ['name' => '%'.$DB->sql_like_escape($searchstring).'%']);

}else{

    $leeloolxpvimeos = $DB->get_records_sql("SELECT v.*, c.fullname coursename FROM {leeloolxpvimeo} v left join {course} c on c.id = v.course"." where v.vimeo_video_id != '' ".$sortbysql, [], $from, $perpage);

    $leeloolxpvimeoscount = $DB->get_record_sql("SELECT count(*) total FROM {leeloolxpvimeo} v left join {course} c on c.id = v.course"." where v.vimeo_video_id != '' ");

}

$leeloolxpvimeos = array_values($leeloolxpvimeos);

foreach( $leeloolxpvimeos as $key=>$leeloolxpvimeo ){

    $coursecontext = context_course::instance($leeloolxpvimeo->course);
    if(is_enrolled($coursecontext, $USER->id)){
        $leeloolxpvimeos[$key]->enrolled = 'enrolled';
    }else{
        $leeloolxpvimeos[$key]->enrolled = 'notenrolled';
    }

    $url = 'https://api.vimeo.com/videos/'.$leeloolxpvimeo->vimeo_video_id;

    $postdata = array();

    $curl = new curl;

    $headers = array();
    $headers[] = 'Authorization: bearer '.$leeloolxpvimeo->vimeo_token;

    $curloptions = array(
        'CURLOPT_HTTPHEADER' => $headers,
        'CURLOPT_RETURNTRANSFER' => true,
        'CURLOPT_CUSTOMREQUEST' => 'GET',
    );

    $output = $curl->post($url, $postdata, $curloptions);
    $arroutput = json_decode($output);
    $leeloolxpvimeos[$key]->image = $arroutput->pictures->base_link;

    $leeloolxpmod = $DB->get_record_sql('SELECT cm.id FROM {course_modules} cm left join {modules} m on m.id = cm.module left join {leeloolxpvimeo} vinner on vinner.id = cm.instance where m.name = "leeloolxpvimeo" and vinner.id = ?', array($leeloolxpvimeo->id) );

    $leeloolxpvimeos[$key]->modid = $leeloolxpmod->id;
}

$leeloolxpextras = $DB->get_records_sql("SELECT v.*, c.fullname coursename FROM {leeloolxpvimeo} v left join {course} c on c.id = v.course where v.vimeo_video_id != '' ".$sortbysql." limit 12");

if( $searchstring && $leeloolxpvimeoscount->total == 0 ){

    foreach( $leeloolxpextras as $key=>$leeloolxpextra ){

        $coursecontext = context_course::instance($leeloolxpextra->course);
        if(is_enrolled($coursecontext, $USER->id)){
            $leeloolxpextras[$key]->enrolled = 'enrolled';
        }else{
            $leeloolxpextras[$key]->enrolled = 'notenrolled';
        }

        $url = 'https://api.vimeo.com/videos/'.$leeloolxpextra->vimeo_video_id;

        $postdata = array();

        $curl = new curl;

        $headers = array();
        $headers[] = 'Authorization: bearer '.$leeloolxpextra->vimeo_token;

        $curloptions = array(
            'CURLOPT_HTTPHEADER' => $headers,
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_CUSTOMREQUEST' => 'GET',
        );

        $output = $curl->post($url, $postdata, $curloptions);
        $arroutput = json_decode($output);
        $leeloolxpextras[$key]->image = $arroutput->pictures->base_link;

        $leeloolxpmod = $DB->get_record_sql('SELECT cm.id FROM {course_modules} cm left join {modules} m on m.id = cm.module left join {leeloolxpvimeo} vinner on vinner.id = cm.instance where m.name = "leeloolxpvimeo" and vinner.id = ?', array($leeloolxpextra->id) );

        $leeloolxpextras[$key]->modid = $leeloolxpmod->id;
    }

}


$response = array();
$response['videos'] = $leeloolxpvimeos;
$response['next'] = $p+1;
$response['totalobj'] = $leeloolxpvimeoscount;
$response['shown'] = $perpage*$p;
$response['leeloolxpextra'] = $leeloolxpextras;

echo json_encode($response);
die;
