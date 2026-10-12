<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleHelpStep extends Model
{
    public const MEDIA_NONE = 'none';

    public const MEDIA_IMAGE = 'image';

    public const MEDIA_VIDEO = 'video';

    public const MEDIA_EMBED = 'embed';

    public const MEDIA_TYPES = [
        self::MEDIA_NONE => 'None',
        self::MEDIA_IMAGE => 'Image',
        self::MEDIA_VIDEO => 'Video',
        self::MEDIA_EMBED => 'Embed URL',
    ];

    protected $fillable = [
        'module_help_id',
        'title',
        'description',
        'media_type',
        'media_path',
        'media_original_name',
        'embed_url',
        'media_caption',
        'cta_label',
        'cta_url',
        'target_selector',
        'placement',
        'is_required',
        'completion_key',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_required' => 'boolean',
        ];
    }

    public function guide(): BelongsTo
    {
        return $this->belongsTo(ModuleHelp::class, 'module_help_id');
    }

    public function mediaUrl(): ?string
    {
        if (! $this->media_path) {
            return null;
        }

        return asset('storage/'.$this->media_path);
    }

    /**
     * Browser href for the step CTA.
     * App-relative paths (/admin/...) get the current request base path
     * so subdirectory installs (e.g. /iv_classes/public) do not 404.
     */
    public function ctaHref(): ?string
    {
        $url = trim((string) $this->cta_url);
        $label = trim((string) $this->cta_label);

        if ($url === '' || $label === '') {
            return null;
        }

        $base = rtrim((string) request()->getBasePath(), '/');

        if (str_starts_with($url, '//')) {
            return $url;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            $parts = parse_url($url);
            $path = (string) ($parts['path'] ?? '');
            $query = isset($parts['query']) ? '?'.$parts['query'] : '';

            // Absolute URL that skipped the app subdirectory → rebuild with base path.
            if ($path !== '' && str_starts_with($path, '/admin')) {
                return $base.$path.$query;
            }

            return $url;
        }

        $path = '/'.ltrim($url, '/');
        if ($base !== '' && (str_starts_with($path, $base.'/') || $path === $base)) {
            return $path;
        }

        return $base.$path;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPreviewArray(): array
    {
        $ctaHref = $this->ctaHref();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'media_type' => $this->media_type,
            'media_caption' => $this->media_caption,
            'media_url' => $this->mediaUrl(),
            'media_original_name' => $this->media_original_name,
            'embed_url' => $this->embed_url,
            'cta_label' => $ctaHref ? trim((string) $this->cta_label) : null,
            'cta_url' => $ctaHref,
            'target' => trim((string) $this->target_selector) ?: null,
            'placement' => in_array($this->placement, ['top', 'bottom', 'left', 'right'], true)
                ? $this->placement
                : 'bottom',
        ];
    }
}
