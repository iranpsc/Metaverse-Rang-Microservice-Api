<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Models\Dynasty\Dynasty;
use App\Models\Feature\FeatureHourlyProfit;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    protected $table = 'features';
    protected $primaryKey = 'id';

    protected $casts = [
        'map_id' => 'int',
        'owner_id' => 'int',
        'index' => 'int'

    ];

    protected $fillable = [
        'map_id',
        'type',
        'owner_id',
        'index'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];


    public function map()
    {
        return $this->belongsTo(Map::class);
    }

    public function properties()
    {
        return $this->hasOne(FeatureProperties::class, 'feature_id', 'id');
    }

	public function geometry()
	{
		return $this->hasOne(Geometry::class,  'feature_id' , 'id' );
	}
	public function images(){
	    return $this->morphMany(Image::class,'imageable');
    }
    public function owner(){
	    return $this->belongsTo(User::class, 'owner_id');
    }

    public function buyRequests()
    {
        return $this->hasMany(BuyFeatureRequest::class, 'feature_id');
    }

    public function sellRequests()
    {
        return $this->hasMany(SellFeatureRequest::class, 'feature_id');
    }

    function hasPendingRequests()
    {
        return ! empty($this->sellRequests
        ->where('seller_id', $this->owner->id)
        ->where('status', 0)->first());
    }

    public function dynasty()
    {
        return $this->belongsTo(Dynasty::class, 'id', 'feature_id');
    }

    public function latestTraded()
    {
        return $this->hasOne(Trade::class)->latestOfMany();
    }

    public function latestSellRequest() {
        return $this->hasOne(SellFeatureRequest::class)->latestOfMany();
    }

    public function underPriced() {
        $sellRequest = $this->latestSellRequest;
        if($sellRequest) {
            return $sellRequest->limit < 100;
        }
        return false;
    }

    public function hourlyProfit()
    {
        return $this->hasOne(FeatureHourlyProfit::class);
    }

    public static function query()
    {
        return parent::query();
    }

    public function locked()
    {
        return $this->properties->label === 'locked';
    }
 }
