@component('mail::message')
<div>
    <br>
    <p style="padding:10px; text-align:left;">
        Hola {{$first_name}} {{$last_name}}<br>
        Este es un correo de confirmacion de Registro.
    </p>
</div>
<div style="padding-left: 10px">
    <h1 style="font-size: 24px">{{$store_name}}</h1>
    <p>Desde ahora puedes disfrutar da nuestra plataforma de comercio electronico.</p>
    <p style="padding-top: 10px">
        Si no pediste ni solicitó un registro, en Tulivery por favor omita este correo
    </p>
    <p style="margin-top: -10px">
        Gracias por visitar Tulivery.
    </p>
</div>
@endcomponent
