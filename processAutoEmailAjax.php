<?php
if(!isset($wpdb))
{
    require_once('../../../wp-config.php');
    require_once('../../../wp-load.php');
    require_once('../../../wp-includes/wp-db.php');
}
global $wpdb;
$activityid = $_GET['activityid'];
$activitytext = $_GET['activitytext'];
$userid = $_GET['userid'];
$postid = $_GET['postid'];
$pageorder =  $_GET['formorder'];

if ($activityid > 0){
//update the db
$sql = "Update wp_course_activities set page_order=". $pageorder .",activity_value='". $activitytext ."',updated_dt=now() where activity_id =". $activityid;
$wpdb->query($sql);
}
else{
$sql = "Insert into wp_course_activities (post_id, user_id,page_order,activity_value,entry_dt, updated_dt) VALUES (".$postid.",". $userid.",". $pageorder.",'". $activitytext."',CURRENT_TIMESTAMP, now())";
$wpdb->query($sql);	
$activityid = $wpdb->insert_id;
}
$returnvars = array(
	  "formorder" => $pageorder,
      "activityid" => $activityid,
      "postid" => $postid,
      "userid" => $userid,
      "activitytext"=> $activitytext,
);
print json_encode($returnvars);

?>