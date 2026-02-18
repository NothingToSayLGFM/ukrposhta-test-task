<?php

namespace App\Service;

use App\Domain\PostIndexSource;
use App\Repository\PostIndexRepository; 
use ZipArchive;

class Importer
{
    private int $batchSize = 500;

    public function __construct(private PostIndexRepository $repository)
    {
    }

    /**
     * Main import process
     */
    public function run(string $archivePath): void
    {
        $zip = new ZipArchive();


        if ($zip->open($archivePath) !== true) {
            throw new \RuntimeException('Cannot open archive');
        }

        $stream = $zip->getStream($zip->getNameIndex(0));
        
        if (!$stream) {
            throw new \RuntimeException('Cannot read CSV from archive');
        }

        $this->repository->createTempImportedTable();
      
        do {
            $header = fgetcsv($stream, 0, ',');
            if ($header === false) {
                throw new \RuntimeException('Empty CSV');
            }
            $header = array_map('trim', $header);
            $header = array_filter($header, fn($h) => $h !== ''); 
            $header = array_values($header);
        } while (count(array_filter($header)) === 0);

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

        if ($header === false) {
            throw new \RuntimeException('Empty CSV');
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

        $map = $this->mapColumns($header);

        $batch = [];
        $importedKeys = [];

        while (($row = fgetcsv($stream, 0, ',')) !== false) {

            if ($row[0] === null || $row[0] === '') {
                array_shift($row);
            }
            $data = $this->normalizeRow($row, $map);

            if (!$data) {
                continue;
            }

            $batch[] = $data;
            $importedKeys[] = $data['postCode'];

            if (count($batch) >= $this->batchSize) {
                $this->flush($batch, $importedKeys);
                $batch = [];
                $importedKeys = [];
            }
        }

        if ($batch) {
            $this->flush($batch, $importedKeys);
        }

        $this->cleanup();

        fclose($stream);
        $zip->close();
    }

    private function col(array $map, string $needle): int
    {
        foreach ($map as $name => $index) {
            if (mb_stripos($name, $needle) !== false) {
                
                return $index;
            }
        }

        throw new \RuntimeException("Column not found: $needle");
    }

    /**
     * Map CSV columns by name
     */
    
    private function mapColumns(array $header): array
    {
        $map = [];

        foreach ($header as $i => $name) {
            $map[$name] = $i;
        }

        return $map;
    }

    /**
     * Normalize CSV row into DB structure
     */
  private function normalizeRow(array $row, array $map): ?array
    {
        $postCode   = trim($row[$this->col($map, 'post office')] ?? '');
        if ($postCode === '') {
            return null;
        }

        $region      = trim($row[$this->col($map, 'Область')] ?? '');
        $districtOld = trim($row[$this->col($map, 'старий')] ?? '');
        $districtNew = trim($row[$this->col($map, 'новий')] ?? '');
        $city        = trim($row[$this->col($map, 'Населений')] ?? '');
        $postOffice  = trim($row[$this->col($map, 'Вiддiлення')] ?? '');

        $hash = md5("$region|$districtOld|$districtNew|$city|$postOffice");

        return [
            'postCode'    => $postCode,
            'region'      => $region,
            'districtOld' => $districtOld,
            'districtNew' => $districtNew,
            'city'        => $city,
            'postOffice'  => $postOffice,
            'hash'        => $hash,
        ];
    }


    /**
     * Flush batch to DB using UPSERT
     */
    private function flush(array $batch, array $keys): void
    {
        $this->repository->begin();

        $this->repository->bulkUpsert($batch, PostIndexSource::IMPORT);
        $this->repository->insertImportedKeys($keys);

        $this->repository->commit();
    }

    /**
     * Remove outdated records (only imported ones)
     */
    private function cleanup(): void
    {
       $this->repository->deleteMissingImported();
    }
}
