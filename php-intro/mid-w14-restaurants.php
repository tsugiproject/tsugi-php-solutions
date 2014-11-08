<?php

require_once "../../config.php";
require_once "../../mod/php-intro/header.php";
require_once "../../mod/php-intro/misc.php";
use Goutte\Client;

line_out("Grading PHP-Intro Restaurants Application");

$url = getUrl('http://www.php-intro.com/exam/mid-w14-restaurants');
$grade = 0;

error_log("Retrieving ".$url);
line_out("Retrieving ".htmlent_utf8($url)."...");
flush();

$client = new Client();

$crawler = $client->request('GET', $url);

// Yes, one gigantic unindented try/catch block
$passed = 0;
$titlepassed = true;
try {

$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);

$retval = webauto_check_title($crawler);
if ( $retval !== true ) {
    error_out($retval);
    $titlepassed = false;
}

line_out("Looking for Add New link.");
$link = $crawler->selectLink('Add New')->link();
$url = $link->getURI();
line_out("Retrieving ".htmlent_utf8($url)."...");

$crawler = $client->request('GET', $url);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;

// Add new fail
line_out("Looking for an 'Add New' submit button on add.php");
$form = $crawler->selectButton('Add New')->form();
line_out("Found 'Add New' submit button");
line_out("-- this autograder expects the form field to be:");
line_out("-- name, hours, and phone");
line_out("-- if your fields do not match these, the next tests will fail.");
line_out(" ");
line_out("Submitting empty form, leaving phone and hours blank.");
$form->setValues(array("name" => "Jerk Pit", "hours" => "", "phone" => ""));
$crawler = $client->submit($form);
$passed++;

$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
checkPostRedirect($client);

line_out("Expecting 'Error in input data'");
if ( strpos(strtolower($html), 'error in') !== false ) {
    $passed++;
} else {
    error_out("Could not find 'Error in input data'");
}

line_out("Looking for Add New link.");
$link = $crawler->selectLink('Add New')->link();
$url = $link->getURI();
line_out("Retrieving ".htmlent_utf8($url)."...");

$crawler = $client->request('GET', $url);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;
line_out("Looking for the form with a 'Add New' submit button");
$form = $crawler->selectButton('Add New')->form();
$name = 'Pizza'.sprintf("%03d",rand(1,100));
$hours = rand(1,100);
$phone = rand(1,100);
line_out("Entering name=$name, hours=$hours, phone=$phone");
$form->setValues(array("name" => $name, "hours" => $hours, "phone" => $phone ) );
$crawler = $client->submit($form);
$passed++;

$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
checkPostRedirect($client);

line_out("Looking '$name' entry");
$pos = strpos($html, $name);
$pos2 = strpos($html, "edit.php", $pos);
$body = substr($html,$pos,$pos2-$pos);
# echo "body=",htmlentities($body);
line_out("Looking for name=$name and phone=$phone");
if ( strpos($body,''.$name) === false || strpos($body,''.$phone) === false ) {
    error_out("Could not find name=$name and phone=$phone");
} else {
    $passed++;
}

line_out("Looking for edit.php link associated with '$name' entry");
$pos3 = strpos($html, '"', $pos2);
$editlink = substr($html,$pos2,$pos3-$pos2);
line_out("Retrieving ".htmlent_utf8($editlink)."...");

$crawler = $client->request('GET', $editlink);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;

line_out("Looking for the form with a 'Update' submit button");
$form = $crawler->selectButton('Update')->form();
line_out("Found 'Update' submit button");
// var_dump($form);
$hours = rand(1,100);
$phone = rand(1,100);
line_out("Editing name=$name, hours=$hours, phone=$phone");
$form->setValues(array("name" => $name, "hours" => $hours, "phone" => $phone));
$crawler = $client->submit($form);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;
checkPostRedirect($client);

// Delete...
line_out("Looking '$name' entry");
$pos = strpos($html, $name);
$pos2 = strpos($html, "delete.php", $pos);
$body = substr($html,$pos,$pos2-$pos);
# echo "body=",htmlentities($body);
line_out("Looking for name=$name and phone=$phone");
if ( strpos($body,''.$name) === false || strpos($body,''.$phone) === false ) {
    error_out("Could not find name=$name and phone=$phone");
} else {
    $passed++;
}

line_out("Looking for delete.php link associated with '$name' entry");
$pos3 = strpos($html, '"', $pos2);
$editlink = substr($html,$pos2,$pos3-$pos2);
line_out("Retrieving ".htmlent_utf8($editlink)."...");

$crawler = $client->request('GET', $editlink);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;

// Do the Delete
line_out("Looking for the form with a 'Delete' submit button");
$form = $crawler->selectButton('Delete')->form();
$crawler = $client->submit($form);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;
checkPostRedirect($client);

line_out("Making sure '$name' has been deleted");
if ( strpos($html,$name) > 0 ) {
    error_out("Entry '$name' not deleted");
} else {
    $passed++;
}

line_out("Cleaning up old Pizza records...");
while (True ) {
    $pos = strpos($html, 'Pizza');
    if ( $pos < 1 ) break;
    $pos2 = strpos($html, "delete.php", $pos);
    if ( $pos2 < 1 ) break;
    $pos3 = strpos($html, '"', $pos2);
    if ( $pos3 < 1 ) break;
    $editlink = substr($html,$pos2,$pos3-$pos2);
    line_out("Retrieving ".htmlent_utf8($editlink)."...");

    $crawler = $client->request('GET', $editlink);
    $html = $crawler->html();
    $OUTPUT->togglePre("Show retrieved page",$html);

    // Do the Delete
    line_out("Looking for the form with a 'Delete' submit button");
    $form = $crawler->selectButton('Delete')->form();
    $crawler = $client->submit($form);
    $html = $crawler->html();
    $OUTPUT->togglePre("Show retrieved page",$html);
    checkPostRedirect($client);
}

line_out("Testing for HTML injection (proper use of htmlentities)...");
line_out("Looking for Add New link.");
$link = $crawler->selectLink('Add New')->link();
$url = $link->getURI();
line_out("Retrieving ".htmlent_utf8($url)."...");

$crawler = $client->request('GET', $url);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;

line_out("Looking for the form with a 'Add New' submit button");
$form = $crawler->selectButton('Add New')->form();
$name = '<b>Pizza</b>'.sprintf("%03d",rand(1,100));
$hours = rand(1,100);
$phone = rand(1,100);
line_out("Entering name=$name, hours=$hours, phone=$phone");
$form->setValues(array("name" => $name, "hours" => $hours, "phone" => $phone));
$crawler = $client->submit($form);
$passed++;

$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
checkPostRedirect($client);

if ( strpos($html, ">Pizza") > 0 ) {
    error_out("Found HTML Injection");
    throw new Exception("Found HTML Injection");
} else if ( strpos($html, "&gt;Pizza") > 0 ) {
    $passed+=2;
    line_out("Passed HTML Injection test");
} else {
    error_out("Cannot find name on page");
}

$pos = strpos($html,"Pizza");
$pos2 = strpos($html, "delete.php", $pos);
line_out("Looking for delete.php link associated with 'Pizza' entry");
$pos3 = strpos($html, '"', $pos2);
$editlink = substr($html,$pos2,$pos3-$pos2);
line_out("Retrieving ".htmlent_utf8($editlink)."...");

$crawler = $client->request('GET', $editlink);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;

if ( strpos($html, ">Pizza") > 0 ) {
    error_out("Found HTML Injection");
    throw new Exception("Found HTML Injection");
} else if ( strpos($html, "&gt;Pizza") > 0 ) {
    $passed+=2;
    line_out("Passed HTML Injection test");
} else {
    error_out("Cannot find name on page");
}

// $passed+=2;

line_out("Looking for the form with a 'Delete' submit button");
$form = $crawler->selectButton('Delete')->form();
$crawler = $client->submit($form);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;
checkPostRedirect($client);


} catch (Exception $ex) {
    // error_out("The last request returned this status code: ".$client->getResponse()->getStatusCode());
    error_out("The autograder did not find something it was looking for in your HTML - test ended.");
    error_log($ex->getMessage());
    error_log($ex->getTraceAsString());
    $detail = "This indicates the source code line where the test stopped.\n" .
        "It may not name any sense without looking at the source code for the test.\n".
        'Caught exception: '.$ex->getMessage()."\n".$ex->getTraceAsString()."\n";
    $OUTPUT->togglePre("Internal error detail.",$detail);
    ob_start();
    var_dump($crawler);
    $result = ob_get_clean();
    $OUTPUT->togglePre("Even more detail.",$result);
}

$perfect = 26;
$score = $passed * (1.0 / $perfect);
if ( $score < 0 ) $score = 0;
if ( $score > 1 ) $score = 1;
$scorestr = "Score = $score ($passed/$perfect)";
if ( $penalty === false ) {
    line_out("Score = $score ($passed/$perfect)");
} else {
    $score = $score * (1.0 - $penalty);
    line_out("Score = $score ($passed/$perfect) penalty=$penalty");
}

if ( ! $titlepassed ) {
    error_out("These pages do not have proper titles so this grade is not official");
    return;
}

if ( $score > 0.0 ) webauto_test_passed($score, $url);

