<?php
namespace Phabric\Collection;

use Illuminate\Support\Collection;
use Phabric\Parsing\ContentParser;
use Pimple\Container;

class Content extends CollectionAbstract
{
    public function getName()
    {
        return 'content';
    }

    public function addCollections(Container $c)
    {
        $c['content_parser'] = function ($c) {
            $parser = new ContentParser();

            $parser->setConfig($c['config']);
            $parser->setMarkdown($c['markdown']);
            $parser->setTwig($c['twig']);
            $parser->setYaml($c['yaml']);

            return $parser;
        };

       return [
           'content' => function ($c) {
               $path = $c['config']->get('paths.content');

               $finder = $c['finder'];
               $finder->files()->in($path)->name('*.yml');

               $items = [];
               foreach ($finder as $file) {
                   $parsedItem = $c['content_parser']->parse($file);
                   $items[$parsedItem->get('uri')] = $parsedItem;
               }

               $content = new Collection($items);

               foreach ($this->availableMacros($c) as $macro => $callback) {
                   $content->macro($macro, $callback);
               }

               return $content;
           },
       ];
    }

    private function availableMacros($c)
    {
        $scopes = collect($c['config']->get('scope'));

        return [
            'scope' => function ($scope) use ($scopes) {
                if ( ! $scopes->has($scope)) {
                    return $this;
                }

                $scopedItems = $this->where('scope', $scope);

                $sort = $scopes->get("$scope.sort");
                $order = $scopes->get("$scope.order", 'DESC');

                return $scopedItems->sortBy($sort, null, strtoupper($order) === 'DESC');
            },
        ];
    }
}