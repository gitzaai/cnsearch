import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import CnsearchAdminButtons from './components/CnsearchAdminButtons';

export default [
  new Extend.Admin()
    .setting(
      () => ({
        setting: 'cnsearch.meili.host',
        label: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_host_label', {}, true),
        type: 'text',
        autocomplete: 'off',
        placeholder: '',
        help: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_host_help', {}, true),
      }),
      30
    )
    .setting(
      () => ({
        setting: 'cnsearch.meili.key',
        label: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_key_label', {}, true),
        type: 'password',
        autocomplete: 'new-password',
        placeholder: '',
        help: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_key_help', {}, true),
      }),
      20
    )
    .setting(
      () => ({
        setting: 'cnsearch.meili.index',
        label: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_index_label', {}, true),
        type: 'text',
        help: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_index_help', {}, true),
      }),
      10
    )
    .setting(
      () => () =>
        m('div.Form-group', [
          m('label', app.translator.trans('gitzaai-cnsearch.admin.buttons.title')),
          m('div.helpText', app.translator.trans('gitzaai-cnsearch.admin.buttons.help')),
          m(CnsearchAdminButtons),
        ]),
      -10
    ),
];
