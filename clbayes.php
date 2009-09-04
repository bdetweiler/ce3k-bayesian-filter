<?php

/*******************************************************************************
 * File:    clbayes.php                                                        *
 * Author:  Brian Detweiler                                                    *
 * Notes:   Perform a Bayesian analysis on the current postings on CL CAS      *
 *          based on the current training corpuses in the database. This was   *
 *          mostly taken from http://www.shiffman.net/teaching/a2z/bayesian/   *
 *          and from Paul Graham's essay, "A Plan For Spam."                   *
 *                                                                             *
 * Scanning rules:                                                             *
 *      * alphanumeric, dashes, apostrophes, dollar signs are part of a token  *
 *      * everything else is a separator                                       *
 *      * Case is preserved                                                    *
 *      * Exclamation points are constituent characters (counted as part of    *
 *        the word)                                                            *
 *      * Periods and commas are constituents if they appear as part of the    *
 *        word. (This was not taken into account)                              *
 *      * A price range like $20-25 yields $20 and $25. (This was not done)    *
 *      * Subject characters are marked accordingly (Subject*foo)              *
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

require_once 'owner.php';
require_once 'tokenizer.php';
require_once 'curl.php';
require_once 'grepper.php';
require_once 'spyc.php';

$curl = new Curl();
$owner = new owner();
$grepper = new grepper();
$tokenizer = new Tokenizer();
$spyc = new Spyc();

$config = $spyc->YAMLLoad('../ce3k.yaml');

$memoryLimit = $config['memory-limit'];

// Depending on your corpus size, the default memory will need to be set to
// something fairly large, as the hashtable takes up a good deal of memory.
ini_set("memory_limit", $memoryLimit);


// This is the amount of spam tolerance we are willing to accomodate for.
$threshold = $config['bayes']['threshold'];

$cityUrl = $config['city-url'];
$searchTerm = $config['search-term'];

// INITIALIZE THE TABLE

// Pull everything out of the database
$allPostings = $owner->getAllCLPostings();
$postingArr = pg_fetch_all($allPostings);
$len = count($postingArr);
$userAgentLen = strlen(trim($_SERVER['HTTP_USER_AGENT']));

for($i = 0; $i < $len; ++$i)
{
    // Print out the percentage done
    if(!$userAgentLen)
    {
        $percentLeft = round(100 * ($i / $len)) . "%";
        print($percentLeft);
    }

    // Tokenize the subject and the body of each post in the database
    $tokenizer->tokenize($postingArr[$i]['subject'],
                         $postingArr[$i]['flagged'],
                         true);
    $tokenizer->tokenize($postingArr[$i]['body'],
                         $postingArr[$i]['flagged'],
                         false);

    // Use the backspace trick to show a running percentage
    if(!$userAgentLen)
    {
        for($j = 0; $j < strlen($percentLeft); ++$j)
            print(chr(8));
    }
}

// Finalize the probabilities of each word
$tokenizer->finalize();


// GET CURRENT POSTINGS

// Grab the main page
// We'll do this roughly every 10 minutes
$response = $curl->get($cityUrl . $searchTerm);


// Explode on <p> tags and iterate through each line
$mainbody = explode("<p>", $response->body);
$links = array();
foreach($mainbody as $line)
{
    $pattern = '/(\/cas\/[0-9]*\.html)/';
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

$hamCount = 0;
/* At this point, $links contains the current RSS urls.
 */
foreach($links as $link)
{
    $pattern = '/\/cas\/([0-9]*)\.html/';
    $matches = null;
    preg_match($pattern, $link, $matches);

    if(count($matches) == 2)
    {

        $postid = $matches[1];

        // Go get the link
        $response = $curl->get($link);

        // To grep through this, it needs to be a flat string. Explode it on 
        // newlines, which will get rid of the new lines, then implode it
        $body = explode("\n", $response->body);
        $body = implode($body);

        // Sometimes the page cannot be found. Ignore it and continue on.
        if($grepper->pageNotFound($response))
            continue;

        // If it's been flagged, move on
        if($grepper->flagged($body))
            continue;

        // If it's been deleted, move on
        if($grepper->deletedByAuthor($body))
            continue;

        // Grab the posting information
        $subject = $grepper->getSubject($body);
        $location = $grepper->getLocation($body);
        $userBody = $grepper->getUserBody($body);
        $postDate = $grepper->getPostDate($body);
        $postTime = $grepper->getTime($body);
        $slashsubject = addslashes($subject);
        $slashbody = addslashes($userBody);

        $tmpBody = "";

        // Remove unicode bullshit
        for($i = 0; $i < strlen($slashbody); ++$i)
            if(ord(substr($slashbody, $i, 1)) != 0 &&
               ord(substr($slashbody, $i, 1)) < 128)
                $tmpBody .= substr($slashbody, $i, 1);

        $slashbody = $tmpBody;


        // ANALYZE POST
        $interesting = $tokenizer->analyzePost($slashsubject, $slashbody);

        // Apply Bayes' rule (via Graham)
        $pposproduct = 1.0;
        $pnegproduct = 1.0;

        // For every word, multiply Spam probabilities ("Pspam") together
        // (As well as 1 - Pspam)
        for($i = 0; $i < count($interesting); ++$i)
        {
            $w = $interesting[$i];
            $pposproduct *= $w->getPSpam();
            $pnegproduct *= (1.0 - $w->getPSpam());
        }

        // Apply formula
        $pSpam = $pposproduct / ($pposproduct + $pnegproduct);


        // If we're within our threshold, print the link and the probability
        if($pSpam < $threshold)
        {
            print("<a href=\"$link\">$slashsubject</a>  -- " 
                 . round($pSpam * 100) 
                 . "% SPAM<br />\n");
            ++$hamCount;
        }
    }
}

if(!$hamCount)
    print("Sorry, there were no valid postings. Try again later.\n");

?>
