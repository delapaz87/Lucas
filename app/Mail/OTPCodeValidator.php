<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OTPCodeValidator extends Mailable
{
    use Queueable, SerializesModels;

    public string $first_name;
    public string $last_name;
    public string $code_otp;

    /**
     * Create a new message instance.
     *
     * @return void
     */

     /**
     * Build the message.
     *
     * @var $textSubject
     * @var $textMessage
     */

    public function __construct(string $first_name, string $last_name, string $code_otp)
    {
        //
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->code_otp = $code_otp;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
        ->subject('Codigo de verificacion desde LucaPOS')
        ->markdown('mail.otpvalidator');
    }
}
