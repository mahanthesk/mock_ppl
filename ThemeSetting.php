<?php
// ThemeSetting.php

class ThemeSetting {
    private $pdo;
    private $table = 'theme_settings';

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get the active theme settings
     */
    public function getActiveTheme() {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE active_theme = 1 LIMIT 1");
            $stmt->execute();
            $theme = $stmt->fetch(PDO::FETCH_ASSOC);

            // If no active theme, get the first one available
            if (!$theme) {
                $stmt = $this->pdo->query("SELECT * FROM {$this->table} LIMIT 1");
                $theme = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            return $theme ?: [];
        } catch (PDOException $e) {
            error_log("Failed to fetch theme: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all themes
     */
    public function getAllThemes() {
        try {
            $stmt = $this->pdo->query("SELECT id, theme_name, active_theme FROM {$this->table} ORDER BY id ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to fetch all themes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Set a theme as active
     */
    public function setActiveTheme($id) {
        try {
            // Deactivate all
            $this->pdo->exec("UPDATE {$this->table} SET active_theme = 0");
            // Activate requested
            $stmt = $this->pdo->prepare("UPDATE {$this->table} SET active_theme = 1 WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Failed to set active theme: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a new theme (duplicate)
     */
    public function createTheme($themeName, $data) {
        $data['theme_name'] = $themeName;
        $data['active_theme'] = 0; // Don't auto-activate
        
        $fields = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        $columns = implode(', ', $fields);
        
        try {
            $stmt = $this->pdo->prepare("INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})");
            return $stmt->execute(array_values($data));
        } catch (PDOException $e) {
            error_log("Failed to create theme: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update theme settings for a given ID
     */
    public function updateTheme($id, $data) {
        $fields = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            // Protect against SQL injection by allowing only alphanumeric and underscores in column names
            if (preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
                $fields[] = "$key = ?";
                $params[] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }

        $params[] = $id; // Append ID for the WHERE clause
        $setClause = implode(', ', $fields);
        
        try {
            $stmt = $this->pdo->prepare("UPDATE {$this->table} SET {$setClause} WHERE id = ?");
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Failed to update theme: " . $e->getMessage());
            return false;
        }
    }
}
?>
