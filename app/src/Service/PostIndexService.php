<?php
namespace App\Service;

use App\Repository\PostIndexRepository;
use App\DTO\PostIndexDTO;
use App\Domain\PostIndexSource;

/**
 * Application service responsible for managing post indexes.
 *
 * Contains business logic for:
 * - retrieving data
 * - searching
 * - adding manual records
 * - deleting records
 */

class PostIndexService
{
    private const LIMIT = 50;

    public function __construct(private PostIndexRepository $repository) {}

    /**
     * Get a single post index by its unique post code.
     *
     * @param string $postCode Unique identifier of the post office.
     * @return PostIndexDTO|null Returns DTO if found, otherwise null.
     */
    public function getByPostCode(string $code): ?PostIndexDTO
    {
        return $this->repository->findByPostCode($code);
    }

    /**
     * Search post indexes by address fragment (city or post office).
     *
     * @param string $query Search string.
     * @param int $page Page number.
     * @return PostIndexDTO[]
     */
    public function search(string $query, int $page = 1): array
    {
        $offset = ($page - 1) * self::LIMIT;
        $dtos = $this->repository->searchByAddress(
            $query,
            self::LIMIT,
            $offset,
        );
        $total = $this->repository->countBySearch($query);

        return [
            "data" => array_map(fn(PostIndexDTO $d) => $d->toArray(), $dtos),
            "meta" => $this->buildMeta($total, $page),
        ];
    }

    /**
     * Retrieve all post indexes with pagination.
     *
     * Used for listing records without applying search filters.
     *
     * @param int $page Page number (starts from 1).
     * @param int $limit Number of records per page.
     * @return PostIndexDTO[] List of post indexes.
     */
    public function listAll(int $page = 1): array
    {
        $offset = ($page - 1) * self::LIMIT;
        $dtos = $this->repository->getAll(self::LIMIT, $offset);
        $total = $this->repository->countAll();

        return [
            "data" => array_map(fn(PostIndexDTO $d) => $d->toArray(), $dtos),
            "meta" => $this->buildMeta($total, $page),
        ];
    }

    /**
     * Add manually created post indexes.
     * These records are marked with source = 'manual'
     * and must NOT be removed by the import script.
     *
     * @param PostIndexDTO[] $dtos
     * @return void
     */
    public function add(array $dtos): void
    {
        foreach ($dtos as $dto) {
            $this->repository->insertOrUpdate($dto, PostIndexSource::MANUAL);
        }
    }

    /**
     * Delete post indexes by their post codes.
     *
     * @param string[] $postCodes
     * @return void
     */
    public function delete(array $postCodes): void
    {
        $this->repository->delete($postCodes);
    }

    private function buildMeta(int $total, int $page): array
    {
        return [
            "total" => $total,
            "per_page" => self::LIMIT,
            "current_page" => $page,
            "last_page" => (int) ceil($total / self::LIMIT),
            "from" => $total > 0 ? ($page - 1) * self::LIMIT + 1 : 0,
            "to" => min($page * self::LIMIT, $total),
        ];
    }
}
