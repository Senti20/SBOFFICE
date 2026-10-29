<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Resolution', 'description' => 'Official resolutions adopted by the Sangguniang Bayan.'],
            ['name' => 'Minutes', 'description' => 'Official session minutes and proceedings.'],
            ['name' => 'Ordinance', 'description' => 'Municipal ordinances and related legislative records.'],
            ['name' => 'Other', 'description' => 'Other approved public records.'],
        ];

        foreach ($types as $type) {
            DocumentType::updateOrCreate(['name' => $type['name']], $type);
        }

        $adminEmail = env('ADMIN_EMAIL', 'admin@sbsecretarybontoc.gov.ph');
        $adminPassword = env('ADMIN_PASSWORD', 'ChangeMe123!');
        $adminName = env('ADMIN_NAME', 'SB Secretary Admin');

        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'password' => Hash::make($adminPassword),
                'role' => 'admin', // Restores the administrator privilege check
            ]
        );

        $sourceDirectory = database_path('seed-data/documents');
        if (! is_dir($sourceDirectory)) {
            return;
        }

        $files = glob($sourceDirectory.'/*.pdf') ?: [];
        foreach ($files as $sourcePath) {
            $originalName = basename($sourcePath);
            if (Document::where('filename', $originalName)->exists()) {
                continue;
            }

            preg_match('/^(DOC-\d+)_?(.*)$/i', $originalName, $match);
            $baseTitle = $match[2] ?? pathinfo($originalName, PATHINFO_FILENAME);
            $baseTitle = trim(str_replace(['_', '-'], ' ', $baseTitle));
            $sessionName = Str::headline($baseTitle ?: pathinfo($originalName, PATHINFO_FILENAME));

            $typeName = str_contains(strtolower($sessionName), 'resolution') ? 'Resolution'
                : (str_contains(strtolower($sessionName), 'minute') ? 'Minutes'
                : (str_contains(strtolower($sessionName), 'ordinance') ? 'Ordinance' : 'Other'));

            $targetDirectory = 'documents/seeded';
            $targetPath = $targetDirectory.'/'.$originalName;
            Storage::disk('public')->makeDirectory($targetDirectory);
            Storage::disk('public')->put($targetPath, file_get_contents($sourcePath));

            $document = Document::create([
                'document_id'    => 'DOC-000', // Temporarily set, updated below
                'filename'       => $originalName,
                'session_name'   => $sessionName,
                'type'           => $typeName,
                'session_id'     => '1',
                'session_date'   => now(),
                'sponsors'       => 'SB Secretary',
                'tags'           => 'official, public record',
                'file_path'      => $targetPath,
                'extracted_text' => 'Sample OCR extracted text.',
                'status'         => 'published',
                'is_public'      => true,
            ]);

            if ($document->document_id === 'DOC-000') {
                $document->update([
                    'document_id' => 'DOC-'.str_pad((string) $document->id, 3, '0', STR_PAD_LEFT)
                ]);
            }
        }
    }
}