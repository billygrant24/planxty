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

        collect($taxonomies)->each(function ($taxonomy, $key) use (&$items) {
            $name = $taxonomy['name'];
            $type = $taxonomy['type'];

            if ($type === 'category') {
                $items[$key] = $this->content->pluck($name)->filter()->unique()->values();
            }

            if ($type === 'tag') {
                $items[$key] = $this->content->pluck($name)->flatten()->filter()->unique()->values();
            }
        });

        return new Collection($items);
    }
}