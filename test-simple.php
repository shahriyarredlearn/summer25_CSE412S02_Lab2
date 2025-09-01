<?php
<?php
header('Content-Type: application/json');
echo json_encode([
    'ok' => true,
    'message' => 'PHP is working',
    'time' => date('Y-m-d H:i:s')
]);
?>