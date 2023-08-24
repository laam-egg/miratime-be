<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateJwtSecret extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:jwt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the JWT secret used for refresh token verification.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // https://stackoverflow.com/a/11449627/13680015
        // https://stackoverflow.com/questions/11449577/why-is-base64-encode-adding-a-slash-in-the-result#comment40679686_11449627
        $key = str_replace(
            ['+', '/'],
            ['-', '_'],
            base64_encode(random_bytes(48))
        );
        // About the number 48 above:
        // I want the JWT secret to have exactly 64 bytes, so if I make a JWT secret
        // using base64_encode, I need to encode a random string of length 48 since:
        // 4n/3 = 64 => n = 48.
        // https://stackoverflow.com/a/13378842/13680015

        
        // https://stackoverflow.com/a/32307974/13680015
        $path = base_path('.env');

        if (file_exists($path)) {
            file_put_contents($path, str_replace(
                'APP_JWT_SECRET='.($this->laravel['config']['jwt.secret']), "APP_JWT_SECRET=$key", file_get_contents($path)
            ));
        }
    }
}
