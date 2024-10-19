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
            var_dump($file);
            try {
                if ($this->validateFile($file)) {
                    $data = $this->parseFile($file);
                    $log = $this->createLog($file);
                    foreach ($data as $record) {
                        $this->processRecord($record, $log);
                    }
                } else {
                    echo('validacion incorrecta');
                    exit();
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
                            var_dump($headers[$index], $expectedHeader);
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
        return [
            'email',
            'jk',
            'Badmail',
            'Baja',
            'Fecha envio',
            'Fecha open',
            'Opens',
            'Opens virales',
            'Fecha click',
            'Clicks',
            'Clicks virales',
            'Links',
            'IPs',
            'Navegadores',
            'Plataformas'
        ];

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
                throw new \Exception('Invalid record data');
            }
            $formatedDate = \DateTime::createFromFormat('d/m/Y H:i', $record['Fecha envio']);
            $visitor = Visitor::firstOrCreate(
                ['email' => $record['email']],
                ['first_visit_date' => $formatedDate, 'last_visit_date' => $formatedDate, 'total_visits' => 1, 'current_year_visits' => 1, 'current_month_visits' => 1]
            );

            if ($visitor->exists) {
                $visitor->last_visit_date = $formatedDate;
                $visitor->total_visits++;
                $visitor->current_year_visits++;
                $visitor->current_month_visits++;
                $visitor->save();
            }

            $this->processStatistics($record, $visitor, $log);
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
        if (!isset($record['email'], $record['Fecha envio'])) {
            echo('data principal');
            return false;
        }

        if (!filter_var($record['email'], FILTER_VALIDATE_EMAIL)) {
            echo('email');
            return false;
        }

        if (!$this->validateDate($record['Fecha envio'], 'd/m/Y H:i')) {
            echo('fecha envio');
            return false;
        }

        if (isset($record['Opens']) && !is_numeric($record['Opens']) && $record['Opens'] != '-') {
            echo('Opens no numeric');
            return false;
        }

        if (isset($record['Clicks']) && !is_numeric($record['Clicks']) && $record['Opens'] != '-') {
            echo('Clicks no numerico');
            return false;
        }

        if (isset($record['IPs']) && $record['IPs'] !== '-') {
            $ips = explode(',', trim($record['IPs'], '"'));
            foreach ($ips as $ip) {
                if (!filter_var(trim($ip), FILTER_VALIDATE_IP)) {
                    echo('algo con las ips');
                    return false;
                }
            }
        } else {
            echo('Campo IPs vacío o inválido, se salta el proceso.');
        }


        return true;
    }

    protected function validateDate($date, $format = 'd/m/Y H:i')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    protected function processStatistics($record, $visitor, $log)
    {
        try {
            Statistic::create([
                'visitor_id' => $visitor->id,
                'send_date' => \DateTime::createFromFormat('d/m/Y H:i', $record['Fecha envio']),
                'open_date' => $record['Fecha open'] === '-' || $record['Fecha open'] === ''  ? null : \DateTime::createFromFormat('d/m/Y H:i', $record['Fecha open']),
                'opens' => $record['Opens'],
                'viral_opens' => $record['Opens virales'],
                'click_date' => $record['Fecha click'] === '-' ? null : \DateTime::createFromFormat('d/m/Y H:i', $record['Fecha click']),
                'clicks' => $record['Clicks'],
                'viral_clicks' => $record['Clicks virales'],
                'links' => $record['Links'] === '-' ? null : $record['Links'],
                'ips' => $record['IPs'],
                'browsers' => $record['Navegadores'],
                'platforms' => $record['Plataformas'],
            ]);
        } catch (\Exception $e) {
            UnprocessedItem::create([
                'log_id' => $log->id,
                'file' => $log->file_name,
                'email' => $record['email'],
                'reason_for_failure' => $e->getMessage(),
                'reprocess_attempted' => false,
            ]);
        }

    }

    protected function logFileError($file, $message)
    {
        $log = $this->createLog($file);
    }
}
