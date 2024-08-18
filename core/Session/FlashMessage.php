<?php
declare(strict_types=1);
namespace STS\core\Session;

class FlashMessage
{
    public function setFlash($key, $message): void
    {
        $_SESSION['flash'][$key] = $message;
    }

    public function getFlash($key) {
        $message = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $message;
    }
}