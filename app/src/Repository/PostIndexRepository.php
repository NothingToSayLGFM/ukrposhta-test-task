<?php
namespace App\Repository;

use PDO;
use App\DTO\PostIndexDTO;
use App\Domain\PostIndexSource;

/**
 * Repository responsible for database interaction with post_indexes table.
 *
 * Uses raw PDO as required by the assignment (no ORM).
 */

class PostIndexRepository
{
    public function __construct(private PDO $pdo) {}

    /**
     * Find a single record by primary key.
     *
     * @param string $postCode
     * @return array|null
     */
    public function findByPostCode(string $code): ?PostIndexDTO
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM post_indexes WHERE post_code = ?",
        );
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        return $row ? new PostIndexDTO($row) : null;
    }

    /**
     * Paginate records ordered by post_code ASC.
     *
     * @param int $page
     * @param int $limit
     * @return array[]
     */
    public function searchByAddress(
        string $q,
        int $limit = 50,
        int $offset = 0,
    ): array {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM post_indexes WHERE city LIKE ? OR post_office LIKE ? ORDER BY post_office ASC LIMIT ? OFFSET ?",
        );
        $stmt->execute(["%$q%", "%$q%", $limit, $offset]);
        return array_map(fn($r) => new PostIndexDTO($r), $stmt->fetchAll());
    }

    /**
     * Retrieve paginated list of post indexes ordered by post code.
     *
     * Used for default listing when no search query is provided.
     *
     * @param int $limit  Maximum number of records to return.
     * @param int $offset Offset for pagination (calculated from page).
     *
     * @return PostIndexDTO[] List of post index DTO objects.
     */
    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM post_indexes ORDER BY post_code ASC LIMIT ? OFFSET ?",
        );
        $stmt->execute([$limit, $offset]);
        return array_map(fn($r) => new PostIndexDTO($r), $stmt->fetchAll());
    }

    /**
     * Insert a new post index or update existing one.
     *
     * This method is primarily used for manually managed records.
     * Import operations should use bulkUpsert() instead.
     *
     * Uses UPSERT semantics to avoid duplicate primary keys.
     *
     * @param PostIndexDTO $dto    Data transfer object containing post index data.
     * @param string       $source Data origin (PostIndexSource::MANUAL by default).
     *
     * @return void
     */
    public function insertOrUpdate(
        PostIndexDTO $dto,
        string $source = PostIndexSource::MANUAL,
    ): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO post_indexes
            (post_code, region, district_old, district_new, city, post_office, source, hash)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                region = VALUES(region),
                district_old = VALUES(district_old),
                district_new = VALUES(district_new),
                city = VALUES(city),
                post_office = VALUES(post_office),
                hash = VALUES(hash)
        ");

        $stmt->execute([
            $dto->postCode,
            $dto->region,
            $dto->districtOld,
            $dto->districtNew,
            $dto->city,
            $dto->postOffice,
            $source,
            $dto->hash,
        ]);
    }

    /**
     * Delete manually created post indexes by their post codes.
     *
     * Imported records are protected and will not be removed by this method.
     *
     * @param string[] $postCodes List of post codes to delete.
     *
     * @return void
     */
    public function delete(array $postCodes): void
    {
        $placeholders = implode(",", array_fill(0, count($postCodes), "?"));
        $stmt = $this->pdo->prepare(
            "DELETE FROM post_indexes WHERE post_code IN ($placeholders)",
        );
        $stmt->execute([...$postCodes]);
    }

    /**
     * Start database transaction.
     * Used during bulk import to ensure atomic synchronization.
     */
    public function begin(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Commit current transaction.
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * Create temporary table to track imported post codes.
     *
     * This table exists only during the import session and is used
     * to detect which records must be deleted after synchronization.
     */
    public function createTempImportedTable(): void
    {
        $this->pdo->exec("
            CREATE TEMPORARY TABLE tmp_imported (
                post_code VARCHAR(10) PRIMARY KEY
            )
        ");
    }

    /**
     * Bulk insert or update post index records.
     *
     * Uses MySQL UPSERT to efficiently sync large datasets.
     *
     * @param array[] $rows Normalized rows produced by Importer
     * @param string  $source Data origin (PostIndexSource::IMPORT or MANUAL)
     */
    public function bulkUpsert(array $rows, string $source): void
    {
        if (!$rows) {
            return;
        }

        $placeholders = [];
        $values = [];

        foreach ($rows as $row) {
            $placeholders[] = "(?,?,?,?,?,?,?,?)";

            $values[] = $row["postCode"];
            $values[] = $row["region"];
            $values[] = $row["districtOld"];
            $values[] = $row["districtNew"];
            $values[] = $row["city"];
            $values[] = $row["postOffice"];
            $values[] = $row["hash"];
            $values[] = $source;
        }

        $sql =
            "
            INSERT INTO post_indexes
            (post_code, region, district_old, district_new, city, post_office, hash, source)
            VALUES " .
            implode(",", $placeholders) .
            "
            ON DUPLICATE KEY UPDATE
                region = VALUES(region),
                district_old = VALUES(district_old),
                district_new = VALUES(district_new),
                city = VALUES(city),
                post_office = VALUES(post_office),
                hash = VALUES(hash)
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
    }

    /**
     * Store imported post codes into temporary table.
     *
     * @param string[] $keys
     */
    public function insertImportedKeys(array $keys): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT IGNORE INTO tmp_imported VALUES (?)",
        );

        foreach ($keys as $k) {
            $stmt->execute([$k]);
        }
    }

    /**
     * Remove records that were previously imported but are missing
     * in the current dataset.
     *
     * Manual records are never affected.
     */
    public function deleteMissingImported(): void
    {
        $sql = "
            DELETE FROM post_indexes
            WHERE source = :source
            AND post_code NOT IN (SELECT post_code FROM tmp_imported)
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "source" => PostIndexSource::IMPORT,
        ]);
    }

    public function countAll(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM post_indexes");
        return (int) $stmt->fetchColumn();
    }

    public function countBySearch(string $q): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM post_indexes WHERE city LIKE ? OR post_office LIKE ?",
        );
        $stmt->execute(["%$q%", "%$q%"]);
        return (int) $stmt->fetchColumn();
    }
}
