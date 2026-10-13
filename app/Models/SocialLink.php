<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    protected $fillable = [
        'label', 'url', 'icon_type', 'icon_class', 'icon_image', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    // Common Bootstrap Icons presets
    public const PRESETS = [
        'bi-facebook'   => 'Facebook',
        'bi-instagram'  => 'Instagram',
        'bi-twitter-x'  => 'X / Twitter',
        'bi-youtube'    => 'YouTube',
        'bi-tiktok'     => 'TikTok',
        'bi-linkedin'   => 'LinkedIn',
        'bi-whatsapp'   => 'WhatsApp',
        'bi-telegram'   => 'Telegram',
        'bi-globe'      => 'Website',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getIconImageUrlAttribute(): ?string
    {
        return $this->icon_image ? url('storage/' . $this->icon_image) : null;
    }
}
