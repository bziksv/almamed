<?php

class shopIcPluginBackendCleanController extends waJsonController
{
    protected $public_path;
    protected $protected_path;
    public function __construct()
    {
        $this->public_path = wa()->getDataPath('products', true, 'shop');
        $this->protected_path = wa()->getDataPath('products', false, 'shop');
    }

    public function execute()
    {
        set_time_limit(0);

        ini_set('memory_limit', '2048M');

        $file = waRequest::file('csv_file');

        if ($file->extension !== 'csv') {
            $this->errors = 'Недопустимый тип файла. Пожалуйста, загрузите CSV-файл.';
            return;
        }

        try {
            $protected_files = new shopIcRecursiveImageFinder($this->protected_path);

            $CSVReader = new shopIcCsvReader($file->tmp_name);
            $compare = new shopIcUnusedFilesComparer($CSVReader);

            $delete_files = $compare->getToDeleteFiles($protected_files->getAllImagesPath());

            if ($protected_files->getCount() == count($delete_files)) {
                $this->errors = 'Ошибка: Похоже, что CSV-файл пуст или содержит некорректные данные, потому что вы пытаетесь удалить все изображения. Отмена операции удаления.';
                return;
            }

            waLog::delete('ic.log');
            waLog::dump($delete_files, 'ic.log');

            $deleter = new shopIcFileDeleter();
            $deleter->deleteList($delete_files);
            $deleter->deleteSubDirs($this->public_path);

        } catch (Exception $e) {
            $this->errors = 'Ошибка при чтении CSV-файла: ' . $e->getMessage();
            return;
        }

        $this->response = [
            'message' => 'Delete completed successfully. Deleted ' . count($delete_files) . ' files.',
        ];
    }
}
