<?php
require_once './header.php';
if (function_exists('mysqli_connect')) {
    echo "mysqli est activé";
} else {
    echo "mysqli n'est pas activé";
}
?>
