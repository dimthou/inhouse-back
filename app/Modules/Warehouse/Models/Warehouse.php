<?php

namespace App\Modules\Warehouse\Models;

use App\Modules\Inventory\Models\Stock;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;
    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id',
        'name',
        'location',
        'is_active',
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }
}
