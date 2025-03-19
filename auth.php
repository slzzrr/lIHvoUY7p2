<?php
function requireRoles(array $allowedRoles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
        header("Location: ../access_denied.php");
        exit();
    }
}
