<?php

namespace App\Services;

use App\Models\Visitor;
use App\Models\Statistic;
use App\Models\Log;
use App\Models\Error;
use App\Models\UnprocessedItem;
use Carbon\Carbon;

class VisitProcessorService
{
    public function processFiles($files)
    {
        foreach ($files as $file) {
            try {
                if ($this->validateFile($file)) {
                    $data = $this->parseFile($file);
                    $log = $this->createLog($file);
                    foreach ($data as $record) {
                        $this->processRecord($record, $log);
                    }
                    $this->deleteFile($file);
                } else {
                    $this->logFileError($file, 'Invalid file format');
                }
            } catch (Exception $e) {
                $this->logFileError($file, $e->getMessage());
            }
        }
    }

    protected function validateFile($file)
    {
        $isValid = true;
        try {
            if (($handle = fopen($file, "r")) !== false) {
                $headers = fgetcsv($handle, 1000, ",");

                $expectedHeaders = $this->generateExpectedHeaders();

                if (count($headers) < count($expectedHeaders)) {
                    $isValid = false;
                } else {
                    foreach ($expectedHeaders as $index => $expectedHeader) {
                        if ($index !== 1 && $headers[$index] !== $expectedHeader) {
                            $isValid = false;
                            break;
                        }
                    }
                }
                fclose($handle);
            } else {
                $isValid = false;
            }
        } catch (Exception $e) {
            throw new Exception("Error validating file: " . $e->getMessage());
        }
        return $isValid;
    }

    protected function generateExpectedHeaders()
    {
        return ['email', 'jvy', 'badmail', 'unsubscribe', 'send_date', 'open_date', 'opens', 'viral_opens', 'click_date', 'clicks', 'viral_clicks', 'links', 'ips', 'browsers', 'platforms'];
    }

    protected function parseFile($file)
    {
        $data = [];
        try {
            if (($handle = fopen($file, "r")) !== false) {
                $headers = fgetcsv($handle, 1000, ",");
                while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                    $record = array_combine($headers, $row);
                    $data[] = $this->sanitizeRecord($record);
                }
                fclose($handle);
            }
        } catch (Exception $e) {
            throw new Exception("Error parsing file: " . $e->getMessage());
        }
        return $data;
    }

    protected function sanitizeRecord($record)
    {
        return array_map('trim', $record);
    }

    protected function createLog($file)
    {
        return Log::create([
            'file_name' => basename($file),
            'successful_records' => 0,
            'error_records' => 0,
            'processing_date' => Carbon::now(),
        ]);
    }

    protected function processRecord($record, $log)
    {
        try {
            if (!$this->validateRecord($record)) {
                throw new Exception('Invalid record data');
            }

            $visitor = Visitor::firstOrCreate(
                ['email' => $record['email']],
                ['first_visit_date' => $record['send_date'], 'last_visit_date' => $record['send_date'], 'total_visits' => 1, 'current_year_visits' => 1, 'current_month_visits' => 1]
            );

            if ($visitor->exists) {
                $visitor->last_visit_date = $record['send_date'];
                $visitor->total_visits++;
                $visitor->current_year_visits++;
                $visitor->current_month_visits++;
                $visitor->save();
            }

            $this->processStatistics($record, $visitor);
            $log->increment('successful_records');
        } catch (Exception $e) {
            Error::create([
                'log_id' => $log->id,
                'file' => $log->file_name,
                'email' => $record['email'] ?? 'N/A',
                'error_description' => $e->getMessage(),
            ]);

            $log->increment('error_records');
        }
    }

    protected function validateRecord($record)
    {
        if (!isset($record['email'], $record['send_date'])) {
            return false;
        }

        if (!filter_var($record['email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (!$this->validateDate($record['send_date'], 'd/m/Y H:i')) {
            return false;
        }

        if (isset($record['opens']) && !is_numeric($record['opens'])) {
            return false;
        }

        if (isset($record['clicks']) && !is_numeric($record['clicks'])) {
            return false;
        }

        if (isset($record['ips'])) {
            $ips = explode(',', trim($record['ips'], '"'));
            foreach ($ips as $ip) {
                if (!filter_var(trim($ip), FILTER_VALIDATE_IP)) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function validateDate($date, $format = 'd/m/Y H:i')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    protected function processStatistics($record, $visitor)
    {
        Statistic::create([
            'visitor_id' => $visitor->id,
            'send_date' => $record['send_date'],
            'open_date' => $record['open_date'],
            'opens' => $record['opens'],
            'viral_opens' => $record['viral_opens'],
            'click_date' => $record['click_date'],
            'clicks' => $record['clicks'],
            'viral_clicks' => $record['viral_clicks'],
            'links' => $record['links'],
            'ips' => $record['ips'],
            'browsers' => $record['browsers'],
            'platforms' => $record['platforms'],
        ]);
    }
    protected function logFileError($file, $message)
    {
        UnprocessedItem::create([
            'log_id' => null,
            'file' => basename($file),
            'email' => 'N/A',
            'reason_for_failure' => $message,
            'reprocess_attempted' => false,
        ]);
    }
}
