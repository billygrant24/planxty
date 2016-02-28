<?php
namespace Phabric\Stages;

final class Hydrate
{
    private $taxonomies;

    public function __construct($taxonomies)
    {
        $this->taxonomies = collect($taxonomies);
    }

    public function __invoke($payload)
    {
        $taxonomies = $this->taxonomies->transform(function ($taxonomy) use ($payload) {
            if ($taxonomy['type'] === 'category') {
                return $payload->pluck($taxonomy['name'])->filter()->unique()->values()->all();
            }

            if ($taxonomy['type'] === 'tag') {
                return $payload->pluck($taxonomy['name'])->flatten()->filter()->unique()->values()->all();
            }
        })->all();

        return $payload->transform(function ($item) use ($taxonomies) {
            $item['app']['taxonomies'] = $taxonomies;

            return $item;
        });
    }
}