<?php

declare(strict_types=1);

namespace Acme\Shop\Models;

use Acme\Shop\Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $number
 * @property ?string $note
 * @property int $total
 */
#[Table(name: 'orders')]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, SoftDeletes;

    public const ATTRIBUTE_ID = 'id';
    public const ATTRIBUTE_NUMBER = 'number';
    public const ATTRIBUTE_NOTE = 'note';
    public const ATTRIBUTE_TOTAL = 'total';
    public const MAX_TOTAL = 1_000_000;

    /**
     * @return string|null
     */
    public function displayNote(): ?string
    {
        return $this->note === null ? null : trim($this->note);
    }

    /**
     * @return bool
     */
    public function isLarge(): bool
    {
        return $this->total > 250_000;
    }
}
