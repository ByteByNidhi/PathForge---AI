<?php

namespace Tests\Unit;

use App\Services\HimalayasJobService;
use App\Services\OpportunitySkillMatcher;
use Tests\TestCase;

class HimalayasJobServiceTest extends TestCase
{
    public function test_it_normalizes_himalayas_job_fields(): void
    {
        $service = new HimalayasJobService;
        $normalized = $service->normalizeJob([
            'guid' => 'https://himalayas.app/companies/acme/jobs/javascript-dev',
            'title' => 'JavaScript Developer',
            'excerpt' => 'Build web apps with React.',
            'companyName' => 'Acme',
            'employmentType' => 'Full Time',
            'minSalary' => 40000,
            'maxSalary' => 60000,
            'salaryPeriod' => 'annual',
            'seniority' => ['Mid-level'],
            'currency' => 'USD',
            'locationRestrictions' => ['India'],
            'categories' => ['JavaScript-Developer'],
            'parentCategories' => ['Developer'],
            'description' => 'Work with JavaScript and React.',
            'pubDate' => strtotime('2026-09-01'),
            'expiryDate' => strtotime('2026-12-01'),
            'applicationLink' => 'https://himalayas.app/companies/acme/jobs/javascript-dev',
        ]);

        $this->assertNotNull($normalized);
        $this->assertSame('himalayas', $normalized['source']);
        $this->assertSame('https://himalayas.app/companies/acme/jobs/javascript-dev', $normalized['external_id']);
        $this->assertSame('JavaScript Developer', $normalized['title']);
        $this->assertSame('Acme', $normalized['organization']);
        $this->assertSame('https://himalayas.app/companies/acme/jobs/javascript-dev', $normalized['application_url']);
        $this->assertSame('Full Time', $normalized['type']);
        $this->assertStringContainsString('Remote', $normalized['location']);
        $this->assertStringContainsString('Salary', $normalized['eligibility']);
        $this->assertSame('2026-12-01', $normalized['deadline']);
    }

    public function test_skill_matcher_attaches_only_reliable_catalog_skills(): void
    {
        $matcher = new OpportunitySkillMatcher;
        $haystack = $matcher->normalizeHaystack(
            'JavaScript Developer using React and Laravel. Categories: JavaScript-Developer Web-Development'
        );

        $this->assertTrue($matcher->skillMatches('JavaScript', $haystack));
        $this->assertTrue($matcher->skillMatches('React', $haystack));
        $this->assertTrue($matcher->skillMatches('Laravel', $haystack));
        $this->assertFalse($matcher->skillMatches('Cybersecurity', $haystack));
        $this->assertFalse($matcher->skillMatches('Docker', $haystack));
    }
}
