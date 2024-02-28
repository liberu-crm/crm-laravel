<?php

namespace App\Models;

use App\Traits\TenantConnectionResolver;
use File;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use LaravelLiberu\People\Models\Person as CorePerson;

class Person extends CorePerson
{
    use HasFactory;
    use TenantConnectionResolver;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $casts = [
        'deleted_at' => 'datetime',
        'birthday' => 'datetime',
        'deathday' => 'datetime',
        'burial_day' => 'datetime',
        'chan' => 'datetime',
    ];

    protected $guarded = ['id'];

    protected $fillable = [
        'gid',
        'givn',
        'surn',
        'sex',
        'child_in_family_id',
        'description',
        'titl', 'name', 'appellative', 'email', 'phone', 'birthday',
        'deathday', 'burial_day', 'bank', 'bank_account', 'chan', 'rin', 'resn', 'rfn', 'afn',
    ];

    public function fullname(): string
    {
        return $this->givn . ' ' . $this->surn;
    }

    public function getSex(): string
    {
        if ($this->sex === 'F') {
            return 'Female';
        }

        return 'Male';
    }

    public static function getList()
    {
        $persons = self::get();
        $result = [];
        foreach ($persons as $person) {
            $result[$person->id] = $person->fullname();
        }

        return collect($result);
    }

    public static function bootUpdatedBy()
    {
        self::creating(fn($model) => $model->setUpdatedBy());

        self::updating(fn($model) => $model->setUpdatedBy());
    }

    public function setUpdatedBy()
    {
        if (!is_dir(storage_path('app/public'))) {
            // dir doesn't exist, make it
            File::makeDirectory(storage_path() . '/app/public', 0777, true);
        }

        file_put_contents(storage_path('app/public/file.txt'), $this->connection);
        if ($this->connection !== 'tenant' && Auth::check()) {
            $this->updated_by = Auth::id();
        }
    }
}
