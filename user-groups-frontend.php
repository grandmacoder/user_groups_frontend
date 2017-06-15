<?php
   /*
   Plugin Name: User groups front end
   Plugin URI: http://www.amyjocarlson.com
   Description: a plugin that the ability for users to create and manage their own groups
   Version: 1.2
   Author: Amy Carlson
   Author URI: http://www.amyjocarlson.com
   License: GPL2
   */
 //enqueue scripts and css
function user_groups_frontend_scripts_with_jquery(){
    // Register the script like this for a plugin:
    wp_register_script( 'user-groups-frontend-custom-script', plugins_url( '/js/jquery.user_groups-frontend.js', __FILE__ ), array( 'jquery' ) );
   // For either a plugin or a theme, you can then enqueue the script:
    wp_enqueue_script( 'user-groups-frontend-custom-script' );
}
add_action( 'wp_enqueue_scripts', 'user_groups_frontend_scripts_with_jquery' );

function user_groups_frontend_styles()
{
    // Register the style like this for a plugin:
    wp_register_style( 'usergroupsfrontend-style', plugins_url( '/css/usergroups_frontend_style.css', __FILE__ ), array(), '20120208', 'all' );
    // For either a plugin or a theme, you can then enqueue the style:
    wp_enqueue_style( 'usergroupsfrontend-style' );
}
add_action( 'wp_enqueue_scripts', 'user_groups_frontend_styles' );

//**************************************************************************************************
//add the shortcodes
add_shortcode('displayUserRosterList','gfe_display_roster_list');
add_shortcode('displaySingleRoster','gfe_display_single_roster'); 
add_shortcode('displayRosterFaculty','gfe_display_roster_faculty');
add_shortcode('displayRosterStudents','gfe_display_roster_members');
add_shortcode('joinRoster','gfe_join_a_roster');
add_shortcode('displayJoinLinkInfo','gfe_display_join_link_info');
//**************************************************************************************************

function gfe_join_a_roster($atts, $content=null){
global $wpdb;
$aParts=explode('_',$_GET['jn']);
//get current user id, if none, then ask them to login.
$currentUser= wp_get_current_user();
$currentUserID = $currentUser->ID;
$groupid = $aParts[0];
if (!is_numeric($groupid)){
return $content;
}
$terminfo=get_term_by( 'id', $groupid,'user-group');
$term_taxonomy_id = $terminfo->term_taxonomy_id;
$term_name = $terminfo->name;
if ($currentUserID <= 0){
$content="Do you have a Transition Coalition account?  Please log in.<br>Don’t have one? Please take 3 minutes to <a href='/register-3/'>Create an Account. </a> ";
//set a cookie for with the join link and pick it up when the user creates an account for a redirect.
$cookie_name = "new_roster_member";
setcookie($cookie_name, "jn=".$groupid."_".time(), time() + (604800), "/"); 
}
else{
//default role on the roster is student
$result = $wpdb->insert(wp_term_relationships , 
  array( 
    'object_id' => $currentUserID,
    'term_taxonomy_id' => $term_taxonomy_id ,
    'term_order' => 3
  ), 
  array( '%d', '%d', '%d'));
 //update the count on the term_taxonomy table for this term tax id
 $wpdb->update( 
	'wp_term_taxonomy', 
	array( 
		'count' => count+1,
	), 
	array( 'term_taxonomy_id' =>$term_taxonomy_id  ), 
	array( 
		'%d',	// value1
	), 
	array( '%d' ) 
);
$content.="<P><strong>Great! </strong>You have been added to the roster, '".$term_name."'. <br>The instructor who invited you to join the roster can keep track of your progress on a variety of learning activities on the Transition Coalition site.<br><br><strong>Please <a title='Contact us' href='#' onclick='usernoise.window.show()'>Contact Us</a> or your instructor if you have questions.</strong></p>";
}
return $content; 
}
//Show join link information along with instructions
function gfe_display_join_link_info($atts, $content=null){
//get the id
$joinLink="<a href=''>The join link</a> ";
$groupid=$_GET['groupid'];
//create a random string
$contentString.="
			<li><span style='color: #ff0000;'><strong>1. Click in the box below to select text.</strong></span></li>
			<li><span style='color: #ff0000;'><strong>2. Copy and paste the text to your email message.</strong></span></li>
			<li><span style='color: #ff0000;'><strong>3. Address email message to members you would like to invite to your roster.  </strong></span></li>
			"; 
			$randString ="";
			$randomOptions = ['a','b','c','d','e','f','g','h','i','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z',0,1,2,3,4,5,6,7,8,9];
				for ($j=0; $j<5; $j++){
					$randString.= $randomOptions[rand(0,36)];
					}
		$randomCourseLink = get_site_url().'/blog/joinroster/?jn=' .$groupid."_".$randString;
$contentString.="<textarea id='joinMember' rows='8' onClick='this.select();' cols='90'>Dear STUDENTS\r\n\r\nAs part of this course you are required to complete some activities on the Transition Coalition website.Please follow the link below or copy/paste it into a browser window. This will take you to the Transition Coalition website to set up your Transition Coalition account and assign you to my course.  When you are assigned to my course, I will be able to follow your progress on assignments I give you on the Transition Coalition website.

You MUST follow this link for the system to work properly.
".$randomCourseLink."</textarea>";
return $contentString;
}
//Show a listing of the students on the roster, include a link to their qi survey list if they have any
function gfe_display_roster_members($atts, $content=null){
global $wpdb;
$groupid=$_GET['groupid'];
$terminfo=get_term_by( 'id', $groupid,'user-group');
$term_taxonomy_id = $terminfo->term_taxonomy_id;
//get all the users with term_order = 1,2,3,4,5 with term taxonomy id. Roster roles: 1 = Roster Leader, 2 = Facilitator, 3 = Student, 4 = Both, 5 = Not assigned **
$roster_members = $wpdb->get_results("Select object_id, term_order from wp_term_relationships where term_order in (0,1,2,3,4,5)  and term_taxonomy_id =".$term_taxonomy_id . " order by term_order" , OBJECT);
$num_members = $wpdb->num_rows;
if ($num_members > 1){
//output a header
//<tr><td style='text-align:left;'>". stripslashes($aGroupNames[$i]) ."-" .$aOptions[$index]['group-semester']."<br><a href='roster-students/?groupid=".$aGroups[$i]."' class=manageStudentsOnRoster id=".$aGroups[$i]." title='Manage the members on this roster'>Manage members</a><br> <a href='/view-a-roster/?id=".$aGroups[$i]. "'>Student Progress</a></td>
//<td style='text-align:left;'>".stripslashes($aOptions[$index]['group-school'])."</td>"
$content .="<p class='rosterSteps'>Name of roster:  ". $terminfo->name ."</p>";
//start table for roster member list
foreach ($roster_members as $roster_leader){
if($roster_leader->term_order == 1){
	$userinfo = get_userdata( $roster_leader->object_id);
	$content .="<p><strong>Roster Leader: </strong>".$userinfo->display_name."&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<strong> Email: </strong> ".$userinfo->user_email."</p>";
	}
}
//create form for roster list. Term_taxonmy_id used to assign rosters in jquery
$content.="<form id='assignRosterMembers' method='post' data-term_taxonomy_id=".$term_taxonomy_id." <div class=rounded_table>
	<table><tr><td style='font-weight:bold; color:#FFF;'>Roster Member</td><td style='font-weight:bold; color:#FFF;'>Email
	</td><td style='font-weight:bold; color:#FFF;'>Delete Member</td><td style='font-weight:bold; color:#FFF;'>Assign Member</td></tr>";
//determine term_order=roster role 
foreach ($roster_members as $member){
//if term order= 0 then set it to 5--user was added to group manually without the join link
      
	if($member->term_order > 1){
	$userinfo = get_userdata( $member->object_id);
        $member_radio_buttons = "";
	$member_role = $member->term_order;
	
//jquery is used to set radio buttons for roster roles, based off user id and and member role in input type elements
//build data for table for list of roster members
	$content.="<tr><td style='text-align:left;'>".$userinfo->display_name."</td><td style='text-align:left;'>".$userinfo->user_email."</td><td style='text-align:left;'><a href='#' class='remove_student_from_roster' id =". $userinfo->ID."_".$groupid .">
	<img src='/wp-content/uploads/2014/08/erase.png' height=18 width=18>Remove ".$userinfo->display_name." from roster</a></td><td style='text-align:left;'>
	<input type='hidden' name='".$userinfo->ID."' id='member_role' value='".$member_role."'>
	<input type='radio' name='".$userinfo->ID."' value='2' >Faciltator<input type='radio'name='".$userinfo->ID."' value='3'>Student<input type='radio' name='".$userinfo->ID."' value='4'>Both</td></tr>";
	}
	else if($member->term_order == 0){
	$userinfo = get_userdata( $member->object_id);
    $member_radio_buttons = "";
	$member_role =5;
	
//jquery is used to set radio buttons for roster roles, based off user id and and member role in input type elements
//build data for table for list of roster members
	$content.="<tr><td style='text-align:left;'>".$userinfo->display_name."</td><td style='text-align:left;'>".$userinfo->user_email."</td><td style='text-align:left;'><a href='#' class='remove_student_from_roster' id =". $userinfo->ID."_".$groupid .">
	<img src='/wp-content/uploads/2014/08/erase.png' height=18 width=18>Remove ".$userinfo->display_name." from roster</a></td><td style='text-align:left;'>
	<input type='hidden' name='".$userinfo->ID."' id='member_role' value='".$member_role."'>
	<input type='radio' name='".$userinfo->ID."' value='2' >Faciltator<input type='radio'name='".$userinfo->ID."' value='3'>Student<input type='radio' name='".$userinfo->ID."' value='4'>Both</td></tr>";
	}
   	
}//end for loop for roster member list

$content.="</table><input id='btnassignrostermembers' class='btnassignrostermembers' type = 'submit' value='Save assigned members'</input></form>";
}//end if there are any students on the roster

if ( $num_members <= 1){
$content .= "<p style='color:red;font-weight:bold;' >Oops! No students have been invited to this roster yet. If you'd like to invite members to your roster refer to the directions below.</p>";
}

return $content;
}
//show a listing of the faculty on a roster. Give the option to remove them from the roster if the user that is
//logged in is the owner of the roster
function gfe_display_roster_faculty($atts, $content=null){
global $wpdb;
$groupid=$_GET['id'];
$aLeaders=array();
$currentUser= wp_get_current_user();
$currentUserID = $currentUser->ID;
$terminfo=get_term_by( 'id', $groupid,'user-group');
$term_taxonomy_id = $terminfo->term_taxonomy_id;
//get the roster faculty leaders
$roster_faculty_leaders = $wpdb->get_results("Select object_id from wp_term_relationships where term_order = 1  and term_taxonomy_id =".$term_taxonomy_id . " order by term_order" , OBJECT);
$i =0;

foreach ($roster_faculty_leaders as $leader){
$aLeaders[$i] =  $leader->object_id;
}
//get all the users with term_order = 1 or 2 for the term taxonomy id
$roster_faculty = $wpdb->get_results("Select object_id, term_order from wp_term_relationships where term_order in (1,2,4) and term_taxonomy_id =".$term_taxonomy_id . " order by term_order" , OBJECT);
$num_members = $wpdb->num_rows;
$content.="<p class='rosterSteps'>Facilitators on the roster, '" . $terminfo->name."'</p>";
foreach ($roster_faculty as $faculty){
    $userinfo = get_userdata( $faculty->object_id);
    $content.= "<p class=roster_faculty_list_avatar>";
	$content.= get_avatar( $faculty->object_id, 40);
	$content.="Contact: <a href='mailto:". $userinfo->user_email."'>". $userinfo->display_name . "</a>";
	if ($faculty->term_order == 1 ){
	//$content .="&nbsp;&nbsp;(Roster leader)";
	}
	
	if (in_array($currentUserID, $aLeaders) && $faculty->term_order == 2){
	$content .= "<br><a href='#' class='remove_faculty' id =". $userinfo->ID."_".$_GET['id'] .">Remove from roster</a>";
	//&nbsp;&nbsp;&nbsp;<a href='#' class='promote_faculty_to_leader' id =". $userinfo->ID."_".$_GET['id'] .">Promote to group leader</a>";
	}
	$content.="</p>";
}
if ( $num_members == 1){
$content .= "<p>Oops! No faculty have been invited to this roster yet. <a href='/pd-hub-roster/?rosterid=".$_GET['id']."&action=editroster&scrollto=invite_faculty'><br>Invite facilitators now</a> if you'd like.";
}
else{
$content .= "<p>If you'd like you can <a href='/pd-hub-roster/?rosterid=".$_GET['id']."&action=editroster&scrollto=invite_faculty'>invite facilitators </a> to this roster.";
}
$content .="<p>Or you can <a href='/roster-students/?groupid=".$_GET['id']."'>manage students on this roster.</a></p>";
return $content;
}
//**************************************************************************************************
//show a single roster's users and module progress

function gfe_display_single_roster($atts, $content=null){
global $wpdb;
$currentUser= wp_get_current_user();
$currentUserID = $currentUser->ID;
$aOptions = get_option( 'user-group-meta' );
$index=$_GET['id'];
$terminfo=get_term_by( 'id', $index,'user-group');
$term_taxonomy_id = $terminfo->term_taxonomy_id;
//$forumID = $wpdb->get_var("Select ID from wp_posts where post_title like '%".$terminfo->name."%' and post_type='forum'");
//$userForumAccess = $wpdb->get_var("Select term_order from wp_term_relationships where object_id =". $currentUserID ." and term_taxonomy_id =".$term_taxonomy_id);
$content.="<p class='rosterSteps'>Roster information</p>";
$content .="<p><strong> Roster name: </strong>". $terminfo->name .", ". $terminfo->description ."
<BR><strong>Semester: </strong>" . $aOptions[$index]['group-semester'] .
"<BR><strong>School: </strong>". stripslashes($aOptions[$index]['group-school'])."</p>";
$formTitleKey= "<strong>" .$terminfo->name . " forum</strong>";

//if ($userForumAccess > 0){
//$content.="<strong>This roster has a forum! </strong>Do you have something to share? Add a topic. Join the discussion.<BR>
 //<strong>Forum:</strong> <a href='". site_url() ."/?p=" . $forumID."'>" . $terminfo->name ."'s forum.</a>";
//}

$content.="<p><span style='font-size: 14pt;'><strong><span style='color: #3b8dbd;'><br>Student Progress</span></strong></span></p>";
$roster = $wpdb->get_results("Select object_id, term_order from wp_term_relationships where term_order in (3, 4) and term_taxonomy_id =".$term_taxonomy_id . " order by term_order" , OBJECT);
$num_members = $wpdb->num_rows;

//Create table for student progress 
$content.="<div class=rounded_table>
<table><tr><td style='font-weight:bold; color:#FFF;'>Roster</td><td style='font-weight:bold; color:#FFF;'>School</td><td style='font-weight:bold; color:#FFF;'>Join Link</td><td style='font-weight:bold; color:#FFF;'>Actions</td></tr>";


/*foreach ($roster as $roster_item){
    $userinfo = get_userdata( $roster_item->object_id);
    $content.= "<p class=roster_faculty_list_avatar>";
	$content.= get_avatar( $roster_item->object_id, 40);
	$content.="Contact: <a href='mailto:". $userinfo->user_email."'>". $userinfo->display_name . "</a>";
    $content.="</p>";
}
*/
return $content;
} //end showing a single roster
//**************************************************************************************************

//**************************************************************************************************
//show a list of a user's rosters
function gfe_display_roster_list($atts, $content=null){
global $wpdb;
$currentUser= wp_get_current_user();
$currentUserID = $currentUser->ID;
$aGroups=array();
$aGroupNames = array();
$aIsGroupOwner = array();
$sql="select t.term_id,term_order, name FROM
wp_term_taxonomy t, wp_term_relationships w, wp_terms x 
WHERE
w.object_id = " . $currentUserID ." and 
t.term_taxonomy_id = w.term_taxonomy_id
AND
t.term_id= x.term_id
and t.term_id not in (Select term_id from wp_terms where term_group = 1 ) 
and taxonomy = 'user-group' order by term_id desc";
$user_rosters = $wpdb->get_results($sql, OBJECT);
$i = 0;
if ($wpdb->num_rows > 0){
foreach ($user_rosters as $roster){
    $aIsGroupOwner[$i] = $roster->term_order;
	$aGroups[$i] = $roster->term_id;
	$aGroupNames[$i] = $roster->name;
	$i++;
}
}
if (count($aGroups) > 0){
      $content.="<strong>On this page you can:</strong>";
	  $content.="<ol>
	  <li>Select student progress to view the progress of students of the roster.</li>
	  <li>Select and view roster facilitators.</li>
	  </ol>
	  <strong>Group leaders can...</strong>
	  <ol>
	  <li>To manage members of your roster click on Manage Members link underneath your roster name</li>
	  <li>Copy and paste the join link into an email if you want to invite a student to join the roster. You may use <a title='Example join text' href='/wp-content/uploads/2014/04/PD_Hub_Join_Link_Example_Letter.doc' target='_blank'>this document</a> as a guide for what to include in the email.</li>
	  <li>Edit the name, semester, or add members to the roster by selecting the Edit link</li>
	  <li>Delete the roster by choosing the Delete link.</li>
       </ol>";
		$content.="<div id='rosterListDelete'>
		<h3 style='color:red; font-size: 14px;'>Note: The roster will be deleted and the members will be removed from it.<br>  If there is a forum for this roster, it will be deleted too. Click the button below if you want to delete the roster. </h3>
		<form id=deleteRosterForm action='' method='post'>
		<input type=hidden id=action name=action value='delete_a_roster'>
		<input type=hidden id=rostergroupid name=rostergroupid value=''>
		<input type=submit name=btnDeleteRoster value='Yes, delete it'>
		</form>
		</div>";
		$content.="<strong>You belong to the following rosters:</strong>";
		$content.="<div class=rounded_table>
		<table><tr><td style='font-weight:bold; color:#FFF;'>Roster</td><td style='font-weight:bold; color:#FFF;'>School</td><td style='font-weight:bold; color:#FFF;'>Join Link</td><td style='font-weight:bold; color:#FFF;'>Actions</td></tr>";
		$aOptions = get_option( 'user-group-meta' );
				for ($i=0; $i < count($aGroups); $i++){
				$randString ="";
				$randomOptions = ['a','b','c','d','e','f','g','h','i','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z',0,1,2,3,4,5,6,7,8,9];
					for ($j=0; $j<5; $j++){
					$randString.= $randomOptions[rand(0,36)];
					}
				$randomCourseLink = get_site_url() .'/blog/joinroster/?jn=' . $aGroups[$i] ."_". $randString;
				//get the users in this group 
				$index = $aGroups[$i];
				$content .=	"<tr><td style='text-align:left;'>". stripslashes($aGroupNames[$i]) ."-" .$aOptions[$index]['group-semester']."<br><a href='manage-roster-page/?groupid=".$aGroups[$i]."&action=editroster' class=manageStudentsOnRoster id=".$aGroups[$i]." title='Manage the members on this roster'>Manage members</a><br> <a href='/student-progress/?id=".$aGroups[$i]. "'>Student Progress</a></td>
				<td style='text-align:left;'>".stripslashes($aOptions[$index]['group-school'])."</td>";
				//<td style='text-align:left;'><a href='/roster-faculty/?id=".$aGroups[$i]. "'>Facilitator list</a></td>";
				if ($aIsGroupOwner[$i] == 1 || $aIsGroupOwner[$i] == 4 ){
				$content .="<td style='text-align:left;'>".$randomCourseLink."</td>
				<td style='text-align:left;'><a href='pd-hub-roster/?rosterid=".$aGroups[$i]."&action=editroster' title='Edit the name, semester, or school for this roster'>Edit Roster</a><br><a href='#' class=confirmDeleteRoster id=".$aGroups[$i]." title='Remove this roster'>Delete Roster</a></td>";
				}
				else{
				$content .="<td style='text-align:left;'>N/A</td>
				<td style='text-align:left;'>N/A</td>";
				}
				$content .=	"</tr>";
				}//end foreach group
			$content.="</table></div>";
} //end if rows are returned
else{
$content="There are currently no rosters associated with your account. Get started by <a href='pd-hub-roster/'>creating a new roster</a>.";
}
return $content;
}//end function
//**************************************************************************************************
//****************************************create a widget that shows the self study groups that a user is a member of ***********************************************
// Creating the widget 
class gfe_widget extends WP_Widget {

function __construct() {
parent::__construct(
// Base ID of your widget
'gfe_widget', 

// Widget name will appear in UI
__('Users Facilitator Groups', 'gfe_widget_domain'), 

// Widget description
array( 'description' => __( 'Get a users facilitator groups', 'gfe_widget_domain' ), ) 
);
}

// Creating widget front-end
// This is where the action happens
public function widget( $args, $instance ) {
$currentUser= wp_get_current_user();
$currentUserID = $currentUser->ID;
$title = apply_filters( 'widget_title', $instance['title'] );
echo $args['before_widget'];
$theoutput=$this->get_users_facilitator_groups($currentUserID);

//if there is a match, get the catID of the category and create a link to the selfstudy page with a catID appended to the link
// before and after widget arguments are defined by themes

if ( ! empty( $title ) && is_user_logged_in() ){
echo $args['before_title'] . $title . $args['after_title'];
echo __( $theoutput, 'gfe_widget_domain' );
echo $args['after_widget'];
}
}
		
// Widget Backend 
public function form( $instance ) {
if ( isset( $instance[ 'title' ] ) ) {
$title = $instance[ 'title' ];
}
else {
$title = __( 'Self Study Groups', 'gfe_widget_domain' );
}
// Widget admin form
?>
<p>
<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
</p>
<?php 
}
//get the user's groups if they are in a facilitator group
public function get_users_facilitator_groups($currentUserID){
global $wpdb;
$group_links= "";
// This is where you run the code and display the output
//get the current user and the groups they are in by name
$currentUser= wp_get_current_user();
$currentUserID = $currentUser->ID;
$aGroups=array();
$aGroupNames = array();
$sql="select t.term_id,term_order, name FROM
wp_term_taxonomy t, wp_term_relationships w, wp_terms x 
WHERE
w.object_id = " . $currentUserID ." and 
t.term_taxonomy_id = w.term_taxonomy_id
AND
t.term_id= x.term_id
and t.term_id not in (Select term_id from wp_terms where term_group = 1 ) 
and taxonomy = 'user-group' order by term_id desc";
$user_rosters = $wpdb->get_results($sql, OBJECT);
$i = 0;

if ($wpdb->num_rows > 0){
foreach ($user_rosters as $roster){
	$aGroups[$i] = $roster->term_id;
	$aGroupNames[$i] = $roster->name;
	$i++;
}
}
//get the selfstudy categories (parent is self study) by name
$args = array(
	'type'                     => 'post',
	'child_of'                 => 0,
	'parent'                   => '311',
	'orderby'                  => 'name',
	'order'                    => 'ASC',
	'hide_empty'               => 1,
	'hierarchical'             => 1,
	'exclude'                  => '',
	'include'                  => '',
	'number'                   => '',
	'taxonomy'                 => 'category',
	'pad_counts'               => false 

);

$categories = get_categories( $args );
foreach ($categories as $category){
	$name = $category->cat_name;
	$id = $category->cat_ID;
    for ($i = 0; $i<count($aGroupNames); $i++){
	//see if the users group matches the self study category
     $matched = strcmp($name,$aGroupNames[$i]);
	 
	 if ($matched == 0){
		//select a page with this same category, there will only be one page for SS per category
	     //query the posts for that category id
		 $term_tx_objects =get_objects_in_term($id,'category');
		 $ids = implode ( ',' , $term_tx_objects);
         $rows=$wpdb->get_results("select guid, post_title from wp_posts where post_type='page' and post_status='publish' and ID in (" . $ids .")", OBJECT);
		  foreach ($rows as $row){
		  $group_links.="<a href='"  . $row->guid ."'>". $row->post_title. "</a><br><br>";
		  }
	  }
	$matched = 0;
	}
	
}
if ($group_links==""){
$group_links ="You are currently not a member of a self-study facilitator community.";
}
return $group_links;
}
// Updating widget replacing old instances with new
public function update( $new_instance, $old_instance ) {
$instance = array();
$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
return $instance;
}
} // Class gfe_widget ends here


//**************************************************************************************************
//****************************************create a widget that shows links to the roster progress page for facilitators on rosters ***********************************************
// Creating the widget 
class gfe_roster_widget extends WP_Widget {

function __construct() {
parent::__construct(
// Base ID of your widget
'gfe_roster_widget', 

// Widget name will appear in UI
__('Student Progress Widget', 'gfe_widget_domain'), 

// Widget description
array( 'description' => __( 'Get links to student roster progress', 'gfe_widget_domain' ), ) 
);
}

// Creating widget front-end
public function widget( $args, $instance ) {
global $wpdb;
$currentUser= wp_get_current_user();
$currentUserID = $currentUser->ID;
//$post = get_post();
//$currentPostID = $post->ID;
//$term_id_for_post =$wpdb->get_var("select term_id  from wp_term_taxonomy where parent > 0 and term_taxonomy_id in (select term_taxonomy_id from wp_term_relationships where object_id =" .$currentPostID .")");
$theoutput="";
$title = apply_filters( 'widget_title', $instance['title'] );
$groupid =apply_filters( 'widget_groupid', $instance['groupid'] );
echo $args['before_widget'];
$theoutput=$this->get_students_roster_progress_links($currentUserID,$groupid);
if ($theoutput==""){
$theoutput.="Currently you are not an facilitator on a roster.";	
}

if ( ! empty( $title ) && is_user_logged_in() ){
echo $args['before_title'] . $title . $args['after_title'];
echo __( $theoutput, 'gfe_widget_domain' );
echo $args['after_widget'];
}
}
		
// Widget Backend 
public function form( $instance ) {
if ( isset( $instance[ 'title' ] ) ) {
$title = $instance[ 'title' ];
}
else {
$title = __( 'Self Study Student Roster Progress', 'gfe_widget_domain' );
}
if ( isset( $instance[ 'groupid' ] ) ) {
$groupid = $instance[ 'groupid' ];
}
else {
$groupid = __( 0, 'gfe_widget_domain' );
}
// Widget admin form
?>
<p>
<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
<label for="<?php echo $this->get_field_id( 'groupid' ); ?>"><?php _e( 'Facilitator Group ID:' ); ?></label> 
<input class="widefat" id="<?php echo $this->get_field_id( 'groupid' ); ?>" name="<?php echo $this->get_field_name( 'groupid' ); ?>" type="text" value="<?php echo esc_attr( $groupid ); ?>" />
</p>
<?php 
}

//get the user's groups if they are in a facilitator group
public function get_students_roster_progress_links($currentUserID,$parentID){
global $wpdb;
$group_links= "";
//get the term_id  from term_relationships, term tax where term_order <> 0 and term_order <> 3 which means not a student and is assigned roster leader, roster instructor or both
$rows = $wpdb->get_results("select object_id, t.term_id, term_order, name 
from wp_term_relationships r, 
wp_term_taxonomy t, 
wp_terms s 
where r.term_taxonomy_id= t.term_taxonomy_id 
and s.term_id = t.term_id  
and object_id = ". $currentUserID ."
and term_order <> 0 
and term_order <> 3
and t.term_id in (select term_id from wp_term_taxonomy where parent=". $parentID." || term_id =". $parentID. ") 
order by object_id", OBJECT);
foreach ($rows as $row){
if ($row->object_id == $currentUserID){
//create a link to that rosters module progress
$group_links .="<a href='".get_site_url()."/student-progress/?id=". $row->term_id."'><strong>Roster: </strong>". $row->name."</a><br>";
}
}
return $group_links;
}
// Updating widget replacing old instances with new
public function update( $new_instance, $old_instance ) {
$instance = array();
$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
$instance['groupid'] = ( ! empty( $new_instance['groupid'] ) ) ? strip_tags( $new_instance['groupid'] ) : '';
return $instance;
}
} //Class gfe_roster widget ends here

// Register and load the widgets
function gfe_load_widget() {
	register_widget( 'gfe_widget' );
	register_widget( 'gfe_roster_widget' );
}
add_action( 'widgets_init', 'gfe_load_widget' );

?>