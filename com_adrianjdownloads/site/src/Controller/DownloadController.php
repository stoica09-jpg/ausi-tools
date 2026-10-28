<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Component\Adrianjdownloads\Site\Controller;

use Adrian\Component\Adrianjdownloads\Administrator\Helper\StorageHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

\defined('_JEXEC') or die;

/**
 * Sends a file to the visitor after checking the access rights.
 */
class DownloadController extends BaseController
{
    public function send()
    {
        $id    = $this->input->getInt('id');
        $model = $this->getModel('Download', 'Site', ['ignore_request' => true]);

        // Throws a 404 when the download is not visible for the visitor.
        $item = $model->getItem($id);

        $user = $this->app->getIdentity();

        // Visitors who can see the file can download it, unless the file is
        // set to require login.
        if ($item->require_login && $user->guest) {
            $return = base64_encode(Uri::getInstance()->toString());
            $this->app->enqueueMessage(Text::_('COM_ADRIANJDOWNLOADS_LOGIN_TO_DOWNLOAD'), 'warning');
            $this->app->redirect(Route::_('index.php?option=com_users&view=login&return=' . $return, false));

            return;
        }

        $path = $item->file_name ? StorageHelper::getFilePath($item->file_name) : null;

        if ($path === null) {
            if ($item->external_url) {
                $model->countDownload($id);
                $this->app->redirect($item->external_url);

                return;
            }

            throw new \Exception(Text::_('COM_ADRIANJDOWNLOADS_ERROR_FILE_NOT_FOUND'), 404);
        }

        $model->countDownload($id);

        $this->sendFile($path, $item->file_original ?: $item->file_name, $item->file_mime ?: 'application/octet-stream');
    }

    private function sendFile(string $path, string $name, string $mime): void
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $inline    = array_filter(array_map(
            static fn ($e) => strtolower(trim($e, " .\t")),
            explode(',', (string) ComponentHelper::getParams('com_adrianjdownloads')->get('inline_types', ''))
        ));
        $disposition = \in_array($extension, $inline, true) ? 'inline' : 'attachment';

        $ascii = preg_replace('/[^\x20-\x7E]/', '_', str_replace(['"', '\\'], '_', $name));

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        @set_time_limit(0);

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: ' . $disposition . '; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($name));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $handle = fopen($path, 'rb');

        if ($handle) {
            while (!feof($handle)) {
                echo fread($handle, 1024 * 1024);
                flush();
            }

            fclose($handle);
        }

        $this->app->close();
    }
}
