<?php
/**
 *  @author nguyenhongphat0 <nguyenhongphat28121998@gmail.com>
 *  @copyright 2018 nguyenhongphat0
 *  @license https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0
 */

include_once('../../config/config.inc.php');
include_once('../../init.php');

/**
 * Handle all ajax calls in module
 */
class DeveloperPackAjax
{
    public function __construct()
    {
        // Check if module is enable and user have permission
        $cookie = new Cookie('psAdmin');
        $is_admin = $cookie->id_employee;
        $is_enabled = Module::isEnabled('developerpack');
        if (!$is_admin || !$is_enabled) {
            $this->end('Access dinied', 403);
        }
        // Call to action
        $action = Tools::getValue('action');
        if (method_exists($this, $action)) {
            $this->$action();
        } else {
            $this->end('Action not found', 404);
        }
    }

    public function end($data, $code = 200)
    {
        header('Content-Type: application/json');
        if (function_exists('http_response_code')) {
            http_response_code($code);
        }
        die(json_encode($data));
    }

    public function phpinforaw()
    {
        phpinfo();
        die();
    }

    public function phpinfo()
    {
        ob_start();
        phpinfo(INFO_MODULES);
        $s = ob_get_contents();
        ob_end_clean();
        $s = strip_tags($s, '<h2><th><td>');
        $s = preg_replace('/<th[^>]*>([^<]+)<\/th>/', '<info>\1</info>', $s);
        $s = preg_replace('/<td[^>]*>([^<]+)<\/td>/', '<info>\1</info>', $s);
        $t = preg_split('/(<h2[^>]*>[^<]+<\/h2>)/', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
        $r = array();
        $count = count($t);
        $p1 = '<info>([^<]+)<\/info>';
        $p2 = '/'.$p1.'\s*'.$p1.'\s*'.$p1.'/';
        $p3 = '/'.$p1.'\s*'.$p1.'/';
        for ($i = 1; $i < $count; $i++) {
            if (preg_match('/<h2[^>]*>([^<]+)<\/h2>/', $t[$i], $matchs)) {
                $name = trim($matchs[1]);
                $vals = explode("\n", $t[$i + 1]);
                foreach ($vals as $val) {
                    if (preg_match($p2, $val, $matchs)) { // 3cols
                        $r[$name][trim($matchs[1])] = array(trim($matchs[2]), trim($matchs[3]));
                    } elseif (preg_match($p3, $val, $matchs)) { // 2cols
                        $r[$name][trim($matchs[1])] = trim($matchs[2]);
                    }
                }
            }
        }
        $this->end($r);
    }

    private function listFiles($path)
    {
        $project = realpath($path);
        $directory = new RecursiveDirectoryIterator($project);
        $files = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::LEAVES_ONLY);
        return $files;
    }

    private function archive($regex, $output, $maxsize, $timeout)
    {
        // Extend excecute limit
        if (Tools::getIsset($timeout)) {
            set_time_limit($timeout);
        }

        // Get files in directory
        $project = realpath('../..');
        $files = $this->listFiles($project);

        // Initialize archive object
        $zip = new ZipArchive();
        $zip->open($output, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $name => $file) {
            // Skip directories (they would be added automatically)
            $ok = (preg_match($regex, $name)) && (!$file->isDir()) && ($file->getSize() < $maxsize);
            if ($ok) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = Tools::substr($filePath, Tools::strlen($project) + 1);

                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            }
        }

        // Zip archive will be created only after closing object
        $zip->close();
    }

    private function escape($path)
    {
        $project = realpath('../..');
        if (Tools::substr($path, 0, 1) === "/") {
            $path = $project.$path;
        }
        $path = str_replace('.', '\.', $path);
        $path = str_replace('/', '\/', $path);
        return($path);
    }

    private function implodeOptions($options)
    {
        $options = array_map(array($this, 'escape'), $options);
        $regex = implode('|', $options);
        return $regex;
    }

    private function includeFiles($includes)
    {
        $regex = $this->implodeOptions($includes);
        $regex = '/^.*('.$regex.').*$/i';
        return $regex;
    }

    private function excludeFiles($excludes)
    {
        $regex = $this->implodeOptions($excludes);
        $regex = '/^((?!'.$regex.').)*$/i';
        return $regex;
    }

    public function zip()
    {
        $files = Tools::getValue('files');
        $timeout = Tools::getValue('timeout');
        if (Tools::getIsset(Tools::getValue('maxsize'))) {
            $maxsize = Tools::getValue('maxsize');
        } else {
            $maxsize = 1000000;
        }
        $empty = empty($files);
        if ($empty) {
            $this->end('Not enough parameters', 404);
        }
        foreach ($files as $file) {
            if ($file === '') {
                $this->end('Empty rules are not allowed', 404);
            }
        }
        $rules = Tools::getValue('rule');
        switch ($rules) {
            case 'include':
                $regex = $this->includeFiles($files);
                break;

            case 'exclude':
                $regex = $this->excludeFiles($files);
                break;

            default:
                $this->end('Invalid rule', 404);
                break;
        }
        mkdir('zip');
        $output = 'zip/'.Tools::getValue('output');
        $this->archive($regex, $output, $maxsize, $timeout);
        $this->end(Tools::getValue('output'));
    }

    private function humanFileSize($size, $unit = "")
    {
        if ((!$unit && $size >= 1<<30) || $unit == "GB") {
            return number_format($size/(1<<30), 2)." GB";
        }
        if ((!$unit && $size >= 1<<20) || $unit == "MB") {
            return number_format($size/(1<<20), 2)." MB";
        }
        if ((!$unit && $size >= 1<<10) || $unit == "KB") {
            return number_format($size/(1<<10), 2)." KB";
        }
        return number_format($size)." bytes";
    }

    public function zipped()
    {
        $files = array_diff(scandir(realpath('zip')), array('.', '..'));
        $res = array();
        foreach ($files as $file) {
            $res[] = array(
                'name' => $file,
                'size' => $this->humanFileSize(filesize('zip/'.$file))
            );
        }
        $this->end($res);
    }

    public function dearchive()
    {
        $file = Tools::getValue('file');
        if (strpos($file, '..') !== false) {
            $this->end('Invalid file name');
        }
        $file = realpath('zip').'/'.$file;
        if (file_exists($file)) {
            unlink($file);
            $this->end("File $file deleted successfully");
        } else {
            $this->end("File not found");
        }
    }

    public function analize()
    {
        $start = microtime(true);
        $project = realpath('../..');
        $files = $this->listFiles($project);
        $size = $d = 0;
        foreach ($files as $file) {
            $size += $file->getSize();
            $d++;
        }
        $this->end(array(
            'total' => $d,
            'size' => $this->humanFileSize($size),
            'execution_time' => (microtime(true) - $start).'s'
        ));
    }

    public function open()
    {
        $project = realpath('../..');
        $filename = Tools::getValue('file');
        $file = $project.'/'.$filename;
        $res = array(
            'status' => 404,
            'message' => 'List directory success'
        );
        if ($filename !== '' && is_file($file)) {
            $file = $project.'/'.Tools::getValue('file');
            $res['content'] = Tools::file_get_contents($file);
            $res['status'] = 200;
            $res['message'] = 'OK';
        }
        if (!is_dir($file)) {
            $file = dirname($file);
            if ($res['status'] != 200) {
                $res['message'] = 'File or directory not found';
            }
        } else {
            $res['status'] = 204;
        }
        $res['pwd'] = $file;
        $ls = scandir($file);
        $res['ls'] = $ls;
        $this->end($res);
    }

    public function save()
    {
        $project = realpath('../..');
        $filename = Tools::getValue('file');
        $content = Tools::getValue('content');
        $file = $project.'/'.$filename;
        if ($filename !== '' && is_file($file)) {
            file_put_contents($file, $content);
            $res = array(
                'status' => 200,
                'message' => 'File saved successfully!'
            );
        } elseif ($filename !== '' && !is_dir($file)) {
            file_put_contents($file, $content);
            $res = array(
                'status' => 200,
                'message' => 'File created successfully!'
            );
        } else {
            $res = array(
                'status' => 404,
                'message' => 'Error saving file!'
            );
        }
        $this->end($res);
    }

    public function delete()
    {
        $project = realpath('../..');
        $filename = Tools::getValue('file');
        $file = $project.'/'.$filename;
        if ($filename !== '' && is_file($file)) {
            unlink($file);
            $res = array(
                'status' => 200,
                'message' => 'File deleted successfully!'
            );
        } else {
            $res = array(
                'status' => 404,
                'message' => 'Nothing has been deleted!'
            );
        }
        $this->end($res);
    }

    public function test()
    {
        $res = array(
            'url' => _PS_BASE_URL_.__PS_BASE_URI__
        );
        $this->end($res);
    }
}

new DeveloperPackAjax();
