<?php
namespace App\Export;

use Exception;

/**
 * Created by PhpStorm.
 * User: Bastiaan
 * Date: 06-05-2016
 * Time: 11:56
 */
abstract class DataExporter
{
    protected abstract function getFileHeaders();
    protected abstract function getItems();
    protected abstract function getFileName();

    public function export()
    {
        $this->writeToCSV();
    }
    
    protected function writeToCSV()
    {
        list($fileName, $data, $headers) = [$this->getFileName(), $this->getItems(), $this->getFileHeaders()];
        $location = PROJECT_DIR .  "/results/$fileName.csv";

        $dirname = dirname($location);
        if (!is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }

        $file = fopen($location, 'w');
        if (!$file) {
            throw new Exception('File could not be opened for writing');
        }
        if ($headers) {
            fputcsv($file, $headers);
        }
        foreach ($data as $item) {
            fputcsv($file, (array) $item);
        }
        fclose($file);
    }
}
