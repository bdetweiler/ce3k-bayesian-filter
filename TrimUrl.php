<?
/*******************************************************************************
 * File:    TrimUrl.php                                                        *
 * Author:  Brian Detweiler                                                    *
 * Notes:   In the initial stages of this project, it was determined that to   *
 *          get an accurate measurement of "spam", we would need to actually   *
 *          see if the people behind the posts were real. Adding in tr.im      *
 *          URLs would allow us to see if a human was clicking the links.      *
 *          Due to some roadblocks described in ce3k.txt, this was scrapped,   *
 *          but the code remains for future use in this, or other projects.    *
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

class TrimUrl
{

    const TRIM_URL = "";
    const VERSION = "";
    const API_KEY = "";
    const USERNAME = "";


    private $destinationUrl = "";
    private $trimUrl        = "";

    function __construct($dest = "")
    {
        $this->destinationUrl = $dest;
    }

    function setDestination($dest)
    {
        $this->destinationUrl = trim($dest);
    }

    function getTrimUrl($dest = "")
    {
        $rval = "";
        
        // there is already a trimmed URL, return that
        if(strlen($this->trimUrl))
            $rval = $this->trimUrl;
        // Else, if they provided a destination, set it and get that
        else if(strlen($dest))
        {
            $this->setDestination($dest);
            $rval = $this->trimIt();
        }
        // Else, if there's already a destination set, tr.im that
        else if(strlen($this->destinationUrl))
            $rval = $this->trimIt();
        // Else... WTF am I supposed to do???!!!
        else
            die("Error: No URL provided to tr.im");

        return $rval;

    }

    function trimIt()
    {
        $trimGetUrl = self::TRIM_URL
                    . "?version="
                    . self::VERSION
                    . "&longUrl="
                    . $this->destinationUrl
                    . "&login="
                    . self::USERNAME
                    . "&apiKey="
                    . self::API_KEY;
        $curl = new Curl();
        $this->trimUrl = trim($curl->get($trimGetUrl));
        
        // bit.ly returns JSON. Extract the link
        $pattern = '/shortUrl": "(.*?)"/';
        preg_match($pattern, $this->trimUrl, $matches);

        if(count($matches) == 2)
            $this->trimUrl = $matches[1];

        return $this->trimUrl;
    }
}
?>
