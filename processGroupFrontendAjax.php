<?php
if(!isset($wpdb))
{
    require_once('../../../wp-config.php');
    require_once('../../../wp-load.php');
    require_once('../../../wp-includes/wp-db.php');
}
global $wpdb;
//*******************************************************************
//handle promoting to a group leader (editor)
//*******************************************************************
if ($_POST['action'] == 'promote_user_to_roster_leader'){
global $wpdb;
$object_id = $_POST['id'];
$groupid = $_POST['groupid'];
$terminfo=get_term_by( 'id', $groupid,'user-group');
$term_taxonomy_id = $terminfo->term_taxonomy_id;
$sql = $wpdb->prepare("UPDATE wp_term_relationships SET term_order = %d WHERE object_id = %d and term_taxonomy_id= %d",array(1,$object_id, $term_taxonomy_id));
$wpdb->query($sql);
$returnvars = array("updated" => "success");
print json_encode($returnvars);
}
//*******************************************************************
//handle removing a user from a roster
//*******************************************************************
elseif ($_POST['action'] == 'remove_user_from_roster'){
global $wpdb;
$object_id = $_POST['id'];
$groupid = $_POST['groupid'];
$terminfo=get_term_by( 'id', $groupid,'user-group');
$term_taxonomy_id = $terminfo->term_taxonomy_id;
$sql = $wpdb->prepare("DELETE from wp_term_relationships WHERE object_id = %d and term_taxonomy_id= %d",array($object_id, $term_taxonomy_id));
$wpdb->query($sql);
//change the count on the roster
$wpdb->update ( 
	'wp_term_taxonomy', 
	array( 
		'count' => count-1	
	), 
	array( 'term_taxonomy_id' => $term_taxonomy_id ), 
	array( 
		'%d'
	), 
	array( '%d' ) 
);
//$wpdb->query('Update wp_term_taxonomy set count=count-1 where term_taxonomy_id ='.$term_taxonomy_id);
$returnvars = array("updated" => "success");
print json_encode($returnvars);
}//end removing from a roster
//*******************************************************************
//handle if the form preloads with data for editing
//*******************************************************************
elseif ($_POST['action'] == 'get_group_info'){
$aOptions = get_option( 'user-group-meta' );
$index=$_POST['id'];
$terminfo=get_term_by( 'id', $index,'user-group');
$returnvars = array(
              "groupname" =>stripslashes($terminfo->name),
	          "groupsemester" =>stripslashes($aOptions[$index]['group-semester']),
			  "groupdescription"=>stripslashes($terminfo->description),
			  "groupschool" =>stripslashes($aOptions[$index]['group-school']),
	          );
print json_encode($returnvars);
}//end preload form
//*******************************************************************
//handle assigning roster members for roster group
//*******************************************************************
elseif ($_POST['action'] == "save_member_order"){
//name = user_id, order = term_order (member role) from jquery
	  global $wpdb;
	  $member_name= $_POST['name'];
	  $member_order=$_POST['order'];
	  $term_tax_id =$_POST['term_taxonomy_id'];
	  //update table for member roles of roster group
	  $table = 'wp_term_relationships';
	  $data_array = array('term_order' => $member_order);
	  $where = array('object_id' => $member_name, 'term_taxonomy_id' => $term_tax_id );
	  $result = $wpdb->update( $table, $data_array, $where);
	  $returnvars = array(
              "rosteredit" =>"successful",
		);
		print json_encode($returnvars);
}
//*******************************************************************
//handle deleting a roster
//*******************************************************************
elseif ($_POST['action'] == 'delete_a_roster'){
global $wpdb;
$groupid = $_POST['rostergroupid'];

	wp_delete_term( $groupid, 'user-group');
//update the term meta serialized data
	$term_meta = (array) get_option('user-group-meta');
//unset the option values
	unset($term_meta[$groupid]['group-color']);
	unset($term_meta[$groupid]['group-school']);
	unset($term_meta[$groupid]['group-semester']);
//serialize the array 
	$sSerialized = serialize($term_meta);
//update the options with new data
	global $wpdb;
	$table = "wp_options";
	$data_array = array('option_value' => $sSerialized);
	$where = array('option_name' => 'user-group-meta');
	$wpdb->update( $table, $data_array, $where );
	$returnvars = array("updated" => "success");
print json_encode($returnvars);

}//end delete a roster
//*******************************************************************
//handle adding a new roster
//*******************************************************************
elseif (!$_POST['editroster']){

	  $groupname=$_POST['groupname'];
	  $groupdesc= $_POST['groupdescription'];
	  $groupschool= $_POST['groupschool'];
	  $groupsemester=$_POST['groupsemester'];
	  $groupid=$_POST['groupid'];
	  $randomcolor ='#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
      $bNewAccount = 0;
      $forumPostID =0;
//if there is no group ID yet then  create the group taxonomy
if ($groupid == ""){
//create the group
$parent_term = term_exists( $groupname, 'user-group' ); // array is returned if taxonomy is given
$parent_term_id = $parent_term['term_id']; // get numeric term id
	$msg = "";
	if ($parent_term_id > 0){
	$msg='The group name is already in use. Please choose another.';	
	}
	elseif ($groupid <= 0){
		$bNewAccount =1;
        $wpReturn = wp_insert_term(
	     $groupname, // the term 
	     'user-group', // the taxonomy
	    array(
	   'description'=> $groupdesc,
	   'slug' => str_replace(' ', '-', $groupname),
	  )
	);
	}
    $groupid = $wpReturn['term_id'];
	$termtaxid = $wpReturn['term_taxonomy_id'];
}
//------------------------------
//update the wp options with the group details
//------------------------------
if ($groupid > 0){
//get the serialized data into an array
$term_meta = (array) get_option('user-group-meta');
//set the option values
$term_meta[$groupid]['group-color'] =  $randomcolor;
$term_meta[$groupid]['group-school'] = $groupschool;
$term_meta[$groupid]['group-semester']=$groupsemester;
//serialize the array 
$sSerialized = serialize($term_meta);
//update the options with new data
global $wpdb;
$table = "wp_options";
$data_array = array('option_value' => $sSerialized);
$where = array('option_name' => 'user-group-meta');
$wpdb->update( $table, $data_array, $where );
//add the current user as the first faculty member of this group
$current_user = wp_get_current_user();
$terminfo=get_term_by( 'id', $groupid,'user-group');
$term_taxonomy_id = $terminfo->term_taxonomy_id;
$insert="INSERT into wp_term_relationships (object_id, term_taxonomy_id, term_order) VALUES (". $current_user->ID ."," . $term_taxonomy_id .",1)";
$wpdb->query($insert);
}
//create a forum post and attach it to this group
$returnvars = array(
	  "groupid" => $groupid,
	  "forumid" => $forumPostID,
	  "userid" =>  $current_user->ID,
	  "returnmsg" =>$msg,
);
print json_encode($returnvars);
}
//*******************************************************************
//handle editing a roster
//*******************************************************************
elseif ($_POST['editroster'] == 1){
      $groupname=$_POST['groupname'];
	  $groupdesc= $_POST['groupdescription'];
	  $groupschool= $_POST['groupschool'];
	  $groupsemester=$_POST['groupsemester'];
	  $groupid=$_POST['groupid'];
//get the serialized data into an array
$term_meta = (array) get_option('user-group-meta');
$term_meta[$groupid]['group-school'] = $groupschool;
$term_meta[$groupid]['group-semester']=$groupsemester;
//serialize the array 
$sSerialized = serialize($term_meta);
//update the options with new data
global $wpdb;
//update wp_options - school and semester
$table = "wp_options";
$data_array = array('option_value' => $sSerialized);
$where = array('option_name' => 'user-group-meta');
$wpdb->update( $table, $data_array, $where );
//update wp_terms - name of group
$table = "wp_terms";
$data_array = array('name' => $groupname);
$where = array('term_id' => $groupid);
$wpdb->update( $table, $data_array, $where );
//update wp_term_taxonomy - description of group
$table = "wp_term_taxonomy";
$data_array = array('description' => $groupdesc);
$where = array('term_id' => $groupid);
$wpdb->update( $table, $data_array, $where );

$returnvars = array(
              "rosteredit" =>"successful",
);
print json_encode($returnvars);
}
/*
elseif ($_POST['selectedFaculty'] <> ""){
//get the term tax id from the term and add the user id to the relationships table
global $wpdb;
//get the user id based on the email
	$id=$wpdb->get_var("Select id from $wpdb->users where user_email = '" .trim($_POST['selectedFaculty']) ."'");
	$groupid=$_POST['rostergroupid'];
//get the term tax id for the group
	$terminfo=get_term_by( 'id', $groupid,'user-group');
	$term_tax_id = $terminfo->term_taxonomy_id;
	$terminfo2=get_term_by( 'name','PD Hub Teachers','user-group');
	$term_tax_id2 = $terminfo2->term_taxonomy_id;
	$user_info = get_userdata($id);
//get the owner of the group
	$current_user = wp_get_current_user();
	$current_id = $current_user->ID;
	$current_user_info = get_userdata($current_id);
	if ($_POST['faculty_student'] == 1){
	$form_order=4;
	}
	else{
	$form_order=1;
	}
		//add the user to the roster--using the order field as a key for the user being a faculty member and owner on the group instead of a student
		//4 indicates faculty and student
		$insert="INSERT into wp_term_relationships (object_id, term_taxonomy_id, term_order) VALUES (". $id ."," . $term_tax_id .",". $form_order .")";
		$wpdb->query($insert);
		//update the count on term_taxonomy
		$wpdb->query('Update wp_term_taxonomy set count=count+1 where term_taxonomy_id ='.$term_tax_id );
		//make sure this user has been added as a pd hub teacher, it should happen when they select
		//faculty as their role, but if they did not start out as faculty and have not changed their role, they will not be 
		//in the PD Hub teachers group.
	$isPDHubGroup=$wpdb->get_var("Select object_id from wp_term_relationships where term_taxonomy_id =". $term_tax_id2 . " and object_id =".$id);
			if ($isPDHubGroup <= 0){
				$insert="INSERT into wp_term_relationships (object_id, term_taxonomy_id, term_order) VALUES (". $id ."," . $term_tax_id2 .",0)";
				$wpdb->query($insert);
				//update the count on term_taxonomy
				 $wpdb->query('Update wp_term_taxonomy set count=count+1 where term_taxonomy_id =' . $term_tax_id2 );
				//change or update role to faculty
				update_user_meta( $id, 'transition_profile_role', 'College/university faculty or instructor');
			}
//email the user to inform them that they were added to the roster
$headers = 'From: Transition Coalition<transition@transitioncoalition.org>' . "\r\n";
//set up the email message
$message="Hello ". $user_info->user_firstname ." " . $user_info->user_lastname .",\r\n";
$message.= $current_user_info->user_firstname ." " . $current_user_info->user_lastname . " has added you to a Transition Coalition faculty roster on the Transition Coalition website.\r\n";
$message.="As a member of the roster you will be able to chart student progress on modules and have online discourse with other faculty members who are assigned to the roster.\r\n";
$message.="Simply log into your account at " . site_url() . " and navigate to TC Community->PD Hub->My PD Hub Rosters.\r\n\r\n";
$message.="The name of your roster is '" . $terminfo->name  ."'\r\n\r\n";
$message.="Please do not respond to this email because it was automatically generated. If you need assistance either contact the person who added you to the roster, or use the feedback form on the Transition Coalition website.\r\n\r\n";
$message.="Thank you.\r\n\r\nThe Transition Coalition team";
wp_mail(trim($_POST['selectedFaculty']), 'You have been added to a roster', $message, $headers);
$returnvars = array(
              "addedtoroster" =>"successful",
);
print json_encode($returnvars);
}
//*******************************************************************
//handle the search term coming in for the auto populate select list
//*******************************************************************
elseif ($_POST['term'] <> ""){
$searchterm = $_POST['term'];
$query = "SELECT id, user_email FROM wp_users w, wp_usermeta u  where w.id=u.user_id
and (user_email like '" . $searchterm ."%' || meta_value like '%" . $searchterm ."%') AND meta_key in ('first_name','last_name'))";
$rows = $wpdb->get_results($query);
$i = 0;
foreach ($rows as $row){
$return_arr[$i] = $row->user_email;
$i++;
}
echo json_encode($return_arr);
}
*/
//*******************************************************************
//if this is a delete request delete the group info and user associations
//*******************************************************************
?>