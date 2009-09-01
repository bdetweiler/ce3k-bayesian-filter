<?
/*******************************************************************************
 * File:    tokenizer.php                                                      *
 * Author:  Brian Detweiler                                                    *
 * Notes:   Tokenizes both the database and new emails to be analyzed. Stores  *
 *          tokens in a hash table and performs bayesian classification on it. *
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

require_once('clword.php');

class Tokenizer
{

    const TOKENS = "\r\n\t/,;-* ";
    const LIMIT = 15;

    private $hash  = Array();
    private $totalBadWords = 0;
    private $totalGoodWords = 0;

    public function __construct()
    {;;;}

    public function __destruct()
    {;;;}



    public function tokenize($text, $isSpam, $isSubject)
    {
        $tok = strtok($text, self::TOKENS);

        while($tok !== false)
        {
            // echo "Word = $tok\n";
            $tok = strtok(self::TOKENS);


            if(!$tok)
                continue;

            $catTok = $tok;
            if($isSubject)
                $catTok = "Subject*" . $tok;

            if(array_key_exists($catTok, $this->hash))
            {
                if($isSpam == "t")
                {
                    ++$this->totalBadWords;
                    $this->hash[$catTok]->countBad();
                }
                else
                {
                    ++$this->totalGoodWords;
                    $this->hash[$catTok]->countGood();
                }
            }
            else
            {
                if($isSpam == "t")
                {
                    ++$this->totalBadWords;
                    $w = new Word($catTok);
                    $w->countBad();
                    $this->hash[$catTok] = $w;
                }
                else
                {
                    ++$this->totalGoodWords;
                    $w = new Word($catTok);
                    $w->countGood();
                    $this->hash[$catTok] = $w;
                }
            }
        }
    }

    public function getHash()
    {
        return $this->hash;
    }

    public function finalize()
    {
        foreach($this->hash as $i=>$val)
        {
            $this->hash[$i]->finalizeProb($this->totalBadWords, $this->totalGoodWords);
        }
    }

    private function sortInteresting($interesting)
    {
        $n = count($interesting);

        do
        {
            $swapped = false;
            $n = $n - 1;
            for($i = 0; $i < $n; ++$i)
            {
                if($interesting[$i]->getPSpam() < $interesting[($i + 1)]->getPSpam())
                {
                    $temp = $interesting[$i];
                    $interesting[$i] = $interesting[$i + 1];
                    $interesting[$i + 1] = $temp;
                    $swapped = true;
                }

            }
        }while($swapped);

        return $interesting;
    }

    public function analyzePost($subject, $body)
    {
        // Create an arraylist of 15 most "interesting" words
        // Words are most interesting based on how different their Spam probability is from 0.5
        $interesting = array();


        $isSubject = true;
        $text = $subject;

        for($k = 0; $k < 2; ++$k)
        {
            if($k == 1)
            {
                $isSubject = false;
                $text = $body;
            }

            $tok = strtok($text, self::TOKENS);

            while($tok !== false)
            {
                // echo "Word = $tok\n";
                $tok = strtok(self::TOKENS);


                if(!$tok)
                    continue;

                $catTok = $tok;
                if($isSubject)
                    $catTok = "Subject*" . $tok;

                if(array_key_exists($catTok, $this->hash))
                {
                    $w = $this->hash[$catTok];
                }
                else
                {
                    $w = new Word($catTok);
                    $w->setPSpam(0.40);
                    // $w->countBad();
                    // $this->hash[$catTok] = $w;
                }

                if(!count($interesting))
                    array_push($interesting, $w);
                else
                {
                    for($i = 0; $i < count($interesting); ++$i)
                    {
                        // For every word in the list already
                        $nw = $interesting[$i];

                        // If it's the same word, don't bother
                        if($w->getWord() == $nw->getWord())
                        {
                            break;
                        }
                        // If it's more interesting stick it in the list
                        else if($w->interesting() > $nw->interesting())
                        {
                            array_push($interesting, $w);
                            break;
                            // If we get to the end, just tack it on there
                        }
                        else if ($i == count($interesting) - 1)
                        {
                            array_push($interesting, $w);
                        }
                    }
                }
            }
        }

        $interesting = $this->sortInteresting($interesting);

        // If the list is bigger than the limit, delete entries
        // at the end (the more "interesting" ones are at the
        // start of the list
        while(count($interesting) > self::LIMIT)
            array_pop($interesting);

        return $interesting;
  }


}
?>
