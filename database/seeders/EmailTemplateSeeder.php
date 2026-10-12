<?php

namespace Database\Seeders;

use App\Services\MailTemplateService;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        app(MailTemplateService::class)->syncFromRegistry(overwriteContent: false);
    }
}
