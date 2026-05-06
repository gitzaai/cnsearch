<?php

namespace Gitzaai\Cnsearch\Search;

use Flarum\Search\AbstractFulltextFilter;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\SearchState;
use Illuminate\Database\Eloquent\Builder;

class MeilisearchDiscussionFulltextFilter extends AbstractFulltextFilter
{
    public function __construct(
        protected DiscussionIndexer $indexer
    ) {
    }

    public function search(SearchState $state, string $value): void
    {
        if (! $state instanceof DatabaseSearchState) {
            return;
        }

        $ids = $this->indexer->searchDiscussionIds($value);
        $query = $state->getQuery();

        if ($ids === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('discussions.id', $ids);

        $state->setDefaultSort(function (Builder $query) use ($ids) {
            $wrappedId = $query->getGrammar()->wrap('discussions.id');

            foreach ($ids as $id) {
                $query->orderByRaw("$wrappedId != ?", [$id]);
            }
        });
    }
}
