<?php
global $wp;
global $wpdb;
global $ret;
$cu = wp_get_current_user();
$page = home_url( add_query_arg( array(), $wp->request ) );
$actionValue="add";
$onChecked="";
$offChecked="";
$dateValue="";
$timeValue="";
$cellBG['On']="p-3 mb-2 bg-success text-white";
$cellBG['Off']="p-3 mb-2 bg-danger text-white";
if(isset($_GET['id'])){
	$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	$tasks=new task_engine($mysqli);
	$task=$tasks->get_task(array("id"=>$_GET['id']));
	$taskActions=json_decode($task['files']);
	$actionValue="update";
	$dateValue=date("F j, Y",strtotime($task['dueDate']));
	$timeValue=date("g:i a",strtotime($task['dueDate']));
	foreach($taskActions->restaurants as $r){$restaurants[]='"'.$r.'"';}
	$selectedRestaurants=implode(", ",$restaurants);
	if($taskActions->action=="true"){$onChecked="checked='checked'";}else{$offChecked="checked='checked'";}
}
$query = "SELECT levelUpID,restaurantName FROM pbc2.pbc_pbrestaurants WHERE levelUpID is not null AND isOpen =1";
$records=$wpdb->get_results($query);
if(!empty($records)){
	$restaurants="<div style='width:100%;'><select style='width:100%;' class=\"custom-select multipleSelect\" id=\"restaurantPicker\" name=\"change[restaurants][]\" multiple=\"multiple\">";
	foreach($records as $rec){
		$boards[$rec->levelUpID]=$rec->restaurantName;
		$restaurants.="\n<option value='".$rec->levelUpID."'$ch>".$rec->restaurantName."</option>";
	}
	$restaurants.="</select>";
}
$restaurants.="</div>";
if($_SERVER['REQUEST_METHOD'] == 'POST'){
	$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	$tasks=new task_engine($mysqli);
	if($_POST['action']=='add'){
		$date=date("Y-m-d",strtotime($_POST["startDate"]));
		$time=date("H:i:s",strtotime($_POST["time_picker"]));
		$tasks->add_task(['what'=>'execBackground',
		'target'=>"/home/jewmanfoo/levelup-website-bot/change.sh ",
		'files' => json_encode($_POST['change']),
		'dueDate' => $date . " " . $time]);
	}
	if($_POST['action']=='delete'){
		$tasks->delete_task($_POST["task_id"]);
	}
	if($_POST['action']=='update'){
		$date=date("Y-m-d",strtotime($_POST["startDate"]));
		$time=date("H:i:s",strtotime($_POST["time_picker"]));
		$tasks->update_task ($_POST['id'], array('files' => json_encode($_POST['change']),'dueDate' => $date . " " . $time));
	}
}
$jQuery="
<script>
jQuery(document).ready(function() {
	jQuery('#time_picker').timepicker({
		'timeFormat': 'h:mm p',
		interval: 15,
		minTime: '5:00 am',
		maxTime: '9:00 pm',
		dynamic: false,
		dropdown: true,
 	  scrollbar: true
	});
  jQuery('#startDate').datepicker({
      dateFormat : 'MM d, yy'
  });
	jQuery('#restaurantPicker').select2({
		allowClear: true,
  	theme: \"classic\"
	});";
	if(isset($selectedRestaurants)){$jQuery.="
		jQuery('#restaurantPicker').val([".$selectedRestaurants."]);
		jQuery(\"#restaurantPicker\").trigger(\"change\");";}
	$jQuery.="
});
</script>
";
		$ret.=$jQuery."
	<div>
		<h4>Things to remember when using this system</h4>
		<ul>
			<li>You must set dates/times for off and on</li>
			<li>All times are in Cental. You need to adjust for CO and DC</li>
		</ul>
	</div>
		<div class='form-group'>
			<form method='post' action='".$page."' >
			<input type='hidden' name='action' value='".$actionValue."' />";
	if(isset($task['id'])){$ret.="<input type='hidden' name='id' value='".$task['id']."' />";}
			$ret.="
				<div class='form-group'>
					<div class='row'>
						<div class='col'>
							<div class=\"form-group\" >
								<label for=\"startDate\">Date</label>
								<input class=\"form-control\" type=\"text\" id=\"startDate\" name=\"startDate\" value=\"".$dateValue."\"/>
							</div>
						</div>
						<div class='col'>
							<div class=\"form-group\" >
								<label for='time_picker'>Time</label>
								<input class=\"form-control\" id='time_picker' name='time_picker' style='width: 100px;' value=\"".$timeValue."\"/><br />
							</div>
						</div>
						<div class='col'>
						<label for=''>App State</label>
							<div class=\"input-group\" >
								<div class=\"input-group-prepend\">
									<div class=\"input-group-text\">
				 						<input type='radio' value='true' name='change[action]' id='ocAction-open' ".$onChecked." />	<label for='ocAction-open'>On</label>
									</div>
									<div class=\"input-group-text\">
										<input type='radio' value='false' name='change[action]' id='ocAction-close' ".$offChecked." /> <label for='ocAction-close'>Off</label><br />
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class='row'>
						<div class='col'>
						<label for=''>Restaurants</label>
							" . $restaurants . "
						</div>
					</div>
					<div class='row'>
						<div class='col'>
							<input type='submit' value='".strtoupper($actionValue)."' />
						</div>
					</div>
				</div>
			</form>
		</div>
	";
	$query = "SELECT files,dueDate,id FROM pbc2.pbc_tasks WHERE target ='/home/jewmanfoo/levelup-website-bot/change.sh ' AND dueDate >= CURDATE() AND deleted='0' AND dateCompleted is NULL ORDER BY dueDate ";
	$records=$wpdb->get_results($query);
	$count=0;
	$stateChange["false"]="Off";
	$stateChange["true"]="On";
	if(!empty($records)){
		$ret.="<h4 id='uc'>Upcoming Changes</h4><div style='height:500px;overflow: auto;'>
		<table class='table table-striped'><thead><tr><th><strong>Date/Time</strong></th><th><strong>Restaurant(s)</strong></th><th colspan='3'><strong>State Change</strong></th></thead></tr><tbody>";
		foreach($records as $rec){
			$data=json_decode($rec->files);
			$rets=array();
			foreach($data->restaurants as $r){$rets[]=$boards[$r];}
			$ret.="<tr><td>".date("m/d/Y",strtotime($rec->dueDate)) . "<br>" . date("g:i a",strtotime($rec->dueDate)) ."</td><td>".implode(", ",$rets)."</td><td class='".$cellBG[$stateChange[$data->action]]."'>".$stateChange[$data->action]."</td>
			<td>
				<form method='post' action='".$page."' >
					<input type='hidden' name='action' value='delete' />
					<input type='hidden' name='task_id' value='".$rec->id."' />
					<input type='submit' value='Delete' />
				</form>
			</td>
			<td>
			<form method='get' action='".$page."' >
				<input type='hidden' name='id' value='".$rec->id."' />
				<input type='submit' value='Edit' />
			</form>
			</td>
			</tr>";
		}
		$ret.="</tbody></table></div>";
	}
	/*
function addInlineScripts_sluc(){
	wp_add_inline_script("runtime_jquery_sluc",$jQuery,'before');
}
add_action( 'wp_enqueue_scripts', 'addInlineScripts_sluc');
*/
