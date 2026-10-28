<?php

/**
 * @package     Adrian jDownloads
 * @copyright   (C) 2026 Adrian
 * @license     GNU General Public License version 2 or later
 */

namespace Adrian\Component\Adrianjdownloads\Administrator\Model;

use Adrian\Component\Adrianjdownloads\Administrator\Helper\DatabaseHelper;
use Adrian\Component\Adrianjdownloads\Administrator\Helper\StorageHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;

\defined('_JEXEC') or die;

/**
 * Model for editing one download, including the file upload.
 */
class DownloadModel extends AdminModel
{
    public $typeAlias = 'com_adrianjdownloads.download';

    protected $text_prefix = 'COM_ADRIANJDOWNLOADS';

    public function getTable($name = 'Download', $prefix = 'Administrator', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_adrianjdownloads.download', 'download', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        // Users who cannot change the state get the field disabled.
        if (!Factory::getApplication()->getIdentity()->authorise('core.edit.state', 'com_adrianjdownloads')) {
            $form->setFieldAttribute('state', 'disabled', 'true');
            $form->setFieldAttribute('state', 'filter', 'unset');
        }

        return $form;
    }

    protected function loadFormData()
    {
        $app  = Factory::getApplication();
        $data = $app->getUserState('com_adrianjdownloads.edit.download.data', []);

        if (empty($data)) {
            $data = $this->getItem();

            // Pre-select the category filtered in the list for new items.
            if ($this->getState('download.id') == 0) {
                $filters = (array) $app->getUserState('com_adrianjdownloads.downloads.filter');
                $catid   = $filters['category_id'] ?? null;

                if (\is_array($catid)) {
                    $catid = reset($catid);
                }

                if ($catid) {
                    $data->set('catid', (int) $catid);
                }
            }
        }

        $this->preprocessData('com_adrianjdownloads.download', $data);

        return $data;
    }

    protected function prepareTable($table)
    {
        if (empty($table->id)) {
            if (empty($table->ordering)) {
                $db    = $this->getDatabase();
                $query = DatabaseHelper::query($db)
                    ->select('MAX(' . $db->quoteName('ordering') . ')')
                    ->from($db->quoteName('#__adrianjdownloads_files'));
                $db->setQuery($query);
                $table->ordering = (int) $db->loadResult() + 1;
            }
        }
    }

    protected function getReorderConditions($table)
    {
        $db = $this->getDatabase();

        return [$db->quoteName('catid') . ' = ' . (int) $table->catid];
    }

    public function save($data)
    {
        $input  = Factory::getApplication()->getInput();
        $files  = $input->files->get('jform', [], 'raw');
        $upload = $files['upload_file'] ?? null;
        $oldFile = '';

        if (!empty($data['id'])) {
            $existing = $this->getTable();

            if ($existing->load((int) $data['id'])) {
                $oldFile = (string) $existing->file_name;
            }
        }

        if (\is_array($upload) && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $stored = $this->storeUpload($upload);

            if ($stored === null) {
                return false;
            }

            $data['file_name']     = $stored['file_name'];
            $data['file_original'] = $stored['file_original'];
            $data['file_size']     = $stored['file_size'];
            $data['file_mime']     = $stored['file_mime'];
        } elseif (!empty($data['remove_file'])) {
            $data['file_name']     = '';
            $data['file_original'] = '';
            $data['file_size']     = 0;
            $data['file_mime']     = '';
        } else {
            // Never let the form overwrite the stored file data.
            unset($data['file_name'], $data['file_original'], $data['file_size'], $data['file_mime']);
        }

        unset($data['remove_file']);

        $isNewFile = isset($data['file_name']);

        if (empty($data['id']) && empty($data['file_name']) && empty($data['external_url'])) {
            $this->setError(Text::_('COM_ADRIANJDOWNLOADS_ERROR_FILE_REQUIRED'));

            return false;
        }

        if (!parent::save($data)) {
            if ($isNewFile && !empty($data['file_name'])) {
                $this->deleteStoredFile($data['file_name']);
            }

            return false;
        }

        // The old file was replaced or removed.
        if ($isNewFile && $oldFile !== '' && $oldFile !== ($data['file_name'] ?? '')) {
            $this->deleteStoredFile($oldFile);
        }

        return true;
    }

    public function delete(&$pks)
    {
        $pks   = (array) $pks;
        $files = [];
        $table = $this->getTable();

        foreach ($pks as $pk) {
            if ($table->load((int) $pk) && $table->file_name) {
                $files[(int) $pk] = $table->file_name;
            }
        }

        if (!parent::delete($pks)) {
            return false;
        }

        if (ComponentHelper::getParams('com_adrianjdownloads')->get('delete_files', 1)) {
            foreach ($pks as $pk) {
                if (isset($files[(int) $pk])) {
                    $this->deleteStoredFile($files[(int) $pk]);
                }
            }
        }

        return true;
    }

    /**
     * Validates and moves an uploaded file into the storage folder.
     *
     * @return  array|null  File data or null on error (error is set).
     */
    private function storeUpload(array $upload): ?array
    {
        if (($upload['error'] ?? 0) !== UPLOAD_ERR_OK || empty($upload['tmp_name']) || !is_uploaded_file($upload['tmp_name'])) {
            $this->setError(Text::sprintf('COM_ADRIANJDOWNLOADS_ERROR_UPLOAD_FAILED', (int) ($upload['error'] ?? 0)));

            return null;
        }

        $original  = basename(str_replace('\\', '/', (string) $upload['name']));
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));

        if ($extension === '' || !\in_array($extension, StorageHelper::getAllowedExtensions(), true)) {
            $this->setError(Text::sprintf('COM_ADRIANJDOWNLOADS_ERROR_EXTENSION_NOT_ALLOWED', $extension));

            return null;
        }

        $maxMb = (float) ComponentHelper::getParams('com_adrianjdownloads')->get('max_size_mb', 0);

        if ($maxMb > 0 && $upload['size'] > $maxMb * 1024 * 1024) {
            $this->setError(Text::sprintf('COM_ADRIANJDOWNLOADS_ERROR_FILE_TOO_BIG', $maxMb));

            return null;
        }

        if (!InputFilter::isSafeFile($upload)) {
            $this->setError(Text::_('COM_ADRIANJDOWNLOADS_ERROR_UNSAFE_FILE'));

            return null;
        }

        $base = pathinfo($original, PATHINFO_FILENAME);
        $base = preg_replace('/[^A-Za-z0-9_\-]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base) ?: $base);
        $base = trim(substr((string) $base, 0, 100), '-_') ?: 'file';
        $name = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '-' . $base . '.' . $extension;
        $dest = StorageHelper::getStoragePath() . '/' . $name;

        if (!move_uploaded_file($upload['tmp_name'], $dest)) {
            $this->setError(Text::_('COM_ADRIANJDOWNLOADS_ERROR_MOVE_FAILED'));

            return null;
        }

        @chmod($dest, 0644);

        $mime = '';

        if (\function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = (string) finfo_file($finfo, $dest);
            finfo_close($finfo);
        }

        return [
            'file_name'     => $name,
            'file_original' => $original,
            'file_size'     => (int) filesize($dest),
            'file_mime'     => $mime ?: 'application/octet-stream',
        ];
    }

    private function deleteStoredFile(string $fileName): void
    {
        $path = StorageHelper::getFilePath($fileName);

        if ($path !== null) {
            @unlink($path);
        }
    }
}
