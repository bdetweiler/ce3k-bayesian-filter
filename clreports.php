<?php

/*******************************************************************************
 * File:    cl.php                                                             *
 * Author:  Brian Detweiler                                                    *
 * Notes:   Gives a reprot of the information in the database.                 *
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
require_once 'owner.php';
require_once 'grepper.php';
?>

<html>
<body>
<?
    $owner = new owner();
    $totalPosts = $owner->getTotalPostings();
    $totalFlagged = $owner->getTotalFlaggedPostings();
    $withPic = pg_num_rows($owner->getCLPostingsWithPic());
    $withPicAndFlagged = pg_num_rows($owner->getCLPostingsWithPicAndFlagged());
?>
<table>
    <thead>
        <tr>
            <th colspan="2">
                Casual Encounters of the 3rd Kind
            <th>
        </tr>
        <tr>
            <th colspan="2">
                A Craigslist Experiment
            </th>
        </tr>
    </thead>
    <tr>
        <td>
            <b>Total postings:</b>
        </td>
        <td>
            <?print($totalPosts . "\n");?>
        </td>
    </tr>
    <tr>
        <td>
            <b>Flagged postings:</b>
        </td>
        <td>
            <?print($totalFlagged . "<br />\n");?>
        </td>
    </tr>
    <tr>
        <td>
            <b>Percent Flagged:</b>
        </td>
        <td>
            <?print(100 * $totalFlagged / $totalPosts);?>%
        </td>
    </tr>
    <tr>
        <td>
            <b>Total postings with pics:</b> 
        </td>
        <td>
            <?print($withPic);?>
        </td>
    </tr>
    <tr>
        <td>
            <b>Total postings with pics that were flagged:</b> 
        </td>
        <td>
            <?print($withPicAndFlagged);?>
        </td>
    </tr>
    <tr>
        <td>
            <b>Total postings with pics that were NOT flagged:</b> 
        </td>
        <td>
            <?print($withPic - $withPicAndFlagged);?>
        </td>
    </tr>
    <tr>
        <td>
            <b>Flagged percentage of posts with pics:</b>
        </td>
        <td>
            <?print(100 * $withPicAndFlagged / $withPic);?>%
        </td>
    </tr>
</table>


</body>
</html>
