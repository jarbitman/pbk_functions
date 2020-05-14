<?php
global $wp;
global $wpdb;
global $ret;
$groups[1]="BREAKFAST SCRAMBLES";
$groups[8]="BREAKFAST OATMEAL";
$groups[2]="SHAKES";
$groups[3]="BOWLS/BAR-RITOS";
$groups[9]="BOWLS with RICED CAULIFLOWER";
$groups[4]="CHILIS/SOUPS";
$groups[5]="SALADS/WRAPS";
$groups[6]="KIDS MENU";
$groups[7]="COFFEE";
$allergens=array("Wheat/Gluten","Egg","Peanut","Tree Nuts","Dairy","Soy Protein","Sesame","Fish/Shellfish");
$preferences=array("Vegetarian","Vegan","Keto","Paleo");
$page = home_url(add_query_arg(array(), $wp->request));
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $itemInfo=$_POST['itemInfo'];
    $itemInfo['allergens']=implode(", ", $_POST['allergens']);
    $itemInfo['preferences']=implode(", ", $_POST['preferences']);
    $itemInfo=json_encode($itemInfo);
    if ($_REQUEST['item']=='_NEW') {
        $wpdb->query($wpdb->prepare(
            "
      INSERT INTO pbc_public_nutritional (itemName,itemSection,published,itemInfo)VALUES(%s,%s,%s,%s)
      ",
            $_POST['itemName'],
            $_POST['itemSection'],
            $_POST['published'],
            $itemInfo
        ));
        $_POST['item']=$wpdb->insert_id;
    } else {
        $wpdb->query($wpdb->prepare(
            "
      REPLACE INTO pbc_public_nutritional (idpbc_public_nutritional,itemName,itemSection,published,itemInfo)VALUES(%s,%s,%s,%s,%s)
      ",
            $_POST['item'],
            $_POST['itemName'],
            $_POST['itemSection'],
            $_POST['published'],
            $itemInfo
        ));
    }
    if ($wpdb->last_error !== '') {
        require_once("/var/www/html/c2.theproteinbar.com/wp-content/plugins/pbr_finance/includes/ToastFunctions/classes/ToastReport.php");
        $rpt= new ToastReport();
        $rpt->reportEmail("jon@theproteinbar.com", "SQL Error \n".$wpdb->print_error()."\n\nPosted Data \n".print_r($_POST, true), "Nutrition_Guide Save Error");
        $message="<div class='alert' id='message' style='text-align:center;'><p style='padding:3px;'>There was an error saving. This error has been reported.</p></div>";
    } else {
        $message="<div class='success' id='message' style='text-align:center;'><p style='padding:3px;'>The updates have been saved.</p></div>";
    }
    $ret.="<script src=\"https://code.jquery.com/jquery-1.10.1.min.js\"></script>
      ".$message."
      <script type=\"text/javascript\">
        $(document).ready(function(){
          setTimeout(function(){
          $(\"#message\").hide(\"20000\")
        }, 30000);
        });
      </script>
";
}
  if (isset($_REQUEST['archived']) && $_REQUEST['archived']=="yes") {
      $published=0;
      $checked="checked='checked'";
  } else {
      $published=1;
      $checked="";
  }
  $items = $wpdb->get_results("SELECT itemName,idpbc_public_nutritional,itemSection FROM pbc_public_nutritional  WHERE published='".$published."' order by itemSection,itemName");
  foreach ($items as $item) {
      $listItems[$item->itemSection][$item->idpbc_public_nutritional]=$item->itemName;
  }
  $ret.="\n
  <script>
  jQuery( function() {
    jQuery('#itemSelector').select2({
      theme: \"classic\"
    });
  } );
  </script>
	<div>
		<form method='get' action='".$page."'  name='itemSelector'>
			<select name='item' onchange=\"this.form.submit()\" id='itemSelector' class='form-control multipleSelect'>
      <option value=''>Choose an Item</option>
      <option value='_NEW'>Add a New Item</option>";
    foreach ($listItems as $section=>$items) {
        $ret.="<optgroup label='".$groups[$section]."'>";
        foreach ($items as $id=>$item) {
            if (isset($_REQUEST['item']) && $_REQUEST['item']==$id) {
                $sel="selected='selected'";
            } else {
                $sel="";
            }
            $ret.="\n<option value='".$id."' $sel>".stripslashes($item)."</option>";
        }
        $ret.="</optgroup>";
    }
    $ret.="</select></form></div>
  <div>
    <form method='get' action='".$page."'  name='showPublished'>
      <input class='form-control' type='checkbox' name='archived' onchange=\"this.form.submit()\" value='yes' $checked/> View Archived Items?
    </form>
  </div>
  ";
  if (isset($_REQUEST['item'])) {
      $item = $wpdb->get_row("SELECT * FROM pbc_public_nutritional  WHERE idpbc_public_nutritional='".$_REQUEST['item']."'");
      if ((!isset($item->itemName) || $item->itemName=='') &&  $_REQUEST['item']!='_NEW') {
          echo  "<div class='alert' id='message' style='text-align:center;'><p style='padding:3px;'>ITEM NOT FOUND</p></div>";
          exit;
      }
      $itemInfo=json_decode($item->itemInfo);
      $ret.="
  <div style='width:100%;'>
    <h3>".stripslashes($item->itemName)."</h3>
    <form method='post' action='".$page."'>
      <input class='form-control' type='hidden' name='item' value='".$_REQUEST['item']."' />
    <div class='container-fluid'>
    <div class='row'>
      <div class='col'><label for='itemName'>Item Name</label><br><input class='form-control' required type='text' name='itemName' value='".stripslashes($item->itemName)."' id='itemName' /></div>
      <div class='col'><label for='published'>Published?</label><br>
      <select class='form-control' name='published' id='published' >
        <option value='1' ";
      if ($item->published==1) {
          $ret.="selected='selected'";
      }
      $ret.=  ">Yes</option>
        <option value='0' ";
      if ($item->published==0) {
          $ret.="selected='selected'";
      }
      $ret.=  ">No</option>
      </select>
      </div>
      <div class='col'><label for='itemSection'>Category</label><br>
      <select class='form-control' name='itemSection' id='itemSection' >
        <option value=''>Choose One</option>";
      foreach ($groups as $id=>$name) {
          if ($item->itemSection==$id) {
              $sel="selected='selected'";
          } else {
              $sel="";
          }
          $ret.= "<option value='".$id."' ".$sel.">".$name."</option>";
      }
      $ret.=    "
      </select>
      </div>
    </div>
      <div class='row'>
        <div class='col'><label for='PR'>Protein</label><br><input class='form-control' type='text' name='itemInfo[PR]' value='".$itemInfo->PR."' id='PR' /></div>
        <div class='col'><label for='Cal'>Calories</label><br><input class='form-control' type='text' name='itemInfo[Cal]' value='".$itemInfo->Cal."' id='Cal' /></div>
        <div class='col'><label for='TF'>Total Fat</label><br><input class='form-control' type='text' name='itemInfo[TF]' value='".$itemInfo->TF."' id='TF' /></div>
      </div>
      <div class='row'>
        <div class='col'><label for='SF'>Saturated Fat</label><br><input class='form-control' type='text' name='itemInfo[SF]' value='".$itemInfo->SF."' id='SF' /></div>
        <div class='col'><label for='TRF'>Trans Fat</label><br><input class='form-control' type='text' name='itemInfo[TRF]' value='".$itemInfo->TRF."' id='TRF' /></div>
        <div class='col'><label for='CHO'>Cholesterol</label><br><input class='form-control' type='text' name='itemInfo[CHO]' value='".$itemInfo->CHO."' id='CHO' /></div>
      </div>
      <div class='row'>
        <div class='col'><label for='SOD'>Sodium</label><br><input class='form-control' type='text' name='itemInfo[SOD]' value='".$itemInfo->SOD."' id='SOD' /></div>
        <div class='col'><label for='NC'>Net Carbs</label><br><input class='form-control' type='text' name='itemInfo[NC]' value='".$itemInfo->NC."' id='NC' /></div>
        <div class='col'><label for='TC'>Total Carbs</label><br><input class='form-control' type='text' name='itemInfo[TC]' value='".$itemInfo->TC."' id='TC' /></div>
      </div>
      <div class='row'>
        <div class='col'><label for='DF'>Dietary Fiber</label><br><input class='form-control' type='text' name='itemInfo[DF]' value='".$itemInfo->DF."' id='DF' /></div>
        <div class='col'><label for='SG'>Sugars</label><br><input class='form-control' type='text' name='itemInfo[SG]' value='".$itemInfo->SG."' id='SG' /></div>
        <div class='col'></div>
      </div>
    </div>
      <div class='form-group'>
        <label for='allergens'>Allergens</label><br>";
      $allergy=explode(", ", $itemInfo->allergens);
      foreach ($allergens as $allergen) {
          if (in_array($allergen, $allergy)) {
              $check="checked='checked'";
          } else {
              $check="";
          }
          $ret.="<input class='form-control' type='checkbox' name='allergens[]' value='".$allergen."' ".$check."> ".$allergen."<br>";
      }
      $ret.=  "
      </div>
      <div class='form-group'>
        <label for='preferences'>Dietary Preferences</label><br>";
      $dietary=explode(", ", $itemInfo->preferences);
      foreach ($preferences as $preference) {
          if (in_array($preference, $dietary)) {
              $check="checked='checked'";
          } else {
              $check="";
          }
          $ret.="<input class='form-control' type='checkbox' name='preferences[]' value='".$preference."' ".$check."> ".$preference."<br>";
      }
      $ret.=  "
      </div>
      <div><input class='form-control' type='submit' value='Save' /></div>
    </form>
    </div>
    ";
  }
