<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

function validateLogin()
{

    $is_login = true;
    $url = '';
    $data = array();
    $data['is_login'] = $is_login;
    $data['url'] = $url;

    return $data;
}
function validateAdmin()
{
    // Purchase code validation removed - always allow admin access
}

if (! function_exists('replaceNullWithEmptyStringRecursive')) {
    function replaceNullWithEmptyStringRecursive($array)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = replaceNullWithEmptyStringRecursive($value); // Recursively call the function for nested arrays
            } else {
                $value = $value ?? ''; // Replace null with an empty string
            }
        }
        return $array;
    }
}

if (! function_exists('replaceNullWithEmptyStringRecursive')) {
    function replaceNullWithEmptyStringRecursive($collection)
    {
        return $collection->transform(function ($item) {
            if ($item instanceof \Illuminate\Support\Collection) {
                return replaceNullWithEmptyStringRecursive($item); // Recursively call the function for nested collection
            }
            return $item ?? ''; // Replace null with an empty string
        });
    }
}

function isInstalled()
{
    if (!file_exists(storage_path('installed'))) {
        return false;
    }
    return true;
}

function isDemoMode()
{
    $isDemoMode = env('DEMO_MODE');
    return $isDemoMode;
}

function isDevMode()
{
    return env('DEV_MODE');
}

function currentVersion()
{
    $versionFilePath = base_path('version.txt');
    $version = file_get_contents($versionFilePath);
    $version = trim($version);
    if ($version == "") {
        $version = "3.0.2.1";
    }
    return $version;
}
function fixVersion()
{
    $versionFilePath = base_path('version.txt');
    $currentVersion = currentVersion();
    file_put_contents($versionFilePath, $currentVersion);
}
