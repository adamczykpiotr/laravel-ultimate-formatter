<?php

namespace Acme\Shop\Models;

use Acme\Shop\Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $number
 * @property ?string $note
 * @property int $total
 */
class Order extends Model
{
    public const ATTRIBUTE_ID = 'id';
    public const ATTRIBUTE_NUMBER = 'number';
    public const ATTRIBUTE_NOTE = 'note';
    public const ATTRIBUTE_TOTAL = 'total';
    use SoftDeletes;
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    public const MAX_TOTAL = 1000000;

    protected $table = 'orders';



    /**
     * @return ?string
     */
    public function displayNote()
    {
        return $this->note === null ? null : trim($this->note);
    }

    public function isLarge(): bool
    {
        return $this->total > 250000;
    }
}
