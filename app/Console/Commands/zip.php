<?php

// Nyilatkozat ellenőrző és projekt zippelő Laravel beadandókhoz
// Készítette Tóta Dávid

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
    const statement_preview = "S2lqZWxlbnRlbSwgaG9neSBlenQgYSBtZWdvbGTDoXN0IMOpbiBrw7xsZHRlbSBiZSBhIFN6ZXJ2ZXJvbGRhbGkgd2VicHJvZ3JhbW96w6FzIExhcmF2ZWwgYmVhZGFuZMOzIGZlbGFkYXTDoWhvei4KQSBmZWxhZGF0IGJlYWTDoXPDoXZhbCBlbGlzbWVyZW0sIGhvZ3kgdHVkb23DoXN1bCB2ZXR0ZW0gYSBueWlsYXRrb3phdGJhbiBmb2dsYWx0YWthdC4KCi0gS2lqZWxlbnRlbSwgaG9neSBleiBhIG1lZ29sZMOhcyBhIHNhasOhdCBtdW5rw6FtLgotIEtpamVsZW50ZW0sIGhvZ3kgbmVtIG3DoXNvbHRhbSB2YWd5IGhhc3puw6FsdGFtIGhhcm1hZGlrIGbDqWx0xZFsIHN6w6FybWF6w7MgbWVnb2xkw6Fzb2thdC4KLSBLaWplbGVudGVtLCBob2d5IG5lbSB0b3bDoWJiw610b3R0YW0gbWVnb2xkw6FzdCBoYWxsZ2F0w7N0w6Fyc2FpbW5haywgw6lzIG5lbSBpcyB0ZXR0ZW0gYXp0IGvDtnp6w6kuCi0gVHVkb23DoXN1bCB2ZXR0ZW0sIGhvZ3kgYXogRcO2dHbDtnMgTG9yw6FuZCBUdWRvbcOhbnllZ3lldGVtIEhhbGxnYXTDs2kgS8O2dmV0ZWxtw6lueXJlbmRzemVyZSAoRUxURSBzemVydmV6ZXRpIMOpcyBtxbFrw7Zkw6lzaSBzemFiw6FseXphdGEsIElJLiBLw7Z0ZXQsIDc0L0MuIMKnKSBraW1vbmRqYSwgaG9neSBtaW5kYWRkaWcsIGFtw61nIGVneSBoYWxsZ2F0w7MgZWd5IG3DoXNpayBoYWxsZ2F0w7MgbXVua8OhasOhdCAtIHZhZ3kgbGVnYWzDoWJiaXMgYW5uYWsgamVsZW50xZFzIHLDqXN6w6l0IC0gc2Fqw6F0IG11bmvDoWpha8OpbnQgbXV0YXRqYSBiZSwgYXogZmVneWVsbWkgdsOpdHPDqWduZWsgc3rDoW3DrXQuCi0gVHVkb23DoXN1bCB2ZXR0ZW0sIGhvZ3kgYSBmZWd5ZWxtaSB2w6l0c8OpZyBsZWdzw7pseW9zYWJiIGvDtnZldGtlem3DqW55ZSBhIGhhbGxnYXTDsyBlbGJvY3PDoXTDoXNhIGF6IGVneWV0ZW1yxZFsLgo=";

    // Nyilatkozat template (<NÉV>, <NEPTUN>, <DATE> mellékelve, amibe behelyettesíthetők az adatok)
    const statement_template = "IyBOeWlsYXRrb3phdAoKw4luLCA8TkFNRT4gKE5lcHR1biBrw7NkOiA8TkVQVFVOPikga2lqZWxlbnRlbSwgaG9neSBlenQgYSBtZWdvbGTDoXN0IMOpbiBrw7xsZHRlbSBiZSBhIFN6ZXJ2ZXJvbGRhbGkgd2VicHJvZ3JhbW96w6FzIExhcmF2ZWwgYmVhZGFuZMOzIGZlbGFkYXTDoWhvei4KQSBmZWxhZGF0IGJlYWTDoXPDoXZhbCBlbGlzbWVyZW0sIGhvZ3kgdHVkb23DoXN1bCB2ZXR0ZW0gYSBueWlsYXRrb3phdGJhbiBmb2dsYWx0YWthdC4KCi0gS2lqZWxlbnRlbSwgaG9neSBleiBhIG1lZ29sZMOhcyBhIHNhasOhdCBtdW5rw6FtLgotIEtpamVsZW50ZW0sIGhvZ3kgbmVtIG3DoXNvbHRhbSB2YWd5IGhhc3puw6FsdGFtIGhhcm1hZGlrIGbDqWx0xZFsIHN6w6FybWF6w7MgbWVnb2xkw6Fzb2thdC4KLSBLaWplbGVudGVtLCBob2d5IG5lbSB0b3bDoWJiw610b3R0YW0gbWVnb2xkw6FzdCBoYWxsZ2F0w7N0w6Fyc2FpbW5haywgw6lzIG5lbSBpcyB0ZXR0ZW0gYXp0IGvDtnp6w6kuCi0gVHVkb23DoXN1bCB2ZXR0ZW0sIGhvZ3kgYXogRcO2dHbDtnMgTG9yw6FuZCBUdWRvbcOhbnllZ3lldGVtIEhhbGxnYXTDs2kgS8O2dmV0ZWxtw6lueXJlbmRzemVyZSAoRUxURSBzemVydmV6ZXRpIMOpcyBtxbFrw7Zkw6lzaSBzemFiw6FseXphdGEsIElJLiBLw7Z0ZXQsIDc0L0MuIMKnKSBraW1vbmRqYSwgaG9neSBtaW5kYWRkaWcsIGFtw61nIGVneSBoYWxsZ2F0w7MgZWd5IG3DoXNpayBoYWxsZ2F0w7MgbXVua8OhasOhdCAtIHZhZ3kgbGVnYWzDoWJiaXMgYW5uYWsgamVsZW50xZFzIHLDqXN6w6l0IC0gc2Fqw6F0IG11bmvDoWpha8OpbnQgbXV0YXRqYSBiZSwgYXogZmVneWVsbWkgdsOpdHPDqWduZWsgc3rDoW3DrXQuCi0gVHVkb23DoXN1bCB2ZXR0ZW0sIGhvZ3kgYSBmZWd5ZWxtaSB2w6l0c8OpZyBsZWdzw7pseW9zYWJiIGvDtnZldGtlem3DqW55ZSBhIGhhbGxnYXTDsyBlbGJvY3PDoXTDoXNhIGF6IGVneWV0ZW1yxZFsLgoKS2VsdDogPERBVEU+Cg==";

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
        'resources/views/welcome.blade.php',
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
                    $this->io->success("A nyilatkozat korábban már ki lett töltve " . $name . " névre és " . $neptun . " Neptun kódra.");
                    $this->io->note("Ha a fenti adatok tévesek, akkor töröld ki a STATEMENT.txt fájlt, majd hívd meg újra a zip parancsot, ilyenkor újra meg fog jelenni a nyilatkozat kitöltő.");
                    $this->newLine();
                    return true;
                } else {
                    $this->warn("A korábban kitöltött nyilatkozat ellenőrzése nem sikerült, ezért újra ki kell tölteni.");
                    $this->newLine();
                }
            }
        }
        // Nyilatkozat megjelenítése a hallgatónak, majd az elfogadás, és az adatok bekérése
        $this->line('NYILATKOZAT:');
        $this->newLine();
        $this->line(base64_decode(self::statement_preview));
        $this->newLine();
        if ($this->confirm('Elolvastad, elfogadod, és magadra nézve kötelező érvényűnek tekinted a fenti nyilatkozatot?')) {
            $this->info("Kérjük, add meg a nevedet és a Neptun kódodat, hogy be tudjuk helyettesíteni azokat a nyilatkozatba.");
            // Név bekérése
            $name = $this->validatedAsk('Mi a neved?', [
                'required',
                'min:3',
                'max:128',
                'regex:/^[\pL\s\-]+$/u'
            ], [
                'required' => 'A név megadása kötelező.',
                'min' => 'A név hossza legalább :min karakter.',
                'max' => 'A név nem lehet hosszabb, mint :max karakter.',
                'regex' => 'A név alfanumerikus karakterekből és szóközökből állhat.'
            ]);
            // Neptun kód bekérése
            $neptun = Str::upper($this->validatedAsk('Mi a Neptun kódod?', [
                'required',
                'string',
                'size:6',
                'regex:/[a-zA-Z0-9]/'
            ], [
                'required' => 'A Neptun kód megadása kötelező.',
                'size' => 'A Neptun kód hossza pontosan :size karakter.',
                'regex' => 'A Neptun kód csak A-Z karakterekből és számokból állhat.'
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
            $this->io->success("A nyilatkozat kitöltése sikeresen megtörtént " . $name . " névre és " . $neptun . " Neptun kódra.");
            $this->io->note("Ha a fenti adatok tévesek, akkor töröld ki a STATEMENT.txt fájlt, majd hívd meg újra a zip parancsot, ilyenkor újra meg fog jelenni a nyilatkozat kitöltő.");
        } else {
            $this->error('A nyilatkozat a tárgy követelményei szerint kötelező a beadandó leadásához és az értékelés megszerzéséhez.');
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
                'Ezekre a fájlokra szükség van:',
                ...$files_diff
            ]);
        }

        if (!$error) {
            $this->io->success('Az előzetes, automatizált ellenőrzéseink szerint a projekted rendben van.');
        }
        return !$error;
    }

    private function zip() {
        // zipfiles elkészítése, ha még nem létezik
        if (!(file_exists(base_path('zipfiles')) && is_dir(base_path('zipfiles')))) {
            mkdir(base_path('zipfiles'));
            $this->info("zipfiles mappa létrehozva a zip fájlok számára");
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
        $this->io->success('A zip fájl elkészült: ' . $zip_name . ' (méret: ' . $zip_size->format('kB') . ')');
        $this->io->note('A feladat megfelelő, hiánytalan beadása a hallgató felelőssége, ezért mindenképp ellenőrizd azt, mielőtt beadod!');
        $this->io->note('A legjobb, ha kicsomagolod és telepíted a feladatban látható parancsokkal, hogy minden jól működik-e, mintha az oktatók javítanák!');

        // Nagy méretű zip fájl esetén figyelmeztessük a hallgatót, valószínűleg bennehagyott valamit, amire nincs is szükség
        if ($zip_size->isGreaterThan(\ByteUnits\Binary::megabytes(2))) {
            $this->io->warning('A zip fájl mérete nagyobb a megszokottnál, kérjük ellenőrid, vannak-e benne felesleges dolgok, pl. képek, stb!');
        } else if ($zip_size->isGreaterThan(\ByteUnits\Binary::megabytes(10))) {
            $this->io->error('A zip fájl mérete JÓVAL nagyobb a megszokottnál, kérjük ellenőrid, vannak-e benne felesleges dolgok, pl. képek, stb!');
        }
        return true;
    }

    public function handle() {
        $this->io = new SymfonyStyle($this->input, $this->output);
        $this->io->title('Szerveroldali webprogramozás - Automatikus zippelő Laravelhez');
        $this->init();
        $this->io->section('1. lépés: Nyilatkozat');
        if ($this->statement()) {
            $this->io->section('2. lépés: Projekt ellenőrzése');
            if ($this->check()) {
                $this->io->section('3. lépés: Becsomagolás');
                $this->zip();
            }
        }
        return 0;
    }
}
