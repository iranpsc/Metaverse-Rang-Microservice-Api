<?php

namespace App\Models;

use App\Models\Dynasty\Dynasty;
use App\Models\Feature\FeatureHourlyProfit;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\FeatureIndicators;
use App\Models\Feature\Building;
use App\Models\Feature\BuildingModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Feature extends Model
{
    protected $fillable = [
        'map_id',
        'type',
        'owner_id',
    ];

    public function buildingModels(): BelongsToMany
    {
        return $this->belongsToMany(BuildingModel::class, 'buildings', 'feature_id', 'model_id')
            ->using(Building::class)
            ->as('building');
    }

    public function map()
    {
        return $this->belongsTo(Map::class);
    }

    public function properties()
    {
        return $this->hasOne(FeatureProperties::class);
    }

    public function geometry()
    {
        return $this->hasOne(Geometry::class,  'feature_id', 'id');
    }

    public function coordinates()
    {
        return $this->hasManyThrough(Coordinate::class, Geometry::class);
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }
    public function owner()
    {
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
        return !empty($this->sellRequests
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

    public function latestSellRequest()
    {
        return $this->hasOne(SellFeatureRequest::class)->latestOfMany();
    }

    public function underPriced()
    {
        $sellRequest = $this->latestSellRequest;
        if ($sellRequest) {
            return $sellRequest->limit < 100;
        }
        return false;
    }

    public function hourlyProfit()
    {
        return $this->hasOne(FeatureHourlyProfit::class);
    }

    public function locked()
    {
        return $this->properties->label === 'locked'
            && LockedFeature::whereFeatureId($this->id)->whereStatus(0)->exists();
    }

    public function getColor()
    {
        return match ($this->properties->karbari) {
            FeatureIndicators::Amozeshi => 'blue',
            FeatureIndicators::Tejari   => 'red',
            FeatureIndicators::Maskoni  => 'yellow'
        };
    }

    public function changeStatusToSoldAndPriced()
    {
        return match ($this->properties->karbari) {
            FeatureIndicators::Maskoni  => FeatureIndicators::MaskoniSoldAndPriced,
            FeatureIndicators::Tejari   => FeatureIndicators::TejariSoldAndPriced,
            FeatureIndicators::Amozeshi => FeatureIndicators::AmozeshiSoldAndPriced,
        };
    }

    public function changeStatusToSoldAndNotPriced()
    {
        return match ($this->properties->karbari) {
            FeatureIndicators::Maskoni  => FeatureIndicators::MaskoniSoldAndNotPriced,
            FeatureIndicators::Tejari   => FeatureIndicators::TejariSoldAndNotPriced,
            FeatureIndicators::Amozeshi => FeatureIndicators::AmozeshiSoldAndNotPriced
        };
    }

    public function getFeatureColor()
    {
        return match ($this->properties->karbari) {
            FeatureIndicators::Amozeshi =>  'آبی',
            FeatureIndicators::Tejari =>  'قرمز',
            FeatureIndicators::Maskoni =>  'زرد',
        };
    }

    public function getApplicationTitle()
    {
        return match ($this->properties->karbari) {
            FeatureIndicators::Amozeshi =>  'آموزشی',
            FeatureIndicators::Tejari =>  'تجاری',
            FeatureIndicators::Maskoni =>  'مسکونی',
            FeatureIndicators::Edari => 'اداری',
            FeatureIndicators::Behdashti => 'بهداشتی',
            FeatureIndicators::FazaSabz => 'فضای سبز',
            FeatureIndicators::Farhangi => 'فرهنگی',
            FeatureIndicators::Parking => 'پارکینگ',
            FeatureIndicators::Mazhabi => 'مذهبی',
            FeatureIndicators::Nemayeshgah => 'نمایشگاه',
            FeatureIndicators::Gardeshgari => 'گردشگری',
        };
    }

    public function getCoordinates()
    {
        return implode('|', $this->geometry->coordinates->map(function ($coordinate) {
            return $coordinate->implodeXY();
        })->toArray());
    }

    public function getKarbariCoefficient()
    {
        return match ($this->properties->karbari) {
            FeatureIndicators::Amozeshi => 0.3,
            FeatureIndicators::Tejari => 0.2,
            FeatureIndicators::Maskoni => 0.1,
            default => 1
        };
    }
}
