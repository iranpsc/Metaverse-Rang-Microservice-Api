<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BuyFeatureRequest extends Model
{
    use SoftDeletes;

	protected $casts = [
		'seller_id' => 'int',
		'feature_id' => 'int',
		'buyer_id' => 'int'
	];

	protected $fillable = [
		'seller_id',
		'buyer_id',
		'feature_id',
		'status',
		'note',
		'price_psc',
		'price_irr',
	];

	public  function seller() {
	    return $this->belongsTo(User::class , 'seller_id');
    }

    public function buyer() {
	    return $this->belongsTo(User::class, 'buyer_id');
    }

    public function feature() {
	    return $this->belongsTo(Feature::class , 'feature_id');
    }

    public function lockedAsset() {
        return $this->hasOne(LockedAsset::class);
    }
}
