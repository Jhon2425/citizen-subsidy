<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'logged_in_at',
        'logged_out_at',
    ];

    protected function casts(): array
    {
        return [
            'logged_in_at'  => 'datetime',
            'logged_out_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Used by the view as $history->browser */
    protected function browser(): Attribute
    {
        return Attribute::get(function () {
            $ua = (string) $this->user_agent;

            return match (true) {
                str_contains($ua, 'Edg/')                                   => 'Edge',
                str_contains($ua, 'OPR/') || str_contains($ua, 'Opera')     => 'Opera',
                str_contains($ua, 'Firefox/')                               => 'Firefox',
                str_contains($ua, 'Chrome/')                                => 'Chrome',
                str_contains($ua, 'Safari/')                                => 'Safari',
                default                                                     => 'Unknown browser',
            };
        });
    }

    /** Used by the view as $history->platform */
    protected function platform(): Attribute
    {
        return Attribute::get(function () {
            $ua = (string) $this->user_agent;

            return match (true) {
                str_contains($ua, 'Windows')                                => 'Windows',
                str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')    => 'iOS',
                str_contains($ua, 'Android')                                => 'Android',
                str_contains($ua, 'Mac OS X')                               => 'macOS',
                str_contains($ua, 'Linux')                                  => 'Linux',
                default                                                     => 'Unknown device',
            };
        });
    }
}