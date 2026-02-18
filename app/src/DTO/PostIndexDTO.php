<?php
namespace App\DTO;

/**
 * Data Transfer Object representing a Post Index entity.
 *
 * Used to transfer structured data between layers:
 * Controller/Service/Repository
 */

class PostIndexDTO
{
    public string $postCode;
    public string $region;
    public string $districtOld;
    public string $districtNew;
    public string $city;
    public string $postOffice;
    public string $hash;

    /**
     * @param array $data Raw database or request data.
     */
    public function __construct(array $data)
    {
        $this->postCode = $data["post_code"];
        $this->region = $data["region"];
        $this->city = $data["city"];
        $this->postOffice = $data["post_office"];

        $this->districtOld = $data["district_old"];
        $this->districtNew = $data["district_new"];

        $this->hash = md5(
            $this->postCode .
                $this->region .
                $this->districtOld .
                $this->districtNew .
                $this->city .
                $this->postOffice,
        );
    }

    /**
     * Convert DTO into array representation for JSON output.
     *
     * @return array<string, string>
     */

    public function toArray(): array
    {
        return [
            "post_code" => $this->postCode,
            "region" => $this->region,
            "district_new" => $this->districtNew,
            "district_old" => $this->districtOld,
            "city" => $this->city,
            "post_office" => $this->postOffice,
            "hash" => $this->hash,
        ];
    }
}
