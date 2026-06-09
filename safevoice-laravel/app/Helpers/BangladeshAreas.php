<?php
namespace App\Helpers;

/**
 * Bangladesh administrative areas — 8 divisions, 64 districts.
 * Used for lawyer serving_area validation and location-based routing.
 */
class BangladeshAreas
{
    // ── Core data ──────────────────────────────────────────────────
    private static array $data = [
        'Dhaka' => [
            'Dhaka', 'Gazipur', 'Narayanganj', 'Narsingdi', 'Manikganj',
            'Munshiganj', 'Rajbari', 'Faridpur', 'Gopalganj', 'Madaripur',
            'Shariatpur', 'Kishoreganj', 'Tangail',
        ],
        'Chittagong' => [
            'Chittagong', 'Cox\'s Bazar', 'Comilla', 'Feni', 'Noakhali',
            'Lakshmipur', 'Chandpur', 'Brahmanbaria', 'Khagrachari',
            'Rangamati', 'Bandarban',
        ],
        'Rajshahi' => [
            'Rajshahi', 'Bogura', 'Joypurhat', 'Naogaon', 'Natore',
            'Chapainawabganj', 'Pabna', 'Sirajganj',
        ],
        'Khulna' => [
            'Khulna', 'Bagerhat', 'Satkhira', 'Jessore', 'Jhenaidah',
            'Magura', 'Narail', 'Kushtia', 'Meherpur', 'Chuadanga',
        ],
        'Barisal' => [
            'Barisal', 'Bhola', 'Patuakhali', 'Pirojpur', 'Jhalokati',
            'Barguna',
        ],
        'Sylhet' => [
            'Sylhet', 'Moulvibazar', 'Habiganj', 'Sunamganj',
        ],
        'Rangpur' => [
            'Rangpur', 'Dinajpur', 'Gaibandha', 'Kurigram', 'Lalmonirhat',
            'Nilphamari', 'Panchagarh', 'Thakurgaon',
        ],
        'Mymensingh' => [
            'Mymensingh', 'Jamalpur', 'Sherpur', 'Netrokona',
        ],
    ];

    // ── Public API ─────────────────────────────────────────────────

    /** All 8 division names */
    public static function divisions(): array
    {
        return array_keys(self::$data);
    }

    /** All districts of a specific division */
    public static function districtsOf(string $division): array
    {
        return self::$data[$division] ?? [];
    }

    /** Flat list of all 64 districts */
    public static function allDistricts(): array
    {
        return array_merge(...array_values(self::$data));
    }

    /** Which division does a district belong to? Returns null if not found. */
    public static function divisionOfDistrict(string $district): ?string
    {
        foreach (self::$data as $division => $districts) {
            if (in_array($district, $districts, true)) {
                return $division;
            }
        }
        return null;
    }

    /**
     * Structured payload for the Areas API endpoint.
     * Returns divisions array, each with their districts list.
     *
     * Example response shape:
     * [
     *   { "division": "Dhaka", "districts": ["Dhaka","Gazipur",...] },
     *   ...
     * ]
     */
    public static function forApi(): array
    {
        return array_map(
            fn($division, $districts) => [
                'division'  => $division,
                'districts' => $districts,
            ],
            array_keys(self::$data),
            array_values(self::$data),
        );
    }

    /**
     * Structured payload for UI dropdowns (alias of forApi()).
     * Returns: [{ division: "Dhaka", districts: ["Dhaka", "Gazipur", ...] }, ...]
     */
    public static function structured(): array
    {
        return self::forApi();
    }
}