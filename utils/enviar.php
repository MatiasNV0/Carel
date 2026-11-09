<?php
/**
 * Recibe el formulario de contacto.html y lo envía por mail.
 * Pensado para Hostinger (hosting con PHP). No requiere librerías.
 *
 * ANTES DE SUBIR, revisá la sección CONFIGURACIÓN.
 */

// ===================== CONFIGURACIÓN =====================
$DESTINO   = 'info@carelsrl.com.ar';          // dónde llegan las consultas
$REMITENTE = 'no-reply@carelsrl.com.ar';      // DEBE ser un mail de tu propio dominio
                                              // (créalo en hPanel > Correos electrónicos)
$ASUNTO    = 'Nueva consulta desde la web';
$MIN_SEGUNDOS_ENTRE_ENVIOS = 30;              // freno básico anti-spam por visitante
// =========================================================

header('Content-Type: application/json; charset=utf-8');

function responder(bool $ok, string $msg, int $code = 200): void {
    http_response_code($code);
    echo json_encode(['ok' => $ok, 'mensaje' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(false, 'Método no permitido.', 405);
}

// Honeypot: un campo oculto que los humanos no completan, los bots sí.
if (!empty($_POST['website'] ?? '')) {
    responder(true, 'Enviado.'); // se simula éxito para no darle pistas al bot
}

// Límite de frecuencia por sesión
session_start();
$ahora = time();
if (isset($_SESSION['ultimo_envio']) && $ahora - $_SESSION['ultimo_envio'] < $MIN_SEGUNDOS_ENTRE_ENVIOS) {
    responder(false, 'Esperá unos segundos antes de enviar otro mensaje.', 429);
}

// Limpieza: sin saltos de línea (evita inyección de cabeceras) y con largo máximo
function campo(string $k, int $max, bool $multilinea = false): string {
    $v = trim((string)($_POST[$k] ?? ''));
    if (!$multilinea) $v = preg_replace('/[\r\n]+/', ' ', $v);
    return mb_substr($v, 0, $max);
}

$nombre   = campo('nombre', 100);
$empresa  = campo('empresa', 120);
$email    = campo('email', 150);
$telefono = campo('telefono', 40);
$tipo     = campo('tipo', 40);
$mensaje  = campo('mensaje', 4000, true);

$tipos = [
    'presupuesto' => 'Presupuesto mayorista (bulto cerrado)',
    'tecnica'     => 'Consulta técnica y normas DIN / ISO / ASTM',
    'listado'     => 'Envío de listado de materiales para planta',
    'distribuidor'=> 'Apertura de cuenta corriente comercial',
];

if ($nombre === '' || $telefono === '' || $mensaje === '' || !isset($tipos[$tipo])
    || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responder(false, 'Revisá los datos: hay campos obligatorios vacíos o inválidos.', 422);
}

$cuerpo  = "Nueva consulta recibida desde el sitio web\n";
$cuerpo .= "----------------------------------------\n";
$cuerpo .= "Nombre:    $nombre\n";
$cuerpo .= "Empresa:   " . ($empresa !== '' ? $empresa : '(no indicada)') . "\n";
$cuerpo .= "Email:     $email\n";
$cuerpo .= "Teléfono:  $telefono\n";
$cuerpo .= "Consulta:  {$tipos[$tipo]}\n";
$cuerpo .= "----------------------------------------\n\n";
$cuerpo .= $mensaje . "\n";

$cabeceras = [
    'From: Web Carel SRL <' . $REMITENTE . '>',
    'Reply-To: ' . $nombre . ' <' . $email . '>',
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'X-Mailer: PHP/' . phpversion(),
];
$asunto = '=?UTF-8?B?' . base64_encode($ASUNTO . ' - ' . $tipos[$tipo]) . '?=';

// El 5º parámetro (-f) fija el remitente del sobre, mejora la entrega en Hostinger.
$enviado = mail($DESTINO, $asunto, $cuerpo, implode("\r\n", $cabeceras), '-f' . $REMITENTE);

if (!$enviado) {
    error_log('enviar.php: mail() falló');
    responder(false, 'No pudimos enviar el mensaje. Probá de nuevo o escribinos por WhatsApp.', 500);
}

$_SESSION['ultimo_envio'] = $ahora;
responder(true, '¡Mensaje recibido!');