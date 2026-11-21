{{-- Texto base del aviso de privacidad integral. REVISAR CON ASESOR LEGAL antes de producción. --}}
@php($t = config('tarzo.taller'))
<p><strong>{{ $t['razon_social'] }}</strong>@if($t['domicilio']), con domicilio en {{ $t['domicilio'] }},@endif es responsable del tratamiento de tus datos personales conforme a la legislación mexicana de protección de datos personales en posesión de particulares.</p>
<p><strong>Datos que recabamos:</strong> nombre, teléfono, correo electrónico y domicilio; datos fiscales para facturación (RFC, régimen fiscal, código postal); datos del vehículo (marca, modelo, placas, número de serie, kilometraje, póliza de seguro); fotografías y video del vehículo; y tu firma autógrafa digital.</p>
<p><strong>Finalidades necesarias:</strong> recibir, diagnosticar y reparar tu vehículo; elaborar presupuestos; informarte del avance del servicio; gestionar el siniestro con tu aseguradora; facturar y cobrar; y atender garantías.</p>
<p><strong>Finalidades adicionales</strong> (puedes negarte sin afectar el servicio): recordatorios de mantenimiento, encuestas de satisfacción y promociones.</p>
<p><strong>Transferencias:</strong> compartimos los datos necesarios con tu aseguradora (en órdenes de seguro), con el proveedor autorizado de certificación de facturas (PAC) y el SAT, y con las autoridades que lo requieran por ley.</p>
<p><strong>Derechos ARCO:</strong> puedes acceder, rectificar, cancelar u oponerte al uso de tus datos, o revocar tu consentimiento, escribiendo a {{ $t['email_privacidad'] ?: 'nuestro correo de contacto' }}. Responderemos en los plazos que marca la ley.</p>
<p>Cualquier cambio a este aviso se publicará en {{ route('aviso-privacidad') }}.</p>
