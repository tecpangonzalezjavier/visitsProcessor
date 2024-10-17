<?php

namespace AppConsoleCommands;

use IlluminateConsoleCommand;
use IlluminateSupportFacadesStorage;
use ZipArchive;
use AppServicesVisitProcessorService;
use AppModelsLog;

class ProcessVisitFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'visits:process-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch visit files from SFTP and process them';

    /**
     * The visit processor service instance.
     *
     * @var VisitProcessorService
     */
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
        $this->info('Connecting to SFTP...');
        try {
            $disk = Storage::disk('sftp');
            $files = $disk->files('/home/vinkOS/archivosVisitas');

            $localFiles = [];
            foreach ($files as $file) {
                if (!preg_match('/^report_d+.txt$/', basename($file))) {
                    $this->info("Skipping file with invalid name or extension: $file");
                    continue;
                }

                if (Log::where('file_name', basename($file))->exists()) {
                    $this->info("Skipping already processed file: $file");
                    continue;
                }

                $this->info("Fetching file: $file");
                $localPath = storage_path('app/temp/' . basename($file));
                Storage::put($localPath, $disk->get($file));
                $localFiles[] = $localPath;
            }

            $this->info('Processing files...');
            $this->visitProcessorService->processFiles($localFiles);

            $this->info('Creating backup zip...');
            $zip = new ZipArchive;
            $zipPath = storage_path('app/backups/backup_' . now()->format('Ymd_His') . '.zip');
            if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                foreach ($localFiles as $localFile) {
                    $zip->addFile($localFile, basename($localFile));
                }
                $zip->close();
            } else {
                throw new Exception('Failed to create zip file.');
            }

            $this->info('Deleting processed files...');
            foreach ($localFiles as $localFile) {
                if (file_exists($localFile)) {
                    unlink($localFile);
                }
            }

            $this->info('Process completed successfully.');
        } catch (Exception $e) {
            $this->error('Error processing visit files: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
