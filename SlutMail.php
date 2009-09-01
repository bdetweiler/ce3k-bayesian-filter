<?php
/*******************************************************************************
 * File:    SlutMail.php                                                       *
 * Author:  Brian Detweiler                                                    *
 * Notes:   This was not used in the final version of ce3k, but I decided to   *
 *          leave it in, because it could be used if you wish to send form     *
 *          letters including image attachments, or tr.im/bit.ly links.        *
 *          The advantage to sending links is, you can see when they have been *
 *          clicked on, so you know if you are even getting through.           *
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

include_once('phpmailer/class.phpmailer.php');

class SlutMail
{

    // Gmail connection information as well as the email stuff itself
    private $username  = "";
    private $password  = "";
    private $to        = "";
    private $to_name   = "";
    private $from_name = "";
    private $subject   = "";
    private $trimLink1 = "";
    private $trimLink2 = "";
    private $body      = "";
    private $altBody   = "";


    function __construct($body = "")
    {
        if($body == "")
        {
            $this->body = "";
            $this->altBody = "";
        }
        else
        {
            $this->body    = $body;
            $this->altBody = $body;
        }
    }

    function getBody()
    {
        return($this->body);
    }

    function setBody($htmlBody, $altBody = "")
    {

        // Removes all tags
        $pattern = '/<<1>>/';
        $htmlBody = preg_replace($pattern,
                                 "<a href=\"$this->trimLink1\">$this->trimLink1</a>",
                                 $htmlBody);

        // Removes all tags
        $pattern = '/<<2>>/';
        $htmlBody = preg_replace($pattern,
                                 "<a href=\"$this->trimLink2\">$this->trimLink2</a>",
                                 $htmlBody);

        $this->body = $htmlBody;

        if($altBody == "")
            $altBody = $htmlBody;

        $this->altBody = $altBody;
    }

    function resetBody()
    {
        $this->body = "";
        $this->altBody = "";
    }

    function setTrimLinks($trim1, $trim2)
    {
        $this->trimLink1 = $trim1;
        $this->trimLink2 = $trim2;
    }

    function setSubject($subj)
    {
        $this->subject = $subj;
    }

    function sendMail($to)
    {

        $mail = new PHPMailer();
        // send via SMTP
        $mail->IsSMTP();
        // turn on SMTP authentication
        $mail->SMTPAuth = true;
        $mail->Username = $this->useranme;
        $mail->Password = $this->password;
        // Reply to this email ID
        $webmaster_email = $this->username;
        // Recipients email ID
        // Recipient's name
        $name = $this->to_name;
        $mail->From = $webmaster_email;
        $mail->FromName = $this->from_name;
        $mail->AddAddress($to, $name);
        $mail->AddReplyTo($webmaster_email, $this->from_name);
        // set word wrap
        $mail->WordWrap = 50;
        // $mail->AddAttachment("/var/tmp/file.tar.gz"); // attachment
        // $mail->AddAttachment("/tmp/image.jpg", "new.jpg"); // attachment
        // send as HTML
        $mail->IsHTML(true);
        $mail->Subject = $this->subject;
        // HTML Body
        $mail->Body = $this->body;
        // Text Body
        $mail->AltBody = $this->altBody;

        if(!$mail->Send())
            die("Mailer Error: " . $mail->ErrorInfo);
        else
            return;
    }
}
?> 
