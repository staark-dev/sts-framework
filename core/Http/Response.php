<?php

namespace STS\core\Http;

class Response {
    protected int $statusCode;
    protected array $headers = [];
    protected string $body;
    protected array $cookies = [];

    public function __construct(string $body = '', int $statusCode = 200, array $headers = []) {
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    // Setează codul de stare HTTP
    public function setStatusCode(int $statusCode): self {
        $this->statusCode = $statusCode;
        return $this;
    }

    // Obține codul de stare HTTP
    public function getStatusCode(): int {
        return $this->statusCode;
    }

    // Setează antetele HTTP
    public function setHeader(string $name, string $value): self {
        $this->headers[$name] = $value;
        return $this;
    }

    // Setează mai multe antete HTTP
    public function setHeaders(array $headers): self {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
        return $this;
    }

    // Obține antetele HTTP
    public function getHeaders(): array {
        return $this->headers;
    }

    // Setează corpul răspunsului
    public function setBody(string $body): self {
        $this->body = $body;
        return $this;
    }

    // Obține corpul răspunsului
    public function getBody(): string {
        return $this->body;
    }

    // Setează un cookie
    public function setCookie(string $name, string $value, int $expiry = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true): self {
        $this->cookies[] = [
            'name' => $name,
            'value' => $value,
            'expiry' => $expiry,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httpOnly' => $httpOnly
        ];
        return $this;
    }

    // Trimite toate cookie-urile
    protected function sendCookies() {
        foreach ($this->cookies as $cookie) {
            setcookie(
                $cookie['name'],
                $cookie['value'],
                $cookie['expiry'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httpOnly']
            );
        }
    }

    // Trimite un răspuns JSON
    public function json(array $data, int $statusCode = 200): self {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'application/json');
        $this->setBody(json_encode($data));
        return $this;
    }

    // Redirecționează către o altă locație
    public function redirect(string $url, int $statusCode = 302): self {
        $this->setStatusCode($statusCode);
        $this->setHeader('Location', $url);
        return $this;
    }

    // Trimite un fișier pentru descărcare
    public function download(string $filePath, string $fileName = null): self {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: $filePath");
        }

        $fileName = $fileName ?? basename($filePath);
        $this->setHeader('Content-Description', 'File Transfer');
        $this->setHeader('Content-Type', 'application/octet-stream');
        $this->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $this->setHeader('Content-Length', filesize($filePath));
        $this->setBody(file_get_contents($filePath));
        return $this;
    }

    // Trimite răspunsul către client
    public function send() {
        // Setează codul de stare HTTP
        http_response_code($this->statusCode);

        // Trimite antetele HTTP
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // Trimite cookie-urile
        $this->sendCookies();

        // Trimite corpul răspunsului
        //echo $this->body;
    }
}
