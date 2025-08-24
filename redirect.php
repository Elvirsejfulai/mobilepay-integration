<?php
declare(strict_types=1);
require __DIR__.'/config.php';
$paymentId = $_GET['paymentId'] ?? null;
?>
<!doctype html><html><body>
<?php if($paymentId): ?>
<h1>Hvala! Uplata zaprimljena.</h1><p>ID: <?=htmlspecialchars($paymentId)?></p>
<?php else: ?>
<h1>Uplata prekinuta ili odbijena.</h1>
<?php endif; ?>
</body></html>
