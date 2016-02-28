<?php
namespace Phabric\Stages;

final class Setup
{
    private $scopes;

    public function __construct($scopes)
    {
        $this->scopes = $scopes;
    }

    public function __invoke($payload)
    {
        $payload = collect(iterator_to_array($payload));
        $scopes = $this->scopes;

        $payload->macro('scope', function ($scope) use ($scopes) {
            if ( ! $scopes) {
                return $this;
            }

            $scopedItems = $this->where('scope', $scope);

            $scope = collect($scopes[$scope]);
            $sort = $scope->get("sort");
            $order = $scope->get("order", 'DESC');

            return $scopedItems->sortBy($sort, null, strtoupper($order) === 'DESC');
        });

        return $payload;
    }
}