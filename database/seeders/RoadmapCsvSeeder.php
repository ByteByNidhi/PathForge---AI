<?php

namespace Database\Seeders;

use App\Models\LearningPath;
use App\Models\RoadmapStep;
use Illuminate\Database\Seeder;

class RoadmapCsvSeeder extends Seeder
{
    /**
     * Import roadmap data from the master CSV into learning_paths and roadmap_steps.
     * Existing rows are left in place; matching paths and steps are reused.
     */
    public function run(): void
    {
        $csvPath = base_path('PathForge-Roadmaps - Master_Roadmaps.csv');

        if (! is_readable($csvPath)) {
            throw new \RuntimeException("Roadmap CSV not found or unreadable: {$csvPath}");
        }

        $handle = fopen($csvPath, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Unable to open roadmap CSV: {$csvPath}");
        }

        try {
            $header = fgetcsv($handle);

            if ($header === false) {
                throw new \RuntimeException('Roadmap CSV is empty.');
            }

            $header = array_map(fn ($column) => trim((string) $column), $header);
            $paths = [];

            while (($row = fgetcsv($handle)) !== false) {
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $record = $this->mapRow($header, $row);

                $pathName = trim((string) ($record['Path Name'] ?? ''));
                $description = trim((string) ($record['Description'] ?? ''));
                $stepNo = (int) ($record['Step No'] ?? 0);
                $stepTitle = trim((string) ($record['Step Title'] ?? ''));
                $xpReward = (int) ($record['XP Reward'] ?? 0);

                if ($pathName === '' || $stepTitle === '' || $stepNo < 1) {
                    continue;
                }

                if (! isset($paths[$pathName])) {
                    $paths[$pathName] = LearningPath::firstOrCreate(
                        ['path_name' => $pathName],
                        ['description' => $description !== '' ? $description : null]
                    );
                }

                RoadmapStep::firstOrCreate(
                    [
                        'path_id' => $paths[$pathName]->id,
                        'step_no' => $stepNo,
                    ],
                    [
                        'title' => $stepTitle,
                        'xp_reward' => $xpReward,
                    ]
                );
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<int, string|null>  $row
     * @return array<string, string>
     */
    private function mapRow(array $header, array $row): array
    {
        $record = [];

        foreach ($header as $index => $column) {
            $record[$column] = isset($row[$index]) ? (string) $row[$index] : '';
        }

        return $record;
    }

    /**
     * @param  array<int, string|null>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
