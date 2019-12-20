<?php
global $wp;
global $wpdb;
global $ret;
$aboveStore=pbk_check_privledge();
$cu = wp_get_current_user();
$page = home_url( add_query_arg( array(), $wp->request ) );
$restaurant=new Restaurant();
include dirname(__FILE__) . '/forms/incident_header.php';
include dirname(__FILE__) . '/forms/foodborneIllness.php';
include dirname(__FILE__) . '/forms/injury.php';
include dirname(__FILE__) . '/forms/lostStolenProperty.php';
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  $incidentType=$_POST['incidentType'];
  $reportInfo=json_encode($_POST['reportInfo'][$incidentType]);
  $guestInfo=json_encode($_POST['guest']);
  $dateOfIncident=date("Y-m-d",strtotime($_POST['startDate'])) . " " . date("G:i:s",strtotime($_POST['timeOfIncident']));
  $content['format']='A4-P';
  $content['title']=$restaurant->incidentTypes[$incidentType]["Name"] . ' Incident Report ' . $_POST['restaurantID'] . "-" . date("Ymd");
  $content['html']=pbk_form_incident_header($_POST)."<h3>" . $restaurant->incidentTypes[$incidentType]["Name"] . "</h3>";
  switch($incidentType){
    case "foodborneIllness":
      $content['html'].=pbk_form_foodborneIllness($_POST['reportInfo']['foodborneIllness']);
      $incidentTypeName="foodborneIllness";
      break;
    case "injury":
      $content['html'].=pbk_form_injury($_POST['reportInfo']['injury']);
      $incidentTypeName="injury";
      break;
    case "lostStolenProperty":
      $content['html'].=pbk_form_lostStolenProperty($_POST['reportInfo']['lostStolenProperty']);
      $incidentTypeName="lostStolenProperty";
      break;
  }
  $wpdb->query(
    $wpdb->prepare(
      "INSERT INTO pbc_incident_reports (dateOfIncident,reporterName,restaurantID,guestInfo,incidentType,reportInfo)
      VALUES(%s,%s,%s,%s,%s,%s)",$dateOfIncident,$_POST['reporterName'],$_POST['restaurantID'],$guestInfo,$incidentTypeName,$reportInfo));
  $email= new ToastReport();
  if($wpdb->last_error !== '') {
  $ret.=  pbk_show_response(array("class"=>"alert","message"=>  "There was an error saving. This error has been reported.<br>" . $wpdb->print_error()));
    $email->reportEmail("jon@theproteinbar.com","SQL Error \n".$wpdb->print_error()."\n\nPosted Data \n".print_r($_POST,true),"Incident Report Save Error");
    exit();
  }
  if($pdf=$restaurant->buildHTMLPDF(json_encode($content))){
    $email->reportEmail($cu->user_email.",jon@theproteinbar.com","Please see attached PDF","New Incident Report",$pdf);
  $ret.=  pbk_show_response(array("class"=>"success","message"=>  "The incident report has been saved."));
  }
}
if(isset($_POST)){unset($_POST);}
$ret.="
<script>
jQuery(document).ready(function() {
  jQuery('#dateOfIncident').datepicker({
    dateFormat : 'MM d, yy',
    maxDate: '0'
  });
  jQuery('#time_picker').timepicker({
		'timeFormat': 'h:mm p',
		interval: 5,
		    minTime: '5:00 am',
		    maxTime: '9:00 pm',
				dynamic: false,
				dropdown: true,
 	    	scrollbar: true
	});
  jQuery(\"#submit\").prop(\"disabled\", true);
  jQuery(\"#submit\").addClass(\"btn btn-secondary btn-lg disabled\");
  jQuery(\"#incidentType\").change(function () {
    var elementToChange=jQuery(\"#incidentType\").val();
    jQuery(\"#foodborneIllness\").hide();
    jQuery(\"#injury\").hide();
    jQuery(\"#lostStolenProperty\").hide();
    jQuery(\"#choose\").hide();
    jQuery(\"#\" + elementToChange).show();
    jQuery(\"#incidentType\").prop(\"disabled\", true);
    jQuery(\"#submit\").removeClass(\"btn btn-secondary btn-lg disabled\");
    jQuery(\"#submit\").prop(\"disabled\", false);
    jQuery(\"#hiddenIncidentType\").html(\"<input type='hidden' name='incidentType' value='\" + elementToChange + \"' />\");
  });
  jQuery(\"#restaurantID\").select2({
  	theme: \"classic\"
	});
  jQuery(\"#submit\").click(function(event){
    if(jQuery(\"#guest_Phone\").val()=='' && jQuery(\"#guest_Email\").val()==''){
      alert('Please Provide a Contact Method');
      jQuery(\"#guest_Phone\").css(\"border\",\"1px solid red\");
      jQuery(\"#guest_Email\").css(\"border\",\"1px solid red\");
      error_free=false;
    }
  	var error_free=true;
    var elementToCheck=jQuery(\"#incidentType\").val();
    if(elementToCheck=='foodborneIllness'){
      if(!jQuery(\"#conclusions\").is(':checked')){jQuery(\"#conclusions_label\").after('<span class=\"alert alert-danger\">Please Confirm</span>');error_free=false;}
      if(!jQuery(\"#contacted\").is(':checked')){jQuery(\"#contacted_label\").after('<span class=\"alert alert-danger\">Please Confirm</span>');error_free=false;}
      if(jQuery(\"#fbi_summary\").val()==''){jQuery(\"#fbi_summary\").after('<span class=\"alert alert-danger\">Please Add a Summary</span>');error_free=false;}
    }
    if(elementToCheck=='lostStolenProperty'){
      if(jQuery(\"#isStolen\").val()==''){jQuery(\"#isStolen\").after('<span class=\"alert alert-danger\">Required</span>');error_free=false;}
      if(jQuery(\"#itemValue\").val()==''){jQuery(\"#contacted_label\").after('<span class=\"alert alert-danger\">Required</span>');error_free=false;}
      if(jQuery(\"#property_summary\").val()==''){jQuery(\"#property_summary\").after('<span class=\"alert alert-danger\">Required</span>');error_free=false;}
      if(jQuery(\"#property_witness\").val()==''){jQuery(\"#property_witness\").after('<span class=\"alert alert-danger\">Required</span>');error_free=false;}
    }
    if(elementToCheck=='injury'){
      if(jQuery(\"#injuryType :selected\").length==0){jQuery(\"#injuryType\").after('<span class=\"alert alert-danger\">Required</span>');error_free=false;}
      if(jQuery(\"#bodyPart :selected\").length==0){jQuery(\"#bodyPart\").after('<span class=\"alert alert-danger\">Required</span>');error_free=false;}
      if(jQuery(\"#bodySide :selected\").length==0){jQuery(\"#bodySide\").after('<span class=\"alert alert-danger\">Required</span>');error_free=false;}
      if(jQuery(\"#injury_summary\").val()==''){jQuery(\"#injury_summary\").after('<span class=\"alert alert-danger\">Required</span>');error_free=false;}
      if(jQuery(\"#injury_witness\").val()==''){jQuery(\"#injury_witness\").after('<span class=\"alert alert-danger\">Required</span>');error_free=false;}
    }
    if (!error_free){
    		event.preventDefault();
    }else{
      window.scrollTo(0,0);
      jQuery(\"#incidentForm\").hide();
      jQuery(\"#processingGif\").show();
      jQuery(\"#submit\").prop(\"disabled\", true);
    }
  });
});
</script>
<div id='incidentForm'>
<form method='post' action='".home_url( add_query_arg( array(), $wp->request ) )."' >
  <div class=\"form-group\">
    ".pbk_form_incident_header()."
  </div>
  <div class='alert alert-primary' id='choose' >Please Select an Incident Type</div>
    <div class=\"form-group\" id='foodborneIllness' style=\"display: none;\">
    ".pbk_form_foodborneIllness()."
    </div>
    <div class=\"form-group\" id='injury' style=\"display: none;\">
  ".pbk_form_injury()."
    </div>
    <div class=\"form-group\" id='lostStolenProperty' style=\"display: none;\">
  ".pbk_form_lostStolenProperty()."
    </div>
    <div class=\"form-group\" id=''>
    <input type='submit' id='submit' value='Save Incident Report' />
  </div>
</form>
</div>
<div id='processingGif' style=\"display: none;text-align:center;\"><img src='" . PBKF_URL . "/assets/images/processing.gif' style='height:92px;width:92px;' /></div>
";
