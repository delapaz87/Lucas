<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegisterCompany extends Mailable
{
    use Queueable, SerializesModels;

    public string $first_name;
    public string $last_name;
    public string $store_name;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $first_name, string $last_name, string $store_name)
    {
        //
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->store_name = $store_name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
        ->subject('Registro de Compañia desde LucaPOS')
        ->markdown('mail.register-company');
    }
}
