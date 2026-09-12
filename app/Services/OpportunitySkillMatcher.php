<?php

namespace App\Services;

use App\Models\Skill;
use Illuminate\Support\Collection;

class OpportunitySkillMatcher
{
    /**
     * @param  Collection<int, Skill>|null  $skills
     * @return list<Skill>
     */
    public function match(string $text, ?Collection $skills = null): array
    {
        $haystack = $this->normalizeHaystack($text);

        if ($haystack === '') {
            return [];
        }

        $catalog = $skills ?? Skill::query()->orderBy('name')->get();
        $matched = [];

        foreach ($catalog as $skill) {
            if ($this->skillMatches($skill->name, $haystack)) {
                $matched[] = $skill;
            }
        }

        return $matched;
    }

    public function skillMatches(string $skillName, string $normalizedHaystack): bool
    {
        foreach ($this->tokensFor($skillName) as $token) {
            if ($this->containsToken($normalizedHaystack, $token)) {
                return true;
            }
        }

        return false;
    }

    public function normalizeHaystack(string $text): string
    {
        $text = mb_strtolower(strip_tags($text));
        $text = str_replace(['-', '_', '/', '\\', '.'], ' ', $text);
        $text = preg_replace('/[^a-z0-9+# ]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * @return list<string>
     */
    private function tokensFor(string $skillName): array
    {
        $name = mb_strtolower(trim($skillName));
        $tokens = [$this->normalizeHaystack($name)];

        $aliases = [
            'javascript' => ['javascript'],
            'ui ux design' => ['ui ux design', 'ui ux', 'ux design', 'ui design'],
            'cybersecurity' => ['cybersecurity', 'cyber security'],
            'machine learning' => ['machine learning'],
        ];

        $key = $this->normalizeHaystack($name);
        if (isset($aliases[$key])) {
            $tokens = array_merge($tokens, $aliases[$key]);
        }

        $tokens = array_values(array_unique(array_filter($tokens)));

        return array_values(array_filter(
            $tokens,
            static fn (string $token) => mb_strlen($token) >= 2
        ));
    }

    private function containsToken(string $haystack, string $token): bool
    {
        if ($token === '') {
            return false;
        }

        $pattern = '/(^| )'.preg_quote($token, '/').'( |$)/u';

        return preg_match($pattern, $haystack) === 1;
    }
}
