<?php
require_once "../config.php";

use \Tsugi\Core\LTIX;

// Sanity checks
$LTI = LTIX::requireData(array('user_id', 'link_id', 'role','context_id'));

$p = $CFG->dbprefix;
if ( isset($_POST['lat']) && isset($_POST['lng']) ) {
    if ( abs($_POST['lat']) > 85 || abs($_POST['lng']) > 180 ) {
        $_SESSION['error'] = "Latitude or longitude out of range";
        header( 'Location: '.addSession('index.php') ) ;
        return;
    }
    $stmt = $PDOX->queryDie(
        "INSERT INTO {$p}sample_map 
            (context_id, user_id, lat, lng, updated_at) 
            VALUES ( :CID, :UID, :LAT, :LNG, NOW() ) 
            ON DUPLICATE KEY 
            UPDATE lat = :LAT, lng = :LNG, updated_at = NOW()",
        array(
            ':CID' => $CONTEXT->id, ':UID' => $USER->id,
            ':LAT' => $_POST['lat'], ':LNG' => $_POST['lng']));
    $_SESSION['success'] = 'Location updated...';
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// Retrieve our row
$row = $PDOX->rowDie("SELECT lat,lng FROM {$p}sample_map 
        WHERE context_id = :CID AND user_id = :UID",
    array(":CID" => $CONTEXT->id, ":UID" => $USER->id)
);

// The default for latitude and longitude
$lat = 42.279070216140425;
$lng = -83.73981015789798;
if ( $row !== false ) {
    $lat = $row['lat'];
    $lng = $row['lng'];
}

//Retrieve the other rows
$stmt = $PDOX->prepare("SELECT lat,lng,displayname FROM {$p}sample_map 
        JOIN {$p}lti_user
        ON {$p}sample_map.user_id = {$p}lti_user.user_id
        WHERE context_id = :CID AND {$p}sample_map.user_id <> :UID");
$stmt->execute(array(":CID" => $CONTEXT->id, ":UID" => $USER->id));
$points = array();
while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    $points[] = array($row['lat']+0.0,$row['lng']+0.0,$row['displayname']);
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

if ( !isset($CFG->google_map_api_key) ) {
    echo('<p>There is no MAP api key ($CFG->google_map_api_key)</p>'."\n");
    $OUTPUT->footer();
    return;
}

?>
<div id="map_canvas" style="margin: 10px; width:500px; max-width: 100%; height:500px"></div>
<form method="post">
 Latitude: <input size="30" type="text" id="latbox" name="lat" 
  <?php echo(' value="'.htmlent_utf8($lat).'" '); ?> >
 Longitude: <input size="30" type="text" id="lngbox" name="lng"
  <?php echo(' value="'.htmlent_utf8($lng).'" '); ?> >
 <button type="submit">Save Location</button>
</form>
<?php
$OUTPUT->footerStart();
?>
<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&key=<?= $CFG->google_map_api_key ?>"></script>
<script type="text/javascript">
var map;

// https://developers.google.com/maps/documentation/javascript/reference
function initialize_map() {
  var myLatlng = new google.maps.LatLng(<?php echo($lat.", ".$lng); ?>);
  window.console && console.log("Building map...");

  var myOptions = {
     zoom: 2,
     center: myLatlng,
     mapTypeId: google.maps.MapTypeId.ROADMAP
  }

  map = new google.maps.Map(document.getElementById("map_canvas"), myOptions); 

  var marker = new google.maps.Marker({
    draggable: true,
    position: myLatlng, 
    map: map,
    title: "Your location"
  });

  google.maps.event.addListener(marker, 'dragend', function (event) {
    // getPosition returns a google.maps.LatLng class for
    // for the dropped marker
    // Make sure to watch the console to see the structure of the event
    window.console && console.log(this.getPosition());
    // TODO: Fix these next two lines - search the web for a solution
    document.getElementById("latbox").value = this.getPosition().lat();
    document.getElementById("lngbox").value = this.getPosition().lng();
  });

  // Add the other points
  window.console && console.log("Loading "+other_points.length+" points");
  for ( var i = 0; i < other_points.length; i++ ) {
    var row = other_points[i];
    // if ( i < 3 ) { alert(row); }
    var newLatlng = new google.maps.LatLng(row[0], row[1]);
    var iconpath = '<?php echo($CFG->staticroot); ?>/img/icons/';
    var icon = 'green.png';
    var marker = new google.maps.Marker({
      position: newLatlng,
      map: map,
      icon: iconpath + icon,
      // TODO: See if you can get the user's displayname here
      title : row[2]
     });
  }
}

// Load the other points 
other_points = 
<?= json_encode($points) ?> 
;

// Ask jQuery to run our function once the document has loaded
$(document).ready(function() {
    initialize_map();
});
</script>
<?php
$OUTPUT->footerEnd();
