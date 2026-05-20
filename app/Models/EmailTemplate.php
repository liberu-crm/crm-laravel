<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    use HasFactory, IsTenantModel;

    protected $fillable = [
        'name',
        'subject',
        'body',
        'html_body',
        'category',
        'is_active',
        'created_by',
        'metadata',
        'variables',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'variables' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Replace template variables with actual values
     */
    public function render(array $data): array
    {
        $subject = $this->subject;
        $body = $this->body;
        $htmlBody = $this->html_body;

        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $subject = str_replace($placeholder, $value, $subject);
            $body = str_replace($placeholder, $value, $body);
            $htmlBody = str_replace($placeholder, $value, $htmlBody);
        }

        return [
            'subject' => $subject,
            'body' => $body,
            'html_body' => $htmlBody,
        ];
    }

    /**
     * Extract variables from template content
     */
    public static function extractVariables(string $content): array
    {
        preg_match_all('/\{\{([^}]+)\}\}/', $content, $matches);
        return array_unique($matches[1] ?? []);
    }
}
