import app from 'flarum/forum/app';
import SearchManager from 'flarum/common/SearchManager';
import Search from 'flarum/forum/components/Search';

app.initializers.add('gitzaai-cnsearch-forum', () => {
  SearchManager.MIN_SEARCH_LEN = 1;
  Search.MIN_SEARCH_LEN = 1;
});
