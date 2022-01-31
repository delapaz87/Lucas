@component('mail::message')
<div>
    <br>
    <p style="padding:10px; text-align:left;">
        Hola {{$first_name}} {{$last_name}}<br>
        <br>
        Usuario: {{$email}}
        <br>
        Su contraseña ha sido cambiada con exito.
    </p>
</div>
<div style="padding-left: 10px">
    <p>Desde ahora puedes disfrutar da nuestra plataforma de comercio electronico.</p>
    <p style="padding-top: 10px">
        Si no ha solicitado el cambio de contraseña por favor ingresa a la pagina de Tulivery y cambie su contraseña por motivos
        de seguridad.
    </p>
    <p style="margin-top: -10px">
        Gracias por visitar Tulivery.
    </p>
</div>
@endcomponent
