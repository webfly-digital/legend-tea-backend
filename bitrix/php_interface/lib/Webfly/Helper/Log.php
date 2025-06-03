<?php

namespace Webfly\Helper;

/**
 * CB24Log
 */

/**
 * Логирование отладочной информации
 *
 * Пример использования:
 * \CB24Log::Add('string message'.print_r($someVar, true));
 * Для очистки лога:
 * \CB24Log::Add('some required text', true);
 * @static
 */
class Log
{


    /**
     * Запись в лог
     * @param string $msg текст, который будет записан в лог
     * @param boolean $clean если установлено - очистит лог-файл
     */
    public static function Add($msg, $clean = false, $error = false, $nameDir = '')
    {
        if (!empty($nameDir)) $DIR = $_SERVER["DOCUMENT_ROOT"] . '/bitrix/logs/' . $nameDir;
        else  $DIR = $_SERVER["DOCUMENT_ROOT"] . '/bitrix/logs/logs';


        if ($error) $DIR = $_SERVER["DOCUMENT_ROOT"] . '/bitrix/logs/logs_errors';

        if ($DIR && is_string($msg)) {
            $DATE = date('Y-m-d H:i:s');
            $strLogFile = date('Y-m-d') . ".log";
            $strCalledFrom = '';
            if (function_exists('debug_backtrace')) {
                $locations = debug_backtrace();
                $strCalledFrom = 'F: ' . $locations[0]['file'] . "\n" . '      L: ' . $locations[0]['line'];
            }

            $logMsg = "\n" .
                'date: ' . $DATE . "\n" .
                'mess: ' . $msg . "\n" .
                'from: ' . $strCalledFrom . "\n" .
                'uri : ' . $_SERVER['REQUEST_URI'] . "\n" .
                '----------------------------------------------------------';
            if ($clean) {
                self::CleanLog($DIR . '/' . $strLogFile);
            }
            self::createWritableFolder($DIR . '/');
            self::AppendLog($logMsg, $DIR . '/' . $strLogFile);
        }
    }

    /**
     * Функция добавления новой записи в лог
     * @param string $msg текст записи
     */
    private
    static function AppendLog($msg, $filename)
    {
        $log_file = $filename;
        $mode = 'ab';

        if (!file_exists($log_file)) $mode = 'x';

        if ($fp = fopen($log_file, $mode)) {
            fwrite($fp, $msg);
            fclose($fp);
        }
    }

    /**
     * Удаление лог-файла
     */
    private
    static function CleanLog($filename)
    {
        @unlink($filename);
    }

    /**
     * Удаление лог файлов
     * @param $dateMinus
     * @param $dir
     * @throws \Exception
     */
    public
    static function CleanLogFiles($dateMinus, $dir)
    {
        if ($dir && $dateMinus) {
            $dateMinus = (int)$dateMinus;
            $date = new \DateTime();
            $date->sub(new \DateInterval("P{$dateMinus}D"));
            $dateMin = $date->format('Y-m-d');

            $folder = $dir . '/';
            $files = array_diff(scandir($folder), array('.', '..'));

            foreach ($files as $file) {
                if ($file == '.htaccess') continue;
                $fileDate = str_replace('.log', '', $file);
                if ($fileDate < $dateMin)
                    @unlink("$folder/$file");
            }
        }
    }

    /*public static function GetLog($filename) {
        $log = false;
        if ($fp = fopen(self::$dir.'/'.$filename, 'rb')) {
            $log = fread($fp);
            fclose($fp);
        }
        return $log;
    }*/
    public static function createWritableFolder($folder)
    {

        if (file_exists($folder)) {
            // Folder exist.
            return is_writable($folder);
        }
        // Folder not exit, check parent folder.
        $folderParent = dirname($folder);
        if ($folderParent != '.' && $folderParent != '/') {
            if (!self::createWritableFolder(dirname($folder))) {
                // Failed to create folder parent.
                return false;
            }
            // Folder parent created.
        }

        if (is_writable($folderParent)) {
            // Folder parent is writable.
            if (mkdir($folder, 0777, true)) {
                // Folder created.
                return true;
            }
            // Failed to create folder.
        }

        // Folder parent is not writable.
        return false;
    }
}



