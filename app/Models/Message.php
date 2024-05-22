<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'message',
        'company_id',
        'senders_id',
        'recipients_id',
        'status'
    ];

    /**
     * Handle compression and decompression for specified attributes.
     *
     * @param string $key
     * @param mixed $value
     * @param bool $compress
     * @return mixed
     */
    protected function compressAttribute($key, $value, $compress = true)
    {
        if ($compress && in_array($key, ['message', 'subject'])) {
            return gzcompress($value);
        } elseif (!$compress && in_array($key, ['message', 'subject'])) {
            return gzuncompress($value);
        }
        return $value;
    }

    /**
     * Get the value of an attribute with decompression if applicable.
     *
     * @param string $key
     * @return mixed
     */
    public function getAttributeValue($key)
    {
        $value = parent::getAttributeValue($key);
        return $this->compressAttribute($key, $value, false);
    }

    /**
     * Set the value of an attribute with compression if applicable.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setAttribute($key, $value)
    {
        parent::setAttribute($key, $this->compressAttribute($key, $value));
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'senders_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipients_id');
    }
}
