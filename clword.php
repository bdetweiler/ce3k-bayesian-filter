<?
/*******************************************************************************
 * File:    cl.php                                                             *
 * Author:  Brian Detweiler                                                    *
 * Notes:   Describes a "Word" object in terms of a token we wish to examine.  *
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

class Word
{
    private $word;      // The String itself
    private $countBad;  // The total times it appears in "bad" messages
    private $countGood; // The total times it appears in "good" messages
    private $rBad;      // bad count / total bad words
    private $rGood;     // good count / total good words
    private $pSpam;     // probability this word is Spam


    public function __construct($word)
    {
        $this->word      = $word;
        $this->countBad  = 0;
        $this->countGood = 0;
        $this->rBad      = 0;
        $this->rGood     = 0;
        $this->pSpam     = 0;
    }

    public function __destruct()
    {;;;}

    public function __toString()
    {
        $rval = "Word__\n" 
              . "  * word = "      . $this->word      . "\n"
              . "  * countBad = "  . $this->countBad  . "\n"
              . "  * countGood = " . $this->countGood . "\n"
              . "  * rBad = "      . $this->rBad      . "\n"
              . "  * rGood = "     . $this->rGood     . "\n"
              . "  * pSpam = "     . $this->pSpam     . "\n";
        return $rval;
    }

    // Increment bad counter
    public function countBad()
    {
        ++$this->countBad;
    }

    // Increment bad counter
    public function countGood()
    {
        ++$this->countGood;
    }

    // Implement bayes rules to compute how likely this word is "spam"
    public function finalizeProb($totalBadWords, $totalGoodWords)
    {
        $this->rGood = $this->countGood / $totalGoodWords;
        $this->rBad = $this->countBad / $totalBadWords;

        if($this->rGood + $this->rBad > 0)
            $this->pSpam = $this->rBad / ($this->rBad + $this->rGood);

        if($this->pSpam < 0.01)
            $this->pSpam = 0.01;
        else if($this->pSpam > 0.99)
            $this->pSpam = 0.99;
    }

    // The "interesting" rating for a word is
    // How different from 0.5 it is
    public function interesting()
    {
        return abs(0.5 - $this->pSpam);
    }

    public function setPSpam($prob)
    {
        $this->pSpam = $prob;
    }

    public function getPSpam()
    {
        return $this->pSpam;
    }

    public function getWord()
    {
        return $this->word;
    }
}

?>
