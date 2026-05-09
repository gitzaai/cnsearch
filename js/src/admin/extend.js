import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import CnsearchAdminPage from './CnsearchAdminPage';

export default [
  new Extend.Admin()
    .page(CnsearchAdminPage)
    .generalIndexItems(() => [
      {
        id: 'gitzaai-cnsearch.meili.host',
        label: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_host_label', {}, true),
        help: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_host_help', {}, true),
      },
      {
        id: 'gitzaai-cnsearch.meili.key',
        label: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_key_label', {}, true),
        help: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_key_help', {}, true),
      },
      {
        id: 'gitzaai-cnsearch.meili.index',
        label: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_index_label', {}, true),
        help: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_index_help', {}, true),
      },
      {
        id: 'gitzaai-cnsearch.meili.actions',
        label: app.translator.trans('gitzaai-cnsearch.admin.buttons.title', {}, true),
        help: app.translator.trans('gitzaai-cnsearch.admin.buttons.help', {}, true),
      },
    ]),
];
