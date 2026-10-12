<?php

namespace App\Services\Dashboard\Concerns;

/**
 * Per-section dashboard permissions.
 *
 * A section is shown when the viewer holds its permission ("{PERMISSION_MODULE}.{section}",
 * defined in config/permission_matrix.php) AND any one of the module abilities it needs
 * (SECTION_ABILITIES: section => list of ability keys, empty = no module data required).
 *
 * The using class must define PERMISSION_MODULE, SECTION_ABILITIES, $user and can().
 */
trait GatesSections
{
    /** @var array<string, bool>|null */
    private ?array $sections = null;

    public function shows(string $section): bool
    {
        return $this->sections()[$section] ?? false;
    }

    /**
     * @return array<string, bool> section => visible
     */
    public function sections(): array
    {
        if ($this->sections !== null) {
            return $this->sections;
        }

        $this->sections = [];

        foreach (static::SECTION_ABILITIES as $section => $abilities) {
            $this->sections[$section] = $this->user->can(static::PERMISSION_MODULE.'.'.$section)
                && ($abilities === [] || array_filter($abilities, fn (string $ability) => $this->can($ability)) !== []);
        }

        return $this->sections;
    }

    /**
     * Part of cache keys, so a permission change is reflected immediately.
     */
    private function accessFingerprint(): string
    {
        return substr(md5(json_encode([$this->abilities, $this->sections()])), 0, 12);
    }
}
