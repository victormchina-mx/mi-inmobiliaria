<?php
/**
 * Aviso de cita nueva (AgentAgenda::notify) al responsable del inmueble y
 * al agente que muestra. Variables: $a (cita con detalles), $role
 * ('owner' | 'shower'), $creator, $link.
 */
use App\Libraries\Agenda;
use App\Libraries\FichaTecnica;

$code = FichaTecnica::code((int) $a['pid']);
$zone = implode(', ', array_filter([trim((string) $a['location']), trim((string) $a['city'])]));
$row  = static fn (string $label, string $value): string => '<tr><td style="padding:7px 0;width:150px;color:#6B7280;font-size:14px;vertical-align:top">' . $label . '</td><td style="padding:7px 0;color:#0F172A;font-size:14px;font-weight:600">' . $value . '</td></tr>';
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:0;background:#F6F4EF;font-family:Arial,Helvetica,sans-serif">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F6F4EF;padding:30px 12px">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden">
    <tr><td style="background:#0F172A;padding:26px 32px">
        <div style="color:#C9A96E;font-size:12px;font-weight:bold;letter-spacing:2px">AGENDA DE CITAS</div>
        <div style="color:#ffffff;font-size:22px;font-weight:bold;margin-top:6px">Nueva cita en <?= $role === 'owner' ? 'tu inmueble' : 'tu agenda' ?> · <?= $code ?></div>
    </td></tr>
    <tr><td style="padding:28px 32px 10px;color:#374151;font-size:15px;line-height:1.6">
        <p style="margin:0 0 16px"><b><?= esc($creator) ?></b> agendó una visita<?= $role === 'shower' ? ' en la que tú muestras el inmueble' : '' ?>:</p>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #F0EEE8;border-bottom:1px solid #F0EEE8">
            <?= $row('Inmueble', esc($code . ' · ' . ($a['title'] ?? ''))) ?>
            <?= $row('Zona', esc($zone ?: '—')) ?>
            <?= $row('Fecha y hora', esc(ucfirst(Agenda::when($a['starts_at'], $a['ends_at'])))) ?>
            <?= $row('Muestra', esc($a['shower_name'] ?? '—')) ?>
            <?= $row('Cliente', esc($a['client_name'])) ?>
            <?php if (! empty($a['client_notes'])): ?><?= $row('Notas', esc($a['client_notes'])) ?><?php endif ?>
        </table>
        <?php if ($role === 'owner'): ?>
        <p style="margin:16px 0 0;font-size:14px;color:#6B7280">Si las llaves no están en la oficina, coordina la entrega con quien muestra.</p>
        <?php endif ?>
    </td></tr>
    <tr><td style="padding:18px 32px 30px">
        <a href="<?= esc($link, 'attr') ?>" style="display:inline-block;background:#C9A96E;color:#ffffff;text-decoration:none;font-weight:bold;font-size:15px;padding:13px 24px;border-radius:10px">Ver la cita en la agenda</a>
    </td></tr>
    <tr><td style="background:#FAFAF8;padding:16px 32px;color:#9CA3AF;font-size:12px">Aviso automático de la agenda compartida de agentes · inmobiliaria.vmch.net</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
