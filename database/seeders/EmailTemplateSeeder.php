<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // All email templates have been removed from the system
        // This seeder is kept for future use if email templates are needed again
        
        $templates = [];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                [
                    'key' => $template['key'],
                    'language' => $template['language']
                ],
                $template
            );
        }
    }
}