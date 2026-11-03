<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
    .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; }
    .header { background-color: #3c50e0; padding: 30px; text-align: center; }
    .header h1 { color: #ffffff; margin: 0; font-size: 22px; }
    .body { padding: 35px 40px; color: #333333; }
    .body p { line-height: 1.6; margin-bottom: 16px; }
    table.fields { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    table.fields td { padding: 8px 0; vertical-align: top; }
    table.fields td.label { width: 110px; font-weight: bold; color: #555; }
    .message-box { background: #f4f6ff; border-left: 4px solid #3c50e0;
                    padding: 14px 16px; border-radius: 4px; font-size: 14px; color: #333; }
    .footer { background: #f0f0f0; text-align: center; padding: 18px;
              font-size: 12px; color: #888888; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Real Estate &mdash; Nuevo mensaje de contacto</h1>
    </div>
    <div class="body">
        <p>Se recibió un nuevo mensaje desde el formulario de contacto del sitio:</p>

        <table class="fields">
            <tr><td class="label">Nombre:</td><td><?= esc($name) ?></td></tr>
            <tr><td class="label">Correo:</td><td><?= esc($email) ?></td></tr>
            <tr><td class="label">Teléfono:</td><td><?= esc($phone ?: 'No proporcionado') ?></td></tr>
            <tr><td class="label">Asunto:</td><td><?= esc($subject) ?></td></tr>
        </table>

        <div class="message-box">
            <?= nl2br(esc($message)) ?>
        </div>

        <p style="margin-top:24px; font-size:13px; color:#777;">Puedes responder directamente a este correo — el botón "Responder" de tu cliente de correo se dirigirá a <?= esc($email) ?>.</p>
    </div>
    <div class="footer">
        &copy; <?= date('Y') ?> Real Estate &mdash; Correo generado automáticamente desde inmobiliaria.vmch.net
    </div>
</div>
</body>
</html>
