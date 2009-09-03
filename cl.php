<?php

/*******************************************************************************
 * File:    cl.php                                                             *
 * Author:  Brian Detweiler                                                    *
 * Notes:   Given a Craigslist Casual Encounters link, send out an automated   *
 *          email with two trackable tr.im links, and store all interesting    *
 *          information in a database.                                         *
 *                                                                             *
 * Copyright (c) 2009, Brian Detweiler                                         *
 * All rights reserved.                                                        *
 * Redistribution and use in source and binary forms, with or without          *
 * modification, are permitted provided that the following conditions are met: *
 *                                                                             *
 *  * Redistributions of source code must retain the above copyright notice,   *
 *    this list of conditions and the following disclaimer.                    *
 *  * Redistributions in binary form must reproduce the above copyright        *
 *    notice, this list of conditions and the following disclaimer in the      *
 *    documentation and/or other materials provided with the distribution.     *
 *  * Neither the name of (1, 1) Productions nor the names of its contributors *
 *    may be used to endorse or promote products derived from this software    *
 *    without specific prior written permission.                               *
 *                                                                             *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" *
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE   *
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE  *
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE   *
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR         *
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF        *
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS    *
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN     *
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)     *
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE  *
 * POSSIBILITY OF SUCH DAMAGE.                                                 *
 ******************************************************************************/
require_once 'curl.php';
require_once 'SlutMail.php';
require_once 'TrimUrl.php';
require_once 'owner.php';
require_once 'grepper.php';
// require_once '';
$owner = new owner();

// Grepper helps parse out pieces of information from the Craigslist URL
$grepper = new grepper();

// Curl retrieves URLs
$curl = new Curl();


$cityUrl = "http://omaha.craigslist.org";
$searchTerm = "/search/cas/?query=w4m";

// Fill this in with links to Facebook pics
$facebookPic1 = "";
$facebookPic2 = "";

$myReply = "";

?>

<html>
<body>

<form method="POST" action="cl.php">
    Craigslist link:
    <br />
    <input type="text" name="http" id="http" />
    <br />
    <input type="submit" name="submit" id="submit" value="Submit" />
    Message:
    <br />

    <textarea name="message" 
              id="message" 
              style="height: 400px;"><? print($myReply); ?></textarea>
    <br />
</form>

<a href="clreports.php">CL Reports</a><br />
<a href="clauto.php">CL Auto</a><br />

<?php

if(isset($_POST["http"]))
{
    // The Craigslist link we want to retrieve
    $cl_link = trim($_POST["http"]);
    // The message to send to the recipient
    $message = trim($_POST["message"]);


    // First thing's first, get the Craigslist link
    $response = $curl->get($cl_link);

    // To grep through this, it needs to be a flat string. Explode it on 
    // newlines, which will get rid of the new lines, then implode it
    $body = explode("\n", $response->body);
    $body = implode($body);


    // Grab the postid
    $postid = $grepper->getPostId($cl_link);

    // If it's been flagged, log it, and quit.
    if($grepper->flagged($body))
    {
        $owner->insCLPost_flagged($postid);

        die("\n<br />This entry has been flagged.\n<br />");
    }

    
    // The two tr.im urls for the pictures
    $trimUrl1 = new TrimUrl();
    $trimUrl2 = new TrimUrl();

    // Populate the direct pic link with a random URL Parameter to make the 
    // tr.im links unique
    // if($owner->apiLimitReached())
        // die("Error: API Limit Reached. Try again later.");

    $trimLink1 = $trimUrl1->setDestination($facebookPic1 . "?" . rand());
    $trimLink2 = $trimUrl2->setDestination($facebookPic2 . "?" . rand());

    $trimLink1 = $trimUrl1->trimIt(true);
    $trimLink2 = $trimUrl2->trimIt(true);

    if($trimLink1 == "")
        die("Trim link for $facebookPic1 failed.");

    if($trimLink2 == "")
        die("Trim link for $facebookPic2 failed.");

    // Get the email
    $email = $grepper->getCLEmail($body);

    // Grab the subject
    $subject = $grepper->getSubject($body);

    // Grab the location (if there is one)
    $location = $grepper->getLocation($body);

    // Grab the entire userbody
    $userBody = $grepper->getUserBody($body);

    $postDate = $grepper->getPostDate($body);
    $postTime = $grepper->getTime($body);

    // Print out the grepped information so we can see it in our brower.
    print("<hr />\n");
    print("Postid: $postid\n");
    print("<br />\n");
    print("Subject: $subject\n");
    print("<br />\n");
    print("Location: $location\n");
    print("<br />\n");
    print("Age: $age\n");
    print("<br />\n");
    print("Email: $email\n");
    print("<br />\n");
    print("Body: $userBody\n");
    print("<br />\n");
    print("postDate: $postDate\n");
    print("<br />\n");
    print("postTime: $postTime\n");
    print("<br />\n");
    print("<hr />\n");

    // This actually sends an email through a gmail account
    $slutMail = new SlutMail();
    $slutMail->setSubject("re: " . $subject);
    $slutMail->setTrimLinks($trimLink1, $trimLink2);
    $slutMail->setBody(stripslashes($message));

    $slashsubject = addslashes($subject);
    $slashbody = addslashes($userBody);

    // Grab the main page
    // We'll do this roughly every 10 minutes
    $response = $curl->get($cityUrl . $searchTerm);

    // Explode on <p> tags and iterate through each line
    $mainbody = explode("<p>", $response->body);
    $mainbody = implode($mainbody);
    $mainbody = explode("\n", $response->body);
    $mainbody = implode($mainbody);

    $hasPic = $grepper->hasPic($mainbody, $postid);

    if($hasPic)
        $hasPic = 't';
    else
        $hasPic = 'f';

    $result = $owner->getCLPosting($postid);
    
    if(!pg_num_rows($result))
    {
        $owner->insCLPost($postid,
                          $slashsubject,
                          $slashbody,
                          $email,
                          $postDate,
                          $postTime,
                          $hasPic);
    }

    $owner->insTrimUrl($postid, $trimLink1);
    $owner->insTrimUrl($postid, $trimLink2);
    
    $mySubject = addslashes("re: " . $subject);
    $myBody = addslashes($slutMail->getBody());

    $now = date('Y-m-d');
    $owner->insMyReply($postid, 
                       $now, 
                       $mySubject, 
                       $myBody);



    $slutMail->sendMail($email);

    print("\n\n<br /><br />");
    print($slutMail->getBody());
    echo "\n<br />Message has been sent\n<br />";
}
?>
