<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Model;

class GeoGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_blacklist',
        'is_global',
        'uuid',
        'aws_cloudfront_distribution_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function countries()
    {
        return $this->belongsToMany(Country::class, 'country_geo_group_maps');
    }
    public function awsCloudfrontDistribution()
    {
        return $this->belongsTo(AwsCloudfrontDistribution::class);
    }

}
