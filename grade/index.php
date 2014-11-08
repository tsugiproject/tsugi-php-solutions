<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/core/gradebook/lib.php";

// Retrieve the launch data if present
$LTI = ltiRequireData(array('user_id', 'result_id', 'role','context_id'));
$p = $CFG->dbprefix;
$displayname = $USER->displayname;

if ( isset($_POST['reset']) ) {
    $sql = "UPDATE {$p}lti_result SET grade = 0.0 WHERE result_id = :RI";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(':RI' => $LTI['result_id']));
    $_SESSION['success'] = "Grade reset";
    header( 'Location: '.sessionize('index.php') ) ;
    return;
}

if ( isset($_POST['grade']) )  {
    $gradetosend = $_POST['grade'] + 0.0;
    if ( $gradetosend < 0.0 || $gradetosend > 1.0 ) {
        $_SESSION['error'] = "Grade out of range";
        header('Location: '.sessionize('index.php'));
        return;
    }

    // TODO: Use a SQL SELECT to retrieve the actual grade from tsugi_lti_result
    // The key for the grade row is in the $LTI['result_id'];
    $stmt = $pdo->prepare("SELECT grade FROM {$p}lti_result WHERE result_id = :ID");
    $stmt->execute(array(":ID" => $LTI['result_id']));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $oldgrade = 0.0;
    if ( $row !== false ) $oldgrade = $row['grade'];

    if ( $gradetosend < $oldgrade ) {
        $_SESSION['error'] = "Grade lower than $oldgrade - not sent";
    } else {
        // Call the XML APIs to send the grade back to the LMS.
        $retval = gradeSend($gradetosend, false);
        if ( $retval === true ) {

            $sql = "UPDATE {$p}lti_result SET 
                grade = :GR WHERE result_id = :ID";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array(
                ':GR' => $gradetosend,
                ':ID' => $_SESSION['lti']['result_id']));

            $_SESSION['success'] = "Grade $gradetosend sent to server.";
        } else if ( is_string($retval) ) {
            $_SESSION['error'] = "Grade not sent: ".$retval;
        } else {
            echo("<pre>\n");
            var_dump($retval);
            echo("</pre>\n");
            die();
        }
    }

    // Redirect to ourself 
    header('Location: '.sessionize('index.php'));
    return;
}

// Start of the output
$OUTPUT->header();
$OUTPUT->bodyStart();

if ( isset($_SESSION['error']) ) {
    echo '<p style="color:red">'.$_SESSION['error']."</p>\n";
    unset($_SESSION['error']);
}
if ( isset($_SESSION['success']) ) {
    echo '<p style="color:green">'.$_SESSION['success']."</p>\n";
    unset($_SESSION['success']);
}

if ( $displayname ) {
    echo("<p>Welcome <strong>\n");
    echo(htmlent_utf8($displayname));
    echo("</strong></p>\n");
}
?>
<form method="post">
Enter grade:
<input type="number" name="grade" step="0.01" min="0" max="1.0"><br/>
<input type="submit" name="send" value="Send grade">
<input type="submit" name="reset" value="Reset grade"><br/>
</form>
<?php

echo('<p>$LTI["result_id"] is: '.$LTI['result_id']."</p>\n");

$dump = safe_var_dump($_SESSION);
echo("\n<pre>\nSession Dump:\n".$dump."\n</pre>\n");

$OUTPUT->footer();

