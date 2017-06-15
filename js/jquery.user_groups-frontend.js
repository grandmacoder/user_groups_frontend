/*
	jquery.user_groups-frontend.js
	
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
	
*/
jQuery(document).ready(function($) {
var current_page = $(location).attr('href');
var baseURL = window.location.protocol+"//"+window.location.host;
var lookupURL=baseURL +'/wp-content/plugins/user-groups-frontend/processGroupFrontendAjax.php';
	//see if there is an id passed in, if so, load all the screen values and show all three divs 
	   var qs = [], hash;
	   var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
		for(var i = 0; i < hashes.length; i++){   
			hash = hashes[i].split('=');
			qs.push(hash[0]);
			qs[hash[0]] = hash[1];
		}
		var id = qs["rosterid"];
		var action = qs["action"];
		var scrollto=qs["scrollto"];
		if (scrollto == 'invite_students'){
		document.getElementById('invite_students').scrollIntoView();
		}
		var baseURL = window.location.protocol+"//"+window.location.host;
		var lookupURL=baseURL +'/wp-content/plugins/user-groups-frontend/processGroupFrontendAjax.php';
//don't do any document.ready unless it is the roster page, the roster listing page, or the roster faculty page
if (current_page.indexOf('roster-faculty') > -1) {
var baseURL = window.location.protocol+"//"+window.location.host;
var urltoget=baseURL +'/wp-content/plugins/user-groups-frontend/processGroupFrontendAjax.php';
$(".remove_faculty").click( function() {
var id_and_group= $(this).attr('id');
//two values were added to the id, the group and the user id
var id_group_vals=id_and_group.split('_');
var faculty_id=id_group_vals[0];
var group_id = id_group_vals[1];
	$.post(urltoget, {'action':'remove_user_from_roster','id':faculty_id,'groupid':group_id}, function(ret){
	alert("The user was successfully removed from the roster");
	location.reload();
	});
});
$(".promote_faculty_to_leader").click( function() {
var id_and_group= $(this).attr('id');
var id_group_vals=id_and_group.split('_');
var faculty_id=id_group_vals[0];
var group_id = id_group_vals[1];
	$.post(urltoget, {'action':'promote_user_to_roster_leader','id':faculty_id,'groupid':group_id}, function(ret){
	alert("The user was changed to a group leader");
	location.reload();
	});
});
}
//Assign roster members for roster group
else if (current_page.indexOf('manage-roster-page') > -1) {

	var baseURL = window.location.protocol+"//"+window.location.host;
	var urltoget=baseURL +'/wp-content/plugins/user-groups-frontend/processGroupFrontendAjax.php';
	var groupid = $('#getGroupId').data('groupid');
	var emailMessage = joinRosterMessage(groupid, baseURL);
	$("#joinMember").val(emailMessage);
//check radio boxes according to user id and term order from hidden field
		$('input[type=hidden]').each(function() {
		var member_role_check = this.value;
		var member_user = this.name;
		$('input[name='+member_user+'][value='+member_role_check+']').attr('checked', 'checked');
		});	
//submit form for roster members to be assigned roles		
$( "#assignRosterMembers" ).on( "submit", function( event ) {	
		event.preventDefault();
	   	var baseURL = window.location.protocol+"//"+window.location.host;
	    var pluginAjaxURL = baseURL +'/wp-content/plugins/user-groups-frontend/processGroupFrontendAjax.php';
		term_tax_id = $(this).data('term_taxonomy_id');
		$('input:checked', '#assignRosterMembers').each(function() {
			  var value;
			  var name;
              value = this.value;
			  name = this.name;
			  //call ajax to set new roster member roles
			    if(value != "" && name != ""){
			    $.post(pluginAjaxURL, {'action':'save_member_order','name':name,'order':value,'term_taxonomy_id':term_tax_id},function(returnvars){
			
	                   });
					}
	   });//end foreach function 
	   alert("Roster Members Assigned");
	   
});//end assign roster members to roster group	

$(".remove_student_from_roster").click( function() {
var id_and_group= $(this).attr('id');
//two values were added to the id, the group and the user id
var id_group_vals=id_and_group.split('_');
var id=id_group_vals[0];
var group_id = id_group_vals[1];
	$.post(urltoget, {'action':'remove_user_from_roster','id':id,'groupid':group_id}, function(ret){
	alert("The user was successfully removed from the roster");
	location.reload();
	});
});
$(".showQiSurveyList").click( function() {
var showItem=$(this).attr('id');
var divToShow = '#surveyList_'+showItem;
if($(divToShow).css('display') == 'none'){ 
$(divToShow).show();
}
else{
$(divToShow).hide();
}
});

}//end if current page is manage-roster-page
else if (current_page.indexOf('pd-hub-roster') > -1) {
var baseURL = window.location.protocol+"//"+window.location.host;
var lookupURL=baseURL +'/wp-content/plugins/user-groups-frontend/processGroupFrontendAjax.php';

//don't go any further if the editroster parameter was not sent in through the qs
  if (action =='editroster' && id > 0){
  
	$('#rosterBanner1').html("Edit roster");
	$('#rosterBanner2').html("Invite!");
	   
	    //go ahead and load the form with the already saved data and show all three sections of the form.
			$.post(lookupURL, {'action':'get_group_info','id':id}, function(returndata){
			var obj =jQuery.parseJSON( returndata );
			$(".groupid").val(id);
			$(".groupname").val(obj.groupname);
			$(".groupsemester").val(obj.groupsemester);
			$(".groupschool").val(obj.groupschool);
			//set semester and year drop down list values according to input hidden field named groupschool
			//first split the value of groupschool into string array
			var objString = $(".groupsemester").val();
			var objA = objString.split(" ");

			//loop through select field to select the last saved edit
			$("#roster_semester > option").each (function() {
				if(this.value == objA[0]){
					//remove select option tag for editing roster
					$("#selectOption1").remove();
					//select the correct field for select list
					$(this).attr('selected', 'selected');
				}
			});
			//loop through select field to select the last saved edit
			$("#roster_year > option").each (function() {
				if(this.value == objA[1]){
					//remove select option tag for editing roster
					$("#selectOption2").remove();
					//select the correct field for select list
					$(this).attr('selected', 'selected');
				}else{//put value of group semester object in for select option
					$("#selectOption2").val(objA[1]);
					$("#selectOption2").text(objA[1]);
					$("#selectOption2").attr('selected', 'selected');
				}
			});
			$(".groupdescription").val(obj.groupdescription);
			//this is set to 1 so that we can tell if we are editing when the form gets submitted
			$(".editroster").val("1");
			$(".rostergroupid").val(id);
			});		
	        //show all the divs	for editing	
			$("#invite_faculty").show();
			$("#invite_students").show();
			//put email message text in joinMember
			var emailMessage = joinRosterMessage(id, baseURL);
			$("#joinMember").val(emailMessage);
			$("#create_roster").show();
			
	}else{
	
	$('#rosterBanner1').html("Step #1 Create a roster!");
	$('#rosterBanner2').html("Step #2 Invite!");
	$('#selectOption1').html("Select Semester");
	$('#selectOption2').html("Select Year");
	
	}
//handle the create a roster form
$( "#btnsubmitroster" ).click( function( event ) {
			//check for empty form values
			var editform = $(".editroster").val();
			if ($(".groupname").val() == "" ){
			$( "#messagearearoster" ).html( "<p style='color:#c2132f;font-weight:bold;'>Please enter a group name.</p>" );
			}
			else if ($(".groupdescription").val() == "" ){
			$( "#messagearearoster" ).html( "<p style='color:#c2132f;font-weight:bold;'>Please enter a short description for your group.</p>" );
			}else if($("#roster_semester").val() == "Select"){
			$( "#messagearearoster" ).html( "<p style='color:#c2132f;font-weight:bold;'>Please enter a semester for your roster.</p>" );
			}else if($("#roster_year").val() == "Select"){
			$( "#messagearearoster" ).html( "<p style='color:#c2132f;font-weight:bold;'>Please enter a year for your roster.</p>" );	
			}
			else{
			var baseURL = window.location.protocol+"//"+window.location.host;
			var pluginAjaxURL = baseURL +'/wp-content/plugins/user-groups-frontend/processGroupFrontendAjax.php';
			event.preventDefault();
			var rosterYear = $("#roster_year").val();
			var rosterSemester = $("#roster_semester").val();
			$("#groupsemester").val(rosterSemester+" "+rosterYear);
			var data = $( "#createrosterform" ).serialize();
			$.ajax({
			url: pluginAjaxURL,
			type: 'POST',
			dataType:'json',
			data : data,
			success: function(response){
			if (response['returnmsg'] != "" && editform != 1){
			$( "#messagearearoster" ).html( "<h3 style='color:red; font-size: 14px;'>The group name you selected is already in use. Please choose another</h3>" );
		        return false;
			}
			else if ( response['rosteredit'] == 'successful'){
			
			$('#create-group-summary').html("<p style='color:#c2132f;font-size: 16px; font-weight:bold;'><br>Your roster was updated.</p>");
			return false;
			}else if(response['groupid'] > 0 ){
				var forumlink = baseURL+'/?p='+ response['forumid'];
				var updated =  response['rosteredit'];
				$("#groupid").val(response['groupid']);
				//set the group id on the email form
				$(".rostergroupid").val(response['groupid']);
                              	$('#create-group-summary').html("<p style='color:#c2132f;font-size: 16px; font-weight:bold;'><br>Great! Your roster was created. Proceed to Step #2 to add participants/studentes to your roster. </p>");
		               //show the Invite div
				$("#invite_faculty").show();
				$("#invite_students").show();
				//call function for email roster invite
				var emailMessage = joinRosterMessage(response['groupid'], baseURL);
				//show email message in text box 
				$("#joinMember").val(emailMessage);
				//hide create roster
				$("#create_roster").hide();	
			 }
			 },
			error: function(xhr, textStatus, errorThrown){alert(textStatus);},
			
		 });
		 
        } //end of else
    });
//get the add faculty to roster form
	$( "#addfacultytoroster" ).on( "submit", function( event ) {
			var baseURL = window.location.protocol+"//"+window.location.host;
			var pluginAjaxURL = baseURL +'/wp-content/plugins/user-groups-frontend/processGroupFrontendAjax.php';
			event.preventDefault();
			var data = $( this ).serialize();
			$.ajax({
			url: pluginAjaxURL,
			type: 'POST',
			dataType:'json',
			data : data,
				success: function(returndata){	
				},
				error: function(xhr, textStatus, errorThrown){alert(textStatus);},
				complete: function(response){
				$( "#messageareafaculty" ).html( "<p style='color:red;font-weight:bold;'>The faculty member was added to the roster and has been notified by email.</p>" );
				alert("The user was added to the roster.");
				},
	       }); //end the ajax call
		});//end add faculty to roster	
}//end if we are on the pdhub page

});//end document ready

//create a email message to invite members to roster 
function joinRosterMessage(groupId, baseURL){
	var letters = ['a','b','c','d','e','f','g','h','i','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z',0,1,2,3,4,5,6,7,8,9];
	var randomstring = '';
			   for(var i=0; i < 5; i++){
				 var rlet = Math.floor(Math.random()*letters.length);
				 randomstring += letters[rlet];
				}
	   var joinlink = baseURL+'/blog/joinroster/?jn='+ groupId + '_' + randomstring;
	   //content for join link paragraph for textbox
		var textvalue="Dear STUDENTS\r\n\r\nAs part of this course you are required to complete some activities on the Transition Coalition website.\r\nPlease follow the link below or copy/paste it into a browser window.  This will take you to the Transition Coalition website to set up your Transition Coalition account and assign you to my course.  When you are assigned to my course,"+ 
				" I will be able to follow your progress on assignments I give you on the Transition Coalition website.\r\n\r\n"+
				"You MUST follow this link for the system to work properly.\r\n\r\n"+joinlink;				
	return textvalue;
}

