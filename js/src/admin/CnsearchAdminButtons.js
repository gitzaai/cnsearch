import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';

export default class CnsearchAdminButtons extends Component {
  oninit(vnode) {
    super.oninit(vnode);
    this.loadingReindex = false;
    this.loadingTest = false;
    this.checking = false;
    this.documentCount = null;
    this.sourceDiscussions = null;
    this.sourcePosts = null;
    this.lastSync = null;
    this.statusText = app.translator.trans('gitzaai-cnsearch.admin.buttons.status_unknown');
    this.lastChecked = null;

    this.refreshStatus();
  }

  errorMessage(error, fallback) {
    return (
      error.response?.body?.errors?.[0]?.detail ||
      error.response?.errors?.[0]?.detail ||
      error.body?.errors?.[0]?.detail ||
      error.message ||
      fallback
    );
  }

  view() {
    const reindexLabel = app.translator.trans('gitzaai-cnsearch.admin.buttons.reindex');
    const testConnectionLabel = app.translator.trans('gitzaai-cnsearch.admin.buttons.test_connection');

    return m('div.CnsearchAdminButtons', [
      m('div.CnsearchAdminButtons-status', [
        m('strong', app.translator.trans('gitzaai-cnsearch.admin.buttons.status_label') + ':'),
        ' ',
        m('span', this.statusText),
        m('div', app.translator.trans('gitzaai-cnsearch.admin.buttons.indexed_count', { count: this.documentCount ?? '-' })),
        m(
          'div',
          app.translator.trans('gitzaai-cnsearch.admin.buttons.source_discussions', {
            count: this.sourceDiscussions ?? '-',
          })
        ),
        m(
          'div',
          app.translator.trans('gitzaai-cnsearch.admin.buttons.source_posts', {
            count: this.sourcePosts ?? '-',
          })
        ),
        m(
          'div',
          app.translator.trans('gitzaai-cnsearch.admin.buttons.last_sync', {
            time: this.lastSync ?? app.translator.trans('gitzaai-cnsearch.admin.buttons.not_synced'),
          })
        ),
        this.lastChecked ? m('div', app.translator.trans('gitzaai-cnsearch.admin.buttons.status_last_checked', { time: this.lastChecked })) : null,
      ]),
      m('div.CnsearchAdminButtons-actions', [
        Button.component({
          className: 'Button Button--primary',
          loading: this.loadingReindex,
          onclick: this.reindex.bind(this),
          'aria-label': reindexLabel,
        }, reindexLabel),
        Button.component({
          className: 'Button',
          loading: this.loadingTest,
          onclick: this.testConnection.bind(this),
          style: 'margin-left: 0.5em;',
          'aria-label': testConnectionLabel,
        }, testConnectionLabel),
      ]),
    ]);
  }

  refreshStatus() {
    if (this.checking) {
      return;
    }

    this.checking = true;
    m.redraw();

    app
      .request({
        method: 'GET',
        url: `${app.forum.attribute('apiUrl')}/cnsearch/status`,
      })
      .then((response) => {
        this.connected = true;
        this.statusText = app.translator.trans('gitzaai-cnsearch.admin.buttons.status_connected');
        this.documentCount = response.data.count;
        this.sourceDiscussions = response.data.sourceDiscussions ?? null;
        this.sourcePosts = response.data.sourcePosts ?? null;
        this.lastSync = response.data.lastReindexAt ? new Date(response.data.lastReindexAt * 1000).toLocaleString() : null;
      })
      .catch((error) => {
        this.connected = false;
        this.statusText = this.errorMessage(error, app.translator.trans('gitzaai-cnsearch.admin.buttons.status_disconnected'));
        this.documentCount = null;
        this.sourceDiscussions = null;
        this.sourcePosts = null;
        this.lastSync = null;
      })
      .finally(() => {
        this.checking = false;
        this.lastChecked = new Date().toLocaleString();
        m.redraw();
      });
  }

  reindex() {
    this.loadingReindex = true;
    m.redraw();

    app
      .request({
        method: 'POST',
        url: `${app.forum.attribute('apiUrl')}/cnsearch/reindex`,
        body: { batchSize: 100 },
      })
      .then(() => {
        app.alerts.show({ type: 'success' }, app.translator.trans('gitzaai-cnsearch.admin.buttons.reindex_success'));
        this.refreshStatus();
      })
      .catch((error) => {
        const message = this.errorMessage(error, app.translator.trans('gitzaai-cnsearch.admin.buttons.reindex_failed'));
        app.alerts.show({ type: 'error' }, message);
      })
      .finally(() => {
        this.loadingReindex = false;
        m.redraw();
      });
  }

  testConnection() {
    this.loadingTest = true;
    m.redraw();

    app
      .request({
        method: 'GET',
        url: `${app.forum.attribute('apiUrl')}/cnsearch/test-connection`,
      })
      .then(() => {
        app.alerts.show({ type: 'success' }, app.translator.trans('gitzaai-cnsearch.admin.buttons.test_connection_success'));
      })
      .catch((error) => {
        const message = this.errorMessage(error, app.translator.trans('gitzaai-cnsearch.admin.buttons.test_connection_failed'));
        app.alerts.show({ type: 'error' }, message);
      })
      .finally(() => {
        this.loadingTest = false;
        this.refreshStatus();
      });
  }
}
