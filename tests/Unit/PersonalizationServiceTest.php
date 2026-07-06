<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\LandingPage;
use App\Services\PersonalizationService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PersonalizationServiceTest extends TestCase
{
    private PersonalizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PersonalizationService;
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function segmentProvider(): array
    {
        return [
            'customer stage wins'            => [['lifecycle_stage' => 'customer', 'activity_count' => 0], 'customer'],
            'opportunity stage'              => [['lifecycle_stage' => 'opportunity'], 'opportunity'],
            'sql stage'                      => [['lifecycle_stage' => 'sql'], 'sales_qualified'],
            'mql stage'                      => [['lifecycle_stage' => 'mql'], 'marketing_qualified'],
            'activity>=5 => marketing'       => [['activity_count' => 5], 'marketing_qualified'],
            'activity 8 no stage => mkt'     => [['activity_count' => 8], 'marketing_qualified'],
            'lead stage'                     => [['lifecycle_stage' => 'lead'], 'engaged_lead'],
            'activity>=3 => engaged'         => [['activity_count' => 3], 'engaged_lead'],
            'subscriber stage'               => [['lifecycle_stage' => 'subscriber'], 'prospect'],
            'activity>=1 => prospect'        => [['activity_count' => 1], 'prospect'],
            'empty => new_visitor'           => [[], 'new_visitor'],
            'zero activity => new_visitor'   => [['activity_count' => 0], 'new_visitor'],
        ];
    }

    /**
     * @param  array<string, mixed>  $userData
     */
    #[DataProvider('segmentProvider')]
    public function test_determine_segment_maps_user_data_to_expected_segment(array $userData, string $expected): void
    {
        $this->assertSame($expected, $this->service->determineSegment($userData));
    }

    public function test_apply_segment_blocks_keeps_matching_block_and_strips_others(): void
    {
        $content = '[segment:prospect]Welcome prospect![/segment]'
            .'[segment:customer]Thanks customer![/segment]Common';

        $result = $this->service->applySegmentBlocks($content, 'prospect');

        $this->assertSame('Welcome prospect!Common', $result);
    }

    public function test_apply_segment_blocks_supports_comma_separated_segments(): void
    {
        $content = '[segment:prospect, engaged_lead]Hi there![/segment]';

        $this->assertSame('Hi there!', $this->service->applySegmentBlocks($content, 'engaged_lead'));
        $this->assertSame('', $this->service->applySegmentBlocks($content, 'customer'));
    }

    public function test_apply_segment_blocks_handles_not_segment_inverse(): void
    {
        $content = '[not_segment:customer]Sign up now[/not_segment]';

        $this->assertSame('Sign up now', $this->service->applySegmentBlocks($content, 'prospect'));
        $this->assertSame('', $this->service->applySegmentBlocks($content, 'customer'));
    }

    public function test_apply_conditional_content_includes_only_matching_conditions(): void
    {
        $content = '[if:industry=tech]Tech content[/if][if:industry=finance]Finance content[/if]';

        $result = $this->service->applyConditionalContent($content, ['industry' => 'tech']);

        $this->assertSame('Tech content', $result);
    }

    public function test_apply_conditional_content_excludes_when_key_missing_or_mismatched(): void
    {
        $content = '[if:plan=pro]Pro perks[/if]';

        $this->assertSame('', $this->service->applyConditionalContent($content, []));
        $this->assertSame('', $this->service->applyConditionalContent($content, ['plan' => 'free']));
        $this->assertSame('Pro perks', $this->service->applyConditionalContent($content, ['plan' => 'pro']));
    }

    public function test_personalize_content_runs_end_to_end(): void
    {
        $page = new LandingPage;
        $page->content = 'Hello {name}! '
            .'[segment:prospect]You are a prospect.[/segment]'
            .'[segment:customer]VIP treatment.[/segment]'
            .'[if:industry=tech]Tech offer[/if]'
            .'[not_segment:customer]Join us[/not_segment]';

        $userData = [
            'name' => 'Alice',
            'lifecycle_stage' => 'subscriber', // => prospect segment
            'industry' => 'tech',
        ];

        $result = $this->service->personalizeContent($page, $userData);

        // Placeholder replaced.
        $this->assertStringContainsString('Hello Alice!', $result);
        // Matching segment kept, non-matching stripped.
        $this->assertStringContainsString('You are a prospect.', $result);
        $this->assertStringNotContainsString('VIP treatment.', $result);
        // Conditional matched; not_segment (inverse) kept for a non-customer.
        $this->assertStringContainsString('Tech offer', $result);
        $this->assertStringContainsString('Join us', $result);
        // No leftover markup.
        $this->assertStringNotContainsString('[segment', $result);
        $this->assertStringNotContainsString('[/segment', $result);
        $this->assertStringNotContainsString('[if:', $result);
        $this->assertStringNotContainsString('[not_segment', $result);
    }
}
