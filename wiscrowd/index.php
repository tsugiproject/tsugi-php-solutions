<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

use \Tsugi\Core\LTIX;

// Sanity checks
$LTI = LTIX::requireData(array('user_id', 'result_id', 'role','context_id'));
$p = $CFG->dbprefix;

if ( isset($_POST['check']) ) {
    header( 'Location: '.sessionize('index.php') ) ;
    return;
} else if ( $USER->instructor && isset($_POST['reset']) ) {
    $sql = "DELETE FROM {$p}solution_wiscrowd WHERE link_id = :LI";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(':LI' => $LINK->id));
    $_SESSION['success'] = 'Guesses cleared';
    header( 'Location: '.sessionize('index.php') ) ;
    return;
} else if ( isset($_POST['guess']) ) {
    if ( ! is_numeric($_POST['guess']) ) {
        $_SESSION['error'] = 'Non-numeric guess is not allowed';
        header( 'Location: '.sessionize('index.php') ) ;
        return;
    }

    $sql = "INSERT INTO {$p}solution_wiscrowd
            (link_id, user_id, guess) VALUES 
            ( :LI, :UI, :GU ) 
            ON DUPLICATE KEY UPDATE guess = :GU";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        ':LI' => $LINK->id,
        ':UI' => $USER->id,
        ':GU' => $_POST['guess']));
    $_SESSION['success'] = 'Guess recorded';
    header( 'Location: '.sessionize('index.php') ) ;
    return;
}

// View 
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

// A nice welcome...
echo("<p>Welcome");
if ( isset($USER->displayname) ) {
    echo(" ");
    echo(htmlent_utf8($USER->displayname));
}
if ( isset($CONTEXT->title) ) {
    echo(" from ");
    echo(htmlent_utf8($CONTEXT->title));
}

if ( $USER->instructor ) {
    echo(" (Instructor)");
}
echo("</p>\n");

echo('<form method="post">');
echo("Enter guess:\n");
echo('<input type="text" name="guess" value=""> ');
echo('<input type="submit" class="btn btn-primary" name="send" value="Guess"> ');
if ( $USER->instructor ) {
echo('<input type="submit" class="btn btn-info" name="check" onclick="$(\'#guesses\').toggle(); return false;"value="Toggle guesses"> ');
echo('<input type="submit" name="check" class="btn btn-success" value="Check guesses"> ');
echo('<input type="submit" name="reset" class="btn btn-danger" value="Reset guesses"><br/>');
}
echo("\n</form>\n");

echo("\n<div id=\"guesses\">\n");

//Retrieve the guesses
if ( $USER->instructor ) {
    $stmt = $pdo->prepare("SELECT guess,displayname FROM {$p}solution_wiscrowd
        JOIN {$p}lti_user
        ON {$p}solution_wiscrowd.user_id = {$p}lti_user.user_id
        WHERE link_id = :LI AND guess > 0.0");
    $stmt->execute(array(":LI" => $LINK->id));
    $data = array();
    while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
        $data[] = array($row['guess'],$row['displayname']);
    }

    $total = 0;
    $count = 0;
    foreach ( $data as $element ) {
        $count++;
        $total = $total + $element[0];
    }

    if ( $count > 0 ) {
        echo "Guesses=".$count." Average=".($total/$count)."<br/>\n";
    }
    rsort($data);
    foreach ( $data as $element ) {
        echo '<span title="'.htmlentities($element[1]).'">'.$element[0].'</span>';
        echo "<br/>\n";
    }

echo("\n</div>\n");

$OUTPUT->footer();
}
