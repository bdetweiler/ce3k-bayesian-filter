<?
/*******************************************************************************
 * File:    grepper.php                                                        *
 * Author:  Brian Detweiler                                                    *
 * Notes:   Provides a class for grepping out pieces of a cl post.             *
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

class grepper
{
    const HASPIC   = '/.*name=\"hasPic\" value=\"1\"*/';
    const FLAGGED  = '/.*flagged<\/a> for removal.*/';
    const DELETED  = '/.*deleted by.*/';
    const POST_ID  = '/.*\/([0-9]+?)\.html/';
    const MAILTO   = '/mailto:(.*?)\?/';
    const SUBJECT  = '/.*<h2>(.*?)<\/h2>/';
    const NOTFOUND = '/.*<h2>[ ]*Page[ ]*Not[ ]*Found[ ]*<\/h2>.*/';
    const LOCATION = '/Location: (.*?)<.*/';
    const USERBODY = '/userbody">(.*)PostingID:/';
    const POSTDATE = '/(20[0-9][0-9]-[0-9]?[0-9]-[0-9]?[0-9])/';
    const JUNK     = '/(.*?)<ul><li>.*/';
    const TAGS     = '/<.*?>/';
    const SPACES   = '/&nbsp;/';
    const AGE      = '/.*([1234][0-9]).*/';
    const LONGDATE = '/([0-9][0-9][0-9][0-9]-[0-9]?[0-9]-[0-9]?[0-9])/';

    function __construct()
    {;;;}

    static function flagged($text)
    {
        $rval = false;

        // Has this been flagged?
        $pattern = self::FLAGGED;
        preg_match($pattern, $text, $matches);

        if(count($matches))
            $rval = true;

        return $rval;
    }

    static function deletedByAuthor($text)
    {
        $rval = false;

        // Has this been flagged?
        $pattern = self::DELETED;
        preg_match($pattern, $text, $matches);

        if(count($matches))
            $rval = true;

        return $rval;
    }

    static function getUserBody($text)
    {
        $rval = "";
        $pattern = self::USERBODY;
        preg_match($pattern, $text, $matches);

        if(count($matches) == 2)
        {
            // Removes location and the "it's not okay to contact..." stuff
            $pattern = self::JUNK;
            preg_match($pattern, $matches[1], $newmatches);
          
            if(count($newmatches) == 2)
            {
                // Removes all tags
                $pattern = self::TAGS;
                $rval = preg_replace($pattern, "", $newmatches[1]);

                // Removes all spaces
                $pattern = self::SPACES;
                $rval = trim(preg_replace($pattern, " ", $rval));
            }
        }

        return $rval;
    }

    static function getAge($text)
    {
        $rval = "";

        $pattern = self::AGE;
        preg_match($pattern, $text, $matches);
      
        if(count($matches) == 2)
            $rval = $matches[1];
        else
            $rval = 0;
        return $rval;
    }

    static function getPostId($text)
    {
        $rval = "";

        // Grab the postid
        $pattern = self::POST_ID;
        preg_match($pattern, $text, $matches);

        if(count($matches) == 2)
            $rval = $matches[1];

        return $rval;
    }

    static function getCLEmail($text)
    {
        $rval = "";
        $pattern = self::MAILTO;
        preg_match($pattern, $text, $matches);


        /* Craigslist displays the email as a the Decimal HTML equivalent. 
         * So we just need to revert each one to standard ASCII
         */
        if(count($matches) == 2)
        {

            $pattern = "/#/";
            preg_match($pattern, $matches[1], $tmp);

            if(count($tmp))
            {
                $rval = "";
                $characters = explode("&#", trim($matches[1]));
                foreach($characters as $char)
                {
                    if(trim($char) != "")
                    {
                        $rmSemiColon = str_split($char);
                        array_pop($rmSemiColon);
                        $rval .= chr(implode($rmSemiColon));
                    }
                }
            }
            else
                $rval = $matches[1];
        }

        return $rval;
    }

    static function pageNotFound($text)
    {
        $rval = false;
        $pattern = self::NOTFOUND;
        preg_match($pattern, $text, $matches);

        if(count($matches))
            $rval = true;

        return $rval;
    }

    static function hasPic($text, $post_id)
    {
        
        $rval = false;

        $pattern = '/' . $post_id . '.html">.*?<\/a> <span class="p"> pic/';


        preg_match($pattern, $text, $matches);

        $pattern = '/' . $post_id . '.html">.*?<\/font> <span class="p"> pic/';


        if(count($matches))
            $rval = true;

        return $rval;
    }

    static function getSubject($text)
    {
        $rval = "";
        $pattern = self::SUBJECT;
        preg_match($pattern, $text, $matches);

        if(count($matches) == 2)
            $rval = trim($matches[1]);

        return $rval;
    }

    static function getLocation($text)
    {
        $rval = "";
        $pattern = self::LOCATION; 
        preg_match($pattern, $text, $matches);

        if(count($matches) == 2)
            $rval = trim($matches[1]);

        return $rval;
    }

    static function getPostDate($text)
    {
        $rval = "";

        // Grab the post date
        //const POSTDATE = '/Date: (.*?)<.*/';
        $pattern = self::POSTDATE;
        preg_match($pattern, $text, $matches);

        if(count($matches) == 2)
            $rval = $matches[1];
        else
            $rval = "";

        /*
        $pattern = self::LONGDATE;
        preg_match($pattern, $postDateTime, $postDate);
        if(count($postDate))
            $postDate = $postDate[1];
        else
            $postDate = "";
        */

        return $rval;
    }

    static function getTime($text)
    {
        $pattern = '/([ 0-9]?[0-9]:[0-9]?[0-9][ ]*[PpAa][Mm])/';
        preg_match($pattern, $text, $postTime);
        if(count($postTime))
            $postTime = trim($postTime[1]);
        else
            $postTime = "";

        $postTime = str_split($postTime);


        // If it's PM, do 24-hour conversion
        if($postTime[count($postTime) - 2] == "P")
        {
            // Single digit
            if($postTime[1] == ":")
                $convertMe = array_shift($postTime);
            else
            {
                $convertMe = array_shift($postTime);
                $convertMe .= array_shift($postTime);
            }

            if($convertMe == 1)
                $convertMe = 13;
            else if($convertMe == 2)
                $convertMe = 14;
            else if($convertMe == 3)
                $convertMe = 15;
            else if($convertMe == 4)
                $convertMe = 16;
            else if($convertMe == 5)
                $convertMe = 17;
            else if($convertMe == 6)
                $convertMe = 18;
            else if($convertMe == 7)
                $convertMe = 19;
            else if($convertMe == 8)
                $convertMe = 20;
            else if($convertMe == 9)
                $convertMe = 21;
            else if($convertMe == 10)
                $convertMe = 22;
            else if($convertMe == 11)
                $convertMe = 23;
        }
        else
        {
            // Single digit
            if($postTime[1] == ":")
                $convertMe = array_shift($postTime);
            else
            {
                $convertMe = array_shift($postTime);
                $convertMe .= array_shift($postTime);
            }

            if($convertMe == 12)
                $convertMe == "00";
        }

        if(strlen($convertMe) == 1)
            $convertMe = "0" . $convertMe;

        // Get rid of the AM/PM
        array_pop($postTime);
        array_pop($postTime);

        // Push "seconds"
        array_push($postTime, ":");
        array_push($postTime, 0);
        array_push($postTime, 0);

        array_unshift($postTime, $convertMe);

        $postTime = implode($postTime);

        return $postTime;

    }

    static function getDateTimeStamp($text)
    {
        $dateTimeStamp = getDate($text) . " " . getTime($postTime);
        return $dateTimeStamp;
    }
}
?>
