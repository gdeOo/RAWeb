<?php

class LocalValetDriver extends ValetDriver
{
    private $site_folder = '/public';

    /**
     * Determine if the driver serves the request.
     *
     * @param  string $sitePath
     * @param  string $siteName
     * @param  string $uri
     * @return bool
     */
    public function serves($sitePath, $siteName, $uri)
    {
        return true;
    }

    /**
     * Determine if the incoming request is for a static file.
     *
     * @param  string $sitePath
     * @param  string $siteName
     * @param  string $uri
     * @return string|false
     */
    public function isStaticFile($sitePath, $siteName, $uri)
    {
        if (file_exists($staticFilePath = $sitePath . $this->site_folder . $uri)) {
            return $staticFilePath;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     *
     * @param  string $sitePath
     * @param  string $siteName
     * @param  string $uri
     * @return string
     */
    public function frontControllerPath($sitePath, $siteName, $uri)
    {
        $path = $sitePath . $this->site_folder;

        if ($uri == '/') {
            return $path . '/index.php';
        }

        if (mb_strpos(mb_strtolower($uri), '/achievement/') === 0) {
            $_GET['ID'] = basename($uri);
            return $sitePath . $this->site_folder . '/achievementInfo.php';
        }

        if (mb_strpos(mb_strtolower($uri), '/game/') === 0) {
            $_GET['ID'] = basename($uri);
            return $sitePath . $this->site_folder . '/gameInfo.php';
        }

        if (mb_strpos(mb_strtolower($uri), '/user/') === 0) {
            $_GET['ID'] = basename($uri);
            return $sitePath . $this->site_folder . '/userInfo.php';
        }

        return strpos($uri, '.php')
            ? $path . $uri
            : $path . $uri . '.php';
    }
}
