<?php

namespace Database\Seeders;

class RoadmapSkillCatalog
{
    /**
     * Recommended catalog skills for each career path.
     *
     * @return array<string, list<string>>
     */
    public static function pathSkills(): array
    {
        return [
            'Cybersecurity' => ['Cybersecurity', 'Python', 'Git', 'Linux', 'Networking', 'Communication'],
            'Web Development' => ['HTML', 'CSS', 'JavaScript', 'React', 'Git', 'PHP', 'Laravel', 'MySQL'],
            'Data Science' => ['Python', 'SQL', 'MySQL', 'Git', 'Communication'],
            'AI & Machine Learning' => ['Python', 'SQL', 'Git', 'Communication'],
            'Cloud & DevOps' => ['Linux', 'Git', 'Docker', 'Networking', 'Cybersecurity', 'MySQL'],
            'Mobile Development' => ['JavaScript', 'Git', 'MySQL', 'UI/UX Design', 'Communication'],
            'UI/UX Design' => ['UI/UX Design', 'Figma', 'HTML', 'CSS', 'Communication'],
        ];
    }

    /**
     * Skills taught by each roadmap step, keyed by path name then step number.
     *
     * @return array<string, array<int, list<string>>>
     */
    public static function stepSkills(): array
    {
        return [
            'Cybersecurity' => [
                1 => ['Linux', 'Communication'],
                2 => ['Networking'],
                3 => ['Linux'],
                4 => ['Cybersecurity'],
                5 => ['Linux', 'Cybersecurity'],
                6 => ['Networking', 'Cybersecurity'],
                7 => ['Linux'],
                8 => ['Python', 'Cybersecurity'],
                9 => ['Cybersecurity'],
                10 => ['Cybersecurity'],
                11 => ['Cybersecurity', 'HTML'],
                12 => ['Cybersecurity'],
                13 => ['Cybersecurity', 'JavaScript'],
                14 => ['Cybersecurity'],
                15 => ['Cybersecurity'],
                16 => ['Cybersecurity', 'Python'],
                17 => ['Cybersecurity', 'Communication'],
                18 => ['Cybersecurity'],
                19 => ['Cybersecurity', 'Git'],
                20 => ['Communication', 'Cybersecurity'],
            ],
            'Web Development' => [
                1 => ['HTML'],
                2 => ['CSS'],
                3 => ['HTML', 'CSS'],
                4 => ['JavaScript'],
                5 => ['JavaScript', 'HTML'],
                6 => ['JavaScript'],
                7 => ['Git'],
                8 => ['HTML', 'CSS', 'JavaScript'],
                9 => ['React'],
                10 => ['React'],
                11 => ['PHP', 'Laravel'],
                12 => ['PHP', 'JavaScript'],
                13 => ['MySQL'],
                14 => ['Laravel', 'PHP'],
                15 => ['Laravel', 'PHP'],
                16 => ['React', 'Laravel'],
                17 => ['Cybersecurity', 'JavaScript'],
                18 => ['Git', 'JavaScript'],
                19 => ['Git', 'Linux'],
                20 => ['HTML', 'CSS', 'JavaScript', 'Git'],
            ],
            'Data Science' => [
                1 => ['Python'],
                2 => ['Python'],
                3 => ['Python'],
                4 => ['Python'],
                5 => ['Python'],
                6 => ['Python'],
                7 => ['Python'],
                8 => ['Python'],
                9 => ['SQL', 'MySQL'],
                10 => ['Python'],
                11 => ['Python'],
                12 => ['Python'],
                13 => ['Python'],
                14 => ['Python'],
                15 => ['Python'],
                16 => ['Python'],
                17 => ['Python'],
                18 => ['Python', 'Git'],
                19 => ['Python', 'Git'],
                20 => ['Python', 'Git', 'Communication'],
            ],
            'AI & Machine Learning' => [
                1 => ['Python'],
                2 => ['Python'],
                3 => ['Python'],
                4 => ['Python'],
                5 => ['Python'],
                6 => ['Python'],
                7 => ['Python'],
                8 => ['Python'],
                9 => ['Python'],
                10 => ['Python'],
                11 => ['Python'],
                12 => ['Python'],
                13 => ['Python'],
                14 => ['Python'],
                15 => ['Python'],
                16 => ['Python'],
                17 => ['Python'],
                18 => ['Python', 'Git'],
                19 => ['Python', 'Git'],
                20 => ['Python', 'Git', 'Communication'],
            ],
            'Cloud & DevOps' => [
                1 => ['Linux'],
                2 => ['Networking'],
                3 => ['Git'],
                4 => ['Linux'],
                5 => ['Linux'],
                6 => ['Linux'],
                7 => ['Docker', 'Linux'],
                8 => ['Docker'],
                9 => ['Git', 'Docker'],
                10 => ['Linux', 'Git'],
                11 => ['Networking', 'Cybersecurity'],
                12 => ['MySQL'],
                13 => ['Docker', 'Linux'],
                14 => ['Linux'],
                15 => ['Linux', 'Git', 'Docker'],
                16 => ['Docker', 'Git'],
                17 => ['Communication'],
                18 => ['Cybersecurity', 'Docker'],
                19 => ['Linux', 'Git', 'Docker'],
                20 => ['Linux', 'Git', 'Docker', 'Communication'],
            ],
            'Mobile Development' => [
                1 => ['JavaScript'],
                2 => ['JavaScript'],
                3 => ['JavaScript', 'UI/UX Design'],
                4 => ['UI/UX Design'],
                5 => ['UI/UX Design', 'JavaScript'],
                6 => ['JavaScript'],
                7 => ['JavaScript'],
                8 => ['MySQL', 'JavaScript'],
                9 => ['JavaScript'],
                10 => ['JavaScript'],
                11 => ['JavaScript'],
                12 => ['JavaScript'],
                13 => ['Cybersecurity', 'JavaScript'],
                14 => ['JavaScript', 'Git'],
                15 => ['JavaScript'],
                16 => ['JavaScript', 'React'],
                17 => ['Git'],
                18 => ['JavaScript'],
                19 => ['JavaScript', 'Git', 'UI/UX Design'],
                20 => ['JavaScript', 'Git', 'Communication'],
            ],
            'UI/UX Design' => [
                1 => ['UI/UX Design'],
                2 => ['UI/UX Design'],
                3 => ['UI/UX Design'],
                4 => ['UI/UX Design'],
                5 => ['UI/UX Design'],
                6 => ['Communication', 'UI/UX Design'],
                7 => ['Communication', 'UI/UX Design'],
                8 => ['UI/UX Design'],
                9 => ['UI/UX Design', 'Figma'],
                10 => ['Figma', 'UI/UX Design'],
                11 => ['Figma'],
                12 => ['Figma', 'UI/UX Design'],
                13 => ['CSS', 'HTML', 'UI/UX Design'],
                14 => ['HTML', 'UI/UX Design'],
                15 => ['Communication', 'UI/UX Design'],
                16 => ['UI/UX Design', 'Figma'],
                17 => ['Communication', 'UI/UX Design'],
                18 => ['Communication', 'UI/UX Design'],
                19 => ['Figma', 'UI/UX Design'],
                20 => ['Figma', 'UI/UX Design', 'Communication'],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function skillsForStep(string $pathName, int $stepNo, string $csvSkills = ''): array
    {
        $fromCsv = self::parseSkillList($csvSkills);

        if ($fromCsv !== []) {
            return $fromCsv;
        }

        return self::stepSkills()[$pathName][$stepNo] ?? [];
    }

    /**
     * @return list<string>
     */
    public static function skillsForPath(string $pathName): array
    {
        $recommended = self::pathSkills()[$pathName] ?? [];
        $fromSteps = [];

        foreach (self::stepSkills()[$pathName] ?? [] as $names) {
            foreach ($names as $name) {
                $fromSteps[] = $name;
            }
        }

        return array_values(array_unique(array_merge($recommended, $fromSteps)));
    }

    /**
     * @return list<string>
     */
    public static function parseSkillList(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $parts = preg_split('/\s*,\s*/', $raw) ?: [];

        return array_values(array_filter(
            array_map(static fn (string $part) => trim($part), $parts),
            static fn (string $part) => $part !== ''
        ));
    }
}
