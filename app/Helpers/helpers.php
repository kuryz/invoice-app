<?php

use App\Core\Session;
use App\Core\RequestContext;
use App\Services\CacheService;
use App\Models\GeneralSetting;
use Carbon\Carbon;

if (!function_exists('session')) {
    function session(): Session
    {
        return new Session();
    }
}

if (!function_exists('request')) {
    function request(): \App\Core\Request
    {
        return RequestContext::get();
    }
}

if (!function_exists('auth')) {
    function auth(?string $guard = null): \App\Core\AuthManager
    {
        return new \App\Core\AuthManager($guard);
    }
}

if (!function_exists('selected')) {
    function selected($value=null, $selected=null)
    {
        if ($value == null) {
            return '';
        }
        return $value == $selected ? 'selected' : '';
    }
}

if (!function_exists('cache')) {
    function cache(): CacheService
    {
        return new CacheService();
    }
}

if(!function_exists('esc')){
    function esc($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

if(!function_exists('money')){
    function money($c, $v) {
        return $c . ' ' . number_format((float)$v, 2);
    }
}

if(!function_exists('time_elapsed_string')){
    function time_elapsed_string($datetime, $full = false) {
        $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $weeks = floor($diff->d / 7);
    $days = $diff->d - ($weeks * 7);

    $string = array(
        'y' => ['value' => $diff->y, 'label' => 'year'],
        'm' => ['value' => $diff->m, 'label' => 'month'],
        'w' => ['value' => $weeks, 'label' => 'week'],
        'd' => ['value' => $days, 'label' => 'day'],
        'h' => ['value' => $diff->h, 'label' => 'hour'],
        'i' => ['value' => $diff->i, 'label' => 'minute'],
        's' => ['value' => $diff->s, 'label' => 'second'],
    );

    foreach ($string as $k => &$v) {
        if ($v['value']) {
            $v = $v['value'] . ' ' . $v['label']
                . ($v['value'] > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) {
        $string = array_slice($string, 0, 1);
    }

    return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
}

if (!function_exists('showDateTime')) {
    function showDateTime($date, $format = 'Y-m-d h:i A')
    {
        $lang = session()->get('lang', 'en');
        Carbon::setLocale($lang);
        return Carbon::parse($date)->translatedFormat($format);
    }
}


if (!function_exists('urlPath')) {
    function urlPath($routeName, $routeParam = null)
    {
        // Since we don't have route() helper, we'll build a simple URL by route name.
        // For now, return a placeholder.
        $basePath = '/';
        $url = '/' . $routeName;
        if ($routeParam) {
            $url .= '/' . $routeParam;
        }
        $path = str_replace($basePath, '', $url);
        return $path;
    }
}

if (!function_exists('showMobileNumber')) {
    function showMobileNumber($number)
    {
        $length = strlen($number);
        return substr_replace($number, '***', 2, $length - 4);
    }
}

if (!function_exists('showEmailAddress')) {
    function showEmailAddress($email)
    {
        $endPosition = strpos($email, '@') - 1;
        return substr_replace($email, '***', 1, $endPosition);
    }
}

if (!function_exists('getRealIP')) {
    function getRealIP()
    {
        $ip = $_SERVER["REMOTE_ADDR"] ?? '127.0.0.1';
        //Deep detect ip
        if (filter_var(@$_SERVER['HTTP_FORWARDED'], FILTER_VALIDATE_IP)) {
            $ip = $_SERVER['HTTP_FORWARDED'];
        }
        if (filter_var(@$_SERVER['HTTP_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
            $ip = $_SERVER['HTTP_FORWARDED_FOR'];
        }
        if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        if (filter_var(@$_SERVER['HTTP_X_REAL_IP'], FILTER_VALIDATE_IP)) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        }
        if (filter_var(@$_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        if ($ip == '::1') {
            $ip = '127.0.0.1';
        }
        return $ip;
    }
}

if (!function_exists('appendQuery')) {
    function appendQuery($key, $value)
    {
        return request()->fullUrlWithQuery([$key => $value]);
    }
}

if (!function_exists('dateSort')) {
    function dateSort($a, $b)
    {
        return strtotime($a) - strtotime($b);
    }
}

if (!function_exists('dateSorting')) {
    function dateSorting($arr)
    {
        usort($arr, "dateSort");
        return $arr;
    }
}

if (!function_exists('gs')) {
    function gs()
    {
        $cache = cache();
        $general = $cache->get('GeneralSetting');
        if (!$general) {
            $general = GeneralSetting::first();
            if (!$general) {
                // Return default settings
                $general = [
                    'site_name' => 'PayShield',
                    'site_title' => 'escrow Platform',
                    'currency' => 'USD',
                    'currency_symbol' => '$',
                    'cur_rate' => 1,
                    'balance_transfer' => 1,
                    'balance_transfer_min' => 1,
                    'balance_transfer_max' => 1000,
                    'balance_transfer_fixed_charge' => 0,
                    'balance_transfer_per_charge' => 0,
                    'active_template' => 'basic',
                    'timezone' => 'UTC',
                ];
            }
            if ($general) {
                $cache->set('GeneralSetting', $general, 3600);
            }
        }
        return $general;
    }
}

// Additional helper for __() translation placeholder
if (!function_exists('__')) {
    function __($string)
    {
        return $string; // Can be extended with translation logic
    }
}

if (!function_exists('getImage')) {
    function getImage($path, $size = null, $avatar=false)
    {
        if ($avatar) {
            return $_ENV['APP_URL'].'/assets/images/default-member.png';
        }
        if ($size) {
            // return route('placeholder.image', $size);
        }
        // return asset('assets/images/default.png');
        return '/' . ltrim($path, '/');
    }
}


if (!function_exists('slug')) {
    function slug($string) {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    }
}

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}

if (!function_exists('config')) {
    function config(string $key, $default = null)
    {
        static $config = null;
        
        if ($config === null) {
            $config = [];
            $configFiles = glob(__DIR__ . '/../../config/*.php');
            foreach ($configFiles as $file) {
                $name = basename($file, '.php');
                $config[$name] = require $file;
            }
        }
        
        $keys = explode('.', $key);
        $value = $config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
}

if (!function_exists('keyToTitle')) {
    function keyToTitle(?string $key): string
    {
        if (empty($key)) {
            return '';
        }
        return ucwords(str_replace('_', ' ', $key));
    }
}

if(!function_exists('diffForHumans')){
    function diffForHumans($date)
    {
        $lang = session()->get('lang', 'en');
        \Carbon\Carbon::setlocale($lang);
        return \Carbon\Carbon::parse($date)->diffForHumans();
    }
}

if(!function_exists('removeElement')){
    function removeElement($array, $value)
    {
        return array_diff($array, (is_array($value) ? $value : array($value)));
    }
}
if(!function_exists('cryptoQR')){
    function cryptoQR($wallet)
    {
        // return "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=$wallet&choe=UTF-8";
        return "https://quickchart.io/qr?text=$wallet&size=200";
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim($_ENV['APP_URL'] ?? '', '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('titleToKey')) {
    function titleToKey($text)
    {
        return strtolower(str_replace(' ', '_', $text));
    }
}

if (!function_exists('strLimit')) {
    function strLimit($title = null, $length = 10)
    {
        // return Str::limit($title, $length);
    }
}

if (!function_exists('apiPost')) {
    /**
     * Send a POST request to an API.
     *
     * @param string $url
     * @param array  $data
     * @param array  $headers
     * @param int    $timeout
     * @return array
     */
    function apiPost(string $url,array $data = [],array $headers = [],int $timeout = 30): array {
        $curl = curl_init();

        $defaultHeaders = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => array_merge($defaultHeaders, $headers),
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        return [
            'success' => empty($error) && $status >= 200 && $status < 300,
            'status'  => $status,
            'body'    => $body,
            'json'    => json_decode($body, true),
            'error'   => $error,
        ];
    }
}

if (!function_exists('logger')) {
    function logger(): \App\Services\Logger
    {
        static $logger = null;
        if ($logger === null) {
            $logger = new \App\Services\Logger();
        }
        return $logger;
    }
}

if (!function_exists('array_snake_case')) {
    function array_snake_case(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $snakeKey = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $key));
            $result[$snakeKey] = is_array($value) ? array_snake_case($value) : $value;
        }
        return $result;
    }
}

if (!function_exists('getPaginate')) {
    function getPaginate($perPage = 20, $page = null) {
        $page = $page ?: (int) ($_GET['page'] ?? 1);
        return ['per_page' => $perPage, 'page' => $page];
    }
}

if (!function_exists('paginateLinks')) {
    function paginateLinks(array $pagination, string $baseUrl = ''): string
    {
        $current = max(1, (int)($pagination['current_page'] ?? 1));
        $last    = max(1, (int)($pagination['last_page'] ?? 1));
        if ($last <= 1) {
            return '';
        }
        if ($baseUrl === '') {
            $baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
        }
        $query = $_GET;
        $html = '<nav><ul class="pagination justify-content-center">';
        // Previous
        if ($current > 1) {
            $query['page'] = $current - 1;
            $html .= '<li class="page-item">
                        <a class="page-link" href="' . $baseUrl . '?' . http_build_query($query) . '">&laquo;</a>
                      </li>';
        } else {
            $html .= '<li class="page-item disabled">
                        <span class="page-link">&laquo;</span>
                      </li>';
        }
    
        $window = 2;
    
        $start = max(1, $current - $window);
        $end   = min($last, $current + $window);
    
        // Shift window near beginning
        if ($current <= $window + 2) {
            $end = min($last, 1 + ($window * 2));
        }
    
        // Shift window near end
        if ($current >= $last - ($window + 1)) {
            $start = max(1, $last - ($window * 2));
        }
    
        // First page
        if ($start > 1) {
            $query['page'] = 1;
            $html .= '<li class="page-item">
                        <a class="page-link" href="' . $baseUrl . '?' . http_build_query($query) . '">1</a>
                      </li>';
    
            if ($start > 2) {
                $html .= '<li class="page-item disabled">
                            <span class="page-link">...</span>
                          </li>';
            }
        }
    
        // Window
        for ($i = $start; $i <= $end; $i++) {
    
            $query['page'] = $i;
    
            $html .= '<li class="page-item' . ($i == $current ? ' active' : '') . '">
                        <a class="page-link" href="' . $baseUrl . '?' . http_build_query($query) . '">' . $i . '</a>
                      </li>';
        }
    
        // Last page
        if ($end < $last) {
    
            if ($end < $last - 1) {
                $html .= '<li class="page-item disabled">
                            <span class="page-link">...</span>
                          </li>';
            }
    
            $query['page'] = $last;
    
            $html .= '<li class="page-item">
                        <a class="page-link" href="' . $baseUrl . '?' . http_build_query($query) . '">' . $last . '</a>
                      </li>';
        }
    
        // Next
        if ($current < $last) {
            $query['page'] = $current + 1;
            $html .= '<li class="page-item">
                        <a class="page-link" href="' . $baseUrl . '?' . http_build_query($query) . '">&raquo;</a>
                      </li>';
        } else {
            $html .= '<li class="page-item disabled">
                        <span class="page-link">&raquo;</span>
                      </li>';
        }
    
        $html .= '</ul></nav>';
        return $html;
    }
}

if (!function_exists('getTrx')) {
    function getTrx($length = 12) {
        return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, $length));
    }
}

if (!function_exists('showAmount')) {
    function showAmount($amount, $decimal = 2) {
        return number_format((float)$amount, $decimal);
    }
}

if(!function_exists('showAmount')){
    function showAmount($amount, $decimal = 2, $currency = '') {
        return number_format((float)$amount, $decimal) . ' ' . $currency;
    }
}

if (!function_exists('menuActive')) {
    function menuActive($routeName, $activeClass = 'active') {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        if (is_array($routeName)) {
            foreach ($routeName as $route) {
                if (strpos($path, $route) !== false) return $activeClass;
            }
        } else {
            if (strpos($path, $routeName) !== false) return $activeClass;
        }
        return '';
    }
}

if (!function_exists('asset')) {
    function asset($path) {
        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('old')) {
    function old($key, $default = '') {
        return $default;
    }
}

if (!function_exists('getAmount')) {
    function getAmount($amount) {
        return number_format($amount, 2);
    }
}

if(!function_exists('currentRoute')){
    function currentRoute($name) {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        return strpos($path, $name) !== false;
    }
}


if(!function_exists('fileUploader')){
    function fileUploader($file, $location, $size = null, $old = null, $thumb = null)
    {
        $fileManager = new \App\Repositories\FileManagerRepository($file);
        $fileManager->path = $location;
        $fileManager->size = $size;
        $fileManager->old = $old;
        $fileManager->thumb = $thumb;

        return $fileManager->upload();
    }
}
if (!function_exists('fileManager')) {

    function fileManager()
    {
        return new \App\Repositories\FileManagerRepository();
    }
}

if (!function_exists('getFileSize')) {
    function getFileSize($key)
    {
        return fileManager($key)->size;
    }
}

if (!function_exists('getFileExt')) {
    function getFileExt($key)
    {
        return fileManager($key)->extensions;
    }
}

if (!function_exists('array_camel_case')) {
    function array_camel_case(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $camelKey = lcfirst(str_replace('_', '', ucwords($key, '_')));
            $result[$camelKey] = is_array($value) ? array_camel_case($value) : $value;
        }
        return $result;
    }
}
if (!function_exists('generateUuid')) {
    function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(
            ord($data[6]) & 0x0f | 0x40
        );
        $data[8] = chr(
            ord($data[8]) & 0x3f | 0x80
        );
        return vsprintf(
            '%s%s-%s-%s-%s-%s%s%s',
            str_split(bin2hex($data), 4)
        );
    }
}