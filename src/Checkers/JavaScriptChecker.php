<?php
namespace App\Checkers;

/**
 * Created by PhpStorm.
 * User: Bastiaan
 * Date: 04-03-2016
 * Time: 14:23
 */
class JavaScriptChecker extends ProjectChecker
{
    protected $buildFiles = [];
    protected $packageArray;
    protected $dependenciesJSON;

    public static $configFileNames = [
        'eslint' => ['.eslintrc', '.eslintrc.js', '.eslintrc.json', '.eslintrc.yml', '.eslintrc.yaml'],
        'jshint' => ['.jshintrc'],
        'jscs' => ['.jscsrc']
    ];

    public function doLanguageSpecificProcessing()
    {
        $packageContent = $this->project->getFile('package.json');
        $this->packageArray = json_decode($packageContent, true);
        $this->dependenciesJSON = $this->getCombinedDependenciesJSON();

        $this->buildFiles['npm-scripts'] = json_encode(array_get($this->packageArray, 'scripts', []));

        if ($this->project->usesBuildTool('grunt')) {
            $this->buildFiles['grunt'] = $this->project->getFile('Gruntfile.js');
        }
        if ($this->project->usesBuildTool('gulp')) {
            $this->buildFiles['gulp'] = $this->project->getFile('gulpfile.js');
        }
        if ($this->project->usesBuildTool('make')) {
            $this->buildFiles['make'] = $this->project->getFile('Makefile');
        }

        $jshint = $this->checkJSHint();
        $jscs = $this->checkJSCS();
        $eslint = $this->checkESLint();

        return $jshint || $jscs || $eslint;
    }

    protected function checkJSHint()
    {
        $jshintConfigFile = array_intersect(static::$configFileNames['jshint'], $this->projectRootFiles) || array_has($this->packageArray, 'jshintConfig');
        $jshintDependency = str_contains($this->dependenciesJSON, 'jshint');
        $jshintBuildTask = $this->buildFilesContain('jshint');

        return $this->attachASAT('jshint', $jshintConfigFile, $jshintDependency, $jshintBuildTask);
    }

    protected function checkJSCS()
    {
        $jscsConfigFile = array_intersect(static::$configFileNames['jscs'], $this->projectRootFiles) || array_has($this->packageArray, 'jscsConfig');
        $jscsDependency = str_contains($this->dependenciesJSON, 'jscs');
        $jscsBuildTask = $this->buildFilesContain('jscs');

        return $this->attachASAT('jscs', $jscsConfigFile, $jscsDependency, $jscsBuildTask);
    }

    protected function checkESLint()
    {
        $eslintConfigFile = (bool) array_intersect(static::$configFileNames['eslint'], $this->projectRootFiles) || array_has($this->packageArray, 'eslintConfig');
        $eslintDependency = str_contains($this->dependenciesJSON, 'eslint');
        $eslintBuildTask = $this->buildFilesContain('eslint');

        return $this->attachASAT('eslint', $eslintConfigFile, $eslintDependency, $eslintBuildTask);
    }

    protected function getCombinedDependenciesJSON()
    {
        $dependencies = array_get($this->packageArray, 'dependencies', []);
        $devDependencies = array_get($this->packageArray, 'devDependencies', []);
        $optionalDependencies = array_get($this->packageArray, 'optionalDependencies', []);
        return json_encode($dependencies) . json_encode($devDependencies) . json_encode($optionalDependencies);
    }

    protected function buildFilesContain($string)
    {
        return (bool) array_first($this->buildFiles, function($key, $value) use ($string) {
            $term = $key == 'gulp' ? "gulp-$string" : $string;
            return codeContains($value, $term);
        });
    }

    protected function getBuildTools()
    {
        return [
            'grunt' => 'Gruntfile.js',
            'gulp' => 'gulpfile.js',
            'make' => 'Makefile'
        ];
    }
}
