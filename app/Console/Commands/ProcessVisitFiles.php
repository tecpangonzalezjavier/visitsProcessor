<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Storage;
use ZipArchive;
use App\Services\VisitProcessorService;
use App\Models\Log;

class ProcessVisitFiles extends Command
{

    protected $signature = 'visits:process-files';
    protected $description = 'Fetch visit files from SFTP and process them';
    protected $visitProcessorService;

    /**
     * Create a new command instance.
     *
     * @param VisitProcessorService $visitProcessorService
     * @return void
     */
    public function __construct(VisitProcessorService $visitProcessorService)
    {
        parent::__construct();
        $this->visitProcessorService = $visitProcessorService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Connecting to local files...');

        $filesPath = storage_path('files/');

        $files = scandir($filesPath);
        $localFiles = [];

        foreach ($files as $file) {
            if (!preg_match('/^report_\d+\.txt$/', basename($file))) {
                $this->info("Skipping file with invalid name or extension: $file");
                continue;
            }

            if (Log::where('file_name', basename($file))->exists()) {
                $this->info("Skipping already processed file: $file");
                continue;
            }

            $fullFilePath = $filesPath . DIRECTORY_SEPARATOR . $file;

            if (file_exists($fullFilePath)) {
                $this->info("Fetching file: $file");

                $localPath = 'temp/' . basename($file);

                if (!Storage::exists('temp')) {
                    Storage::makeDirectory('temp');
                }

                $contents = file_get_contents($fullFilePath);

                Storage::put($localPath, $contents);

                $localFiles[] = storage_path('app/' . $localPath);
            } else {
                $this->error("El archivo $file no existe o no se puede acceder.");
            }

        }
        $this->info('Processing files...');
        $this->visitProcessorService->processFiles($localFiles);


        $this->info('Creating backup zip...');
        $zip = new ZipArchive;
        $zipPath = storage_path('app/backups/backup_' . now()->format('Ymd_His') . '.zip');
        $zip->open($zipPath, ZipArchive::CREATE);
        foreach ($localFiles as $localFile) {
            $zip->addFile($localFile, basename($localFile));
        }
        $zip->close();
        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            foreach ($localFiles as $localFile) {
                $zip->addFile($localFile, basename($localFile));
            }
            $zip->close();

            $this->info('Backup zip created successfully.');

            $this->info('Deleting processed temporary files...');
            foreach ($localFiles as $localFile) {
                if (file_exists($localFile)) {
                    unlink($localFile);
                }
            }
            $this->info('Deleting original files...');
            foreach ($files as $file) {
                $originalFilePath = $filesPath . DIRECTORY_SEPARATOR . $file;
                if (file_exists($originalFilePath) && preg_match('/^report_\d+\.txt$/', basename($file))) {
                    unlink($originalFilePath);
                }
            }
        } else {
            $this->error('Failed to create zip file.');
        }
        $this->info('Process completed successfully.');


        return 0;
    }

}
