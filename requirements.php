<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
if(isset($_SESSION['pass_req'])) {
    $pass = $_SESSION['pass_req'];
} else {
    $pass = $_POST['pass_req'];
}
if ($pass == "svt") {
    $_SESSION['pass_req'] = $pass;
} else { ?>
    <form method="POST" action="requirements.php">
        Password <input type="password" name="pass_req" />
        <input type="submit" name="submit" value="Go" />
    </form>
    <?php
    exit;
}

class PhpPsInfo
{
    protected $login;
    protected $password;

    const DEFAULT_PASSWORD = 'svt';
    const DEFAULT_LOGIN = 'svt';

    const TYPE_OK = true;
    const TYPE_ERROR = false;
    const TYPE_WARNING = null;

    protected $requirements = [
        'versions' => [
            'php' => '7.1',
            'mysql' => '7.1',
        ],
        'extensions' => [
            'curl' => true,
            'fileinfo' => false,
            'gd' => true,
            'iconv' => false,
            'imagick' => true,
            'json' => false,
            'mbstring' => false,
            'openssl' => true,
            'pdo_mysql' => true,
            'xml' => false,
            'simplexml' => false,
            'zip' => true,
            'gettext' => false,
        ],
        'packages' => [
            'python3' => false,
            'python3-pil' => false,
            'python3-numpy' => false,
            'python3-pip' => false,
            'hugin-tools' => false,
            'pyshtools' => false,
            'ruby-fastimage' => false
        ],
        'config' => [
            'allow_url_fopen' => true,
            'file_uploads' => true,
            'max_input_vars' => false,
            'memory_limit' => '128M',
            'post_max_size' => false,
            'set_time_limit' => false,
            'upload_max_filesize' => false,
            'max_execution_time' => 60,
            'max_input_time' => 60,
            'shell_exec' => false,
            'session' => true
        ],
        'directories' => [
            'config' => 'config',
            'backend/assets' => 'backend/assets',
            'backend/css' => 'backend/css',
            'backend/js' => 'backend/js',
            'backend/header' => 'backend/header',
            'backend/tmp_panoramas' => 'backend/tmp_panoramas',
            'favicons' => 'favicons',
            'services/export_tmp' => 'services/export_tmp',
            'services/import_tmp' => 'services/import_tmp',
            'video' => 'video',
            'video360' => 'video360',
            'viewer/content' => 'viewer/content',
            'viewer/content/thumb' => 'viewer/content/thumb',
            'viewer/css' => 'viewer/css',
            'viewer/js' => 'viewer/js',
            'viewer/header' => 'viewer/header',
            'viewer/gallery' => 'viewer/gallery',
            'viewer/icons' => 'viewer/icons',
            'viewer/media' => 'viewer/media',
            'viewer/maps' => 'viewer/maps',
            'viewer/objects360' => 'viewer/objects360',
            'viewer/panoramas' => 'viewer/panoramas',
            'viewer/panoramas/lowres' => 'viewer/panoramas/lowres',
            'viewer/panoramas/mobile' => 'viewer/panoramas/mobile',
            'viewer/panoramas/multires' => 'viewer/panoramas/multires',
            'viewer/panoramas/original' => 'viewer/panoramas/original',
            'viewer/panoramas/preview' => 'viewer/panoramas/preview',
            'viewer/panoramas/thumb' => 'viewer/panoramas/thumb',
            'viewer/panoramas/thumb_custom' => 'viewer/panoramas/thumb_custom',
            'viewer/products' => 'viewer/products',
            'viewer/videos' => 'viewer/videos',
        ],
        'files' => [
            'services/ffmpeg' => 'services/ffmpeg',
            'services/generate.py' => 'services/generate.py',
            'services/slideshow.rb' => 'services/slideshow.rb',
        ],
        'apache_modules' => [
            'mod_deflate' => false,
            'mod_expires' => false,
            'mod_filter' => false,
            'mod_headers' => true,
            'mod_rewrite' => false,
        ],
    ];

    protected $recommended = [
        'versions' => [
            'php' => '8.1',
            'mysql' => '8.1',
        ],
        'extensions' => [
            'curl' => true,
            'fileinfo' => true,
            'gd' => true,
            'imagick' => true,
            'json' => true,
            'mbstring' => true,
            'openssl' => true,
            'pdo_mysql' => true,
            'xml' => true,
            'simplexml' => true,
            'zip' => true,
            'gettext' => true,
        ],
        'config' => [
            'allow_url_fopen' => true,
            'file_uploads' => true,
            'max_input_vars' => 5000,
            'memory_limit' => '512M',
            'post_max_size' => false,
            'set_time_limit' => true,
            'upload_max_filesize' => false,
            'max_execution_time' => 300,
            'max_input_time' => 300,
            'shell_exec' => true,
            'session' => true
        ],
        'apache_modules' => [
            'mod_deflate' => false,
            'mod_expires' => false,
            'mod_filter' => false,
            'mod_headers' => false,
            'mod_rewrite' => false,
        ],
    ];

    /**
     * Set up login and password with parameter or
     * you can set server env vars:
     *  - PS_INFO_LOGIN
     *  - PS_INFO_PASSWORD
     *
     * @param string $login    Login
     * @param string $password Password
     *
     */
    public function __construct($login = self::DEFAULT_LOGIN, $password = self::DEFAULT_PASSWORD)
    {
        $this->login = !empty($login) ? $login : $this->login;
        $this->password = !empty($password) ? $password : $this->password;
    }

    /**
     * Check authentication if not in cli and have a login
     */
    public function checkAuth()
    {
        if (PHP_SAPI === 'cli' ||
            empty($this->login)
        ) {
            return;
        }

        if (!isset($_SERVER['PHP_AUTH_USER']) ||
            $_SERVER['PHP_AUTH_PW'] != $this->password ||
            $_SERVER['PHP_AUTH_USER'] != $this->login
        ) {
            header('WWW-Authenticate: Basic realm="Authentification"');
            header('HTTP/1.0 401 Unauthorized');
            echo '401 Unauthorized';
            exit(401);
        }
    }

    /**
     * Get versions data
     *
     * @return array
     */
    public function getVersions()
    {
        $command = 'command -v php 2>&1';
        if(is_callable('shell_exec') && false === stripos(ini_get('disable_functions'), 'shell_exec')) {
            $output = shell_exec($command);
        } else {
            $output = "";
        }
        if(empty($output)) $output = PHP_BINARY;
        $path_php = trim($output);
        $data = [
            'Web server' => [$this->getWebServer()],
            'PHP Type' => [
                strpos(PHP_SAPI, 'cgi') !== false ?
                    'CGI with Apache Worker or another webserver - '.$path_php :
                    'Apache Module (low performance) - '.$path_php
            ],
        ];

        $data['PHP Version'] = [
            $this->requirements['versions']['php'],
            $this->recommended['versions']['php'],
            PHP_VERSION,
            version_compare(PHP_VERSION, $this->recommended['versions']['php'], '>=') ?
                self::TYPE_OK : (
            version_compare(PHP_VERSION, $this->requirements['versions']['php'], '>=') ?
                self::TYPE_WARNING :
                self::TYPE_ERROR
            )
        ];

        if (!extension_loaded('mysqli') || !is_callable('mysqli_connect')) {
            $data['MySQLi Extension'] = [
                true,
                true,
                'Not installed',
                self::TYPE_ERROR,
            ];
        } else {
            $data['MySQLi Extension'] = [
                $this->requirements['versions']['mysql'],
                $this->recommended['versions']['mysql'],
                mysqli_get_client_info(),
                self::TYPE_OK,
            ];
        }

        $data['Internet connectivity'] = [
            true,
            true,
            gethostbyname('simplevirtualtour.it') !== 'simplevirtualtour.it',
            gethostbyname('simplevirtualtour.it') !== 'simplevirtualtour.it',
        ];

        $data['Kernel Version'] = [
            false,
            3.2,
            php_uname('r'),
            version_compare(php_uname('r'), 3.2, '>=') ?
                self::TYPE_OK : (
            version_compare(php_uname('r'), 3.2, '>=') ?
                self::TYPE_WARNING :
                self::TYPE_ERROR
            )
        ];

        return $data;
    }

    /**
     * Get php extensions data
     *
     * @return array
     */
    public function getPhpExtensions()
    {
        $data = [];
        $vars = [
            'Client URL Library (curl)' => 'curl',
            'Image Processing (gd)' => 'gd',
            'Image Processing (imagick)' => 'imagick',
            'Multibyte String (mbstring)' => 'mbstring',
            'OpenSSL' => 'openssl',
            'File Information (fileinfo)' => 'fileinfo',
            'JavaScript Object Notation (json)' => 'json',
            'Localization (gettext)' => 'gettext',
            'XML' => 'xml',
            'SimpleXML' => 'simplexml',
        ];
        foreach ($vars as $label => $var) {
            $value = extension_loaded($var);
            $data[$label] = [
                $this->requirements['extensions'][$var],
                $this->recommended['extensions'][$var],
                $value
            ];
        }

        $vars = [
            'Zip' => ['zip', 'ZipArchive'],
        ];
        foreach ($vars as $label => $var) {
            $value = class_exists($var[1]);
            $data[$label] = [
                $this->requirements['extensions'][$var[0]],
                $this->recommended['extensions'][$var[0]],
                $value
            ];
        }

        return $data;
    }

    public function getPackages() {
        $data = [];
        $vars = [
            'Python 3 (python3)' => ['python3','Multires, Video360'],
            'Python 3 Pillow (python3-pil)' => ['python3-pil','Multires'],
            'Python 3 NumPy (python3-numpy)' => ['python3-numpy','Multires'],
            'Python 3 Preferred Installer Program (python3-pip)' => ['python3-pip','Multires'],
            'Hugin Tools (hugin-tools)' => ['hugin-tools','Multires'],
            'Spherical Harmonic Tools (pyshtools)' => ['pyshtools','Multires'],
            'Ruby Fastimage (ruby-fastimage)' => ['ruby-fastimage','Slideshow'],
        ];
        foreach ($vars as $label => $var) {
            if(is_callable('shell_exec') && false === stripos(ini_get('disable_functions'), 'shell_exec')) {
                $value = true;
                switch($var[0]) {
                    case 'pyshtools':
                        $command = 'pip3 list | grep -F pyshtools 2>&1';
                        $output = shell_exec($command);
                        if (strpos(strtolower($output), 'pyshtools') === false) {
                            $value = false;
                        }
                        break;
                    case 'python3-pil':
                        $command = 'dpkg-query -W -f=\'${Status}\' python3-pil 2>&1';
                        $output = shell_exec($command);
                        if (strpos(strtolower($output), 'command not found') !== false || strpos(strtolower($output), 'no packages found') !== false) {
                            $command = 'rpm -q python3-pil 2>&1';
                            $output = shell_exec($command);
                            if ((strpos(strtolower($output), 'not installed') !== false) || (strpos(strtolower($output), 'not found') !== false)) {
                                $command = 'python3 -m pip list | grep -i \'pillow\'';
                                $output = shell_exec($command);
                                if (strpos(strtolower($output), 'pillow') === false) {
                                    $value = false;
                                }
                            }
                        } else {
                            if (strpos(strtolower($output), 'installed') === false) {
                                $value = false;
                            }
                        }
                        break;
                    case 'python3-numpy':
                        $command = 'dpkg-query -W -f=\'${Status}\' python3-numpy 2>&1';
                        $output = shell_exec($command);
                        if (strpos(strtolower($output), 'command not found') !== false || strpos(strtolower($output), 'no packages found') !== false) {
                            $command = 'rpm -q python3-numpy 2>&1';
                            $output = shell_exec($command);
                            if ((strpos(strtolower($output), 'not installed') !== false) || (strpos(strtolower($output), 'not found') !== false)) {
                                $command = 'python3 -m pip list | grep -i \'numpy\'';
                                $output = shell_exec($command);
                                if (strpos(strtolower($output), 'numpy') === false) {
                                    $value = false;
                                }
                            }
                        } else {
                            if (strpos(strtolower($output), 'installed') === false) {
                                $value = false;
                            }
                        }
                        break;
                    case 'ruby-fastimage':
                        $command = 'dpkg-query -W -f=\'${Status}\' ruby-fastimage 2>&1';
                        $output = shell_exec($command);
                        if (strpos(strtolower($output), 'command not found') !== false || strpos(strtolower($output), 'no packages found') !== false) {
                            $command = 'rpm -q ruby-fastimage 2>&1';
                            $output = shell_exec($command);
                            if ((strpos(strtolower($output), 'not installed') !== false) || (strpos(strtolower($output), 'not found') !== false)) {
                                $command = 'gem list | grep -i \'fastimage\'';
                                $output = shell_exec($command);
                                if (strpos(strtolower($output), 'fastimage') === false) {
                                    $value = false;
                                }
                            }
                        } else {
                            if (strpos(strtolower($output), 'installed') === false) {
                                $value = false;
                            }
                        }
                        break;
                    default:
                        $command = 'dpkg-query -W -f=\'${Status}\' '.$var[0].' 2>&1';
                        $output = shell_exec($command);
                        if (strpos(strtolower($output), 'command not found') !== false || strpos(strtolower($output), 'no packages found') !== false) {
                            $command = 'rpm -q '.$var[0].' 2>&1';
                            $output = shell_exec($command);
                            if ((strpos(strtolower($output), 'not installed') !== false) || (strpos(strtolower($output), 'not found') !== false)) {
                                $command = 'command -v '.$var[0];
                                $output = shell_exec($command);
                                if (strpos(strtolower($output), $var[0]) === false) {
                                    $value = false;
                                }
                            }
                        } else {
                            if (strpos(strtolower($output), 'installed') === false) {
                                $value = false;
                            }
                        }
                        break;
                }
                $data[$label] = [
                    $var[1],
                    $this->requirements['packages'][$var[0]],
                    $value
                ];
            } else {
                $data[$label] = [
                    $var[1],
                    $this->requirements['packages'][$var[0]],
                    false
                ];
            }

        }
        return $data;
    }

    /**
     * Get php config data
     *
     * @return array
     */
    public function getPhpConfig()
    {
        $data = [];

        if(session_status() != PHP_SESSION_DISABLED) {
            $data['session'] = [
                $this->requirements['config']['session'],
                $this->recommended['config']['session'],
                true
            ];
        } else {
            $data['session'] = [
                $this->requirements['config']['session'],
                $this->recommended['config']['session'],
                false
            ];
        }

        if(is_callable('shell_exec') && false === stripos(ini_get('disable_functions'), 'shell_exec')) {
            $data['shell_exec'] = [
                $this->requirements['config']['shell_exec'],
                $this->recommended['config']['shell_exec'],
                true
            ];
        } else {
            $data['shell_exec'] = [
                $this->requirements['config']['shell_exec'],
                $this->recommended['config']['shell_exec'],
                false
            ];
        }

        $vars = [
            'allow_url_fopen',
            'file_uploads',
        ];
        foreach ($vars as $var) {
            $value = (bool) ini_get($var);
            $data[$var] = [
                $this->requirements['config'][$var],
                $this->recommended['config'][$var],
                $value
            ];
        }

        $vars = [
            'max_execution_time',
            'max_input_time',
        ];
        foreach ($vars as $var) {
            $value = (int) ini_get($var);
            $data[$var] = [
                $this->requirements['config'][$var],
                $this->recommended['config'][$var],
                $value
            ];
        }

        $vars = [
            'max_input_vars',
            'memory_limit',
            'post_max_size',
            'upload_max_filesize',
        ];
        foreach ($vars as $var) {
            $value = ini_get($var);
            if ($this->toBytes($value) >= $this->toBytes($this->recommended['config'][$var])) {
                $result = self::TYPE_OK;
            } elseif ($this->toBytes($value) >= $this->toBytes($this->requirements['config'][$var])) {
                $result = self::TYPE_WARNING;
            } else {
                $result = self::TYPE_ERROR;
            }

            $data[$var] = [
                $this->requirements['config'][$var],
                $this->recommended['config'][$var],
                $value,
                $result,
            ];
        }

        $vars = [
            'set_time_limit',
        ];
        foreach ($vars as $var) {
            $value = is_callable($var);
            $data[$var] = [
                $this->requirements['config'][$var],
                $this->recommended['config'][$var],
                $value
            ];
        }

        return $data;
    }

    /**
     * Check if directories are writable
     *
     * @return array
     */
    public function getDirectories()
    {
        $data = [];
        foreach ($this->requirements['directories'] as $directory) {
            $directoryPath = getcwd() . DIRECTORY_SEPARATOR . trim($directory, '\\/');
            $data[$directory] = [file_exists($directoryPath) && is_writable($directoryPath)];
        }

        return $data;
    }

    public function getFiles()
    {
        $data = [];
        foreach ($this->requirements['files'] as $file) {
            $filePath = getcwd() . DIRECTORY_SEPARATOR . $file;
            $data[$file] = [file_exists($filePath) && is_executable($filePath)];
        }

        return $data;
    }

    public function getServerModules()
    {
        $data = [];
        if (!function_exists('apache_get_modules')) {
            return $data;
        }

        $modules = apache_get_modules();
        $vars = array_keys($this->requirements['apache_modules']);
        foreach ($vars as $var) {
            $value = in_array($var, $modules);
            $data[$var] = [
                $this->requirements['apache_modules'][$var],
                $this->recommended['apache_modules'][$var],
                $value,
            ];
        }

        return $data;
    }

    /**
     * Convert PHP variable (G/M/K) to bytes
     * Source: http://php.net/manual/fr/function.ini-get.php
     *
     * @param mixed $value
     *
     * @return integer
     */
    public function toBytes($value)
    {
        if (is_numeric($value)) {
            return $value;
        }

        $value = trim($value);
        $val = (int) $value;
        switch (strtolower($value[strlen($value)-1])) {
            case 'g':
                $val *= 1024;
            // continue
            case 'm':
                $val *= 1024;
            // continue
            case 'k':
                $val *= 1024;
        }

        return $val;
    }

    /**
     * Transform value to string
     *
     * @param mixed $value Value
     *
     * @return string
     */
    public function toString($value)
    {
        if ($value === true) {
            return 'Yes';
        } elseif ($value === false) {
            return 'No';
        } elseif ($value === null) {
            return 'N/A';
        }

        return strval($value);
    }

    /**
     * Get html class
     *
     * @param array $data
     * @return string
     */
    public function toHtmlClass(array $data)
    {
        if (count($data) === 1 && !is_bool($data[0])) {
            return 'table-info';
        }


        if (count($data) === 1 && is_bool($data[0])) {
            $result = $data[0];
        } elseif (array_key_exists(3, $data)) {
            $result = $data[3];
        } else {
            if ($data[2] >= $data[1]) {
                $result = self::TYPE_OK;
            } elseif ($data[2] >= $data[0]) {
                $result = self::TYPE_WARNING;
            } else {
                $result = self::TYPE_ERROR;
            }
        }

        if ($result === false) {
            return 'table-danger';
        }

        if ($result === null) {
            return 'table-warning';
        }

        return 'table-success';
    }

    public function toHtmlClass_p(array $data)
    {
        if($data[2]) {
            return 'table-success';
        } else {
            return 'table-warning';
        }
    }

    /**
     * Detect Web server
     *
     * @return string
     */
    protected function getWebServer()
    {
        if (stristr($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false) {
            return 'Apache';
        } elseif (stristr($_SERVER['SERVER_SOFTWARE'], 'LiteSpeed') !== false) {
            return 'Lite Speed';
        } elseif (stristr($_SERVER['SERVER_SOFTWARE'], 'Nginx') !== false) {
            return 'Nginx';
        } elseif (stristr($_SERVER['SERVER_SOFTWARE'], 'lighttpd') !== false) {
            return 'lighttpd';
        } elseif (stristr($_SERVER['SERVER_SOFTWARE'], 'IIS') !== false) {
            return 'Microsoft IIS';
        }

        return 'Not detected';
    }

    /**
     * Determines if a command exists on the current environment
     * Source: https://stackoverflow.com/questions/12424787/how-to-check-if-a-shell-command-exists-from-php
     *
     * @param string $command The command to check
     *
     * @return bool
     */
    protected function commandExists($command)
    {
        $which = (PHP_OS == 'WINNT') ? 'where' : 'which';

        $process = proc_open(
            $which . ' ' . $command,
            [
                ['pipe', 'r'], //STDIN
                ['pipe', 'w'], //STDOUT
                ['pipe', 'w'], //STDERR
            ],
            $pipes
        );

        if ($process !== false) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);

            return $stdout != '';
        }

        return false;
    }
}

// Init render
$info = new PhpPsInfo();
//$info->checkAuth();
?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
        <meta name="description" content=""/>
        <meta name="author" content=""/>
        <title>Requirements</title>
        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" />
        <style>
            h1 {font-size:1.5rem;}
        </style>
    </head>

    <body>

    <?php
    ob_start();
    if(function_exists('phpinfo')) @phpinfo(-1);
    $phpinfo = array('phpinfo' => array());
    if(preg_match_all('#(?:<h2>(?:<a name=".*?">)?(.*?)(?:</a>)?</h2>)|(?:<tr(?: class=".*?")?><t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>)?)?</tr>)#s', ob_get_clean(), $matches, PREG_SET_ORDER))
        foreach($matches as $match){
            $array_keys = array_keys($phpinfo);
            $end_array_keys = end($array_keys);
            if(strlen($match[1])){
                $phpinfo[$match[1]] = array();
            }else if(isset($match[3])){
                $phpinfo[$end_array_keys][$match[2]] = isset($match[4]) ? array($match[3], $match[4]) : $match[3];
            }else{
                $phpinfo[$end_array_keys][] = $match[2];
            }
        }
    $system = (isset($phpinfo['phpinfo']['System']) ? $phpinfo['phpinfo']['System'] : '');
    ?>

    <div class="container-fluid">
        <div class="row justify-content-md-center">
            <main role="main" class="col-12 mt-3">
                <h1>General information & PHP/MySQL Version</h1>
                <div class="table-responsive">
                    <table class="table table-striped table-sm text-center">
                        <thead>
                        <tr>
                            <th class="text-left">#</th>
                            <th>Required</th>
                            <th>Recommended</th>
                            <th>Current</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td class="text-left">System</td>
                            <td class="table-info" colspan="3"><?php echo $system; ?></td>
                        </tr>
                        <tr>
                            <td class="text-left">IP Address</td>
                            <td class="table-info" colspan="3"><?php echo get_ip_server(); ?></td>
                        </tr>
                        <?php foreach ($info->getVersions() as $label => $data) : ?>
                            <?php if (count($data) === 1) : ?>
                                <tr>
                                    <td class="text-left"><?php echo $label ?></td>
                                    <td class="<?php echo $info->toHtmlClass($data); ?>" colspan="3"><?php echo $info->toString($data[0]) ?></td>
                                </tr>
                            <?php else : ?>
                                <tr>
                                    <td class="text-left"><?php echo $label ?></td>
                                    <td><?php echo $info->toString($data[0]) ?></td>
                                    <td><?php echo $info->toString($data[1]) ?></td>
                                    <td class="<?php echo $info->toHtmlClass($data); ?>"><?php echo $info->toString($data[2]) ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (count($info->getServerModules()) > 0): ?>
                    <h1>Apache Modules</h1>

                    <div class="table-responsive">
                        <table class="table table-striped table-sm text-center">
                            <thead>
                            <tr>
                                <th class="text-left">#</th>
                                <th>Required</th>
                                <th>Recommended</th>
                                <th>Current</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($info->getServerModules() as $label => $data) : ?>
                                <tr>
                                    <td class="text-left"><?php echo $label ?></td>
                                    <td><?php echo $info->toString($data[0]) ?></td>
                                    <td><?php echo $info->toString($data[1]) ?></td>
                                    <td class="<?php echo $info->toHtmlClass($data); ?>"><?php echo $info->toString($data[2]) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <h1>PHP Configuration</h1>

                <div class="table-responsive">
                    <table class="table table-striped table-sm text-center">
                        <thead>
                        <tr>
                            <th class="text-left">#</th>
                            <th>Required</th>
                            <th>Recommended</th>
                            <th>Current</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($info->getPhpConfig() as $label => $data) : ?>
                            <tr>
                                <td class="text-left"><?php echo $label ?></td>
                                <td><?php echo $info->toString($data[0]) ?></td>
                                <td><?php echo $info->toString($data[1]) ?></td>
                                <td class="<?php echo $info->toHtmlClass($data); ?>"><?php echo $info->toString($data[2]) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <h1>PHP Extensions</h1>

                <div class="table-responsive">
                    <table class="table table-striped table-sm text-center">
                        <thead>
                        <tr>
                            <th class="text-left">#</th>
                            <th>Required</th>
                            <th>Recommended</th>
                            <th>Current</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($info->getPhpExtensions() as $label => $data) : ?>
                            <tr>
                                <td class="text-left"><?php echo $label ?></td>
                                <td><?php echo $info->toString($data[0]) ?></td>
                                <td><?php echo $info->toString($data[1]) ?></td>
                                <td class="<?php echo $info->toHtmlClass($data); ?>"><?php echo $info->toString($data[2]) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <h1>Packages</h1>

                <div class="table-responsive">
                    <table class="table table-striped table-sm text-center">
                        <thead>
                        <tr>
                            <th class="text-left">#</th>
                            <th>Tool</th>
                            <th>Current</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($info->getPackages() as $label => $data) : ?>
                            <tr>
                                <td class="text-left"><?php echo $label ?></td>
                                <td><?php echo $info->toString($data[0]) ?></td>
                                <td class="<?php echo $info->toHtmlClass_p($data); ?>"><?php echo $info->toString($data[2]) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <h1>Directories</h1>

                <div class="table-responsive">
                    <table class="table table-striped table-sm text-center">
                        <thead>
                        <tr>
                            <th class="text-left">#</th>
                            <th>Is Writable</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($info->getDirectories() as $label => $data) : ?>
                            <tr>
                                <td class="text-left"><?php echo $label ?></td>
                                <td class="<?php echo $info->toHtmlClass($data); ?>"><?php echo $info->toString($data[0]) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <h1>Files</h1>

                <div class="table-responsive">
                    <table class="table table-striped table-sm text-center">
                        <thead>
                        <tr>
                            <th class="text-left">#</th>
                            <th>Is Executable</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($info->getFiles() as $label => $data) : ?>
                            <tr>
                                <td class="text-left"><?php echo $label ?></td>
                                <td class="<?php echo $info->toHtmlClass($data); ?>"><?php echo $info->toString($data[0]) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
    </body>
    </html>

<?php
function get_ip_server() {
    $server_ip = '';
    $server_name = $_SERVER['SERVER_NAME'];
    if(array_key_exists('SERVER_ADDR', $_SERVER)) {
        $server_ip = $_SERVER['SERVER_ADDR'];
        if(!filter_var($server_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $server_ip = gethostbyname($server_name);
        }
    } elseif(array_key_exists('LOCAL_ADDR', $_SERVER)) {
        $server_ip = $_SERVER['LOCAL_ADDR'];
    } elseif(array_key_exists('SERVER_NAME', $_SERVER)) {
        $server_ip = gethostbyname($_SERVER['SERVER_NAME']);
    } else {
        if(stristr(PHP_OS, 'WIN')) {
            $server_ip = gethostbyname(php_uname("n"));
        } else {
            $ifconfig = shell_exec('/sbin/ifconfig eth0');
            preg_match('/addr:([\d\.]+)/', $ifconfig, $match);
            $server_ip = $match[1];
        }
    }
    return $server_ip;
}
?>