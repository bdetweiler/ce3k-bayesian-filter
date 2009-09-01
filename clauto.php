<?php
/*******************************************************************************
 * File:    clauto.php                                                         *
 * Author:  Brian Detweiler                                                    *
 * Notes:   This script was designed to scrape Craigslist Casual Encounters,   *
 *          and log the information into a database. It should be cron'd to be *
 *          run every 5 minutes or so.                                         *
 *                                                                             *
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
 *******************************************************************************/

require_once 'curl.php';
require_once 'SlutMail.php';
require_once 'TrimUrl.php';
require_once 'owner.php';
require_once 'grepper.php';

// One of these days, I'll decide on upper or lower case.
$curl = new Curl();
$owner = new owner();
$grepper = new grepper();

$cityUrl = "http://omaha.craigslist.org";
$search = "/search/cas/?query=w4m";

// Grab the main page
// We'll do this roughly every 10 minutes
$response = $curl->get($cityUrl . $searchTerm);

// Explode on <p> tags and iterate through each line
$mainbody = explode("<p>", $response->body);
$links = array();
foreach($mainbody as $line)
{
    $pattern = '/(\/cas\/[0-9]*?\.html)"/';
    preg_match($pattern, $line, $matches);
    if(count($matches) == 2)
    {
        $pattern = '/query/';
        preg_match($pattern, $line, $newmatches);

        if(count($newmatches) == 0)
            array_push($links, $cityUrl . $matches[1]);
    }
}

$mainbody = implode($mainbody);

/* At this point, $links contains the current RSS urls.
 * Cycle through them and if there's no record in the database, add it.
 * If there is, check to see if it's been flagged yet.
 * If it has, make note of that.
 */
foreach($links as $link)
{
    print("$link<br />\n");
    $pattern = '/\/cas\/([0-9]*?)\.html/';
    $matches = null;
    preg_match($pattern, $link, $matches);
    if(count($matches) == 2)
    {
        $postid = $matches[1];

        $hasPic = $grepper->hasPic($mainbody, $postid);

        $result = $owner->getCLPosting($postid);
        // If it's already in the database
        if(pg_num_rows($result))
        {
            // If it was NOT flagged
            if(!$owner->getCLPostingFlagged($postid))
            {
                // Go get the link again
                $response = $curl->get($link);

                // If it WAS flagged this time, log it
                if($grepper->flagged($response))
                {
                    print("flagging!\n");
                    $owner->flag($postid);
                }
            }

        }
        // It is NOT in the database
        else
        {
            // Go get the link
            $response = $curl->get($link);

            // To grep through this, it needs to be a flat string. Explode it on 
            // newlines, which will get rid of the new lines, then implode it
            $body = explode("\n", $response->body);
            $body = implode($body);

            // Sometimes the page cannot be found. Ignore it and continue on.
            if($grepper->pageNotFound($response))
            {
                print("\n<br /><font color=\"#ff0000\"><b>Page Not Found</b></font>\n<br />");
                continue;
            }

            // Grab the postid
            $postid = $grepper->getPostId($link);

            // If it's been flagged, log it, and quit.
            if($grepper->flagged($body))
            {
                // $owner->insCLPost_flagged($postid);

                print("\n<br /><font color=\"#ff0000\"><b>This entry has been flagged.</b></font>\n<br />");
                continue;
            }

            // If it's been flagged, log it, and quit.
            if($grepper->deletedByAuthor($body))
            {
                // $owner->insCLPost_flagged($postid);

                print("\n<br />This entry has been deleted by its author.\n<br />");
                continue;
            }

            // Pull out information from the posting
            $email = $grepper->getCLEmail($body);
            $subject = $grepper->getSubject($body);
            $location = $grepper->getLocation($body);
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

            $slashsubject = addslashes($subject);
            $slashbody = addslashes($userBody);

            $tmpBody = "";

            // Remove unicode bullshit (the database doesn't like unicode)
            for($i = 0; $i < strlen($slashbody); ++$i)
                if(ord(substr($slashbody, $i, 1)) != 0 &&
                   ord(substr($slashbody, $i, 1)) < 128)
                    $tmpBody .= substr($slashbody, $i, 1);

            $slashbody = $tmpBody;

            $owner->insCLPost($postid,
                              $slashsubject,
                              $slashbody,
                              $email,
                              $postDate,
                              $postTime,
                              $hasPic);


            // If we want to slow down the site crawling, we can set this
            // though, it doesn't seem to be a problem at the moment.
            // $sleepytime = 60 - (rand() % 30);
            // sleep($sleepytime);
        }
        
    }
}

?>
