<?php
namespace Phabric\Stages;

final class Paginate
{
    public function __invoke($payload)
    {
        foreach ($payload as $item) {
            if ( ! array_has($item, 'pagination')) {
                continue;
            }

            $pagination = collect($item['pagination']);
            $scope = $pagination->get('scope', 'default');
            $size = $pagination->get('size', null);
            $scopedContent = $payload->scope($scope);
            $contentCount = $scopedContent->count();
            $pageCount = ceil($contentCount / ($size ? $size : 1));


            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                $newItem = $item;
                $newItem['permalink'] = $this->getPagedPermalink($newItem, $pageNumber);

                $pagination->put('items', $scopedContent->forPage($pageNumber, $size));
                $pagination->put('current', $pageNumber);
                $pagination->put('next', $pageNumber < $pageCount ? $this->getPagedPermalink($newItem, $pageNumber + 1) : null);
                $pagination->put('previous', $pageNumber > 1 ? $this->getPagedPermalink($newItem, $pageNumber - 1) : null);
                $pagination->put('first', $newItem['permalink']);
                $pagination->put('last',  $this->getPagedPermalink($newItem, $pageCount));
                $pagination->put('total', $contentCount);

                $newItem['pagination'] = $pagination->toArray();

                $payload[$newItem['permalink']] = $newItem;
            }
        };

        return $payload;
    }

    private function getPagedPermalink($item, $page)
    {
        $url = $item['canonical_url'];

        return $page > 1
            ? str_replace('.html', '-' . $page . '.html', $url)
            : $url;
    }
}