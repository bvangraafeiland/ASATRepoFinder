<?php
namespace App;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Psr\Http\Message\StreamInterface;

class Repository extends Model
{
    public $timestamps = false;

    protected $fillable = ['id', 'full_name', 'stargazers_count', 'created_at', 'pushed_at', 'language', 'default_branch', 'has_issues', 'open_issues_count'];

    protected $dates = ['created_at', 'pushed_at'];

    /**
     * @return BelongsToMany
     */
    public function asats()
    {
        return $this->belongsToMany(AnalysisTool::class)->withPivot(['config_file_present', 'in_dev_dependencies', 'in_build_tool']);
    }

    /**
     * Get the contents of the given file in the repository
     *
     * @param $path
     *
     * @return StreamInterface|null
     * @throws ClientException
     */
    public function getFile($path)
    {
        $githubRaw = new Client([
            'base_uri' => 'http://raw.githubusercontent.com',
        ]);

        try {
            return $githubRaw->get('/' . $this->full_name . '/' . $this->default_branch . '/' . $path)->getBody();
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() == 404) {
                return null;
            } else {
                throw $e;
            }
        }
    }

    /**
     * Check if a project file contains the given substring, case insensitive
     *
     * @param $filePath
     * @param $string
     *
     * @return bool
     */
    public function fileContains($filePath, $string)
    {
        return str_contains(strtolower($this->getFile($filePath)), strtolower($string));
    }

    protected function setPushedAtAttribute($value)
    {
        $this->attributes['pushed_at'] = Carbon::parse($value)->format($this->getDateFormat());
    }

    protected function setCreatedAtAttribute($value)
    {
        $this->attributes['created_at'] = Carbon::parse($value)->format($this->getDateFormat());
    }

    protected function getNameAttribute()
    {
        $parts = explode('/', $this->full_name);
        return $parts[1];
    }
}
