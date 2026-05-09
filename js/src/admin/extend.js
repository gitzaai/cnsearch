import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import CnsearchAdminButtons from './CnsearchAdminButtons';

export default [
  new Extend.Admin()
    .setting(
      () => ({
        setting: 'cnsearch.meili.host',
        label: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_host_label', {}, true),
        help: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_host_help', {}, true),
        type: 'text',
        placeholder: 'http://127.0.0.1:7700',
      }),
      30
    )
    .setting(
      () => ({
        setting: 'cnsearch.meili.key',
        label: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_key_label', {}, true),
        help: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_key_help', {}, true),
        type: 'password',
        placeholder: app.translator.trans('gitzaai-cnsearch.admin.page.meilisearch_key_placeholder', {}, true),
      }),
      20
    )
    .setting(
      () => ({
        setting: 'cnsearch.meili.index',
        label: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_index_label', {}, true),
        help: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_index_help', {}, true),
        type: 'text',
        placeholder: 'flarum_discussions',
      }),
      10
    )
    .setting(
      () => function () {
        return m('div.Form-group', [
          m('label', app.translator.trans('gitzaai-cnsearch.admin.buttons.title')),
          m('div.helpText', app.translator.trans('gitzaai-cnsearch.admin.buttons.help')),
          m(CnsearchAdminButtons),
        ]);
      },
      0
    ),
];
