<?php

namespace App\Console\Commands;

use App\Models\Content;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class SeedKnowledgeHubFromArchive extends Command
{
    protected $signature = 'mmc:seed-knowledge
        {--path= : Path to Knowledge Hub.rar|zip or directory}
        {--type=resource : Content type to assign (article|case_study|resource)}
        {--source=Open Access : Source label}
    ';
    protected $description = 'Seed Knowledge Hub (contents table) from an archive or folder (PDFs, docs, text).';

    public function handle(): int
    {
        $path = $this->option('path') ?: base_path('Knowledge Hub.rar');
        $type = $this->option('type') ?: 'resource';
        $source = $this->option('source') ?: 'Open Access';

        if (!file_exists($path)) {
            $this->error("Path not found: $path");
            return self::FAILURE;
        }

        // Extract if archive
        $workDir = storage_path('app/knowledge_seed/'.uniqid());
        File::ensureDirectoryExists($workDir);

        $lower = strtolower($path);
        if (is_file($path) && str_ends_with($lower, '.rar')) {
            $proc = new Process(['unrar','x','-y',$path,$workDir]);
            $proc->run();
            if (!$proc->isSuccessful()) {
                $this->warn('unrar failed or not installed. Please extract manually and re-run with --path=<folder>.');
                return self::FAILURE;
            }
        } elseif (is_file($path) && (str_ends_with($lower, '.zip'))) {
            $zip = new \ZipArchive();
            if ($zip->open($path) === true) {
                $zip->extractTo($workDir);
                $zip->close();
            } else {
                $this->error('Failed to unzip archive.');
                return self::FAILURE;
            }
        } elseif (is_dir($path)) {
            $workDir = $path;
        } else {
            $this->error('Unsupported file type. Provide .rar, .zip or a directory path.');
            return self::FAILURE;
        }

        // Scan extracted files
        $files = collect(File::allFiles($workDir))
            ->filter(fn($f) => !Str::startsWith($f->getFilename(), '.'))
            ->values();

        $count = 0;
        foreach ($files as $file) {
            $ext = strtolower($file->getExtension());
            // Prioritize pdf/doc/htm/txt
            if (!in_array($ext, ['pdf','doc','docx','txt','html','htm','md'])) {
                continue;
            }

            $title = Str::of($file->getFilenameWithoutExtension())
                        ->replace(['_','-'],' ')
                        ->squish()
                        ->title();

            Content::firstOrCreate(
                ['title' => (string)$title],
                [
                    'type'         => $type,
                    'excerpt'      => null,
                    'body'         => null,
                    'source'       => $source,
                    'published_at' => now(),
                    'url'          => null,
                    'meta'         => [
                        'seed_path' => $file->getPathname(),
                        'ext' => $ext,
                    ],
                ]
            );
            $count++;
        }

        $this->info("Seeded $count Knowledge Hub items as type={$type}, source={$source}.");
        $this->info('You can enhance each with summaries or links later through the admin UI.');
        return self::SUCCESS;
    }
}
