<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainOrder extends Model
{
  protected $table = 'domain_orders';
  
  protected $fillable = [
    'user_id',
    'domain',
    'price',
    'years',
    'status',
    'reseller_order_id',
    'payment_method',
    'payment_id',
    'fatura_id',
    'error_message',
    'registered_at',
    'expires_at',
  ];
  
  protected $casts = [
    'price' => 'decimal:2',
    'years' => 'integer',
    'registered_at' => 'datetime',
    'expires_at' => 'datetime',
  ];
  
  // Status değerleri
  const STATUS_PENDING = 'pending';
  const STATUS_PAID = 'paid';
  const STATUS_ACTIVE = 'active';
  const STATUS_FAILED = 'failed';
  const STATUS_EXPIRED = 'expired';
  
  /**
   * Kullanıcı ilişkisi
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(\App\Models\Uye::class, 'user_id');
  }
  
  /**
   * Fatura ilişkisi
   */
  public function fatura(): BelongsTo
  {
    return $this->belongsTo(Fatura::class, 'fatura_id');
  }
  
  /**
   * Status kontrolü
   */
  public function isPending(): bool
  {
    return $this->status === self::STATUS_PENDING;
  }
  
  public function isPaid(): bool
  {
    return $this->status === self::STATUS_PAID;
  }
  
  public function isActive(): bool
  {
    return $this->status === self::STATUS_ACTIVE;
  }
  
  public function isFailed(): bool
  {
    return $this->status === self::STATUS_FAILED;
  }
  
  /**
   * Scope'lar
   */
  public function scopePending($query)
  {
    return $query->where('status', self::STATUS_PENDING);
  }
  
  public function scopePaid($query)
  {
    return $query->where('status', self::STATUS_PAID);
  }
  
  public function scopeActive($query)
  {
    return $query->where('status', self::STATUS_ACTIVE);
  }
  
  public function scopeFailed($query)
  {
    return $query->where('status', self::STATUS_FAILED);
  }
}

