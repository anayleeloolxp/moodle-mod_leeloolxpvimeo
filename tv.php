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

    $leeloolxpvimeoscount = $DB->get_record_sql("SELECT count(*) total FROM {leeloolxpvimeo} v left join {course} c on c.id = v.course WHERE v.vimeo_video_id != '' AND ".$DB->sql_like('v.name', ':name', false, false)." ".$sortbysql, ['name' => '%'.$DB->sql_like_escape($searchstring).'%']);

}else{

    $leeloolxpvimeos = $DB->get_records_sql("SELECT v.*, c.fullname coursename FROM {leeloolxpvimeo} v left join {course} c on c.id = v.course"." where v.vimeo_video_id != '' ".$sortbysql, [], $from, $perpage);

    $leeloolxpvimeoscount = $DB->get_record_sql("SELECT count(*) total FROM {leeloolxpvimeo} v left join {course} c on c.id = v.course"." where v.vimeo_video_id != '' ".$sortbysql);

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

$nosearchresultscss = 'style="display:none;"';

if( $leeloolxpvimeoscount->total == 0 ){
    $loopvideos = $leeloolxpextras;
    $nosearchresultscss = '';
}else{
    $loopvideos = $leeloolxpvimeos;
}

$listhtml = '';

foreach( $loopvideos as $key=>$leeloolxpvimeo ){

    $coursecontext = context_course::instance($leeloolxpvimeo->course);
    if(is_enrolled($coursecontext, $USER->id)){
        $loopvideos[$key]->enrolled = 'enrolled';
    }else{
        $loopvideos[$key]->enrolled = 'notenrolled';
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
    $loopvideos[$key]->image = $arroutput->pictures->base_link;

    $leeloolxpmod = $DB->get_record_sql('SELECT cm.id FROM {course_modules} cm left join {modules} m on m.id = cm.module left join {leeloolxpvimeo} vinner on vinner.id = cm.instance where m.name = "leeloolxpvimeo" and vinner.id = ?', array($leeloolxpvimeo->id) );

    $loopvideos[$key]->modid = $leeloolxpmod->id;

    if( $leeloolxpvimeo->enrolled == "enrolled" ){
        $link = $CFG->wwwroot . '/mod/leeloolxpvimeo/tv_single.php?id='.$leeloolxpmod->id;
        $enrollicon = '<i class="fa fa-check-circle"></i>';
    }else{
        $link = "javascript:void(0);";
        $link = $CFG->wwwroot . '/mod/leeloolxpvimeo/tv_single.php?id='.$leeloolxpmod->id;
        $enrollicon = '<i class="fa fa-plus-circle"></i>';
    }

    $listhtml .= "<div class='vimeovideosin ".$leeloolxpvimeo->enrolled."'><a href='".$link."'><div class='vimeovideoimg'><img src='".$arroutput->pictures->base_link."'/></div><div class='vimeovideotitle'>".$leeloolxpvimeo->name."</div><div class='vimeovideocourse'><span class='vimeocoursename'>".$leeloolxpvimeo->coursename."</span><span class='vimeoenrollicon'>".$enrollicon."</span></div></a></div>";


}

$PAGE->set_url('/mod/leeloolxpvimeo/tv.php');

$PAGE->set_title('TV');

echo $OUTPUT->header();

$hidecountcss = '';
if( $searchstring == '' ){
    $hidecountcss = 'style="visibility: hidden;"';
}

echo '<div class="search_vimeotv_nav"><div class="search_vimeotv_left"><ul><li><a href="'.$CFG->wwwroot.'"><img src="'.$CFG->wwwroot.'/mod/leeloolxpvimeo/pix/home-icn-img.png" /></a></li><li><a href="'.$CFG->wwwroot.'/mod/leeloolxpvimeo/tv.php"><img src="'.$CFG->wwwroot.'/mod/leeloolxpvimeo/pix/play-icn-img.png" /></a></li></ul></div> <div class="search_vimeotv_div"><input class="search_vimeotv" value="'.$searchstring.'" placeholder="Search Videos"/> <button class="search_vimeotv_btn">Search</button></div></div><div class="search_vimeotv_btm"><div class="search_vi_lft" '.$hidecountcss.' ><span class="total_results">'.$leeloolxpvimeoscount->total.'</span> results for <span class="searchstring">'.$searchstring.'</span></div><div class="search_vi_rit">Sort by: <select class="leeloolxpvimeosortby"><!--<option value="relevance">Relevance</option> --><option value="latest">Recently uploaded</option> <!--<option value="popularity">Popularity</option>--> <option value="nameasc">Title (A to Z)</option> <option value="namedesc">Title (Z to A)</option> <!--<option value="long">Longest</option> <option value="short">Shortest</option>--></select></div></div>';

echo '<div class="nosearchresults" '.$nosearchresultscss.'>Try searching again using broader keywords.</br>You could also watch one of the videos below instead.</div>';

echo '<div class="vimeovideoslist">'.$listhtml.'</div><div class="vimeovideosloading" style="display:none;"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i><span class="sr-only">Loading...</span></div>';


echo '<input type="hidden" class="vimeopagelimit" value="12"/>
<input type="hidden" class="vimeopagenext" value="2"/>
<input type="hidden" class="vimeoshown" value="12"/>
<input type="hidden" class="vimeototal" value="'.$leeloolxpvimeoscount->total.'"/>';
$PAGE->requires->js_init_code('require(["jquery"], function ($) {
    $(document).ready(function () {

        function getvideos(page = 1){

            var checkisloading = $(".vimeovideosloading").is(":visible");

            var total = $(".vimeototal").val();
            var shown = $(".vimeoshown").val();

            if( (total > shown || page == 1) && !checkisloading ){

                $(".nosearchresults").hide();
                $(".search_vi_lft").css("visibility", "hidden");

                $(".vimeovideosloading").show();

                if( page == 1 ){
                    $(".vimeovideoslist").html("");
                }

                $.post(
                    "' . $CFG->wwwroot . '/mod/leeloolxpvimeo/tv_list_ajax.php",
                    {
                        search: $(".search_vimeotv").val(),
                        p: page,
                        limit: $(".vimeopagelimit").val(),
                        sortby: $(".leeloolxpvimeosortby").val(),
                    }, function(response){

                        var searchstring = $(".search_vimeotv").val();

                        if( searchstring == "" ){
                            $(".search_vi_lft").css("visibility", "hidden");
                        }else{
                            $(".search_vi_lft").css("visibility", "visible");
                        }

                        $(".vimeovideosloading").hide();
                        var responsejson = JSON.parse(response);
                        $(".vimeopagenext").val(responsejson.next);
                        $(".vimeoshown").val(responsejson.shown);
                        $(".vimeototal").val(responsejson.totalobj.total);

                        $(".searchstring").text($(".search_vimeotv").val());
                        $(".total_results").text(responsejson.totalobj.total);

                        if( responsejson.totalobj.total == 0 ){
                            var loopvideos = responsejson.leeloolxpextra;
                            $(".nosearchresults").show();
                        }else{
                            var loopvideos = responsejson.videos;
                        }

                        $.each(loopvideos, function(key,val) {  
                            
                            if( val.enrolled == "enrolled" ){
                                var link = "' . $CFG->wwwroot . '/mod/leeloolxpvimeo/tv_single.php?id="+val.modid;
                                var enrollicon = \'<i class="fa fa-check-circle"></i>\';
                            }else{
                                var link = "javascript:void(0);";
                                var link = "' . $CFG->wwwroot . '/mod/leeloolxpvimeo/tv_single.php?id="+val.modid;
                                var enrollicon = \'<i class="fa fa-plus-circle"></i>\';
                            }
                            
                            $(".vimeovideoslist").append("<div class=\'vimeovideosin "+val.enrolled+" \'><a href=\'"+link+"\'><div class=\'vimeovideoimg\'><img src=\'"+val.image+"\'/></div><div class=\'vimeovideotitle\'>"+val.name+"</div><div class=\'vimeovideocourse\'><span class=\'vimeocoursename\'>"+val.coursename+"</span><span class=\'vimeoenrollicon\'>"+enrollicon+"</span></div></a></div>");
                        });   

                    }
                );
            }
        }

        //getvideos(1);

        $(".search_vimeotv").keypress(function (e) {
            var key = e.which;
            if(key == 13)  // the enter key code
            {
                getvideos(1);
                return false;  
            }
        });
        
        $(".search_vimeotv_btn").click(function (e) {
            getvideos(1);
        });

        $(".leeloolxpvimeosortby").change(function (e) {
            getvideos(1);
        });

        var lastScrollTop = 0;
        $(window).scroll(function () { 

            var st = $(this).scrollTop();

            if ( ($(window).scrollTop() >= $( ".vimeovideoslist").offset().top + $(".vimeovideoslist").outerHeight() - window.innerHeight) && st > lastScrollTop ) {

                var page = $(".vimeopagenext").val();
                getvideos(page);                    
                
            }

            lastScrollTop = st;

        });
    });
});');


echo $OUTPUT->footer();
