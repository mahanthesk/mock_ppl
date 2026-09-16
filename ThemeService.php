<?php
// ThemeService.php
require_once 'ThemeSetting.php';

class ThemeService {
    private $pdo;
    private $themeSetting;
    private $cacheFile = __DIR__ . '/cache/theme_settings.json';

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->themeSetting = new ThemeSetting($pdo);
        
        // Ensure cache directory exists
        if (!is_dir(__DIR__ . '/cache')) {
            mkdir(__DIR__ . '/cache', 0755, true);
        }
    }

    /**
     * Get theme (from cache if available, else DB)
     */
    public function getTheme() {
        if (file_exists($this->cacheFile)) {
            $cacheContent = file_get_contents($this->cacheFile);
            if ($cacheContent) {
                return json_decode($cacheContent, true);
            }
        }
        
        // Fallback to DB and recreate cache
        $theme = $this->themeSetting->getActiveTheme();
        $this->cacheTheme($theme);
        return $theme;
    }

    /**
     * Update theme and refresh cache
     */
    public function updateTheme($id, $data) {
        $result = $this->themeSetting->updateTheme($id, $data);
        if ($result) {
            $this->clearThemeCache();
            // Fetch updated and cache it immediately
            $updatedTheme = $this->themeSetting->getActiveTheme();
            $this->cacheTheme($updatedTheme);
        }
        return $result;
    }

    /**
     * Cache the theme data to a JSON file
     */
    private function cacheTheme($themeData) {
        file_put_contents($this->cacheFile, json_encode($themeData));
    }

    /**
     * Clear the cache file
     */
    public function clearThemeCache() {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }
}
?>
