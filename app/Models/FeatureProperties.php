<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class FeaturesProperty
 *
 * @property int $features_id
 * @property string|null $id
 * @property string|null $name
 * @property string|null $owner_id
 * @property string|null $owner_name
 * @property string|null $owner_email
 * @property string|null $owner_username
 * @property string|null $address
 * @property string|null $destiny
 * @property string|null $date
 * @property string|null $stability
 * @property string|null $label
 * @property string|null $price
 * @property string|null $region
 * @property string|null $area
 * @property string|null $karbari
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Feature $feature
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|FeaturesProperty newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FeaturesProperty newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FeaturesProperty query()
 * @method static \Illuminate\Database\Eloquent\Builder|FeaturesProperty whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FeaturesProperty whereArea($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FeaturesProperty whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FeaturesProperty whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FeaturesProperty whereDestiny($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FeaturesProperty whereFeaturesId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FeaturesProperty whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FeaturesProperty whereKarbari($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FeaturesProperty whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FeaturesProperty whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FeaturesProperty whereOwnerEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FeaturesProperty whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FeaturesProperty whereOwnerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FeaturesProperty whereOwnerUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FeaturesProperty wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FeaturesProperty whereRegion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FeaturesProperty whereStability($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FeaturesProperty whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FeatureProperties extends Model
{
	public $incrementing = false;

	protected $casts = [
		'feature_id' => 'int',
        'karbari' => 'string',
        'id' => 'string',
	];

	protected $fillable = [
		'id',
		'feature_id',
		'name',
		'owner',
		'address',
		'density',
		'date',
		'stability',
		'label',
		'price',
		'region',
		'area',
		'karbari',
        'status',
        'rgb',
        'price_psc',
        'price_irr',
        'minimum_price_percentage'
	];

	public function feature()
	{
		return $this->belongsTo(Feature::class, 'feature_id' ,'id');
	}
}
