<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscriptionplan extends Model
{
    use HasFactory;

    protected $appends = [ 'thumbnail_url'];

    protected $with = [ 'credits'];

    public function scopeFilter($query, array $filters)
    {
        $query
            ->when(
                $filters['search'] ?? false, fn($query, $search) =>
                $query
                    ->where(fn($query) =>
                        $query
                            ->where('title', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            // ->orWhere('email', 'like', "%{$search}%")
                            // ->orWhere('email', 'like', "%{$search}%")
                            // ->orWhere('keywords', 'like', "%{$search}%")
                            ->orWhere('price', '=', $search)
                            ->orWhere('validity', '=', $search)
                    )
            )

            ->when($filters['dateStart'] ?? false, function ($query, $dateStart) {
                $dateStart = Carbon::createFromFormat('m/d/Y', $dateStart)->format('Y-m-d');
                $query
                    ->whereDate('created_at', '>=', $dateStart);
            }
            )

            ->when(
                $filters['dateEnd'] ?? false,
                function ($query, $dateEnd) {
                    $dateEnd = Carbon::createFromFormat('m/d/Y', $dateEnd)->format('Y-m-d');
                    $query
                        ->whereDate('created_at', '<=', $dateEnd);
                }
            )

            ->when($filters['published'] ?? false, fn($query, $published) =>
            $query
                ->whereIn('published', json_decode($published))
        
        )

        //     ->when($filters['published'] ?? false, fn($query, $published) =>
        //     $query
        //         ->where('published', fn($query) =>
        //             $query->where('value', json_decode($roles))
        //         )
        // )

        //     ->when($filters['published'] ?? false, fn($query, $published) =>
        //         $query
        //             ->where('published','=',$published[0])
        //     )

            ->when(
                $filters['sortBy'] ?? 'default',
                function ($query, $sortBy) {

                    if ($sortBy === 'date-dsc') {
                        $query->latest();
                    }
                    if ($sortBy === 'date-asc') {
                        $query->oldest();
                    }
                    if ($sortBy === 'default') {
                        $query->latest();
                    }
                }
            );
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_subscriptionplan')->withPivot('course_price');
    }

    public function facilities()
    {
        return $this->belongsToMany(Facility::class, 'facility_subscriptionplan')->withPivot('facility_price');
    }

    protected function thumbnailUrl(): Attribute
    {
        parse_url($this->avatar)['host'] ?? '' === 'images.pexels.com' ? $avatar = $this->avatar : $avatar = '' . $this->avatar;
        return Attribute::make(
            get:fn($value) => asset($this->thumbnail)
        );
    }

    protected function thumbnail(): Attribute
    {
        return Attribute::make(
            // get: fn (string $value) => ucfirst($value),
            set: function ($value) {
                // dd($value);
                if(empty($value)){
                    return 'assets/static/img/subscriptionplan.png';
                }
                else {
                    return $value;
                }

            },
        );
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class);
    }

    public function creditTo($type,$id = 0)
    {
        $credits = $this->credits()->where('creditable_type',$type)->where('creditable_id',0)->first()->credits;
        if($id != 0){
            $credits = $credits + $this->credits()->where('creditable_type',$type)->where('creditable_id',$id)->first()->credits;
        }
        return $credits;
        // foreach ($credits as $credit) {
        //     $credit->
        // }
    }

    // protected static function booted()
    // {
    //     static::deleting(function ($subscription) {
    //         $subscription->courses()->detach();
    //     });
    // }
}
