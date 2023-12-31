<?php

namespace App\Http\Controllers;

use App\Models\Subscriptionplan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Stripe\PaymentIntent;
use Stripe;

class SubscriptionCheckoutController extends Controller
{
    protected $stripe;
       public function __construct()
    {
        $this->stripe = new \Stripe\StripeClient(config('stripe.secret_key'));
    }

    public function checkoutCreate(Subscriptionplan $subscriptionplan){
        $courses_included = $subscriptionplan->courses()->get();
        foreach($courses_included as $course){
            if($course->pivot->course_price == 0) {
                $course->offer_price = 'FREE!';
            } elseif($course->pivot->course_price < $course->price) {
                $course->offer_price = $course->pivot->course_price.' (' .round(($course->pivot->course_price*100)/$course->price,2,PHP_ROUND_HALF_UP).'% Discount)';
            } else {
                $course->offer_price = $course->price;
            }
        }

        $facilities_included = $subscriptionplan->facilities()->get();
        foreach($facilities_included as $facility){
            if($facility->pivot->facility_price == 0) {
                $facility->offer_price = 'FREE!';
            } elseif($facility->pivot->facility_price < $facility->price) {
                $facility->offer_price = $facility->pivot->facility_price.' (' .round(($facility->pivot->facility_price*100)/$facility->price,2,PHP_ROUND_HALF_UP).'% Discount)';
            } else {
                $facility->offer_price = $facility->price;
            }
        }
        $user_have_subscriptionplan = false;
        $logged_user = Auth::guard('web')->user();

        return Inertia::render('Public/SubscriptionplanCheckout', [
            'subscriptionplan'=>$subscriptionplan,
            'intent'=> Auth::user()->createSetupIntent(),
            'stripePublicKey'=> config('stripe.public_key'),
            'user_have_subscriptionplan'=>$logged_user ? $logged_user->hasSubscriptionplan($subscriptionplan->id) : false,
            // 'user_have_subscriptionplan_expires_in' => $user_have_subscriptionplan_expires_in,
            'courses_included'=>$courses_included,
            'facilities_included'=>$facilities_included
        ]);
    }

    public function checkoutPost(Request $request){

        // $request->user()->redirectToBillingPortal(route('public.home'));
        
        $user = Auth::user();
        $paymentMethodID = $request->get('payment_method');
            if( $user->stripe_id == null ){
                $user->createOrGetStripeCustomer();
            }
        $user->addPaymentMethod($paymentMethodID);
        $user->updateDefaultPaymentMethod($paymentMethodID); 
        $plan = $request->get('plan');

        // dd($paymentMethodID);
        // Stripe\Stripe::setApiKey(config('stripe.secret_key'));
        // Stripe::setApiKey(config('stripe.secret_key'));
        // $paymentIntent = $this->stripe->setupIntents->retrieve(
        //     Auth::user()->createSetupIntent()->id,
        //     []
        // );
        // dd($user->addPaymentMethod($paymentMethodID));
        // paymentID = "pm_1NyRyHSH7DwQxAxNTmZqUkZ3"

        $user->newSubscription( 'default', $plan["plan_id"] )
        ->create( $paymentMethodID);

        // if(!$user->subscribed('default')){
        //     $user->newSubscription( 'default', $plan["plan_id"] )
        //             ->create( $paymentMethodID);
        // }else{
        //     $user->subscription('default')->swap( $plan["plan_id"] );
        // }
        return redirect('/')->with('success','You are subscribed to the '.$plan["title"]);;
    }
}
