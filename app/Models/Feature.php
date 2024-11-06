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

    /**
     * The buildings that belong to the feature.
     *
     * @return BelongsToMany
     */
    public function buildingModels(): BelongsToMany
    {
        return $this->belongsToMany(BuildingModel::class, 'buildings', 'feature_id', 'model_id')
            ->using(Building::class)
            ->as('building');
    }

    /**
     * The map that belong to the feature.
     *
     * @return BelongsToMany
     */
    public function map()
    {
        return $this->belongsTo(Map::class);
    }

    /**
     * The properties that belong to the feature.
     *
     * @return BelongsToMany
     */
    public function properties()
    {
        return $this->hasOne(FeatureProperties::class);
    }

    /**
     * The geometry that belong to the feature.
     *
     * @return BelongsToMany
     */
    public function geometry()
    {
        return $this->hasOne(Geometry::class,  'feature_id', 'id');
    }

    /**
     * The coordinates that belong to the feature.
     *
     * @return BelongsToMany
     */
    public function coordinates()
    {
        return $this->hasManyThrough(Coordinate::class, Geometry::class);
    }

    /**
     * The images that belong to the feature.
     *
     * @return BelongsToMany
     */
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    /**
     * The owner that belong to the feature.
     *
     * @return BelongsToMany
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * The trades that belong to the feature.
     *
     * @return BelongsToMany
     */
    public function buyRequests()
    {
        return $this->hasMany(BuyFeatureRequest::class, 'feature_id');
    }

    /**
     * The trades that belong to the feature.
     *
     * @return BelongsToMany
     */
    public function sellRequests()
    {
        return $this->hasMany(SellFeatureRequest::class, 'feature_id');
    }

    /**
     * Determines if the feature has pending requests.
     *
     * @return bool
     */
    function hasPendingRequests()
    {
        return $this->sellRequests()
            ->where('seller_id', $this->owner_id)
            ->where('status', 0)->exists();
    }

    /**
     * The dynasty that belong to the feature.
     *
     * @return BelongsToMany
     */
    public function dynasty()
    {
        return $this->belongsTo(Dynasty::class, 'id', 'feature_id');
    }

    /**
     * Get the latest trade for the feature.
     *
     * @return BelongsToMany
     */
    public function latestTraded()
    {
        return $this->hasOne(Trade::class)->latestOfMany();
    }

    /**
     * Get the latest sell request for the feature.
     *
     * @return BelongsToMany
     */
    public function latestSellRequest()
    {
        return $this->hasOne(SellFeatureRequest::class)->latestOfMany();
    }

    /**
     * Determines if the feature is under priced.
     *
     * @return bool
     */
    public function underPriced()
    {
        $sellRequest = $this->latestSellRequest;
        if ($sellRequest) {
            return $sellRequest->limit < 100;
        }
        return false;
    }

    /**
     * Get the hourly profit for the feature.
     *
     * @return BelongsToMany
     */
    public function hourlyProfit()
    {
        return $this->hasOne(FeatureHourlyProfit::class);
    }

    /**
     * Determines if the feature is locked.
     *
     * @return bool
     */
    public function locked()
    {
        return $this->properties->label === 'locked'
            && LockedFeature::whereFeatureId($this->id)->whereStatus(0)->exists();
    }

    /**
     * Get the feature's color.
     *
     * @return string
     */
    public function getColor()
    {
        return match ($this->properties->karbari) {
            FeatureIndicators::Amozeshi => 'blue',
            FeatureIndicators::Tejari   => 'red',
            FeatureIndicators::Maskoni  => 'yellow'
        };
    }

    /**
     * Get the feature's status.
     *
     * @return string
     */
    public function changeStatusToSoldAndPriced()
    {
        return match ($this->properties->karbari) {
            FeatureIndicators::Maskoni  => FeatureIndicators::MaskoniSoldAndPriced,
            FeatureIndicators::Tejari   => FeatureIndicators::TejariSoldAndPriced,
            FeatureIndicators::Amozeshi => FeatureIndicators::AmozeshiSoldAndPriced,
        };
    }

    /**
     * Get the feature's status.
     *
     * @return string
     */
    public function changeStatusToSoldAndNotPriced()
    {
        return match ($this->properties->karbari) {
            FeatureIndicators::Maskoni  => FeatureIndicators::MaskoniSoldAndNotPriced,
            FeatureIndicators::Tejari   => FeatureIndicators::TejariSoldAndNotPriced,
            FeatureIndicators::Amozeshi => FeatureIndicators::AmozeshiSoldAndNotPriced
        };
    }

    /**
     * Get the feature's Persian color name.
     *
     * @return string
     */
    public function getFeatureColor()
    {
        return match ($this->properties->karbari) {
            FeatureIndicators::Amozeshi =>  'آبی',
            FeatureIndicators::Tejari =>  'قرمز',
            FeatureIndicators::Maskoni =>  'زرد',
        };
    }

    /**
     * Get the feature's Persian title.
     *
     * @return string
     */
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
