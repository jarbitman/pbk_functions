<?php
if ( file_exists( ABSPATH . 'wp-config.php') ) {
require_once( ABSPATH . 'wp-config.php' );
}
add_action('admin_enqueue_scripts', 'pbk_scripts');
require_once( ABSPATH . 'wp-admin/includes/screen.php' );
add_action('admin_menu', 'pbr_setup_menu');
    add_action( 'admin_post_pbr_save_restaurant_option', 'pbr_update_restaurant' );
    add_action( 'admin_post_pbr_save_nho', 'pbr_update_nho' );
    add_action('admin_post_pbr_nho_attendance_update','pbr_nho_attendance');
    function pbr_setup_menu(){
            add_menu_page( 'PBK Functions', 'PBK Functions', 'manage_options', 'Manage-PBK', 'pbr_show_admin_functions');
            add_submenu_page( 'Manage-PBK', 'Edit a Restaurant', 'Edit a Restaurant', 'manage_options', 'pbr-edit-restaurant', 'pbr_edit_restaurant' );
            add_submenu_page( 'Manage-PBK', 'Add a Restaurant', 'Add a Restaurant', 'manage_options', 'pbr-add-restaurant', 'pbr_add_restaurant' );
            add_submenu_page( 'Manage-PBK', 'Manage NHO Events', 'Manage NHO Events', 'manage_options', 'pbr-nho', 'pbr_nho_setup' );
            add_submenu_page( 'Manage-PBK', 'NHO Archive', 'NHO Archive', 'manage_options', 'pbr-nho-archive', 'pbr_nho_history' );
            add_submenu_page( 'Manage-PBK', 'Incident Archive', 'Incident Archive', 'manage_options', 'pbr-incident-history', 'pbr_search_incident' );
    }

    function pbr_admin_init(){
    }
    function pbr_add_restaurant(){
      echo "<div class='wrap'><h2>Add a Restaurant</h2>";
   	  $restaurant = new Restaurant();
   	  echo $restaurant->restaurantEditBox();
      echo "</div>";
    }
    function pbr_show_admin_functions(){
      echo "
      <div class='wrap'>
      <h2>PBK Functions</h2>
        <div class='container-fluid' style='width:100%;'>
          <div class='row'>
            <div class='col'>
              <ul class='nav flex-column'>
                <li class='nav-item'><a class='nav-link' href='" . admin_url( 'admin.php?page=pbr-edit-restaurant') . "'>Edit a Restaurant</a></li>
                <li class='nav-item'><a class='nav-link' href='" . admin_url( 'admin.php?page=pbr-add-restaurant') . "'>Add a Restaurant</a></li>
                <li class='nav-item'><a class='nav-link' href='" . admin_url( 'admin.php?page=pbr-nho') . "'>Manage NHO Events</a></li>
                <li class='nav-item'><a class='nav-link' href='" . admin_url( 'admin.php?page=pbr-nho-archive') . "'>NHO Archive</a></li>
                <li class='nav-item'><a class='nav-link' href='" . admin_url( 'admin.php?page=pbr-incident-history') . "'>Incident Archive</a></li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      ";
    }
function pbr_search_incident(){
  $restaurant = new Restaurant();
  echo "
  <div class='wrap'>
    <h2>Incident Archive</h2>
      <div class='container-fluid' style='width:100%;'>
      <div class='container'>
        <form method='get' action='". admin_url( 'admin.php')."' >
          <input type='hidden' name='page' value='pbr-incident-history' />
          <div class=\"form-group\">
            <div class='row'>
              <div class='col'>
                " . $restaurant->buildDateSelector('startDate',"Starting Date") . "
              </div>
              <div class='col'>
                " . $restaurant->buildDateSelector('endDate',"Ending Date") . "
              </div>
            </div>
          </div>
          <div class=\"form-group\">
            <input id='submit' type='submit' value='SEARCH' />
          </div>
        </form>
        </div>
    " . $restaurant->pbk_form_processing();
  if(isset($_GET['startDate']) && isset($_GET['endDate'])){
    echo "
    <script>
    jQuery(document).ready( function () {
        jQuery('#myTable').DataTable();
        jQuery(\".itemName\").on(\"click\", function(e) {
          jQuery(\"#report_\" + e.target.id).show();
        })
    } );
    </script>
    <div id='queryResults'>
    ";
    if($results=$restaurant->get_incident_reports()){
      include dirname(__FILE__) . '/modules/forms/incident_header.php';
      include dirname(__FILE__) . '/modules/forms/foodborneIllness.php';
      include dirname(__FILE__) . '/modules/forms/injury.php';
      include dirname(__FILE__) . '/modules/forms/lostStolenProperty.php';
      echo "
      <table id='myTable' class=\"table table-striped table-hover\" style='width:100%;'>
        <thead style='background-color:#0e2244; color: #ffffff; text-align: center;font-weight:bold;'>
          <tr>
            <th>Restaurant</th>
            <th>Incident Date</th>
            <th>Reported By</th>
            <th>Incident Type</th>
            <th>Reported Date</th>
            <th></th>
          </tr>
        </thead>
";
      foreach($results as $r){
        $ih["reporterName"]=$r->reporterName;
        $ih["startDate"]=$r->dateOfIncident;
        $ih["timeOfIncident"]=$r->dateOfIncident;
        $ih["restaurantID"]=$r->restaurantID;
        $ih['guest']=json_decode($r->guestInfo,true);
        $content['format']='A4-P';
        $content['title']=$restaurant->incidentTypes[$r->incidentType]["Name"] . ' Incident Report ' . $ih['restaurantID'] . "-" . date("Ymd",strtotime($r->dateOfIncident));
        $content['html']=pbk_form_incident_header($ih)."<h3>" . $restaurant->incidentTypes[$r->incidentType]["Name"] . "</h3>";
        switch($r->incidentType){
          case "foodborneIllness":
            $content['html'].=pbk_form_foodborneIllness(json_decode($r->reportInfo,true));
            break;
          case "injury":
            $content['html'].=pbk_form_injury(json_decode($r->reportInfo,true));
            break;
          case "lostStolenProperty":
            $content['html'].=pbk_form_lostStolenProperty(json_decode($r->reportInfo,true));
            break;
        }
        if($link=$restaurant->buildHTMLPDF(json_encode($content))){$download="<a href='" . $link['Link'] . "' target='_blank'>Download</a>";}else{$download='';}
        echo "
        <tr>
          <td><div class='itemName' id='".$r->id_pbc_incident_reports."'>" . $restaurant->getRestaurantName($r->restaurantID) . "</div></td>
          <td>" . date("m/d/Y",strtotime($r->dateOfIncident)) . "</td>
          <td>" . $r->reporterName . "</td>
          <td>" . $restaurant->incidentTypes[$r->incidentType]["Name"] . "</td>
          <td>" . date("m/d/Y",strtotime($r->reportAdded)) . "</td>
          <td>" . $download . "</td>
        </tr>
        ";
      }
      echo "</table>";
    }else {
      echo "<div class='alert alert-warning'>There were no reports found for " . $_GET['startDate'] . " - " . $_GET['endDate'] . "</div>";
    }
    echo "</div>";
  }
  echo "
    </div>
  </div>";
}
function pbr_edit_restaurant(){
	if(!class_exists('WP_List_Table')){
	   require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}
   echo "<div class=\"wrap\"><div id=\"icon-users\" class=\"icon32\"></div><h2>Edit an Existing Restaurant <a href=\"?page=pbr-add-restaurant\" class=\"add-new-h2\">Add New</a>
            </h2>
            ";
	if(!isset($_GET['restaurant']) && !is_numeric()) {
		require_once( 'classes/testlisttable.php' );
	   $myListTable = new My_Example_List_Table();
		$myListTable->prepare_items();
		$myListTable->display();
	}else {
   	$restaurant = new Restaurant($_GET['restaurant']);
   	echo $restaurant->restaurantEditBox();
	}
  echo "</div>";
}
function pbr_update_restaurant() {
	print_r($_POST);
   	$restaurant = new Restaurant();
   	$restaurant->setRestaurantInfo($_POST);
//   	print_r($restaurant->rinfo);
//   	die();
   	if($restaurant->insertUpdateRestaurantInfo()) {
   		$m=1;
   	}else {
   		$m=2;
   	}
   	wp_redirect(  admin_url( 'admin.php?page=pbr-edit-restaurant&m='.$m ) );
   	exit;
   	//$restaurant->restaurantEditBox();
}
function pbr_nho_setup(){
  echo "<div class=\"wrap\"><div id=\"icon-users\" class=\"icon32\"></div><h2>NHO Events <a href=\"?page=pbr-nho&amp;nhoDate=_new\" class=\"add-new-h2\">Add New</a>
           </h2>
           </div>";
  $restaurant = new Restaurant();
  if(isset($_GET['nhoDate']) && $_GET['nhoDate']=="_new"){
    echo $restaurant->nho_sign_up_manage();
  }elseif(isset($_GET['nhoDate']) && isset($_GET['nhoLocation'])) {
    echo $restaurant->nho_sign_up_manage($_GET);
  }else{
    require_once( 'classes/nhoList.php' );
    $myListTable = new nhoList();
    $myListTable->prepare_items();
    $myListTable->display();
  }
}
function pbr_update_nho() {
  $restaurant = new Restaurant();
  $restaurant->updateNHO($_POST);
}
function pbr_nho_attendance(){
  $restaurant = new Restaurant();
  $restaurant->updateNHOAttendance($_POST);
  wp_redirect(  admin_url( 'admin.php?page=pbr-nho&nhoDate='.date("y-m-d",strtotime($_POST['nhoDate'])).'&nhoLocation='.$_POST['nhoLocation'].'&r=0' ) );
  exit;
}
function pbr_nho_history(){
  echo "<div class='wrap'><h2>View the NHO History for the last 3 months.</h2>";
  $restaurant = new Restaurant();
  echo $restaurant->nhoHistory($_GET);
  echo "</div>";
}
