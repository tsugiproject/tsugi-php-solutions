<?php

require_once "../../config.php";
require_once "webauto.php";
use Goutte\Client;

line_out("Grading PHP-Intro Autos Application");

$url = getUrl('http://www.wa4e.com/exam/mid-f14-autos');
if ( $url === false ) return;
$grade = 0;

error_log("Contacts ".$url);
line_out("Retrieving ".htmlent_utf8($url)."...");
flush();

$client = new Client();

$crawler = $client->request('GET', $url);

// Yes, one gigantic unindented try/catch block
$passed = 0;
$titlefound = true;
try {

$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);

$retval = webauto_check_title($crawler);
if ( $retval !== true ) {
    error_out($retval);
    $titlefound = false;
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
line_out("-- this autograder expects the form fields to be:");
line_out("-- make, model, year, and miles");
line_out("-- if your fields do not match these, the next tests will fail.");
line_out(" ");
line_out("Submitting empty form, leaving year and miles blank.");
$form->setValues(array("make" => "Ford", "model" => "Anytown", "year" => "", "miles" => "", "price" => 10000));
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
$make = 'Ford'.sprintf("%03d",rand(1,100));
$model = rand(1,100);
$year = rand(1,100);
line_out("Entering make=$make, model=$model, year=$year, price=12500");
$form->setValues(array("make" => $make, "model" => $model, "year" => $year, "miles" => "12345", "price" => 12500 ) );
$crawler = $client->submit($form);
$passed++;

$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
checkPostRedirect($client);

line_out("Looking '$make' entry");
$pos = strpos($html, $make);
$pos2 = strpos($html, "edit.php", $pos);
$body = substr($html,$pos,$pos2-$pos);
# echo "body=",htmlentities($body);
line_out("Looking for make=$make and year=$year");
if ( strpos($body,''.$make) === false || strpos($body,''.$year) === false ) {
    error_out("Could not find make=$make and year=$year");
} else {
    $passed++;
}

line_out("Looking for edit.php link associated with '$make' entry");
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
$model = rand(1,100);
$year = rand(1,100);
line_out("Editing make=$make, model=$model, year=$year, price=57123");
$form->setValues(array("make" => $make, "model" => $model, "year" => $year, "miles" => "12345", "price" => "57123"));
$crawler = $client->submit($form);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;
checkPostRedirect($client);

// Delete...
line_out("Looking '$make' entry");
$pos = strpos($html, $make);
$pos2 = strpos($html, "delete.php", $pos);
$body = substr($html,$pos,$pos2-$pos);
# echo "body=",htmlentities($body);
line_out("Looking for make=$make and year=$year");
if ( strpos($body,''.$make) === false || strpos($body,''.$year) === false ) {
    error_out("Could not find make=$make and year=$year");
} else {
    $passed++;
}

line_out("Looking for delete.php link associated with '$make' entry");
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

line_out("Making sure '$make' has been deleted");
if ( strpos($html,$make) > 0 ) {
    error_out("Entry '$make' not deleted");
} else {
    $passed++;
}

line_out("Cleaning up old records...");
while (True ) {
    $pos2 = strpos($html, "delete.php");
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
    $passed--; // Undo the extra pass
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
$make = '<b>Ford</b>'.sprintf("%03d",rand(1,100));
$model = rand(1,100);
$year = rand(1,100);
line_out("Entering make=$make, model=$model, year=$year, price=1000");
$form->setValues(array("make" => $make, "model" => $model, "year" => $year, "miles" => "12345", "price" => "1000"));
$crawler = $client->submit($form);
$passed++;

$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
checkPostRedirect($client);

if ( strpos($html, ">Ford") > 0 ) {
    error_out("Found HTML Injection");
} else if ( strpos($html, "&gt;Ford") > 0 ) {
    $passed+=2;
    line_out("Passed HTML Injection test");
} else {
    error_out("Cannot find make on page");
}

$pos = strpos($html,"Ford");
$pos2 = strpos($html, "delete.php", $pos);
line_out("Looking for delete.php link associated with 'Ford' entry");
$pos3 = strpos($html, '"', $pos2);
$editlink = substr($html,$pos2,$pos3-$pos2);
line_out("Retrieving ".htmlent_utf8($editlink)."...");

$crawler = $client->request('GET', $editlink);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;

if ( strpos($html, ">Ford") > 0 ) {
    error_out("Found HTML Injection");
} else if ( strpos($html, "&gt;Ford") > 0 ) {
    $passed+=2;
    line_out("Passed HTML Injection test");
} else {
    error_out("Cannot find make on page");
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
        "It may not make any sense without looking at the source code for the test.\n".
        'Caught exception: '.$ex->getMessage()."\n".$ex->getTraceAsString()."\n";
    $OUTPUT->togglePre("Internal error detail.",$detail);
    ob_start();
    var_dump($crawler);
    $result = ob_get_clean();
    $OUTPUT->togglePre("Even more detail.",$result);
}

// There is a maximum of 28 passes for this test
$perfect = 26;
$score = webauto_compute_effective_score($perfect, $passed, $penalty);

if ( ! $titlefound ) {
    error_out("These pages do not have proper titles so this grade is not official");
    return;
}

if ( $score > 0.0 ) webauto_test_passed($score, $url);
