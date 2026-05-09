import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import CnsearchAdminButtons from './CnsearchAdminButtons';

export default class CnsearchAdminPage extends ExtensionPage {
  resetSettings() {
    return [
      {
        key: 'cnsearch.meili.host',
        label: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_host_label'),
      },
      {
        key: 'cnsearch.meili.key',
        label: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_key_label'),
      },
      {
        key: 'cnsearch.meili.index',
        label: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_index_label'),
      },
    ];
  }

  content() {
    return m('div', [
      m('div.Form-group', [
        m('h2', app.translator.trans('gitzaai-cnsearch.admin.page.title')),
        m('p.helpText', app.translator.trans('gitzaai-cnsearch.admin.page.description')),
      ]),

      m('div.Form-group', [
        m('label', app.translator.trans('gitzaai-cnsearch.admin.buttons.title')),
        m('div.helpText', app.translator.trans('gitzaai-cnsearch.admin.buttons.help')),
        m(CnsearchAdminButtons),
      ]),

      this.buildSettingComponent({
        setting: 'cnsearch.meili.host',
        label: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_host_label'),
        help: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_host_help'),
        type: 'text',
        placeholder: 'http://127.0.0.1:7700',
      }),

      this.buildSettingComponent({
        setting: 'cnsearch.meili.key',
        label: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_key_label'),
        help: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_key_help'),
        type: 'password',
        placeholder: app.translator.trans('gitzaai-cnsearch.admin.page.meilisearch_key_placeholder'),
      }),

      this.buildSettingComponent({
        setting: 'cnsearch.meili.index',
        label: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_index_label'),
        help: app.translator.trans('gitzaai-cnsearch.admin.settings.meili_index_help'),
        type: 'text',
        placeholder: 'flarum_discussions',
      }),

      m('div.Form-group.Form-controls', [
        this.submitButton(),
        this.resetButton(this.resetSettings(), undefined, 'gitzaai-cnsearch'),
      ]),
    ]);
  }
}
