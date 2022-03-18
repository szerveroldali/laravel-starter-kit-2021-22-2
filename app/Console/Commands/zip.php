<?php

// Statement checker and project zipper for Laravel home projects
// Created by Tóta Dávid

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Console\Style\SymfonyStyle;
use \TOGoS_GitIgnore_Ruleset as Gitignore;

class zip extends Command
{
    protected $signature = 'zip';
    protected $description = 'Creates zip file from your assignment';

    // Nyilatkozat base64-be kódolva (template, tehát <NAME>, <NEPTUN>, <DATE> nélkül)
    const statement_preview = "VGhpcyBzb2x1dGlvbiB3YXMgc3VibWl0dGVkIGFuZCBwcmVwYXJlZCBieSBtZSBmb3IgdGhlIExhcmF2ZWwgaG9tZSBhc3NpZ25tZW50IG9mIHRoZSBXZWIgZW5naW5lZXJpbmcgY291cnNlLgpCeSBzdWJtaXR0aW5nIHRoaXMgYXNzaWdubWVudCwgSSBhY2tub3dsZWRnZSB0aGF0IEkgaGF2ZSB0YWtlbiBub3RlIG9mIHRoZSBzdGF0ZW1lbnRzIGJlbG93OgoKLSBJIGRlY2xhcmUgdGhhdCB0aGlzIHNvbHV0aW9uIGlzIG15IG93biB3b3JrLgotIEkgaGF2ZSBub3QgY29waWVkIG9yIHVzZWQgdGhpcmQgcGFydHkgc29sdXRpb25zLgotIEkgaGF2ZSBub3QgcGFzc2VkIG15IHNvbHV0aW9uIHRvIG15IGNsYXNzbWF0ZXMsIG5laXRoZXIgIG1hZGUgaXQgcHVibGljLgotIFN0dWRlbnRz4oCZIHJlZ3VsYXRpb24gb2YgRcO2dHbDtnMgTG9yw6FuZCBVbml2ZXJzaXR5IChFTFRFIFJlZ3VsYXRpb25zIFZvbC4gSUkuIDc0L0MuIMKnICkgc3RhdGVzIHRoYXQgYXMgbG9uZyBhcyBhIHN0dWRlbnQgcHJlc2VudHMgYW5vdGhlciBzdHVkZW504oCZcyB3b3JrIC0gb3IgYXQgbGVhc3QgdGhlIHNpZ25pZmljYW50IHBhcnQgb2YgaXQgLSBhcyBoaXMvaGVyIG93biBwZXJmb3JtYW5jZSwgaXQgd2lsbCBjb3VudCBhcyBhIGRpc2NpcGxpbmFyeSBmYXVsdC4gVGhlIG1vc3Qgc2VyaW91cyBjb25zZXF1ZW5jZSBvZiBhIGRpc2NpcGxpbmFyeSBmYXVsdCBjYW4gYmUgZGlzbWlzc2FsIG9mIHRoZSBzdHVkZW50IGZyb20gdGhlIFVuaXZlcnNpdHkuCg==";

    // Nyilatkozat template (<NÉV>, <NEPTUN>, <DATE> mellékelve, amibe behelyettesíthetők az adatok)
    const statement_template = "IyBTdGF0ZW1lbnQKCkksIDxOQU1FPiAoTmVwdHVuIGNvZGU6IDxORVBUVU4+KSwgZGVjbGFyZSB0aGF0IEkgaGF2ZSBzdWJtaXR0ZWQgdGhpcyBzb2x1dGlvbiBmb3IgdGhlIExhcmF2ZWwgaG9tZSBhc3NpZ25tZW50IG9mIHRoZSBXZWIgZW5naW5lZXJpbmcgY291cnNlLgpCeSBzdWJtaXR0aW5nIHRoaXMgYXNzaWdubWVudCwgSSBhY2tub3dsZWRnZSB0aGF0IEkgaGF2ZSB0YWtlbiBub3RlIG9mIHRoZSBzdGF0ZW1lbnRzIGJlbG93OgoKLSBJIGRlY2xhcmUgdGhhdCB0aGlzIHNvbHV0aW9uIGlzIG15IG93biB3b3JrLgotIEkgZGVjbGFyZSB0aGF0IEkgaGF2ZSBub3QgY29waWVkIG9yIHVzZWQgdGhpcmQgcGFydHkgc29sdXRpb25zLgotIEkgZGVjbGFyZSB0aGF0IEkgaGF2ZSBub3QgcGFzc2VkIG15IHNvbHV0aW9uIHRvIG15IGNsYXNzbWF0ZXMsIG5laXRoZXIgIG1hZGUgaXQgcHVibGljLgotIEkgYWNrbm93bGVkZ2VkIHRoYXQgdGhlIFN0dWRlbnRz4oCZIHJlZ3VsYXRpb24gb2YgRcO2dHbDtnMgTG9yw6FuZCBVbml2ZXJzaXR5IChFTFRFIFJlZ3VsYXRpb25zIFZvbC4gSUkuIDc0L0MuIMKnICkgc3RhdGVzIHRoYXQgYXMgbG9uZyBhcyBhIHN0dWRlbnQgcHJlc2VudHMgYW5vdGhlciBzdHVkZW504oCZcyB3b3JrIC0gb3IgYXQgbGVhc3QgdGhlIHNpZ25pZmljYW50IHBhcnQgb2YgaXQgLSBhcyBoaXMvaGVyIG93biBwZXJmb3JtYW5jZSwgaXQgd2lsbCBjb3VudCBhcyBhIGRpc2NpcGxpbmFyeSBmYXVsdC4gCi0gSSBhY2tub3dsZWRnZWQgdGhhdCB0aGUgbW9zdCBzZXJpb3VzIGNvbnNlcXVlbmNlIG9mIGEgZGlzY2lwbGluYXJ5IGZhdWx0IGNhbiBiZSBkaXNtaXNzYWwgb2YgdGhlIHN0dWRlbnQgZnJvbSB0aGUgVW5pdmVyc2l0eS4KCkRhdGVkOiA8REFURT4K";

    // Azok a mappák, amelyeknek mindenképpen jelen kell lenniük a zippelés pillanatában. Ha valamelyik nem található, akkor a hallgató hibát kap, és a rendszer kiírja neki, hogy melyek azok a mappák, amik ezek közül hiányoznak.
    const required_dirs = [
        'app',
        'app/Console',
        //'app/Console/Commands',
        'app/Exceptions',
        'app/Http',
        'app/Http/Controllers',
        'app/Http/Middleware',
        'app/Models',
        'app/Providers',
        'bootstrap',
        'bootstrap/cache',
        'config',
        'database',
        'database/factories',
        'database/migrations',
        'database/seeders',
        'lang',
        'lang/en',
        'public',
        'resources',
        'resources/css',
        'resources/js',
        'resources/views',
        'routes',
        'storage',
        'storage/app',
        'storage/framework',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/testing',
        'storage/framework/views',
        'storage/logs',
        //'tests',
        //'tests/Feature',
        //'tests/Unit',
    ];

    // Azok a fájlok, amelyeknek mindenképpen jelen kell lenniük a zippelés pillanatában. Ha valamelyik nem található, akkor a hallgató hibát kap, és a rendszer kiírja neki, hogy melyek azok a fájlok, amik ezek közül hiányoznak.
    const required_files = [
        //'.editorconfig',
        '.env.example',
        '.gitattributes',
        '.gitignore',
        //'.styleci.yml',
        //'README.md',
        'app/Console/Kernel.php',
        'app/Exceptions/Handler.php',
        'app/Http/Controllers/Controller.php',
        'app/Http/Kernel.php',
        'app/Http/Middleware/Authenticate.php',
        'app/Http/Middleware/EncryptCookies.php',
        'app/Http/Middleware/PreventRequestsDuringMaintenance.php',
        'app/Http/Middleware/RedirectIfAuthenticated.php',
        'app/Http/Middleware/TrimStrings.php',
        'app/Http/Middleware/TrustHosts.php',
        'app/Http/Middleware/TrustProxies.php',
        'app/Http/Middleware/VerifyCsrfToken.php',
        'app/Models/User.php',
        'app/Providers/AppServiceProvider.php',
        'app/Providers/AuthServiceProvider.php',
        'app/Providers/BroadcastServiceProvider.php',
        'app/Providers/EventServiceProvider.php',
        'app/Providers/RouteServiceProvider.php',
        'artisan',
        'bootstrap/app.php',
        'composer.json',
        'config/app.php',
        'config/auth.php',
        'config/broadcasting.php',
        'config/cache.php',
        'config/cors.php',
        'config/database.php',
        'config/filesystems.php',
        'config/hashing.php',
        'config/logging.php',
        'config/mail.php',
        'config/queue.php',
        'config/sanctum.php',
        'config/services.php',
        'config/session.php',
        'config/view.php',
        'database/.gitignore',
        'database/factories/UserFactory.php',
        'database/migrations/2014_10_12_000000_create_users_table.php',
        'database/migrations/2014_10_12_100000_create_password_resets_table.php',
        'database/migrations/2019_08_19_000000_create_failed_jobs_table.php',
        'database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php',
        'database/seeders/DatabaseSeeder.php',
        'lang/en/auth.php',
        'lang/en/pagination.php',
        'lang/en/passwords.php',
        'lang/en/validation.php',
        'lang/en.json',
        'package.json',
        //'phpunit.xml',
        'public/.htaccess',
        'public/favicon.ico',
        'public/index.php',
        //'public/robots.txt',
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/js/bootstrap.js',
        //'resources/views/welcome.blade.php',
        'routes/api.php',
        'routes/channels.php',
        'routes/console.php',
        'routes/web.php',
        'storage/framework/.gitignore',
        //'tests/CreatesApplication.php',
        //'tests/Feature/ExampleTest.php',
        //'tests/TestCase.php',
        //'tests/Unit/ExampleTest.php',
        'webpack.mix.js',
        // Init fájlok
        'init.bat',
        'init.sh',
    ];

    private $io;
    private $content;

    public function __construct() {
        parent::__construct();
    }

    private function init() {
        // Project beolvasása
        $this->content = $this->scan('.', [
            Gitignore::loadFromStrings([
                '.git',
                'app/Console/Commands/zip.php'
            ])
        ]);
    }

    // Validálással kibővített console ask
    private function validatedAsk($question, $rules, $messages = []) {
        $value = $this->ask($question);
        $validator = Validator::make(
            ['field' => $value], // értékek
            ['field' => $rules], // szabályok
            $messages            // hibaüzenetek
        );
        if ($validator->fails()) {
            // Minden előfodruló hiba megjelenítése
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return $this->validatedAsk($question, $rules, $messages);
        }
        return $value;
    }

    private function statement() {
        // Ha valaki egyszer már végigment a kitöltési folyamaton, akkor generálódott egy checksum. Ilyenkor, mivel a validált folyamaton ment keresztül, feltételezzük, hogy a kitöltés helyes volt, ezért csak visszaellenőrizzük, hogy a checksum egyezik-e (nem változott azóta a fájl), hogy ne kelljen minden alkalommal újra kitöltenie a nyilatkozatos formot a hallgatónak.
        if (file_exists(base_path('STATEMENT.txt')) && Cache::has('statement_checksum') && Cache::has('statement_name') && Cache::has('statement_neptun_code')) {
            $checksum = Cache::get('statement_checksum');
            $name = Cache::get('statement_name');
            $neptun = Cache::get('statement_neptun_code');
            if ($checksum && $name && $neptun) {
                $statement = file_get_contents(base_path('STATEMENT.txt'));
                if (sha1($statement) === $checksum) {
                    $this->io->success("The statement has previously been filled in with the name " . $name . " and the Neptun code " . $neptun . ".");
                    $this->io->note("If the above information is incorrect, delete the STATEMENT.txt file and call the zip command again, and the statement filler will reappear.");
                    $this->newLine();
                    return true;
                } else {
                    $this->warn("The previously created statement could not be verified and must be completed again.");
                    $this->newLine();
                }
            }
        }
        // Nyilatkozat megjelenítése a hallgatónak, majd az elfogadás, és az adatok bekérése
        $this->line('STATEMENT:');
        $this->newLine();
        $this->line(base64_decode(self::statement_preview));
        $this->newLine();
        if ($this->confirm('Have you read, accept and consider the above statement on you?')) {
            $this->info("Please enter your name and Neptun code so we can replace them in the statement.");
            // Név bekérése
            $name = $this->validatedAsk('What is your name?', [
                'required',
                'min:3',
                'max:128',
                'regex:/^[\pL\s\-]+$/u'
            ], [
                'required' => 'The name is required.',
                'min' => 'Name should be at least :min characters long.',
                'max' => 'The name cannot be longer than :max characters.',
                'regex' => 'The name can consist of alphanumeric characters and spaces.'
            ]);
            // Neptun kód bekérése
            $neptun = Str::upper($this->validatedAsk('What is your Neptun code?', [
                'required',
                'string',
                'size:6',
                'regex:/[a-zA-Z0-9]/'
            ], [
                'required' => 'The Neptun code is required.',
                'size' => 'Neptun code has an exact length of :size characters.',
                'regex' => 'The Neptun code can only consist of A-Z characters and numbers.'
            ]));
            // Aktuális dátum
            $date = Carbon::now('Europe/Budapest')->isoFormat('Y. MM. DD. kk:MM:ss');
            // Nyilatkozat kitöltése
            $filled_statement = Str::of(base64_decode(self::statement_template))
                ->replace('<NAME>', $name)
                ->replace('<NEPTUN>', $neptun)
                ->replace('<DATE>', $date);
            // Adatok tárolása
            file_put_contents(base_path('STATEMENT.txt'), $filled_statement);
            Cache::set('statement_checksum', sha1($filled_statement));
            Cache::set('statement_name', $name);
            Cache::set('statement_neptun_code', $neptun);
            //
            $this->io->success("The statement was successfully filled in with the name " . $name ." and Neptun code " . $neptun . ".");
            $this->io->note("If the above information is incorrect, delete the STATEMENT.txt file and call the zip command again, and the statement filler will reappear.");
        } else {
            $this->error('The statement is required according to the requirements of the subject to be submitted and to obtain the grade.');
            return false;
        }
        $this->newLine();
        return true;
    }

    private function ignored($path, $gitignores) {
        foreach ($gitignores as $gitignore) {
            if ($gitignore->match($path) || $gitignore->match(basename($path))) {
                return true;
            }
        }
        return false;
    }

    private function scan($current_directory, $gitignores = []) {
        $result = [
            'files' => [],
            'dirs' => [],
        ];

        // .gitignore begyűjtése, ha van
        $gitignore_path = $current_directory . '/' . '.gitignore';
        if (file_exists(base_path($gitignore_path))) {
            $gitignores[] = Gitignore::loadFromString(file_get_contents($gitignore_path));
        }

        // Aktuális mappa szkennelése
        $current_content = array_diff(scandir(base_path($current_directory)), array('..', '.'));
        foreach ($current_content as $item) {
            $item_path = str_replace('./', '', $current_directory . '/' . $item);

            // Symlink-ek kihagyása
            if (is_link($item_path)) continue;

            // Fájlok összegyűjtése a jelenlegi mappán belül
            if (is_file($item_path)) {
                if ($this->ignored($item_path, $gitignores)) continue;
                $result['files'][] = $item_path;
            }
            // Mappák összegyűjtése a jelenlegi mappán belül
            else if (is_dir($item_path)) {
                if ($this->ignored($item_path, $gitignores)) continue;
                $result['dirs'][] = $item_path;
                // ha nincs kihagyva a mappa, olvassuk be a tartalmát
                $dir_content = $this->scan(
                    $item_path,
                    $gitignores
                );
                $result['files'] = array_merge($result['files'], $dir_content['files']);
                $result['dirs'] = array_merge($result['dirs'], $dir_content['dirs']);
            }
        }
        return $result;
    }

    private function check() {
        $error = false;

        // Megnézzük, hogy mi a szükséges mappák és a projektben fellelhető mappák metszete
        $common_dirs = array_intersect(self::required_dirs, $this->content['dirs']);
        // ...és ha ez a metszet nem adja vissza a teljes requiredDirs-t (a szükséges mappákat), akkor bizony hiányzik valami...
        $dirs_diff = array_diff(self::required_dirs, $common_dirs);
        if (count($dirs_diff) > 0) {
            $error = true;
            $this->io->error([
                'Ezekre a mappákra szükség van:',
                ...$dirs_diff
            ]);
        }

        // Ugyanaz a logika, mint a mappáknál feljebb
        $common_files = array_intersect(self::required_files, $this->content['files']);
        $files_diff = array_diff(self::required_files, $common_files);
        if (count($files_diff) > 0) {
            $error = true;
            $this->io->error([
                'These files are required:',
                ...$files_diff
            ]);
        }

        if (!$error) {
            $this->io->success('According to our preliminary, automated checks, your project is validated.');
        }
        return !$error;
    }

    private function zip() {
        // zipfiles elkészítése, ha még nem létezik
        if (!(file_exists(base_path('zipfiles')) && is_dir(base_path('zipfiles')))) {
            mkdir(base_path('zipfiles'));
            $this->info("zipfiles folder created for zip files");
        }
        // Adatok összegyűjtése
        $date = Carbon::now('Europe/Budapest')->isoFormat('YMMDD_kkMMssS');
        $neptun = Cache::get('statement_neptun_code');
        $zip_name = base_path("zipfiles" . "/" . $neptun . "_Laravel_" . $date . ".zip");

        // Becsomagolás
        $zip = new \ZipArchive();
        $zip->open($zip_name, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($this->content['files'] as $file) {
            $zip->addFile($file, $file);
        }
        $zip->close();

        // zip méretének ellenőrzése
        $zip_size = \ByteUnits\bytes(filesize($zip_name));
        $this->io->success('The zip file is complete: ' . $zip_name . ' (size: ' . $zip_size->format('kB') . ')');
        $this->io->note('Proper and complete submission of the assignment is the responsibility of the student, so be sure to check it before submitting!');
        $this->io->note('It’s best to unzip and install with the commands you see in the task to see if everything works fine!');

        // Nagy méretű zip fájl esetén figyelmeztessük a hallgatót, valószínűleg bennehagyott valamit, amire nincs is szükség
        if ($zip_size->isGreaterThan(\ByteUnits\Binary::megabytes(2))) {
            $this->io->warning('The size of the zip file is larger than usual, please check if there are any unnecessary things in it, e.g. pictures, etc!');
        } else if ($zip_size->isGreaterThan(\ByteUnits\Binary::megabytes(10))) {
            $this->io->error('The size of the zip file is MUCH larger than usual, please check if there are any unnecessary things in it, e.g. pictures, etc!');
        }
        return true;
    }

    public function handle() {
        $this->io = new SymfonyStyle($this->input, $this->output);
        $this->io->title('Web engineering - Automatic zipper for Laravel');
        $this->io->section('1. step: Statement');
        if ($this->statement()) {
            $this->init();
            $this->io->section('2. step: Checking the project');
            if ($this->check()) {
                $this->io->section('3. step: Creating zip file');
                $this->zip();
            }
        }
        return 0;
    }
}
