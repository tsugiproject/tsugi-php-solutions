<?php

require_once "../../config.php";
require_once "header.php";
require_once "misc.php";
use Goutte\Client;

line_out("Grading PHP-Intro Video Application");

$gradeurl = getUrl('http://www.wa4e.com/exam/mid-w14-videos');
// $gradeurl = getUrl('http://localhost/~csev/mid-w14-videos');
//$gradeurl = getUrl('');
$grade = 0;

error_log("Contacts ".$gradeurl);
line_out("Retrieving ".htmlent_utf8($gradeurl)."...");
flush();

$client = new Client();

$crawler = $client->request('GET', $gradeurl);

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
$gradeurl = $link->getURI();
line_out("Retrieving ".htmlent_utf8($gradeurl)."...");

$crawler = $client->request('GET', $gradeurl);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;

// Add new fail
line_out("Looking for the form with a 'Add New' submit button");
$form = $crawler->selectButton('Add New')->form();
line_out("-- this autograder expects the form field names to be:");
line_out("-- url, email, length, and rating");
line_out("-- if your fields do not match these, the next tests will fail.");
line_out("Causing Add error, leaving length and rating blank.");
$form->setValues(array("url" => "Sarah", "email" => "Anytown", "length" => "", "rating" => ""));
$crawler = $client->submit($form);
$passed++;

$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
checkPostRedirect($client);

line_out("Expecting 'All values are required'");
if ( strpos(strtolower($html), 'are required') !== false ) {
    $passed++;
} else {
    error_out("Could not find 'All values are required'");
}

line_out("Looking for the form with a 'Add New' submit button");
$form = $crawler->selectButton('Add New')->form();

line_out("Causing Add error, putting in bad email address.");
$form->setValues(array("url" => "http://www.wa4e.com", "email" => "PO Box 123", "length" => "12", "rating" => "6"));
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

line_out("Looking for the form with a 'Add New' submit button");
$form = $crawler->selectButton('Add New')->form();
$url = 'http://www.wa4e.com/x.php?data='.sprintf("%03d",rand(1,100));
$email = "sarah@wa4e.com";
$length = rand(1,100);
$rating = rand(1,100);
line_out("Entering url=$url, email=$email, length=$length");
$form->setValues(array("url" => $url, "email" => $email, "length" => $length, "rating" => "12345"));
$crawler = $client->submit($form);
$passed++;

$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
checkPostRedirect($client);

line_out("Looking '$url' entry");
$pos = strpos($html, $url);
$pos2 = strpos($html, "edit.php", $pos);
$body = substr($html,$pos,$pos2-$pos);
# echo "body=",htmlentities($body);
line_out("Looking for email=$email and length=$length");
if ( strpos($body,''.$email) < 1 || strpos($body,''.$length) < 1 ) {
    error_out("Could not find email=$email and length=$length");
} else {
    $passed++;
}

line_out("Looking for edit.php link associated with '$url' entry");
$pos3 = strpos($html, '"', $pos2);
$editlink = substr($html,$pos2,$pos3-$pos2);
line_out("Retrieving ".htmlent_utf8($editlink)."...");

$crawler = $client->request('GET', $editlink);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;

line_out("Looking for the form with a 'Update' submit button");
$form = $crawler->selectButton('Update')->form();
$length = rand(1,100);
$rating = rand(1,100);
line_out("Editing url=$url, length=$length, rating=$rating");
$form->setValues(array("url" => $url, "email" => $email, "length" => $length, "rating" => "12345"));
$crawler = $client->submit($form);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;
checkPostRedirect($client);

// Delete...
line_out("Looking '$url' entry");
$pos = strpos($html, $url);
$pos2 = strpos($html, "delete.php", $pos);
$body = substr($html,$pos,$pos2-$pos);
# echo "body=",htmlentities($body);
line_out("Looking for email=$email and length=$length");
if ( strpos($body,''.$email) < 1 || strpos($body,''.$length) < 1 ) {
    error_out("Could not find email=$email and length=$length");
} else {
    $passed++;
}

line_out("Looking for delete.php link associated with '$url' entry");
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

line_out("Making sure '$url' has been deleted");
if ( strpos($html,$url) > 0 ) {
    error_out("Entry '$url' not deleted");
} else {
    $passed++;
}

line_out("Cleaning up old Sarah records...");
while (True ) {
    $pos = strpos($html, 'Sarah');
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
    $passed++;

    // Do the Delete
    line_out("Looking for the form with a 'Delete' submit button");
    $form = $crawler->selectButton('Delete')->form();
    $crawler = $client->submit($form);
    $html = $crawler->html();
    $OUTPUT->togglePre("Show retrieved page",$html);
    $passed++;
    checkPostRedirect($client);
}

line_out("Testing for HTML injection (proper use of htmlentities)...");
line_out("Looking for Add New link.");
$link = $crawler->selectLink('Add New')->link();
$gradeurl = $link->getURI();
line_out("Retrieving ".htmlent_utf8($gradeurl)."...");

$crawler = $client->request('GET', $gradeurl);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;

line_out("Looking for the form with a 'Add New' submit button");
$form = $crawler->selectButton('Add New')->form();
$url = 'http://www.wa4e.com/x.php?>data='.sprintf("%03d",rand(1,100));
$email = "Sarah_is_so_>@wa4e.com";
$length = rand(1,100);
line_out("Entering url=$url, email=$email, length=$length");
$form->setValues(array("url" => $url, "email" => $email, "length" => $length, "rating" => "12345"));
$crawler = $client->submit($form);
$passed++;

$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
checkPostRedirect($client);

if ( strpos($html, "_>@php") > 0 ) {
    error_out("Found HTML Injection");
    throw new Exception("Found HTML Injection");
} else if ( strpos($html, "_&gt;@php") > 0 ) {
    $passed+=2;
    line_out("Passed HTML Injection test");
} else {
    error_out("Cannot find email address on page");
}

$pos = strpos($html,"Sarah");
$pos2 = strpos($html, "delete.php", $pos);
line_out("Looking for delete.php link associated with 'Sarah' entry");
$pos3 = strpos($html, '"', $pos2);
$editlink = substr($html,$pos2,$pos3-$pos2);
line_out("Retrieving ".htmlent_utf8($editlink)."...");

$crawler = $client->request('GET', $editlink);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;

if ( strpos($html, "x.php?>data") > 0 ) {
    error_out("Found HTML Injection");
    throw new Exception("Found HTML Injection");
} else if ( strpos($html, "x.php?&gt;data") > 0 ) {
    $passed+=2;
    line_out("Passed HTML Injection test");
} else {
    error_out("Cannot find email address on page");
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
    error_out("The autograder did not find something it was looking for in your HTML - test ended.");
    error_log($ex->getMessage());
    error_log($ex->getTraceAsString());
    $detail = "This indicates the source code line where the test stopped.\n" .
        "It may not make any sense without looking at the source code for the test.\n".
        'Caught exception: '.$ex->getMessage()."\n".$ex->getTraceAsString()."\n";
    $OUTPUT->togglePre("Internal error detail.",$detail);
}

$perfect = 28;
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

if ( $score > 0.0 ) webauto_test_passed($score, $gradeurl);

