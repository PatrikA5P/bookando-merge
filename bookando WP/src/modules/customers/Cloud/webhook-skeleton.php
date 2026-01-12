<?php
// Beispiel-WebHook für SaaS Cloud-Anbindung
header('Content-Type: application/json');
echo json_encode(['ok' => true, 'source' => 'Bookando Webhook', 'data' => $_REQUEST]);
exit;