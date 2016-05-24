<?php
namespace App\Runners;

use Exception;

/**
 * Created by PhpStorm.
 * User: Bastiaan
 * Date: 17-03-2016
 * Time: 15:26
 */
class RubyToolRunner extends ToolRunner
{
    protected function getResults($tool)
    {
        $version = array_get($this->getProjectConfig($tool), 'rubocop-version', '0.35.0');
        $src = (array) array_get($this->getProjectConfig($tool), 'src', 'lib');
        $src = implode(' ', $src);

        if (!isset($version, $src)) {
            throw new Exception('Version and/or source directory not set');
        }

        exec("rbenv exec rubocop _{$version}_ $src -f json", $output, $exitCode);

        if ($exitCode == 2) {
            throw new Exception("Rubocop exited with code $exitCode");
        }

        $results = [];
        foreach ($this->jsonOutputToArray($output)['files'] as $file) {
            $offenses = $file['offenses'];
            foreach ($offenses as $offense) {
                $offenseParts = explode('/', $offense['cop_name']);
                $rule = end($offenseParts);
                $results[] = [
                        'file' => $file['path'],
                        'rule' => $rule
                    ] + array_only($offense, ['message']) + array_only($offense['location'], ['line', 'column']);
            }
        }

        return $results;
    }

    protected function hasConfigFile($tool)
    {
        return file_exists('.rubocop.yml');
    }
}
