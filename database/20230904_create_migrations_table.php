<?php

class CreateMigrationsTable
{
    // Aplica migrarea: creează tabelul migrations
    public function up($db)
    {
        $query = "
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                version VARCHAR(255) NOT NULL UNIQUE,
                migrated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;
        ";

        try {
            $db->exec($query);
            echo "Tabelul 'migrations' a fost creat cu succes.\n";
        } catch (PDOException $e) {
            echo "Eroare la crearea tabelului 'migrations': " . $e->getMessage() . "\n";
        }
    }

    // Revert migrarea: șterge tabelul migrations
    public function down($db)
    {
        $query = "DROP TABLE IF EXISTS migrations;";

        try {
            $db->exec($query);
            echo "Tabelul 'migrations' a fost șters cu succes.\n";
        } catch (PDOException $e) {
            echo "Eroare la ștergerea tabelului 'migrations': " . $e->getMessage() . "\n";
        }
    }
}
?>
