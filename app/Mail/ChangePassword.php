<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChangePassword extends Mailable
{
    use Queueable, SerializesModels;
    public string $first_name;
    public string $last_name;
    public string $email;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $first_name, string $last_name, string $email)
    {
        //
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->email = $email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
        ->subject('Cambio de contraseña en LucaPOS')
        ->markdown('mail.change-password');
    }
}
