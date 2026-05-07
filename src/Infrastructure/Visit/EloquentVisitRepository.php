<?php

namespace Infrastructure\Visit;

use App\Models\VisitRecord;
use Application\Persistence\SearchCriteria\Contracts\Criteria;
use Application\Visit\VisitRepository;
use Domain\Visit\Visit;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\DB;
use Infrastructure\Persistence\Mapping\VisitMapper;
use Infrastructure\Persistence\SQL\EloquentCriteriaContext;
use RuntimeException;
use stdClass;
use Webmozart\Assert\Assert;

final readonly class EloquentVisitRepository implements VisitRepository
{
    public function save(Visit $visit): void
    {
        $this->query()->create(VisitMapper::attributesFromDomain($visit));
    }

    public function uniqueTotal(Criteria $criteria): int
    {
        return $this->searchQuery($criteria)->distinct('fingerprint')->count('fingerprint');
    }

    public function uniqueByHour(Criteria $criteria): array
    {
        $query = $this->searchQuery($criteria);

        $records = $query
            ->selectRaw($this->hourExpression().' as hour, count(distinct fingerprint) as visits')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $rows = [];

        foreach ($records as $record) {
            $rows[] = [
                'hour' => $this->stringField($record, 'hour'),
                'visits' => $this->intField($record),
            ];
        }

        return $rows;
    }

    public function uniqueByCity(Criteria $criteria): array
    {
        $records = $this->searchQuery($criteria)
            ->selectRaw('city, count(distinct fingerprint) as visits')
            ->groupBy('city')
            ->orderByDesc('visits')
            ->orderBy('city')
            ->get();

        $rows = [];

        foreach ($records as $record) {
            $rows[] = [
                'city' => $this->stringField($record, 'city'),
                'visits' => $this->intField($record),
            ];
        }

        return $rows;
    }

    /**
     * @return Builder<VisitRecord>
     */
    private function query(): Builder
    {
        return VisitRecord::query();
    }

    /**
     * Aggregate methods intentionally apply only search constraints from Criteria.
     * Grouping, projection and ordering belong to the aggregate itself.
     *
     * @return Builder<VisitRecord>
     */
    private function searchQuery(Criteria $criteria): Builder
    {
        $context = new EloquentCriteriaContext($this->query());

        return $context->search($criteria);
    }

    /**
     * @return literal-string
     */
    private function hourExpression(): string
    {
        $connection = $this->connection();

        return match (true) {
            $connection instanceof SQLiteConnection => "strftime('%Y-%m-%d %H:00', created_at)",
            $connection instanceof PostgresConnection => "to_char(created_at, 'YYYY-MM-DD HH24:00')",
            $connection instanceof MySqlConnection => "date_format(created_at, '%Y-%m-%d %H:00')",
            default => throw new RuntimeException(sprintf(
                'Unsupported visit statistics database connection [%s].',
                $connection::class,
            )),
        };
    }

    private function connection(): Connection
    {
        return DB::connection();
    }

    private function stringField(stdClass|VisitRecord $record, string $field): string
    {
        $value = $this->field($record, $field);

        Assert::scalar($value, "Visit statistics field [$field] must be scalar.");

        return (string) $value;
    }

    private function intField(stdClass|VisitRecord $record): int
    {
        $value = $this->field($record, 'visits');

        Assert::numeric($value, 'Visit statistics field [visits] must be numeric.');

        return (int) $value;
    }

    private function field(stdClass|VisitRecord $record, string $field): mixed
    {
        if ($record instanceof VisitRecord) {
            Assert::keyExists($record->getAttributes(), $field, "Visit statistics field [$field] is missing.");

            return $record->getAttribute($field);
        }

        Assert::propertyExists($record, $field, "Visit statistics field [$field] is missing.");

        return $record->{$field};
    }
}
