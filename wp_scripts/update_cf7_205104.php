<?php
$form_id = 205104;

// 1. Cambiar recipient principal
$mail = get_post_meta($form_id, '_mail', true);
$old_recipient = $mail['recipient'] ?? '(vacío)';
$mail['recipient'] = 'rrhh@healthgroup.es';
update_post_meta($form_id, '_mail', $mail);
$verify = get_post_meta($form_id, '_mail', true);
echo "_mail.recipient: " . $old_recipient . " -> " . $verify['recipient'] . "\n";

// 2. Activar mail_2 (confirmacion al candidato)
$mail2 = get_post_meta($form_id, '_mail_2', true);
$was_active = !empty($mail2['active']);
echo "_mail_2.active antes: " . ($was_active ? 'true' : 'false') . "\n";

$mail2['active'] = true;
if (empty($mail2['recipient'])) {
    $mail2['recipient'] = '[applicant_email]';
}
if (empty($mail2['sender'])) {
    $mail2['sender'] = 'Health Group <info@healthgroup.es>';
}
if (empty($mail2['subject'])) {
    $mail2['subject'] = 'Health Group - Hemos recibido tu candidatura';
}
if (empty($mail2['body'])) {
    $mail2['body'] = "Hola [applicant_name],\n\nHemos recibido tu candidatura. Si tu perfil encaja con alguna de nuestras posiciones, nos pondremos en contacto contigo proximamente.\n\nGracias por tu interes en Health Group.\n\nUn saludo,\nEquipo de Health Group\nwww.healthgroup.es";
}
update_post_meta($form_id, '_mail_2', $mail2);
$verify2 = get_post_meta($form_id, '_mail_2', true);
echo "_mail_2.active despues: " . ($verify2['active'] ? 'true' : 'false') . "\n";
echo "_mail_2.recipient: " . $verify2['recipient'] . "\n";
echo "_mail_2.subject: " . $verify2['subject'] . "\n";
