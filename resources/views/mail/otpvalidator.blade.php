@component('mail::message')
<div>
    <br>
    <p style="padding:10px; text-align:left;">
        Hola {{$first_name}} {{$last_name}}<br>
        Usa el siguiente código de verificación:
    </p>
</div>
<div style="padding-left: 10px">
    <h1 style="font-size: 24px">{{$code_otp}}</h1>
    <p>Este código expirará en 5 minutos.</p>
    <p style="padding-top: 10px">
        Si no pediste ni se te solicitó un código de verificación, cambia tu contraseña de inmediato visitando tu perfil de usuario en Tulivery.  Si tienes preguntas adicionales sobre seguridad de la cuenta, escríbenos a sac@tulivery.com
    </p>
    <p style="margin-top: -10px">
        Gracias por visitar Tulivery.
    </p>
</div>
@endcomponent
