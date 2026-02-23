<?php
$hash = '$2y$10$jmgudhNMOAMdGp26VLz8jOaqwcLtt.ZEfVnRdeHip9Wdy8OLnqdDK';
if (password_verify('password', $hash)) {
    echo "password matches selam_dating hash\n";
} else {
    echo "password DOES NOT match selam_dating hash\n";
}

$common_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
if (password_verify('password', $common_hash)) {
    echo "password matches common hash\n";
} else {
    echo "password DOES NOT match common hash\n";
}
