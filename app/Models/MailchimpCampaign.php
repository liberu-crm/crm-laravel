<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailchimpCampaign extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $table = 'mailchimp_campaigns';

    protected $fillable = [
        'mailchimp_id',
        'name',
        'subject_line',
        'subject_line_a',
        'subject_line_b',
        'type',
        'status',
        'winner_criteria',
        'test_size',
    ];
}
