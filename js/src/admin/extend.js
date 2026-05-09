import Extend from 'flarum/common/extenders';
import CnsearchAdminPage from './CnsearchAdminPage';

export default [
  new Extend.Admin()
    .page(CnsearchAdminPage),
];
