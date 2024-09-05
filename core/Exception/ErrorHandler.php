<?php

// Configurare globală a aplicației
define('DEBUG_MODE', true); // Setează 'false' în mediul de producție

class ErrorHandler
{
    protected $logFile;         // Calea către fișierul de jurnalizare a erorilor
    protected $emailAdmin;      // Adresa de email pentru notificări de erori critice
    protected $logLevel;        // Nivelul de jurnalizare: 'info', 'warning', 'error'
    protected $errorPages;      // Paginile personalizate de eroare
    protected $ignoredErrors;   // Tipurile de erori care vor fi ignorate

    public function __construct($logFile = 'errors.log', $emailAdmin = '', $logLevel = 'error', $errorPages = [])
    {
        $this->logFile = $logFile;
        $this->emailAdmin = $emailAdmin;
        $this->logLevel = $logLevel;
        $this->errorPages = $errorPages;
        $this->ignoredErrors = [E_NOTICE, E_USER_NOTICE]; // Implicit ignorăm avertizările minore

        // Setează handler-ele personalizate pentru erori și excepții
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    // Gestionarea erorilor PHP
    public function handleError($errno, $errstr, $errfile, $errline)
    {
        // Ignoră erorile specificate în lista de erori ignorate
        if (in_array($errno, $this->ignoredErrors)) {
            return false; // Lasă PHP să gestioneze erorile ignorate
        }

        $this->logError("Error: [$errno] $errstr in $errfile on line $errline", 'error');

        if (DEBUG_MODE) {
            $this->displayDeveloperError($errno, $errstr, $errfile, $errline);
        } else {
            $this->redirectToErrorPage($errno);
        }

        return true;
    }

    // Gestionarea excepțiilor necontrolate
    public function handleException($exception)
    {
        $this->logError("Uncaught Exception: " . $exception->getMessage(), 'error');

        if (DEBUG_MODE) {
            $this->displayDeveloperError('Exception', $exception->getMessage(), $exception->getFile(), $exception->getLine(), $exception->getTraceAsString());
        } else {
            $this->redirectToErrorPage(500);
        }
    }

    // Gestionarea shutdown-ului pentru capturarea erorilor fatale
    public function handleShutdown()
    {
        $error = error_get_last();
        if ($error && ($error['type'] === E_ERROR || $error['type'] === E_USER_ERROR)) {
            $this->logError("Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}", 'error');
            if (!DEBUG_MODE) {
                $this->redirectToErrorPage(500);
            }
        }
    }

    // Înregistrarea erorilor în jurnal, cu nivel
    protected function logError($message, $level = 'error')
    {
        if ($this->shouldLog($level)) {
            error_log(date('Y-m-d H:i:s') . " [$level] $message \n", 3, $this->logFile);

            // Notificare prin email pentru erori critice
            if ($level === 'error' && !empty($this->emailAdmin)) {
                $this->sendEmail($message);
            }
        }
    }

    // Verifică dacă eroarea ar trebui jurnalizată conform nivelului setat
    protected function shouldLog($level)
    {
        $levels = ['info' => 0, 'warning' => 1, 'error' => 2];
        return $levels[$level] >= $levels[$this->logLevel];
    }

    // Trimite email către administrator pentru erori critice
    protected function sendEmail($message)
    {
        $subject = "Eroare critică în aplicația ta";
        $body = "A apărut o eroare critică: \n\n" . $message;
        mail($this->emailAdmin, $subject, $body);
    }

    // Redirecționează utilizatorii către pagina de eroare personalizată
    protected function redirectToErrorPage($errorCode)
    {
        if (isset($this->errorPages[$errorCode])) {
            header("Location: " . $this->errorPages[$errorCode]);
            exit();
        } else {
            echo "A apărut o eroare. Vă rugăm să încercați din nou mai târziu.";
        }
    }

    // Metodă pentru afișarea paginii de eroare detaliată pentru dezvoltatori
 
protected function displayDeveloperError($type, $message, $file, $line, $trace = null)
{
    // Informații de context suplimentare
    $contextInfo = print_r([
        'GET Parameters' => $_GET,
        'POST Parameters' => $_POST,
        'Session Data' => $_SESSION,
        'Cookies' => $_COOKIE,
        'Server Info' => $_SERVER,
        'Current User' => isset($_SESSION['user']) ? $_SESSION['user'] : 'Anonim',
        'Last SQL Queries' => $this->getLastSqlQueries(),
    ], true);

    // Trace-ul complet al erorii
    $traceInfo = $trace ? $trace : print_r(debug_backtrace(), true);

    // Variabile locale la momentul erorii
    $localVariables = print_r(get_defined_vars(), true);

    $html = <<<HTML
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eroare de dezvoltator - Detalii</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white">
    <div class="container mt-5">
        <h1 class="text-warning">[$type] $message</h1>
        <p><strong>Fișier:</strong> $file</p>
        <p><strong>Linie:</strong> $line</p>
        <h3>Context:</h3>
        <pre class='bg-secondary p-3'>$contextInfo</pre>
        <h3>Trace:</h3>
        <pre class='bg-secondary p-3'>$traceInfo</pre>
        <h3>Variables:</h3>
        <pre class='bg-secondary p-3'>$localVariables</pre>
        <p class='text-muted'>Această pagină este afișată deoarece aplicația este în modul de depanare.</p>
    </div>
</body>
</html>
HTML;

    echo $html;
    exit();
}


    // Metoda pentru a obține ultimele interogări SQL executate (exemplu pentru PDO)
    protected function getLastSqlQueries()
    {
        // Exemplu: adăugați o metodă care să returneze ultimele interogări SQL din aplicație.
        return isset($GLOBALS['last_sql_queries']) ? implode("\n", $GLOBALS['last_sql_queries']) : 'N/A';
    }

    // Metodă pentru adăugarea tipurilor de erori care trebuie ignorate
    public function addIgnoredErrors($errorTypes)
    {
        $this->ignoredErrors = array_merge($this->ignoredErrors, $errorTypes);
    }

    // Metodă pentru oprirea gestiunii personalizate a erorilor
    public function stop()
    {
        restore_error_handler();
        restore_exception_handler();
    }
}

// Exemplu de utilizare
$errorHandler = new ErrorHandler(
    'errors.log', 
    'admin@exemplu.com', 
    'warning', 
    [
        404 => '/404.html', // Pagina de eroare personalizată pentru 404
        500 => '/500.html'  // Pagina de eroare personalizată pentru 500
    ]
);

// Ignoră avertizările minore și alte tipuri de erori
$errorHandler->addIgnoredErrors([E_WARNING, E_USER_WARNING]);

// Generare de eroare pentru test
trigger_error("Test de eroare!", E_USER_WARNING);

// Aruncare de excepție pentru test
throw new Exception("Test de excepție!");

?>
