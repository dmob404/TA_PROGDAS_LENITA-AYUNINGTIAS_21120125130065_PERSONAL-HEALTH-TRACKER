<?php
file_put_contents("data.json", json_encode([]));
header("Location: index.php?reset=done");
exit;
