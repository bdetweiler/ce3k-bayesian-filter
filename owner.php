<?
/*******************************************************************************
 * File:    owner.php                                                          *
 * Author:  Brian Detweiler                                                    *
 * Notes:   Provides a central location for all database calls.                *
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

class owner
{
    const API_LIMIT = 99;

    // database access parameters
    // alter this as per your configuration
    private $host = "127.0.0.1";
    private $user = "";
    private $pass = "";
    private $db   = "craigslist";
    private $connection = null;

    public function __construct()
    {
        // open a connection to the database server
        $this->connection = pg_connect("host=$this->host dbname=$this->db user=$this->user password=$this->pass");
        if(!$this->connection)
        {
            die("Could not open connection to database server");
        }

    }

    /*
    $query = "SELECT * FROM bot_report";
    $result = pg_query($this->connection, $query) 
        or die("Error in query: $query. " . pg_last_error($this->connection));

    // get the number of rows in the resultset
    $rows = pg_num_rows($result);
    */
    public function __destruct()
    {
        if($this->connection)
            pg_close($this->connection);
    }

    public function insCLPost_flagged($postid)
    {
        $query = "INSERT INTO cl_posting (post_id,
                                          flagged)
                  VALUES                 ($postid,
                                          'true');";

        pg_query($this->connection, $query) 
            or die("Error in query: $query. " . pg_last_error($this->connection));

    }

    public function insCLPost($postid, 
                              $slashsubject, 
                              $slashbody,
                              $email,
                              $postDate,
                              $postTime,
                              $hasPic)
    {
        $query = "INSERT INTO
                  cl_posting
                  VALUES ($postid,
                          '$slashsubject',
                          '$slashbody',
                          '$email',
                          '$postDate',
                          '$postTime',
                          'false',
                          '$hasPic');";
        pg_query($this->connection, $query) 
            or die("Error in query: $query. " . pg_last_error($this->connection));

    }

    public function insTrimUrl($postid, $trimLink)
    {

        $query = "INSERT INTO
                  trim_urls (post_id,
                             trim_url,
                             clicked,
                             click_count)
                  VALUES ($postid,
                          '$trimLink',
                          'false',
                          0);";
        pg_query($this->connection, $query) 
            or die("Error in query: $query. " . pg_last_error($this->connection));
    }

    public function insMyReply($postid,
                        $now,
                        $mySubject,
                        $myBody)
    {

        $query = "INSERT INTO
                  my_reply
                  VALUES ($postid,
                          '$now',
                          '$mySubject',
                          '$myBody')";
        pg_query($this->connection, $query) 
            or die("Error in query: $query. " . pg_last_error($this->connection));
    }

    public function insGirlInitial($postid, $location, $age)
    {
        $query = "INSERT INTO
                  girl
                  VALUES ($postid,
                          '',
                          '',
                          '',
                          '',
                          '',
                          '$location',
                          '',
                          $age)";
        pg_query($this->connection, $query) 
            or die("Error in query: $query. " . pg_last_error($this->connection));
    }

    public function flag($postid)
    {
        $query = "UPDATE cl_posting 
                  SET    flagged = 't'
                  WHERE post_id = $postid";
        pg_query($this->connection, $query) 
            or die("Error in query: $query. " . pg_last_error($this->connection));
    }

    public function getAllCLPostings()
    {
        $query = "SELECT *
                  FROM   cl_posting";
        $result = pg_query($this->connection, $query) 
            or die("Error in query: $query. " . pg_last_error($this->connection));

        return $result;
    }

    public function getCLPosting($postid)
    {
        $query = "SELECT *
                  FROM   cl_posting
                  WHERE  post_id = $postid";
        $result = pg_query($this->connection, $query) 
            or die("Error in query: $query. " . pg_last_error($this->connection));

        return $result;
    }

    public function getCLPostingsWithPic()
    {
        $query = "SELECT *
                  FROM   cl_posting
                  WHERE  haspic = 't'";
        $result = pg_query($this->connection, $query) 
            or die("Error in query: $query. " . pg_last_error($this->connection));

        return $result;
    }

    public function getCLPostingsWithPicAndFlagged()
    {
        $query = "SELECT *
                  FROM   cl_posting
                  WHERE  haspic = 't'
                  AND    flagged = 't'";
        $result = pg_query($this->connection, $query) 
            or die("Error in query: $query. " . pg_last_error($this->connection));

        return $result;
    }

    // @return boolean TRUE if posting was flagged
    public function getCLPostingFlagged($postid)
    {
        $rval = false;
        $query = "SELECT *
                  FROM   cl_posting
                  WHERE  post_id = $postid
                  AND    flagged = 't'";
        $result = pg_query($this->connection, $query) 
            or die("Error in query: $query. " . pg_last_error($this->connection));
       
        if(pg_num_rows($result))
            $rval = true;

        return $rval;
    }

    // @return int The number of flagged postings in the database
    public function getTotalFlaggedPostings()
    {
        $query = "SELECT *
                  FROM   cl_posting
                  WHERE  flagged = 't'";
        $result = pg_query($this->connection, $query) 
            or die("Error in query: $query. " . pg_last_error($this->connection));
       
        return(pg_num_rows($result));
    }

    // @return int The number of postings in the database
    public function getTotalPostings()
    {
        $query = "SELECT *
                  FROM   cl_posting";
        $result = pg_query($this->connection, $query) 
            or die("Error in query: $query. " . pg_last_error($this->connection));
       
        return(pg_num_rows($result));
    }

    public function apiLimitReached()
    {
        $rval = true;
        
        $last24Hours = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

        $query = "SELECT          cl_posting.post_id,
                                  cl_posting.postdate,
                                  cl_posting.time,
                                  trim_urls.trim_url
                  FROM            cl_posting
                  LEFT OUTER JOIN trim_urls
                  ON              cl_posting.post_id = trim_urls.post_id
                  WHERE           cl_posting.postdate
                  BETWEEN         '$last24Hours'
                  AND             NOW()
                  ORDER BY        cl_posting.postdate,
                                  cl_posting.time
                  DESC;";
        $result = pg_query($this->connection, $query) 
            or die("Error in query: $query. " . pg_last_error($this->connection));

        $count = pg_num_rows($result);

        // If it's less than API_LIMIT, we haven't hit our limit yet.
        if($count < self::API_LIMIT)
            $rval = false;

        return $rval;
    }

    public static function main()
    {

        $owner = new owner();

        $text = "<a href=\"/cas/1185829187.html\"> What's for dessert? - w4m - 20 -</a> <span class=\"p\"> pic";
        $post_id = "1185829187";
        $rval = false;
        $pattern = $haspic;
        preg_match($pattern, $text, $matches);

        print("matches: " . count($matches) . "\n");



        $query = "SELECT          post_id
                  FROM            cl_posting";
        $result = pg_query($owner->connection, $query) 
            or die("Error in query: $query. " . pg_last_error($owner->connection));

        $count = pg_num_rows($result);


    }
}

if(realpath($argv[0]) == realpath(__FILE__))
{
    exit(owner::main(array_slice($argv, 1)));
}

?>
