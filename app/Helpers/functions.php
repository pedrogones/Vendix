<?php
if (!function_exists('getPdfDefaultImage')) {
    function getPdfDefaultImage(): string|\Illuminate\Contracts\Routing\UrlGenerator
    {
        return url('assets/pdf-file.png');
    }
}
if (!function_exists('getDefaultNoFile')) {
    function getDefaultNoFile(): string|\Illuminate\Contracts\Routing\UrlGenerator
    {
        return url('assets/no-file.png');
    }
}
if (!function_exists('getUserDefaultAvatar')) {
    function getUserDefaultAvatar(): string|\Illuminate\Contracts\Routing\UrlGenerator
    {
        return url('assets/user_default.png');
    }
}
