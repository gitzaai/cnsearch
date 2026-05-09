import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import CnsearchAdminPage from './CnsearchAdminPage';
import CnsearchAdminButtons from './CnsearchAdminButtons';

export default [
  new Extend.Admin()
    .page(CnsearchAdminPage)
    .generalItems(() => [
      m('div.Form-group', [
        m('label', app.translator.trans('gitzaai-cnsearch.admin.settings.meili_host_label')),
        m('input.FormControl', {
          type: 'text',
          placeholder: 'http://127.0.0.1:7700',
          value: app.data.settings['cnsearch.meili.host'] || '',
          onchange: (e) => app.data.settings['cnsearch.meili.host'] = e.target.value,
        }),
        m('p.helpText', app.translator.trans('gitzaai-cnsearch.admin.settings.meili_host_help')),
      ]),
      m('div.Form-group', [
        m('label', app.translator.trans('gitzaai-cnsearch.admin.settings.meili_key_label')),
        m('input.FormControl', {
          type: 'password',
          placeholder: app.translator.trans('gitzaai-cnsearch.admin.page.meilisearch_key_placeholder'),
          value: app.data.settings['cnsearch.meili.key'] || '',
          onchange: (e) => app.data.settings['cnsearch.meili.key'] = e.target.value,
        }),
        m('p.helpText', app.translator.trans('gitzaai-cnsearch.admin.settings.meili_key_help')),
      ]),
      m('div.Form-group', [
        m('label', app.translator.trans('gitzaai-cnsearch.admin.settings.meili_index_label')),
        m('input.FormControl', {
          type: 'text',
          placeholder: 'flarum_discussions',
          value: app.data.settings['cnsearch.meili.index'] || '',
          onchange: (e) => app.data.settings['cnsearch.meili.index'] = e.target.value,
        }),
        m('p.helpText', app.translator.trans('gitzaai-cnsearch.admin.settings.meili_index_help')),
      ]),
      m('div.Form-group', [
        m('label', app.translator.trans('gitzaai-cnsearch.admin.buttons.title')),
        m('div.helpText', app.translator.trans('gitzaai-cnsearch.admin.buttons.help')),
        m(CnsearchAdminButtons),
      ]),
    ]),
];
