

<?php

namespace App\Services;

use App\Models\Team;
use App\Models\TeamSubscription;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeService
{
    protected StripeClient $stripeClient;

    public function __construct()
    {
        $this->stripeClient = new StripeClient(config('services.stripe.secret'));
    }

    public function createSubscription(Team $team, string $paymentMethodId): TeamSubscription
    {
        if (!config('services.stripe.subscriptions_enabled')) {
            throw new \Exception('Subscriptions are currently disabled');
        }

        try {
            // Create or get Stripe customer
            $stripeCustomer = $this->getOrCreateCustomer($team);

            // Attach payment method to customer
            $this->stripeClient->paymentMethods->attach($paymentMethodId, [
                'customer' => $stripeCustomer->id,
            ]);

            // Set as default payment method
            $this->stripeClient->customers->update($stripeCustomer->id, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethodId,
                ],
            ]);

            // Create subscription
            $subscription = $this->stripeClient->subscriptions->create([
                'customer' => $stripeCustomer->id,
                'items' => [[
                    'price' => config('services.stripe.price_id'),
                    'quantity' => 1,
                ]],
                'trial_period_days' => config('services.stripe.trial_days', 14),
                'expand' => ['latest_invoice.payment_intent'],
            ]);

            // Create local subscription record
            return TeamSubscription::create([
                'team_id' => $team->id,
                'stripe_id' => $subscription->id,
                'stripe_status' => $subscription->status,
                'stripe_price' => config('services.stripe.price_id'),
                'quantity' => 1,
                'trial_ends_at' => now()->addDays(config('services.stripe.trial_days', 14)),
                'ends_at' => null,
            ]);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to create subscription: ' . $e->getMessage());
        }
    }

    public function cancelSubscription(TeamSubscription $subscription): void
    {
        try {
            $this->stripeClient->subscriptions->cancel($subscription->stripe_id, [
                'prorate' => true,
            ]);

            $subscription->update([
                'ends_at' => now(),
            ]);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to cancel subscription: ' . $e->getMessage());
        }
    }

    protected function getOrCreateCustomer(Team $team)
    {
        if ($team->stripe_id) {
            return $this->stripeClient->customers->retrieve($team->stripe_id);
        }

        $customer = $this->stripeClient->customers->create([
            'email' => $team->owner->email,
            'name' => $team->name,
            'metadata' => [
                'team_id' => $team->id,
            ],
        ]);

        $team->update(['stripe_id' => $customer->id]);

        return $customer;
    }

    public function updateSubscriptionQuantity(TeamSubscription $subscription, int $quantity): void
    {
        try {
            $this->stripeClient->subscriptions->update($subscription->stripe_id, [
                'items' => [
                    [
                        'price' => $subscription->stripe_price,
                        'quantity' => $quantity,
                    ],
                ],
            ]);

            $subscription->update(['quantity' => $quantity]);
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to update subscription quantity: ' . $e->getMessage());
        }
    }
}