<?php
namespace Phabric\Collecting;

use Illuminate\Support\Collection;
use Pimple\Container;

final class TaxonomyCollector
{
    public function __construct(Container $c)
    {
        $this->config = $c['config'];
        $this->content = $c['content_collector'];
    }

    public function collect()
    {
        $items = [];
        $taxonomies = $this->config->get('taxonomies');

        collect($taxonomies)->each(function ($taxonomy, $name) {
            if ($taxonomy['type'] === 'category') {
                $item[$name] = $this->content->pluck($name)->unique()->filter();
            }

            if ($taxonomy['type'] === 'tag') {
                $item[$name] = $this->content->pluck($name)->flatten()->values()->unique()->filter();
            }
        });

        return new Collection($items);
    }
}