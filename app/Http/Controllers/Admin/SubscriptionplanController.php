<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\Facility;
use App\Models\Session;
use App\Models\Subscriptionplan;
use App\Services\FileManagement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SubscriptionplanController extends Controller
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new \Stripe\StripeClient(config('stripe.secret_key'));
    }

    public function index()
    {
        return Inertia::render('Admin/Dashboard/Subscriptionplans/Index', [
            'subscriptionplans' => Subscriptionplan::filter(
                request(['search', 'dateStart', 'dateEnd', 'sortBy', 'published'])
            )
                ->paginate(3)->withQueryString(),
            'filters' => Request::only(['search', 'sortBy', 'dateStart', 'dateEnd', 'published']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Dashboard/Subscriptionplans/Create',[
            'sessions'=>Session::all(),
            'facilities'=>Facility::all(),
        ]);
    }

    public function store(FileManagement $fileManagement)
    {
        // dd(request()->all());
        $attributes = $this->validateSubscriptionplan();

        if ($attributes['thumbnail'] ?? false) {
            $thumbnail = $attributes['thumbnail'];
        }
        // unset($attributes['thumbnail']);

        if ($attributes['creditsInfo'] ?? false) {
            $creditsInfo = $attributes['creditsInfo'];
            // dd($attributes);
        }
        unset($attributes['creditsInfo']);

        $subscriptionplan = Subscriptionplan::create($attributes);

        $stripePlan = $this->stripe->plans->create([
            'amount' => $attributes["price"] * 100,
            'currency' => 'aud',
            'interval' => 'day',
            'active' => $attributes["published"] == 1,
            "interval_count" => $attributes["validity"],
            'product' => ['name' => $attributes['title'], 'metadata' => ['description' => $attributes['description'], 'thumbnail_url' => $subscriptionplan->thumbnail_url], 'unit_label' => $subscriptionplan->id],
        ]);

        // $stripePlan = $this->stripe->prices->create([
        //     'unit_amount' => $attributes["price"] * 100,
        //     'currency' => 'aud',
        //     'recurring' => ['interval' => 'day', 'interval_count' => $attributes['validity']],
        //     'product_data' => ['name' => $attributes['title'], 'metadata' => ['description' => $attributes['description'], 'thumbnail_url' => $subscriptionplan->thumbnail_url], 'unit_label' => $subscriptionplan->id],
        // ]);

        $subscriptionplan->plan_id = $stripePlan->id;

        if ($thumbnail ?? false) {
            $thumbnail = $fileManagement->uploadFile(
                file: $thumbnail,
                path: 'assets/app/images/subscriptionplans/id_' . $subscriptionplan['id'] . '/thumbnail'
            );
            $subscriptionplan->thumbnail = $thumbnail;
            $subscriptionplan->save();
        }

        if($creditsInfo ?? false) {
            (new CreditController)->store($creditsInfo,$subscriptionplan->id);
        }

        if (Auth::guard('web')->check()) {
            if (Auth::user()->can('admin')) {
                return redirect('/admin/dashboard/subscriptionplans')->with('success', 'Subscription plan has been created.');
            }
            return;
        }
        return;

    }

    public function edit(Subscriptionplan $subscriptionplan)
    {
        // dd($subscriptionplan->credits()->where('creditable_id',0)->where('creditable_type','App\\Models\\Session')->first());
        return Inertia::render('Admin/Dashboard/Subscriptionplans/Edit', [
            'subscriptionplan' => $subscriptionplan,
            'sessionGenCredits'=>$subscriptionplan->credits()->where('creditable_id',0)->where('creditable_type','App\\Models\\Session')->first(),
            'facilityGenCredits'=>$subscriptionplan->credits()->where('creditable_id',0)->where('creditable_type','App\\Models\\Facility')->first(),
            'sessionCredits'=>$subscriptionplan->credits()->where('creditable_id','!=',0)->where('creditable_type','App\\Models\\Session')->get(),
            'facilityCredits'=>$subscriptionplan->credits()->where('creditable_id','!=',0)->where('creditable_type','App\\Models\\Facility')->get(),

        ]);

    }

    public function update(Subscriptionplan $subscriptionplan, FileManagement $fileManagement)
    {

        $attributes = $this->validateSubscriptionplan($subscriptionplan);
        try {
            $this->stripe->plans->delete(
                $subscriptionplan->plan_id,
                []
            );
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }

        // $stripePlan = $this->stripe->plans->create([
        //     'amount' => $attributes["price"] * 100,
        //     'currency' => 'inr',
        //     'interval' => 'day',
        //     'active' => $attributes["published"] == 1,
        //     "interval_count" => $attributes["validity"],
        //     'product' => ['name' => $attributes["title"]],
        // ]);

        if ($attributes['thumbnail']) {
            $attributes['thumbnail'] =
                $fileManagement->uploadFile(
                    file: $attributes['thumbnail'] ?? false,
                    deleteOldFile: $subscriptionplan->thumbnail ?? false,
                    oldFile: $subscriptionplan->thumbnail,
                    path: 'assets/app/images/subscriptionplans/id_' . $subscriptionplan['id'] . '/thumbnail',
                );
        } else if ($subscriptionplan->thumbnail) {
            $fileManagement->deleteFile(
                fileUrl: $subscriptionplan->thumbnail
            );
        }

        // $attributes["plan_id"] = $stripePlan->id;

        // $stripePlan = $this->stripe->prices->create([
        //     'unit_amount' => $attributes["price"] * 100,
        //     'currency' => 'aud',
        //     'recurring' => ['interval' => 'day', 'interval_count' => $attributes['validity']],
        //     'product_data' => ['name' => $attributes['title'], 'metadata' => ['description' => $attributes['description'], 'thumbnail_url' => asset($attributes['thumbnail'])], 'unit_label' => $subscriptionplan->id],
        // ]);

        $stripePlan = $this->stripe->plans->create([
            'amount' => $attributes["price"] * 100,
            'currency' => 'aud',
            'interval' => 'day',
            'active' => $attributes["published"] == 1,
            "interval_count" => $attributes["validity"],
            'product' => ['name' => $attributes['title'], 'metadata' => ['description' => $attributes['description'], 'thumbnail_url' => $subscriptionplan->thumbnail_url], 'unit_label' => $subscriptionplan->id],
        ]);

        $attributes['plan_id'] = $stripePlan->id;

        $subscriptionplan->update($attributes);

        $subscriptionplan->update(['plan_id' => $stripePlan->id]);

        return back()->with('success', 'Subscription plan Updated!');
    }

    public function destroy(Subscriptionplan $subscriptionplan)
    {
        try {
            $this->stripe->plans->delete(
                $subscriptionplan->plan_id,
                []
            );
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
        $subscriptionplan->delete();
        Storage::disk('public')->deleteDirectory('assets/app/images/subscriptionplans/id_' . $subscriptionplan['id']);

        return redirect('/admin/dashboard/subscriptionplans')->with('success', 'Subscription plan Deleted!');
    }

    protected function validateSubscriptionplan(?Subscriptionplan $subscriptionplan = null): array
    {
        $subscriptionplan ??= new Subscriptionplan();

        return request()->validate(
            [
                'title' => 'required|min:3|max:50',
                'slug' => ['required', Rule::unique('subscriptionplans', 'slug')->ignore($subscriptionplan)],
                'price' => 'required|numeric',
                'validity' => 'required|numeric',
                'description' => 'required|max:1000',
                'published' => 'required|boolean',
                'thumbnail' => is_string(request()->input('thumbnail')) ? 'required' : ['required', 'mimes:jpeg,png', 'max:2048'],
                "creditsInfo"=> 'nullable'
            ],
            [
                'slug' => 'Enter a unique slug for your the subscriptionplan\'s link',
                'thumbnail' => 'Upload thumbnail as jpg/png format with size less than 2MB',
            ]
        );
    }
}