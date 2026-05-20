<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdSet;
use App\Models\AdvertisingAccount;
use App\Models\Campaign;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvertisingManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->team = Team::factory()->create();
        $this->user = User::factory()->create(['current_team_id' => $this->team->id]);
        $this->user->teams()->attach($this->team);
    }

    public function test_advertising_account_can_be_created()
    {
        $account = AdvertisingAccount::factory()->create(['team_id' => $this->team->id]);

        $this->assertDatabaseHas('advertising_accounts', [
            'id' => $account->id,
            'team_id' => $this->team->id,
        ]);
    }

    public function test_campaign_can_be_created_for_advertising_account()
    {
        $account = AdvertisingAccount::factory()->create(['team_id' => $this->team->id]);

        $campaign = Campaign::create([
            'team_id' => $this->team->id,
            'advertising_account_id' => $account->id,
            'name' => 'Test Campaign',
            'status' => 'active',
            'objective' => 'awareness',
            'budget' => 100.00,
            'budget_type' => 'daily',
        ]);

        $this->assertDatabaseHas('campaigns', [
            'advertising_account_id' => $account->id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);
    }

    public function test_advertising_account_has_many_campaigns()
    {
        $account = AdvertisingAccount::factory()->create(['team_id' => $this->team->id]);

        Campaign::create([
            'team_id' => $this->team->id,
            'advertising_account_id' => $account->id,
            'name' => 'Campaign 1',
            'status' => 'active',
        ]);

        Campaign::create([
            'team_id' => $this->team->id,
            'advertising_account_id' => $account->id,
            'name' => 'Campaign 2',
            'status' => 'paused',
        ]);

        $this->assertCount(2, $account->campaigns);
    }

    public function test_ad_set_can_be_created_for_campaign()
    {
        $account = AdvertisingAccount::factory()->create(['team_id' => $this->team->id]);
        $campaign = Campaign::create([
            'team_id' => $this->team->id,
            'advertising_account_id' => $account->id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $adSet = AdSet::create([
            'team_id' => $this->team->id,
            'advertising_account_id' => $account->id,
            'campaign_id' => $campaign->id,
            'name' => 'Test Ad Set',
            'status' => 'active',
            'budget' => 50.00,
            'budget_type' => 'daily',
        ]);

        $this->assertDatabaseHas('ad_sets', [
            'campaign_id' => $campaign->id,
            'name' => 'Test Ad Set',
            'status' => 'active',
        ]);
    }

    public function test_ad_can_be_created_for_ad_set()
    {
        $account = AdvertisingAccount::factory()->create(['team_id' => $this->team->id]);
        $campaign = Campaign::create([
            'team_id' => $this->team->id,
            'advertising_account_id' => $account->id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);
        $adSet = AdSet::create([
            'team_id' => $this->team->id,
            'advertising_account_id' => $account->id,
            'campaign_id' => $campaign->id,
            'name' => 'Test Ad Set',
            'status' => 'active',
        ]);

        $ad = Ad::create([
            'team_id' => $this->team->id,
            'advertising_account_id' => $account->id,
            'campaign_id' => $campaign->id,
            'ad_set_id' => $adSet->id,
            'name' => 'Test Ad',
            'status' => 'active',
            'headline' => 'Buy Now',
            'description' => 'Great deal on products',
            'destination_url' => 'https://example.com',
        ]);

        $this->assertDatabaseHas('ads', [
            'ad_set_id' => $adSet->id,
            'name' => 'Test Ad',
            'status' => 'active',
        ]);
    }

    public function test_campaign_belongs_to_advertising_account()
    {
        $account = AdvertisingAccount::factory()->create(['team_id' => $this->team->id]);
        $campaign = Campaign::create([
            'team_id' => $this->team->id,
            'advertising_account_id' => $account->id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $this->assertEquals($account->id, $campaign->advertisingAccount->id);
    }

    public function test_ad_set_belongs_to_campaign_and_account()
    {
        $account = AdvertisingAccount::factory()->create(['team_id' => $this->team->id]);
        $campaign = Campaign::create([
            'team_id' => $this->team->id,
            'advertising_account_id' => $account->id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);
        $adSet = AdSet::create([
            'team_id' => $this->team->id,
            'advertising_account_id' => $account->id,
            'campaign_id' => $campaign->id,
            'name' => 'Test Ad Set',
            'status' => 'active',
        ]);

        $this->assertEquals($campaign->id, $adSet->campaign->id);
        $this->assertEquals($account->id, $adSet->advertisingAccount->id);
    }

    public function test_ad_belongs_to_ad_set_campaign_and_account()
    {
        $account = AdvertisingAccount::factory()->create(['team_id' => $this->team->id]);
        $campaign = Campaign::create([
            'team_id' => $this->team->id,
            'advertising_account_id' => $account->id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);
        $adSet = AdSet::create([
            'team_id' => $this->team->id,
            'advertising_account_id' => $account->id,
            'campaign_id' => $campaign->id,
            'name' => 'Test Ad Set',
            'status' => 'active',
        ]);
        $ad = Ad::create([
            'team_id' => $this->team->id,
            'advertising_account_id' => $account->id,
            'campaign_id' => $campaign->id,
            'ad_set_id' => $adSet->id,
            'name' => 'Test Ad',
            'status' => 'active',
        ]);

        $this->assertEquals($adSet->id, $ad->adSet->id);
        $this->assertEquals($campaign->id, $ad->campaign->id);
        $this->assertEquals($account->id, $ad->advertisingAccount->id);
    }

    public function test_campaign_has_many_ad_sets()
    {
        $account = AdvertisingAccount::factory()->create(['team_id' => $this->team->id]);
        $campaign = Campaign::create([
            'team_id' => $this->team->id,
            'advertising_account_id' => $account->id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        AdSet::create([
            'team_id' => $this->team->id,
            'advertising_account_id' => $account->id,
            'campaign_id' => $campaign->id,
            'name' => 'Ad Set 1',
            'status' => 'active',
        ]);

        AdSet::create([
            'team_id' => $this->team->id,
            'advertising_account_id' => $account->id,
            'campaign_id' => $campaign->id,
            'name' => 'Ad Set 2',
            'status' => 'paused',
        ]);

        $this->assertCount(2, $campaign->adSets);
    }

    public function test_advertising_account_metadata_is_cast_to_array()
    {
        $account = AdvertisingAccount::factory()->create([
            'team_id' => $this->team->id,
            'metadata' => ['currency' => 'USD', 'timezone' => 'UTC'],
        ]);

        $this->assertIsArray($account->metadata);
        $this->assertEquals('USD', $account->metadata['currency']);
    }
}
