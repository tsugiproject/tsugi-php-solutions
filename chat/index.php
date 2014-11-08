<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

use \Tsugi\Core\LTIX;

// Retrieve the launch data if present
$LTI = LTIX::requireData(array('user_id', 'result_id', 'role','context_id'));
$p = $CFG->dbprefix;
$displayname = $USER->displayname;

// The reset operation is a normal POST - not AJAX
if ( $USER->instructor && isset($_POST['reset']) ) {
    $sql = "DELETE FROM {$p}sample_chat WHERE link_id = :LI";
    $stmt = $PDOX->prepare($sql);
    $stmt->execute(array(':LI' => $LINK->id));
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

if ( isset($_POST['chat']) && strlen($_POST['chat']) > 0 ) {
    error_log("Insert UID=".$USER->id." CHAT=".$_POST['chat']);
    $stmt = $PDOX->prepare("INSERT INTO {$p}sample_chat 
        (link_id, user_id, chat, created_at) 
        VALUES ( :LID, :UID, :CHAT, NOW() )");
    $stmt->execute( array(":LID" => $LINK->id,
        ":CHAT" => $_POST['chat'], ":UID" => $USER->id) );
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

?>
<html><head><title>Chat for 
<?php echo(htmlent_utf8($CONTEXT->title)); ?>
</title>
<script type="text/javascript" 
src="<?php echo($CFG->staticroot); ?>/static/js/jquery-1.10.2.min.js"></script>
<script type="text/javascript">

function htmlentities(str) {
    return $('<div/>').text(str).html();
}

var OLD_TIMEOUT = false;
$(document).ready(function(){ 
  window.console && console.log('Hello JQuery..');
  OLD_TIMEOUT = setTimeout('messages()', 200);
});

function messages() {
    if ( OLD_TIMEOUT ) {
        clearTimeout(OLD_TIMEOUT);
        OLD_TIMEOUT = false;
    }
    window.console && console.log('Updating messages...');
    $.getJSON('<?php echo(addSession('chatlist.php')); ?>', function(data) {
        window.console && console.log(data);
        $("#messages").empty();
        for (var i = 0; i < data.length; i++) {
            entry = data[i];
            $("#messages").append("<p>"+htmlentities(entry.chat)+'<br/>&nbsp;&nbsp;'
                +htmlentities(entry.displayname)+' '+htmlentities(entry.created_at)+"</p>\n");
            console.log(data[i]);
        }
        OLD_TIMEOUT = setTimeout('messages()', 4000);
  });
}
</script>
</head>
<body>
<form id="chatform" method="post">
<input type="text" size="60" name="chat" />
<input type="submit" value="Chat"/>
<?php if ( $USER->instructor ) { ?>
<input type="submit" name="reset" value="Reset"/>
<a style="color:grey" href="chatlist.php" target="_blank">Launch chatlist.php</a>
<?php } ?>
</form>
<p id="messages">
<img id="spinner" src="spinner.gif">
</p>
</body>
