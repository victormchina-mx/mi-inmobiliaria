<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
    .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; }
    .header { background-color: #28a745; padding: 30px; text-align: center; }
    .header h1 { color: #ffffff; margin: 0; font-size: 22px; }
    .body { padding: 35px 40px; color: #333333; }
    .body p { line-height: 1.6; margin-bottom: 16px; }
    .btn { display: inline-block; background-color: #28a745; color: #ffffff !important;
           text-decoration: none; padding: 14px 32px; border-radius: 5px;
           font-size: 15px; font-weight: bold; margin: 10px 0; }
    .btn-wrap { text-align: center; margin: 24px 0; }
    .notice { background: #fff8e1; border-left: 4px solid #ffc107;
              padding: 12px 16px; border-radius: 4px; font-size: 13px; color: #555; }
    .footer { background: #f0f0f0; text-align: center; padding: 18px;
              font-size: 12px; color: #888888; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Real Estate &mdash; Recuperación de Contraseña</h1>
    </div>
    <div class="body">
        <p>Hola, <strong><?= esc($userName) ?></strong>.</p>
        <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta. Haz clic en el botón de abajo para crear una nueva contraseña:</p>

        <div class="btn-wrap">
            <a href="<?= esc($resetUrl) ?>" class="btn">Restablecer contraseña</a>
        </div>

        <div class="notice">
            <strong>Importante:</strong> Este enlace es válido por <strong>1 hora</strong> y solo puede usarse una vez. Si no solicitaste este cambio, puedes ignorar este correo; tu contraseña no será modificada.
        </div>

        <p style="margin-top:24px; font-size:13px; color:#777;">Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
        <a href="<?= esc($resetUrl) ?>" style="color:#28a745; word-break:break-all;"><?= esc($resetUrl) ?></a></p>
    </div>
    <div class="footer">
        &copy; <?= date('Y') ?> Real Estate &mdash; Correo generado automáticamente, no responder.
    </div>
</div>
</body>
</html>
