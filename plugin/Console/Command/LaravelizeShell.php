<?php

App::uses('ConnectionManager', 'Model');
App::uses('Folder', 'Utility');

class LaravelizeShell extends AppShell
{

    public function getOptionParser() {
        $parser = parent::getOptionParser();

        $parser->description("The Laravelize Shell sets up your application to run on Laravel. Be sure to have a backup of your application before running this command.");

        return $parser;
    }

    public function main()
    {
        $baseDir = ROOT;
        $laravelDir = "$baseDir/laravel";
        if (!is_dir($laravelDir)) {
            $this->err('<error>No laravel directory.</error>');
            return false;
        }

        if (!is_dir("$baseDir/app")) {
            $this->err('<error>No app directory.</error>');
            return false;
        }

        /** @var SplFileInfo $item */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($laravelDir, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::UNIX_PATHS)) as $item) {
            $sourcePath = $item->getPathname();
            $relativePath = substr($sourcePath, strlen($laravelDir) + 1);
            $destinationPath = "$baseDir/$relativePath";

            $content = null;
            switch ($relativePath) {
                case 'app/Http/Kernel.php':
                    $content = $this->modifyAppHttpKernel($sourcePath);
                    break;
                case 'config/app.php':
                    $content = $this->modifyConfigApp($sourcePath);
                    break;
                case 'public/index.php';
                    $content = $this->modifyPublicIndex($sourcePath);
                    $destinationPath = ROOT . '/app/webroot/index.php';
                    break;
                case 'routes/web.php':
                    $content = $this->modifyRoutesWeb($sourcePath);
                    break;
                case '.env.example':
                    $content = $this->modifyEnv($sourcePath);
                    $destinationPath = "$baseDir/.env";
                    break;
                case 'artisan':
                    $content = $this->modifyArtisan($sourcePath);
                    break;
                case 'phpunit.xml':
                    $content = $this->modifyPhpunitXml($sourcePath);
                    break;
                case 'server.php':
                case 'webpack.mix.js':
                    $content = file_get_contents($sourcePath);
                    break;
            }

            if (!preg_match('{^(app|bootstrap|config|database|public|resources|routes|storage|tests)/}', $relativePath) && $content === null) {
                continue;
            }

            $dir = dirname($destinationPath);
            if (!is_dir($dir)) {
                mkdir($dir, null, true);
            }

            $destinationPath = str_replace('/', DIRECTORY_SEPARATOR, $destinationPath);
            $this->out(__d('cake_console', 'Creating file %s', $destinationPath));

            if ($content !== null) {
                $result = file_put_contents($destinationPath, $content);
            } else {
                $result = copy($sourcePath, $destinationPath);
            }

            if ($result) {
                $this->out(__d('cake_console', '<success>Wrote</success> `%s`', $destinationPath));
            } else {
                $this->err(__d('cake_console', '<error>Could not write to `%s`</error>.', $destinationPath), 2);
            }
        }

        return true;
    }

    public function modify($sourcePath, $rules)
    {
        return preg_replace_callback_array($rules, file_get_contents($sourcePath));
    }

    public function modifyAppHttpKernel($sourcePath)
    {
        return $this->modify($sourcePath, [
            "{^(\s*)((\\\\Fruitcake\\\\Cors\\\\HandleCors|\\\\App\\\\Http\\\\Middleware\\\\(EncryptCookies|VerifyCsrfToken))::class,)$}m" => function($m) {
                return "$m[1]// $m[2]";
            },
        ]);
    }

    public function modifyConfigApp($sourcePath)
    {
        return $this->modify($sourcePath, [
            '{^(\s*) \* Package Service Providers\.\.\.\n\s* \*/$}m' => function($m) {
                return "$m[0]\n$m[1]Ostoandel\Providers\CakeServiceProvider::class,";
            },
            "{^(\s*)'aliases' => \[$}m" => function ($m) {
                return "$m[0]\n$m[1]    'Configure' => Ostoandel\Fake\Configure::class,";
            },
            "{^(\s*)('(?<name>App|Cache|File|Hash|View)' => Illuminate\\\\Support\\\\Facades\\\\(?P=name)::class,)$}m" => function($m) {
                return "$m[1]// $m[2]";
            },
        ]);
    }

    public function modifyRoutesWeb($sourcePath)
    {
        return $this->modify($sourcePath, [
            "{^Route::get\('/', function \(\) \{.*\}\);}ms" => function($m) {
                return "Route::fallbackToCake();\n/*\n$m[0]\n*/";
            },
        ]);
    }

    public function modifyEnv($sourcePath)
    {
        $config = ConnectionManager::getDataSource('default')->config;
        $config['username'] = $config['login'];
        $config = array_change_key_case($config, CASE_UPPER);

        return $this->modify($sourcePath, [
            '{^(APP_KEY=).*}m' => function($m) {
                return $m[1] . 'base64:'.base64_encode(random_bytes(32));
            },
            '{^(DB_(HOST|PORT|DATABASE|USERNAME|PASSWORD)=).*}m' => function($m) use ($config) {
                return $m[1] . $config[ $m[2] ];
            },
        ]);
    }

    public function modifyArtisan($sourcePath)
    {
        return $this->modify($sourcePath, [
            "{^require __DIR__\.'/vendor/autoload\.php';$}m" => function($m) {
                $vendor = basename(VENDORS);
                return "require_once __DIR__ . '/$vendor/ostoandel/ostoandel/helpers.php';\n"
                    . "require __DIR__ . '/$vendor/autoload.php';";
            },
        ]);
    }

    public function modifyPublicIndex($sourcePath)
    {
        return $this->modify($sourcePath, [
            "{^require __DIR__\.'/\.\./vendor/autoload\.php';$}m" => function($m) {
                $vendor = basename(VENDORS);
                return "define('ROOT', dirname(dirname(__DIR__)));\n"
                    . "require_once ROOT . '/$vendor/ostoandel/ostoandel/helpers.php';\n"
                    . "require ROOT . '/$vendor/autoload.php';";
            },
            "{^\\\$app = require_once __DIR__.'/../bootstrap/app.php';$}m" => function($m) {
                return "require_once ROOT . '/bootstrap/app.php';";
            },
        ]);
    }

    public function modifyPhpunitXml($sourcePath)
    {
        return $this->modify($sourcePath, [
            '{^(\s*)bootstrap="vendor/autoload\.php"$}m' => function($m) {
                $vendor = basename(VENDORS);
                return "$m[1]bootstrap=\"$vendor/autoload.php\"\n"
                    . "$m[1]backupGlobals=\"false\"";
            },
        ]);
    }

}
