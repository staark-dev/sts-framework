<?php
namespace STS\core\Session;

use SessionHandlerInterface;
use STS\core\Database\ORM;

class CustomSessionHandler implements SessionHandlerInterface {
    private ORM $sessionOrm;

    public function __construct() {
        // Inițializăm ORM-ul pentru tabela 'sessions'
        $this->sessionOrm = new ORM('sessions');
    }

    public function open($savePath, $sessionName): bool {
        // Deschide sesiunea (inițializarea poate fi făcută aici dacă e nevoie)
        return true;
    }

    public function close(): bool {
        // Închide sesiunea
        return true;
    }

    public function read($id): string {
        $session = $this->sessionOrm->where('id', $id)->get();

        if ($session) {
            return $session[0]['data'];
        }

        return '';
    }

    public function write($id, $data): bool  {
        // Verifică dacă sesiunea există deja
        $session = $this->sessionOrm->where('id', $id)->get();

        if ($session) {
            // Actualizează sesiunea existentă
            return $this->sessionOrm->update((int)$session[0]['id'], [
                'data' => $data,
                'last_accessed' => date('Y-m-d H:i:s'),
            ]);
        } else {
            // Creează o sesiune nouă
            return $this->sessionOrm->create([
                'id' => $id,
                'data' => $data,
                'last_accessed' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function destroy($id): bool {
        return $this->sessionOrm->delete((int)$id);
    }

    public function gc($maxLifetime): int|false {
        $threshold = date('Y-m-d H:i:s', time() - $maxLifetime);
        return $this->sessionOrm->where('last_accessed', '<', $threshold)->delete();
    }
}