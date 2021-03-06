<?php

namespace App\Domain;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Domain\StopAlert
 *
 * @property int $id
 * @property string $symbol
 * @property int $user_id
 * @property float $trail_amount
 * @property string $trail_amount_units
 * @property float $initial_price
 * @property float $high_price
 * @property \Carbon\Carbon $high_price_updated_at
 * @property float $trigger_price
 * @property int $triggered
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Domain\Stock $stock
 * @property-read \App\Domain\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Domain\StopAlert whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Domain\StopAlert whereHighPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Domain\StopAlert whereHighPriceUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Domain\StopAlert whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Domain\StopAlert whereSymbol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Domain\StopAlert whereTrailAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Domain\StopAlert whereTrailAmountUnits($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Domain\StopAlert whereTriggerPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Domain\StopAlert whereTriggered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Domain\StopAlert whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Domain\StopAlert whereUserId($value)
 */
class StopAlert extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'symbol',
        'user_id',
        'trail_amount',
        'trail_amount_units',
        'initial_price',
        'purchase_date',
        'high_price',
        'high_price_updated_at',
        'trigger_price',
        'triggered'
    ];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'purchase_date',
        'high_price_updated_at',
    ];

    /**
     * @param float $highPrice
     * @param float $trailAmount
     * @param string $trailAmountUnits
     * @return bool|float
     */
    public static function calculateTriggerPrice($highPrice, $trailAmount, $trailAmountUnits = 'percent')
    {
        switch ($trailAmountUnits) {
            case 'percent':
                return $highPrice * (100 - $trailAmount) / 100.0;
                break;
            case 'dollars':
                return $highPrice - $trailAmount;
                break;
            default:
                return false;
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function stock()
    {
        return $this->belongsTo(Stock::class, 'symbol', 'symbol');
    }

    public function setSymbolAttribute($value)
    {
        $this->attributes['symbol'] = strtoupper($value);
    }

    public function setInitialPriceAttribute($value)
    {
        $this->attributes['initial_price'] = (float)$value;
    }

    public function setHighPriceAttribute($value)
    {
        $this->attributes['high_price'] = (float)$value;
    }

    public function setTriggerPriceAttribute($value)
    {
        $this->attributes['trigger_price'] = (float)$value;
    }

    public function setTrailAmountAttribute($value)
    {
        $this->attributes['trail_amount'] = (float)$value;
    }

    /**
     * Sets the trigger_price attribute base on the $high_price and $trail_amount
     * Note: this method does not SAVE the model!
     *
     * @return $this
     */
    public function updateTriggerPrice()
    {
        $this->trigger_price = self::calculateTriggerPrice($this->high_price, $this->trail_amount, $this->trail_amount_units);

        return $this;
    }

    /**
     * @return float
     */
    public function profit()
    {
        return $this->stock()->first()->price - $this->initial_price;
    }
}
